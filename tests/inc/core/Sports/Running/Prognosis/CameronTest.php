<?php

namespace Runalyze\Tests\Sports\Running\Prognosis;

use Runalyze\Sports\Running\Prognosis\Cameron;

class CameronTest extends \PHPUnit\Framework\TestCase
{
    /** @var Cameron */
    protected $Cameron;

    protected function setUp() : void
    {
        $this->Cameron = new Cameron();
    }

    public function testWithoutReferenceTime()
    {
        $this->assertFalse($this->Cameron->areValuesValid());
    }

    public function testSimplePrognosis()
    {
        $this->Cameron->setReferenceResult(2.0, 14 * 60 + 20);

        $this->assertTrue($this->Cameron->areValuesValid());
        $this->assertEqualsWithDelta(22.357 * 60, $this->Cameron->getSeconds(3.0), 0.1);
    }

    public function testMyCurrentResult()
    {
        $this->Cameron->setReferenceResult(5.0, 16 * 60 + 32);

        $this->assertTrue($this->Cameron->areValuesValid());
        $this->assertEqualsWithDelta(9 * 60 + 33, $this->Cameron->getSeconds(3.0), 1);
        $this->assertEqualsWithDelta(16 * 60 + 32, $this->Cameron->getSeconds(5.0), 1);
        $this->assertEqualsWithDelta(34 * 60 + 26, $this->Cameron->getSeconds(10.0), 1);
        $this->assertEqualsWithDelta(1 * 60 * 60 + 15 * 60 + 56, $this->Cameron->getSeconds(21.1), 1);
        $this->assertEqualsWithDelta(2 * 60 * 60 + 41 * 60 + 22, $this->Cameron->getSeconds(42.2), 1);
    }
}
