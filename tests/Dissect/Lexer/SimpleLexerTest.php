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

        $this->lexer
            ->token('A', 'a')
            ->token('(')
            ->token('B', 'b')
            ->token(')')
            ->token('C', 'c')
            ->regex('WS', "/[ \n\t\r]+/")

            ->skip('WS');
    }

    /**
     * @test
     */
    public function simpleLexerShouldWalkThroughTheRecognizers()
    {
        $stream = $this->lexer->lex('a (b) c');

        $this->assertEquals(6, $stream->count()); // with EOF
        $this->assertEquals('(', $stream->get(1)->getType());
        $this->assertEquals(1, $stream->get(3)->getLine());
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
        $this->lexer->token('CLASS', 'class');
        $this->lexer->regex('WORD', '/[a-z]+/');

        $stream = $this->lexer->lex('class classloremipsum');

        $this->assertEquals('CLASS', $stream->getCurrentToken()->getType());
        $this->assertEquals('WORD', $stream->lookAhead(1)->getType());
    }
}
