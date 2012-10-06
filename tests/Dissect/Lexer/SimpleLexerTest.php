<?php

namespace Dissect\Lexer;

use Dissect\Lexer\Recognizer\RegexRecognizer;
use Dissect\Lexer\Recognizer\SimpleRecognizer;
use PHPUnit_Framework_TestCase;

class SimpleLexerTest extends PHPUnit_Framework_TestCase
{
    protected $lexer;

    public function setUp()
    {
        $this->lexer = new SimpleLexer();

        $this->lexer->addRecognizer('A', new SimpleRecognizer('a'));
        $this->lexer->addRecognizer('LEFT_PAREN', new SimpleRecognizer('('));
        $this->lexer->addRecognizer('B', new SimpleRecognizer('b'));
        $this->lexer->addRecognizer('RIGHT_PAREN', new SimpleRecognizer(')'));
        $this->lexer->addRecognizer('C', new SimpleRecognizer('c'));

        $this->lexer->addRecognizer('WS', new RegexRecognizer("/[ \n\t\r]+/"));

        $this->lexer->skipTokens(array('WS'));
    }

    /**
     * @test
     */
    public function simpleLexerShouldWalkThroughTheRecognizers()
    {
        $stream = $this->lexer->lex('a (b) c');

        $this->assertEquals(6, $stream->count()); // with EOF
        $this->assertEquals('LEFT_PAREN', $stream->get(1)->getType());
        $this->assertEquals(1, $stream->get(3)->getLine());
        $this->assertEquals(4, $stream->get(2)->getOffset());
        $this->assertEquals('C', $stream->get(4)->getType());
    }

    /**
     * @test
     */
    public function simpleLexerShouldSkipSpecifiedTokens()
    {
        $stream = $this->lexer->lex('a (b) c');

        foreach ($stream as $token) {
            $this->assertNotEquals('WS', $token->getType());
        }
    }

    /**
     * @test
     */
    public function simpleLexerShouldReturnTheBestMatch()
    {
        $this->lexer->addRecognizer('CLASS', new SimpleRecognizer('class'));
        $this->lexer->addRecognizer('WORD', new RegexRecognizer('/[a-z]+/'));

        $stream = $this->lexer->lex('class classloremipsum');

        $this->assertEquals('CLASS', $stream->getCurrentToken()->getType());
        $this->assertEquals('WORD', $stream->lookAhead(1)->getType());
    }
}
