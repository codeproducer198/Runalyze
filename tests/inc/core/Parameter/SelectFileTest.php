<?php

namespace Runalyze\Parameter;

class ParameterSelectFileTest extends \PHPUnit\Framework\TestCase
{
	/** @var \Runalyze\Parameter\SelectFile */
	protected $object;

	protected function setUp() : void
    {
		$this->object = new SelectFile('path/to/file.jpg', array(
			'folder' => 'dir/',
			'extensions' => array('jpg', 'png', 'gif'),
            'filename_only' => false
		));
	}

	public function testSet()
    {
		$this->assertEquals('path/to/file.jpg', $this->object->value());

		$this->object->set('another/path/to/file.png');
		$this->assertEquals('another/path/to/file.png', $this->object->value());
	}

	public function testWrongExtension()
    {
		$this->expectException(\InvalidArgumentException::class);
		$this->object->set('path/to/file.php');
	}

	public function testWrongPath()
    {
		$this->expectException(\InvalidArgumentException::class);
		$this->object->set('../private/file.jpg');
	}

	public function testRootPath()
    {
		$this->expectException(\InvalidArgumentException::class);
		$this->object->set('/usr/file.jpg');
	}

	public function testNoExtension()
    {
		$this->expectException(\InvalidArgumentException::class);
		$this->object->set('/bin/shell');
	}

	public function testUppercaseVariantsAllowed()
    {
		$this->object->set('another/path/to/file.PNG');
	}

	public function testUppercaseVariantsDisallowed()
    {
		$this->expectException(\InvalidArgumentException::class);
		$this->object->allowUppercaseVariants(false);
		$this->object->set('another/path/to/file.PNG');
	}

    public function testFilenameOnly()
    {
        $select = new SelectFile('file.jpg', array(
            'folder' => 'dir/',
            'extensions' => array('jpg', 'png', 'gif'),
            'filename_only' => true
        ));

        $this->assertEquals('file.jpg', $select->value());
    }
}
