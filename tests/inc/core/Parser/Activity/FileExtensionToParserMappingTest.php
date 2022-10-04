<?php

namespace Runalyze\Tests\Parser\Activity;

use Runalyze\Parser\Activity\Common\ParserInterface;
use Runalyze\Parser\Activity\FileExtensionToParserMapping;
use Runalyze\Parser\Activity\FileType\Tcx;

class FileExtensionToParserMappingTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileExtensionToParserMapping */
    protected $Mapping;

    public function setUp() : void
    {
        $this->Mapping = new FileExtensionToParserMapping();
    }

    public function testInvalidExtension()
    {
        $this->assertNull($this->Mapping->getParserClassFor('foobar'));
    }

    public function testThatMappingIsCaseInsensitive()
    {
        $this->assertEquals(Tcx::class, $this->Mapping->getParserClassFor('tcx'));
        $this->assertEquals(Tcx::class, $this->Mapping->getParserClassFor('TCX'));
    }

    public function testThatAllMappedParserExist()
    {
        foreach (FileExtensionToParserMapping::MAPPING as $extension => $class) {
            $this->assertInstanceOf(ParserInterface::class, new $class);
        }
    }
}
