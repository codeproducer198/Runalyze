<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use Runalyze\Bundle\CoreBundle\Form\Type\DistanceType;
use Runalyze\Metrics\Distance\Unit\Kilometer;

class DistanceTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var DistanceType */
    protected $Type;

    protected function setUp() : void
    {
        $this->Type = new DistanceType(new Kilometer());
    }

    public function testTransform()
    {
        $this->assertEquals('', $this->Type->transform(null));
        $this->assertEquals(round(1.234, $this->Type->getViewPrecision()), $this->Type->transform(1.234));
        $this->assertEquals(round(1234.50, $this->Type->getViewPrecision()), $this->Type->transform(1234.5));
        $this->assertEquals(round(7.69, $this->Type->getViewPrecision()), $this->Type->transform('7.69'));
    }

    public function testReverseTransform()
    {
        $this->assertEquals(null, $this->Type->reverseTransform(null));
        $this->assertEquals(1.23, $this->Type->reverseTransform('1.23'));
        $this->assertEquals(1.234, $this->Type->reverseTransform('1.234'));
        $this->assertEquals(7.69, $this->Type->reverseTransform('7,69'));
    }
}
