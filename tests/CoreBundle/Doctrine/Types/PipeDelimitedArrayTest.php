<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArray;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\PipeDelimitedArrayString;

class PipeDelimitedArrayTest extends \PHPUnit\Framework\TestCase
{
    /** @var PipeDelimitedArray */
    protected $TypeNumber;

    /** @var PipeDelimitedArrayString */
    protected $TypeString;

    /** @var AbstractPlatform */
    protected $PlatformMock;

    public function setUp() : void
    {
        $this->TypeNumber = PipeDelimitedArray::getType(PipeDelimitedArray::PIPE_ARRAY);
        $this->TypeString = PipeDelimitedArrayString::getType(PipeDelimitedArrayString::PIPE_ARRAY);
        $this->PlatformMock = $this->getMockForAbstractClass(AbstractPlatform::class);
    }

    public function testEmptyData()
    {
        $this->assertNull($this->TypeNumber->convertToPHPValue(null, $this->PlatformMock));
        $this->assertNull($this->TypeNumber->convertToPHPValue('', $this->PlatformMock));
        $this->assertNull($this->TypeNumber->convertToDatabaseValue([], $this->PlatformMock));

        $this->assertNull($this->TypeString->convertToPHPValue(null, $this->PlatformMock));
        $this->assertNull($this->TypeString->convertToPHPValue('', $this->PlatformMock));
        $this->assertNull($this->TypeString->convertToDatabaseValue([], $this->PlatformMock));
    }

    public function testSimpleData()
    {
        // numeric
        $r = $this->TypeNumber->convertToPHPValue('3.14|42|0', $this->PlatformMock);
        $this->assertEquals([3.14, 42, 0], $r);
        $this->assertIsNotString($r[0]);
        $this->assertIsNumeric($r[1]);
        $this->assertIsNotString($r[2]);

        $this->assertEquals('3.14|42|0', $this->TypeNumber->convertToDatabaseValue([3.14, 42, 0], $this->PlatformMock));

        // string
        $this->assertEquals(['a', 'b', 3.4], $this->TypeString->convertToPHPValue('a|b|3.4', $this->PlatformMock));
        $this->assertEquals('a|b|3.4', $this->TypeString->convertToDatabaseValue(['a', 'b', 3.4], $this->PlatformMock));

        // | will be change to blank
        $this->assertEquals('a|b c', $this->TypeString->convertToDatabaseValue(['a', 'b|c'], $this->PlatformMock));
    }

    public function testPartiallyEmptyData()
    {
        $this->assertEquals('||-12.3|-11.4|-9.8', $this->TypeNumber->convertToDatabaseValue([null, null, -12.3, -11.4, -9.8], $this->PlatformMock));

        $result = $this->TypeNumber->convertToPHPValue('||-12.3|-11.4|-9.8', $this->PlatformMock);
        $this->assertEquals([null, null, -12.3, -11.4, -9.8], $result);
        $this->assertNull($result[0]);
        $this->assertNull($result[1]);

        $this->assertEquals('||a|b', $this->TypeString->convertToDatabaseValue([null, null, 'a', 'b'], $this->PlatformMock));

        $result = $this->TypeString->convertToPHPValue('||a|b', $this->PlatformMock);
        $this->assertEquals([null, null, 'a', 'b'], $result);
        $this->assertNull($result[0]);
        $this->assertNull($result[1]);
    }
}
