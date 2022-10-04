<?php

namespace Runalyze\Tests\Sports\Running\Prognosis;

use Runalyze\Sports\Running\Prognosis\Bock;

class BockTest extends \PHPUnit\Framework\TestCase
{
    /** @var Bock */
    protected $Bock;

    protected function setUp() : void
    {
        $this->Bock = new Bock();
    }

    public function testWithoutReferenceTime()
    {
        $this->assertFalse($this->Bock->areValuesValid());
    }

    /**
     * Remember: Formulas used in Bock's generator do not match to his tables
     */
    public function testSetFromResultsAndInSeconds()
    {
        $this->Bock->setFromResults(10.0, 30 * 60 + 0, 21.1, 65 * 60);
        $this->assertEqualsWithDelta(8 * 60 + 37, $this->Bock->getSeconds(3.0), 1);
        $this->assertEqualsWithDelta(14 * 60 + 37, $this->Bock->getSeconds(5.0), 1);
        $this->assertEqualsWithDelta(30 * 60 + 0, $this->Bock->getSeconds(10.0), 1);
        $this->assertEqualsWithDelta(65 * 60 + 0, $this->Bock->getSeconds(21.1), 1);
        $this->assertEqualsWithDelta(133 * 60 + 14, $this->Bock->getSeconds(42.2), 1);

        $this->Bock->setFromResults(10.0, 30 * 60 + 0, 21.1, 70 * 60);
        $this->assertEqualsWithDelta(7 * 60 + 39, $this->Bock->getSeconds(3.0), 1);
        $this->assertEqualsWithDelta(13 * 60 + 40, $this->Bock->getSeconds(5.0), 1);
        $this->assertEqualsWithDelta(30 * 60 + 0, $this->Bock->getSeconds(10.0), 1);
        $this->assertEqualsWithDelta(70 * 60 + 0, $this->Bock->getSeconds(21.1), 1);
        $this->assertEqualsWithDelta(153 * 60 + 42, $this->Bock->getSeconds(42.2), 1);

        $this->Bock->setFromResults(10.0, 40 * 60 + 0, 21.1, 90 * 60);
        $this->assertEqualsWithDelta(10 * 60 + 49, $this->Bock->getSeconds(3.0), 1);
        $this->assertEqualsWithDelta(18 * 60 + 51, $this->Bock->getSeconds(5.0), 1);
        $this->assertEqualsWithDelta(40 * 60 + 0, $this->Bock->getSeconds(10.0), 1);
        $this->assertEqualsWithDelta(90 * 60 + 0, $this->Bock->getSeconds(21.1), 1);
        $this->assertEqualsWithDelta(191 * 60 + 4, $this->Bock->getSeconds(42.2), 1);
    }
}
