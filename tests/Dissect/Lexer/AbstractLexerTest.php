<?php

namespace Dissect\Lexer;

use Dissect\Lexer\Exception\RecognitionException;
use Dissect\Parser\Parser;
use PHPUnit_Framework_TestCase;

class AbstractLexerTest extends PHPUnit_Framework_TestCase
{
    protected $lexer;

    public function setUp()
    {
        $this->lexer = new StubLexer();
    }

    /**
     * @test
     */
    public function lexShouldDelegateToExtractTokenUpdatingTheLineAndOffsetAccordingly()
    {
        $stream = $this->lexer->lex("ab\nc");

        $this->assertEquals('a', $stream->getCurrentToken()->getValue());
        $this->assertEquals(1, $stream->getCurrentToken()->getLine());
        $stream->next();

        $this->assertEquals('b', $stream->getCurrentToken()->getValue());
        $this->assertEquals(1, $stream->getCurrentToken()->getLine());
        $stream->next();

        $this->assertEquals("\n", $stream->getCurrentToken()->getValue());
        $this->assertEquals(1, $stream->getCurrentToken()->getLine());
        $stream->next();

        $this->assertEquals('c', $stream->getCurrentToken()->getValue());
        $this->assertEquals(2, $stream->getCurrentToken()->getLine());
    }

    /**
     * @test
     */
    public function lexShouldAppendAnEofTokenAutomatically()
    {
        $stream = $this->lexer->lex("abc");
        $stream->seek(3);

        $this->assertEquals(Parser::EOF_TOKEN_TYPE, $stream->getCurrentToken()->getType());
        $this->assertEquals(1, $stream->getCurrentToken()->getLine());
    }

    /**
     * @test
     */
    public function lexShouldThrowAnExceptionOnAnUnrecognizableToken()
    {
        try {
            $stream = $this->lexer->lex("abcd");
            $this->fail('Expected a RecognitionException.');
        } catch (RecognitionException $e) {
            $this->assertEquals(1, $e->getSourceLine());
        }
    }

    /**
     * @test
     */
    public function lexShouldNormalizeLineEndingsBeforeLexing()
    {
        $stream = $this->lexer->lex("a\r\nb");
        $this->assertEquals("\n", $stream->get(1)->getValue());
    }

    /**
     * @test
     */
    public function lexShouldSkipTokensIfToldToDoSo()
    {
        $stream = $this->lexer->lex('aeb');
        $this->assertNotEquals('e', $stream->get(1)->getType());
    }
}
