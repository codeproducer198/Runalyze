<?php
/**
 * Window for routenet
 * @package Runalyze\Plugins\Stats
 */
use Runalyze\View\Leaflet;
use Runalyze\Model;
use Runalyze\Util\LocalTime;
use Runalyze\Activity\Distance;

require 'class.RunalyzePluginStat_Strecken.php';

// #TSC the positioninterval (use every n gps-position of the shown routes)
$positioninterval = isset($_GET['posint']) ? (int)$_GET['posint'] : 1;
$sport = isset($_GET['sport']) ? (int)$_GET['sport'] : -1;
$year = isset($_GET['y']) ? (int)$_GET['y'] : date('Y');

// #TSC the count of current showable routes increase if the positioninterval increase (and therefore the number of points decrease)
$routesLimit = RunalyzePluginStat_Strecken::MAX_ROUTES_ON_NET * $positioninterval;
?>

<div class="panel-heading">
	<?php echo RunalyzePluginStat_Strecken::panelMenuForRoutenet($positioninterval, $sport, $year); ?>
	<h1><?php _e('Route network'); ?></h1>
</div>

<div class="panel-content">
<?php
$Conditions = '';

if ($sport > 0) {
	$Conditions .= ' AND `'.PREFIX.'training`.`sportid`='.(int)$sport;
}

if ($year > 0) {
	$Conditions .= ' AND `'.PREFIX.'training`.`time` BETWEEN UNIX_TIMESTAMP(\''.(int)$year.'-01-01\') AND UNIX_TIMESTAMP(\''.((int)$year+1).'-01-01\')-1';
}

// #TSC the route-name is limited to 50 chars and then a "..." will concated
$Routes = DB::getInstance()->query('
	SELECT
		`'.PREFIX.'route`.`id`,
		`'.PREFIX.'route`.`geohashes`,
		`'.PREFIX.'route`.`min`,
		`'.PREFIX.'route`.`max`,
		`'.PREFIX.'training`.`time`,
		CONCAT(SUBSTRING(`'.PREFIX.'route`.`name`, 1, 50), CASE WHEN LENGTH(`'.PREFIX.'route`.`name`) > 50 THEN "..." ELSE "" END) as routename,
		`'.PREFIX.'training`.`distance`,
		`'.PREFIX.'sport`.`img` as sporticon
	FROM `'.PREFIX.'training`
		LEFT JOIN `'.PREFIX.'route` ON `'.PREFIX.'training`.`routeid`=`'.PREFIX.'route`.`id`
		LEFT JOIN `'.PREFIX.'sport` ON `'.PREFIX.'training`.`sportid`=`'.PREFIX.'sport`.`id`
	WHERE `'.PREFIX.'training`.`accountid`='.SessionAccountHandler::getId().' AND`'.PREFIX.'route`.`geohashes`!="" '.$Conditions.'
	ORDER BY `time` DESC
	LIMIT '.$routesLimit);

$Map = new Leaflet\Map('map-routenet', 600);

$minLat = 90;
$maxLat = -90;
$minLng = 180;
$maxLng = -180;

$count = 0;
$countPoints = 0;

while ($RouteData = $Routes->fetch()) {
	// only the first 4 "columns" are relevant for the GPS positions itself
	$Route = Model\Route\Entity::createWithIntervalClearing(array_slice($RouteData,0 , 4), $positioninterval);

	if (null !== $RouteData['min'] && null !== $RouteData['max']) {
		$MinCoordinate = (new League\Geotools\Geohash\Geohash())->decode($RouteData['min'])->getCoordinate();
		$MaxCoordinate = (new League\Geotools\Geohash\Geohash())->decode($RouteData['max'])->getCoordinate();

		$minLat = $MinCoordinate->getLatitude() != 0 ? min($minLat, $MinCoordinate->getLatitude()) : $minLat;
		$minLng = $MinCoordinate->getLongitude() != 0 ? min($minLng, $MinCoordinate->getLongitude()) : $minLng;
		$maxLat = $MaxCoordinate->getLatitude() != 0 ? max($maxLat, $MaxCoordinate->getLatitude()) : $maxLat;
		$maxLng = $MaxCoordinate->getLongitude() != 0 ? max($maxLng, $MaxCoordinate->getLongitude()) : $maxLng;
	}

	$Path = new Leaflet\Activity('route-'.$RouteData['id'], $Route, null, false);
	// #TSC set hoverable to mark the route if activated and show the tooltip
	$Path->addOption('hoverable', true);
	$Path->addOption('autofit', false);
	$Path->addOption('tooltip', getTooltipText($RouteData['time'], $RouteData['routename'], $RouteData['distance'], $RouteData['sporticon']));

	$Map->addRoute($Path);

	$count++;
	$countPoints += $Route->num();
}

if (!isset($Route)) {
	echo HTML::error(__('There are no routes matching the criterias.'));
}

$Map->setBounds(array(
	'lat.min' => $minLat,
	'lat.max' => $maxLat,
	'lng.min' => $minLng,
	'lng.max' => $maxLng
));
$Map->display();

function getTooltipText($time, $routename, $distance, $sporticon) {
	$tt = '<strong><i class="' . $sporticon . '"></i> ' . __('Date') . ':</strong> ' . (new LocalTime($time))->format('d.m.Y H:i');
	if(!empty($routename)) {
		$tt .= '<br/>';
		$tt .= '<strong>' . __('Course') . ':</strong> ' . $routename;
	}
	if($distance > 0) {
		$tt .= '<br/>';
		$tt .= '<strong>' . __('Distance') . ':</strong> ' . Distance::format($distance, true, Distance::$DefaultDecimalsSplitTable);
	}
	return $tt;
}
?>

<p class="info">
	<?php echo sprintf( __('The map contains your %s most recent routes matching the criterias.').' Nummer der Positions-Punkte ist %d.', $count , $countPoints); ?>
	<?php echo sprintf( __('More routes are not possible at the moment due to performance issues.'). ' Limit %s.', $routesLimit); ?>
</p>
</div>
