<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Twig;

use Runalyze\Bundle\CoreBundle\Twig\HtmlExtension;

class HtmlExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var HtmlExtension */
    protected $Html;

    public function setUp() : void
    {
        $this->Html = new HtmlExtension();
    }

    public function testAddingNoBreakSpaceCharacters()
    {
        $this->assertEquals('test&nbsp;foo&nbsp;&nbsp;bar', $this->Html->nbsp('test foo  bar'));
    }

    public function testRemovingNoBreakSpaceCharacters()
    {
        $this->assertEquals('test foo  bar', $this->Html->nonbsp('test&nbsp;foo&nbsp;&nbsp;bar'));
    }
}
