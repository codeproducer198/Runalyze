<?php
/**
 * This file contains class::SelfEvaluationPerceivedEffort
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * Enum for SelfEvaluationFeeling (Gefuehl 1 - 5).
 * 
 * @package Runalyze\Activity
 */
class SelfEvaluationFeeling extends AbstractEnum
{
    // respect the order by number!

	/** @var int */
	const VERY_WEAK = 1; // sehr schwach

	/** @var int */
	const WEAK = 2; // schwach

    /** @var int */
    const NORMAL = 3; // normal

    /** @var int */
    const STRONG = 4; // stark

    /** @var int */
    const VERY_STRONG = 5; // sehr stark

    /**
     * Converts the value from the FIT file to an enum.
     *
     * @param float $fitValue fitValue between 0 and 100
     * @return int internal enum
     * @throws \InvalidArgumentException
     */
    public static function fromFitToEnum($fitValue)
    {
        if (!is_numeric($fitValue) || (int)$fitValue < 0 || (int)$fitValue > 100 ) {
            throw new \InvalidArgumentException(sprintf('Provided evaluation effort %s is invalid.', $fitValue));
        }

        switch ((int)$fitValue) {
            case 0.0:
                return self::VERY_WEAK;
            case 25.0:
                return self::WEAK;
            case 50.0:
                return self::NORMAL;
            case 75.0:
                return self::STRONG;
            case 100.0:
                return self::VERY_STRONG;
            default:
                // return the original value / this results in an unknown "label"
                return $fitValue;
        }
    }

    /**
     * Gets the label text for the number.
     *
     * @param int $num internal enum
     * @return string
     * @codeCoverageIgnore
     */
    public static function labelOfEnum($num)
    {
        switch ($num) {
            case self::VERY_WEAK:
                return 'Sehr schwach &#x1F61E;';
            case self::WEAK:
                return 'Schwach &#x1F626;';
            case self::NORMAL:
                return 'Normal &#x1F610;';
            case self::STRONG:
                return 'Stark &#x1F642;';
            case self::VERY_STRONG:
                return 'Sehr stark &#x1F600;';
            default:
                return sprintf('Unknown %u', $num);
        }
    }

    /**
     * Gets the number and label text as a string.
     *
     * @param int $enum internal enum
     * @return description with number and texts
     * @codeCoverageIgnore
     */
    public static function descriptionFromNum($num)
    {
        return $num . ' ' . self::labelOfEnum($num);
    }
}
