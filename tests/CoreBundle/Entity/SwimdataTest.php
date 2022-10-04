<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Swimdata;

class SwimdataTest extends \PHPUnit\Framework\TestCase
{
    /** @var Swimdata */
    protected $Data;

    public function setUp() : void
    {
        $this->Data = new Swimdata();
    }

    public function testEmptyEntity()
    {
        $this->assertTrue($this->Data->isEmpty());
        $this->assertNull($this->Data->getStroke());
        $this->assertNull($this->Data->getStroketype());
    }
}
