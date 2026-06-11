<?php

namespace Runalyze\Service\RouteNameEvaluation;

use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Profile\Sport\SportProfile;

use League\Geotools\Geohash\Geohash;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Implementation for the OSM route evaluation.
 * Calls the OSM Overpass-Rest-API and process the fetched CSV result to extract (in this order/priority):
 *  1 peaks or sattles (ordered by elevation desending)
 *  2 places
 *    a) towns/villages...; order by OSM "size"
 *    b) (alpine) huts or "restaurants"
 *    c) waters (see, lake)
 *    d) forests/woods
 *  3 hiking ways (like Kramersteig)
 *  4 tourism information's
 * in every topic the elements are sorted by alpha
 * #TSC
 */
class RouteNameEvalOsm implements RouteNameEval, LoggerAwareInterface {
    use LoggerAwareTrait;

    const TIMEOUT = 180;

    const EARTH = 6378.137;
    const PI = 3.1415926535898;
    const METER_IN_DEGREE = (1 / ((2 * self::PI / 360) * self::EARTH)) / 1000;

    // size of lat/lng elements in the around for node,way,relation
    // with 600 the request tooks longer and do not run in timeouts
    const AROUND_POS_LIMIT = 600;
    // extend the bounding-box of the min/max of the geohashes
    const BOX_CORR = 30.0;
    // configuration for the around-radius
    const RADIUS_LIMIT = 40.0;
    const RADIUS_DEFAULT = 20.0;

    /** @var string */
    protected $Url;

    /** @var string */
    protected $Proxy;

    /** @var Client */
    protected $HttpClient;

    /**
     * @param string $url
     * @param string $proxy
     * @param Client $client
     * @param LoggerInterface|null $logger
     */
    public function __construct($url, $proxy, Client $client, LoggerInterface $logger = null) {
        $this->Url = $url;
        $this->Proxy = $proxy;
        $this->HttpClient = $client;
        $this->logger = $logger;
    }

    /**
     * Retrieves the route-name and some other relevant informations for the route.
     * 
     * @param Sport $sport
     * @param Route $route
     * @param distance
     * @return null|\Runalyze\Service\RouteNameEvaluation\RouteNameEvalResult
	 * @throws Exception
     */
    public function evaluate(Sport $sport, Route $route, float $distance): ?RouteNameEvalResult {
        if(!empty($this->Url)) {
            // needed to use min/max position
            $route->synchronizeMinMaxGeohashIfRequired();

            $osmPayload = $this->createOsmPayload($route, $distance);

            $csvResponse = $this->request($route->getId(), $osmPayload);
            if($csvResponse != null) {
                $coll = new OsmCsvDataCollection($csvResponse);
                if($coll->hasNames()) {
                    return $this->createResult($sport, $coll);
                } else {
                    $this->logger->warning(sprintf('RouteNameEvalOsm: RouteId=%d has EMPTY result and is skipped.', $route->getId()));
                }
            }
        }
        return null;
    }

    // routeId can be null in case of new import
    private function request(?int $routeId, string $payload): string {
        $this->logger->debug(sprintf('RouteNameEvalOsm: Request for routeId=%d to url=%s with payload: %s', $routeId, $this->Url, $payload));

        $param = ['body' => sprintf("data=%s", $payload),
                  'timeout' => self::TIMEOUT,
                  'headers' => [
                    'User-Agent' => 'Runalyze/4.3.0',
                  ]
                  //, 'debug' => True
        ];
        if(!empty($this->Proxy)) {
            $param['proxy'] = $this->Proxy;
        }

        try {
            $response = $this->HttpClient->request('POST', $this->Url, $param);
            $statusCode = $response->getStatusCode();
            if($statusCode != 200) {
                $this->logger->warning(sprintf('RouteNameEvalOsm: RouteId=%d status-code >> %d', $routeId, $statusCode));
                throw new \Exception('Route evaluation result in HTTP code=' . $statusCode);
                return null;
            } else {
                $repCont = $response->getBody()->getContents();
                // convert TAB from Overpass to | used here; i want to use | it's better to handle ;-)
                $repCont = str_replace("|", "/", $repCont); // replace existing | (in f.e. names) to /
                $repCont = str_replace("\t", "|", $repCont); // now replace all TAB to |
                $this->logger->debug(sprintf("RouteNameEvalOsm: osm-result for routeId=%d: %s", $routeId, $repCont));
                return $repCont;
            }
        } catch (RequestException $e) {
            $this->logger->error('RouteNameEvalOsm: Overpass-API request failed.', ['exception' => $e]);
            throw new \Exception('Route evaluation result in error: ' . $e);
        }
    }

	private function createOsmPayload(Route $route, float $distance): string {
        // [0]=lat [1]=lon
        $coordinates = $route->getLatitudesAndLongitudes();

        $size = count($coordinates[0]);
		$dist = $distance * 1000; // km to meter

        // based on the count of coordinates, the distance (in meter) and the default-"radius of nodes" calculate "which" coordinates should use
        $radius = self::RADIUS_DEFAULT;
		$step = $size / $dist * $radius;

        // but limit it to max coordinates limit
        if ($size / $step > self::AROUND_POS_LIMIT) {
            $this->logger->info(sprintf("limit step from %.2f to %.2f", $step, $size / self::AROUND_POS_LIMIT));
            $step = $size / self::AROUND_POS_LIMIT;

            // if we run in limit, extend the default-value of radius to the radius-limit
            $radius = $dist / self::AROUND_POS_LIMIT;
            if($radius > self::RADIUS_LIMIT) {
                $radius = self::RADIUS_LIMIT;
            }  
		}
        $this->logger->info(sprintf("RouteNameEvalOsm: geo count %d; dist m %d; step %.2f around-element-size %d around-radius %.2f \n",
                                    $size, $dist, $step, $size/$step, $radius));

        // out is CSV (it's smaller than XML or JSON)
        // we generate the csv-output with TAB and replace it later
		$osm = '[out:csv(::type,name,"name:de",place,traffic_sign,tourism,landuse,natural,ele,hiking,sac_scale,amenity;true;"\t")]';
        $osm .= sprintf('[timeout:%d]', self::TIMEOUT);

        // set a (bounding)box for better performance
		$geoMin = (new Geohash())->decode($route->getMin())->getCoordinate();
		$geoMax = (new Geohash())->decode($route->getMax())->getCoordinate();
        // the bounding-box is "corrected" by the radius to avoid ignore point near the original bounding-box border
		$osm .= sprintf("[bbox:%.6f,%.6f,%.6f,%.6f];",
			$this->addLatitude($geoMin->getLatitude(), self::BOX_CORR*-1), $this->addLongitude($geoMin->getLongitude(), $geoMin->getLatitude(), self::BOX_CORR*-1),
			$this->addLatitude($geoMax->getLatitude(), self::BOX_CORR),    $this->addLongitude($geoMax->getLongitude(), $geoMax->getLatitude(), self::BOX_CORR)
		);

        // build the "around" lat/lon
        // we add always the first and last coordinate; then every n (=calculated) coord will be used (to reduce) the amount
        $aroundpos = $coordinates[0][0] . "," . $coordinates[1][0]; // add the first
		for ($i = 0; $i < $size; $i+=$step) {
            if($coordinates[0][$i] != 0 && $coordinates[1][$i] != 0) {
                $aroundpos .= "," . $coordinates[0][$i] . "," . $coordinates[1][$i];
            }
		}
        $aroundpos .= "," . $coordinates[0][$size - 1] . "," . $coordinates[1][$size - 1]; // add the last one

        // only use node, way, relation with a "name"-tag
		$osm .= sprintf('node(around:%.2f,%s)["name"]->.named_node;', $radius, $aroundpos);
		$osm .= sprintf('way(around:%.2f,%s)["name"]->.named_way;', $radius, $aroundpos);
		$osm .= sprintf('relation(around:%.2f,%s)["name"]->.named_rel;', $radius, $aroundpos);

        // set filters
		$node = 'node.named_node["place"];node.named_node["natural"~"peak|saddle"];node.named_node["traffic_sign"="city_limit"];node.named_node["tourism"~"information|alpine_hut|wilderness_hut"];node.named_node["amenity"="restaurant"];';
		$way = 'way.named_way["landuse"="forest"];way.named_way["natural"="wood"];way.named_way["traffic_sign"="city_limit"];';
		$way .= 'way.named_way["hiking"="yes"];way.named_way["sac_scale"];way.named_way["tourism"~"information|alpine_hut|wilderness_hut"];way.named_way["amenity"="restaurant"];';
		$rel = 'relation.named_rel["landuse"="forest"];relation.named_rel["natural"~"wood|water"];';

        // set filters in the final "union"
        $osm .= sprintf('(%s%s%s);out qt;', $node, $way, $rel);

        return $osm;
	}

	private function addLatitude($lat, $add) {
		return $lat + ($add * self::METER_IN_DEGREE);
	}

	private function addLongitude($lon, $lat, $add) {
		return $lon + ($add * self::METER_IN_DEGREE) / cos($lat * (self::PI / 180));
	}

    /**
     * create the result object.
     * based on the found categories it's priorizied which informations is a "route" or a "info" in the notes attribute.
     */
    public function createResult(Sport $sport, OsmCsvDataCollection $coll): ?RouteNameEvalResult {
        $hutSet = false;
        $waySet = false;
        $infoSet = false;

        $peak = $coll->getNames(OsmCsvData::$CAT_PEAK);
        $place = $coll->getNames(OsmCsvData::$CAT_PLACE);
        $hut = $coll->getNames(OsmCsvData::$CAT_HUT);
        $way = $coll->getNames(OsmCsvData::$CAT_WAY);
        $info = $coll->getNames(OsmCsvData::$CAT_INFO);

        // first peaks and places (orte) will be the route
        $routes = array_merge($peak, $place);
        if(empty($routes)) {
            // ...or use huts and ways
            $routes = array_merge($hut, $way);
            $hutSet = !empty($hut);
            $waySet = !empty($way);
            if(empty($routes)) {
                // anywhere empty, use at least the info category :-(
                $routes = $info;
                $infoSet = !empty($info);
            }
        }  
        // if we hiking, but no peak is set, but huts available...
        // special case "hüttenwandern" without peaks
        if(($sport->getInternalSportId() == SportProfile::MOUNTAINEERING || $sport->getInternalSportId() == SportProfile::CROSS_COUNTRY_SKIING)
            && empty($peak) && !empty($hut) && !$hutSet) {
            $routes = array_merge($routes, $hut);;
            $hutSet = true;
        }

        $notes = "";
        if(!$hutSet && !empty($hut)) {
            $notes .= "Verpflegung: " . implode(', ', $hut) . PHP_EOL;
        }
        if(!$waySet && !empty($way)) {
            $notes .= "Wege: " . implode(', ', $way) . PHP_EOL;
        }
        if(!$infoSet && !empty($info)) {
            $notes .= "Info: " . implode(', ', $info) . PHP_EOL;
        }

        if(!empty($routes) || !empty($notes)) {
            $r = implode(' - ', array_slice($routes,0 ,$this->limitRoute($routes)));
            return new RouteNameEvalResult($r, $notes);
        } else {
            return null;
        }
    }

    /**
     * limit the array to only show "full" names of the elements in the available 255 chars.
     */
    private function limitRoute(array $element): int {
        if(empty($element)) {
            return 0;
        }

        $len = 0;
        $i = 0;
        do {
            $len += strlen($element[$i]) + 3; // "+3" means " - "
        } while(++$i < count($element) && $len <= 255); // route attribute is limited to 255 chars

        if($len >= 255) {
            return $i - 1;
        } else {
            return $i;
        }
    }
}