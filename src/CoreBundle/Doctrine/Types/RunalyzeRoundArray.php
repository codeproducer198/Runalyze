<?php

namespace Runalyze\Bundle\CoreBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Runalyze\Activity\Duration;
use Runalyze\Activity\LapIntensity;
use Runalyze\Parser\Activity\Common\Data\Round\Round;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;

class RunalyzeRoundArray extends Type
{
    /** @var string */
    const RUNALYZE_ROUND_ARRAY = 'runalyze_round_array';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    public function getName()
    {
        return self::RUNALYZE_ROUND_ARRAY;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!($value instanceof RoundCollection) || $value->isEmpty()) {
            return null;
        }

        $rounds = [];

        foreach ($value->getElements() as $pause) {
            $rounds[] = $this->getNativeValueForRound($pause);
        }

        return implode('-', $rounds);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $collection = new RoundCollection();

        if ($value !== null && '' != trim($value)) {
            $rounds = explode('-', $value);

            if (is_array($rounds)) {
                foreach ($rounds as $round) {
                    $collection->add($this->getRoundForNativeValue($round));
                }
            }
        }

        return $collection;
    }

    /**
     * @param Round $round
     * @return string
     */
    protected function getNativeValueForRound(Round $round)
    {
        $distance = number_format($round->getDistance(), 3, '.', '');
        $duration = Duration::format($round->getDuration());

        $intensity = $round->getIntensity();
        return ($intensity->getValue()).$distance.'|'.$duration;
    }

    /**
     * @param string $data
     * @return Round
     */
    protected function getRoundForNativeValue($data)
    {
        $intensity = LapIntensity::fromValue(substr($data, 0, 1));

        $data = !is_null($intensity) ? substr($data, 1) : $data;

        $duration = (new Duration(substr(strrchr($data, '|'), 1)))->seconds();
        $distance = (double)strstr($data, '|', true);

        $r = new Round($distance, $duration);
        if (!is_null($intensity)) {
            $r->setIntensity($intensity);
        } else {
            // default is active if no special char exits
            $r->setIntensity(LapIntensity::getInstanceActive());
        }
        return $r;
    }
}
