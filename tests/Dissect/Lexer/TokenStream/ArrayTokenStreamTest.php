<?php

namespace Dissect\Lexer\TokenStream;

use Dissect\Lexer\CommonToken;
use PHPUnit_Framework_TestCase;

class ArrayTokenStreamTest extends PHPUnit_Framework_TestCase
{
    protected $stream;

    protected function setUp()
    {
        $this->stream = new ArrayTokenStream(array(
            new CommonToken('INT', '6', 1, 1),
            new CommonToken('PLUS', '+', 1, 3),
            new CommonToken('INT', '5', 1, 5),
            new CommonToken('MINUS', '-', 1, 7),
            new CommonToken('INT', '3', 1, 9),
        ));
    }

    /**
     * @test
     */
    public function theCursorShouldBeOnFirstTokenByDefault()
    {
        $this->assertEquals('6', $this->stream->getCurrentToken()->getValue());
    }

    /**
     * @test
     */
    public function getPositionShouldReturnCurrentPosition()
    {
        $this->stream->seek(2);
        $this->stream->next();

        $this->assertEquals(3, $this->stream->getPosition());
    }

    /**
     * @test
     */
    public function lookAheadShouldReturnTheCorrectToken()
    {
        $this->assertEquals('5', $this->stream->lookAhead(2)->getValue());
    }

    /**
     * @test
     * @expectedException OutOfBoundsException
     */
    public function lookAheadShouldThrowAnExceptionWhenInvalid()
    {
        $this->stream->lookAhead(15);
    }

    /**
     * @test
     */
    public function getShouldReturnATokenByAbsolutePosition()
    {
        $this->assertEquals('3', $this->stream->get(4)->getValue());
    }

    /**
     * @test
     * @expectedException OutOfBoundsException
     */
    public function getShouldThrowAnExceptionWhenInvalid()
    {
        $this->stream->get(15);
    }

    /**
     * @test
     */
    public function moveShouldMoveTheCursorByToAnAbsolutePosition()
    {
        $this->stream->move(2);
        $this->assertEquals('5', $this->stream->getCurrentToken()->getValue());
    }

    /**
     * @test
     * @expectedException OutOfBoundsException
     */
    public function moveShouldThrowAnExceptionWhenInvalid()
    {
        $this->stream->move(15);
    }

    /**
     * @test
     */
    public function seekShouldMoveTheCursorByRelativeOffset()
    {
        $this->stream->seek(4);
        $this->assertEquals('3', $this->stream->getCurrentToken()->getValue());
    }

    /**
     * @test
     * @expectedException OutOfBoundsException
     */
    public function seekShouldThrowAnExceptionWhenInvalid()
    {
        $this->stream->seek(15);
    }

    /**
     * @test
     */
    public function nextShouldMoveTheCursorOneTokenAhead()
    {
        $this->stream->next();
        $this->assertEquals('PLUS', $this->stream->getCurrentToken()->getType());

        $this->stream->next();
        $this->assertEquals('5', $this->stream->getCurrentToken()->getValue());
    }

    /**
     * @test
     * @expectedException OutOfBoundsException
     */
    public function nextShouldThrowAnExceptionWhenAtTheEndOfTheStream()
    {
        $this->stream->seek(4);
        $this->stream->next();
    }
}
