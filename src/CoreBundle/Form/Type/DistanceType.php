<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit;
use Runalyze\Activity\Distance;

class DistanceType extends AbstractUnitBasedType
{
    public function __construct(AbstractDistanceUnit $distanceUnit)
    {
        parent::__construct($distanceUnit);

        // #TSC set the same precision as other parts
        $this->ViewPrecision = Distance::$DefaultDecimals;
    }
}
