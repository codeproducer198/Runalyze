<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Runalyze\Service\RouteNameEvaluation\RouteNameEvalResult;

/**
 * Testcase for RouteNameEvalResult.
 */
class RouteNameEvalResultTest extends \PHPUnit\Framework\TestCase {

    public function setUp() : void {
    }

    public function testAppendNotes_ExistingNull() {
        $r = new RouteNameEvalResult("thename", "thenote");

        $note = $r->appendNotes(null);
        $this->assertEquals(RouteNameEvalResult::NOTES_START . "\nthenote\n" . RouteNameEvalResult::NOTES_END, $note);
    }

    // existing notes but eval-note is empty -> use only existing one
    public function testAppendNotes_Exist_NewNoteEmpty() {
        $r = new RouteNameEvalResult("thename", "");

        $note = $r->appendNotes("x");
        $this->assertEquals("x", $note);
    }

    // no existing notes exists -> set evaluation note as new
    public function testAppendNotes_ExistIsEmpty() {
        $r = new RouteNameEvalResult("thename", "multi\nline\nnotes\n");

        $note = $r->appendNotes("");
        $this->assertEquals(RouteNameEvalResult::NOTES_START . "\nmulti\nline\nnotes\n" . RouteNameEvalResult::NOTES_END, $note);
    }

    // existing notes exists, but no evaluation note -> append evaluation note to the end
    public function testAppendNotes_ExistNotEmpty_01() {
        $r = new RouteNameEvalResult("thename", "multi\nline\nnotes");

        $note = $r->appendNotes("Alread\nhas some\n");

        $this->assertEquals("Alread\nhas some\n\n" . 
            RouteNameEvalResult::NOTES_START . "\nmulti\nline\nnotes\n" . RouteNameEvalResult::NOTES_END, $note);
    }

    // existing notes exists, but no evaluation note -> append evaluation note to the end
    public function testAppendNotes_ExistNotEmpty_02() {
        $r = new RouteNameEvalResult("thename", "multi\nline\nnotes");

        $note = $r->appendNotes("Alread\nhas some");

        $this->assertEquals("Alread\nhas some\n\n" . 
            RouteNameEvalResult::NOTES_START . "\nmulti\nline\nnotes\n" . RouteNameEvalResult::NOTES_END, $note);
    }

    // existing notes with existing evaluation not exists -> replace eval note
    public function testAppendNotes_exist_01() {
        $r = new RouteNameEvalResult("thename", "multi\nline\nnotes\n");

        $note = $r->appendNotes("Alread\nhas some\n" .
            RouteNameEvalResult::NOTES_START . "old-existing-info\n" . RouteNameEvalResult::NOTES_END);

        $this->assertEquals("Alread\nhas some\n" . 
            RouteNameEvalResult::NOTES_START . "\nmulti\nline\nnotes\n" . RouteNameEvalResult::NOTES_END, $note);
    }

    // existing notes with existing evaluation not exists; with further text after the eval note -> replace eval note
    public function testAppendNotes_exist_02() {
        $r = new RouteNameEvalResult("thename", "new");

        $note = $r->appendNotes("Alread\n" .
            RouteNameEvalResult::NOTES_START . "old\n" . RouteNameEvalResult::NOTES_END .
            "end text");

        // check also a PHP_EOL is added to the eval-note!
        $this->assertEquals("Alread\n" . 
            RouteNameEvalResult::NOTES_START . "\nnew\n" . RouteNameEvalResult::NOTES_END .
            "end text", $note);
    }

    // existing notes with existing evaluation exists -> replace eval note
    public function testAppendNotes_exist_03() {
        $r = new RouteNameEvalResult("thename", "multi\nline\nnotes\n");

        $note = $r->appendNotes(RouteNameEvalResult::NOTES_START . "old-existing-info\n" . RouteNameEvalResult::NOTES_END);

        $this->assertEquals(RouteNameEvalResult::NOTES_START . "\nmulti\nline\nnotes\n" . RouteNameEvalResult::NOTES_END, $note);
    }

}