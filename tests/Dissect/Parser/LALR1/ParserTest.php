<?php

namespace Dissect\Parser\LALR1;

use Dissect\Parser\Exception\UnexpectedTokenException;
use PHPUnit_Framework_TestCase;

class ParserTest extends PHPUnit_Framework_TestCase
{
    protected $lexer;
    protected $parser;

    protected function setUp()
    {
        $this->lexer = new ArithLexer();
        $this->parser = new Parser(new ArithGrammar());
    }

    /**
     * @test
     */
    public function parserShouldProcessTheTokenStreamAndUseGrammarCallbacksForReductions()
    {
        $this->assertEquals(11664, $this->parser->parse($this->lexer->lex(
            '6 ** (1 + 1) ** 2 * (5 + 4)')));
    }

    /**
     * @test
     */
    public function parserShouldThrowAnExceptionOnInvalidInput()
    {
        try {
            $this->parser->parse($this->lexer->lex('6 ** 5 3'));
            $this->fail('Expected an UnexpectedTokenException.');
        } catch (UnexpectedTokenException $e) {
            $this->assertEquals('INT', $e->getToken()->getType());
            $this->assertEquals(array('$eof', '+', '*', '**', ')'), $e->getExpected());
            $this->assertEquals(<<<EOT
Unexpected 3 (INT) at line 1.

Expected one of \$eof, +, *, **, ).
EOT
            , $e->getMessage());
        }
    }
}
