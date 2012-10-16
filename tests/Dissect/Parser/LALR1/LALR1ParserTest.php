<?php

namespace Dissect\Parser\LALR1;

use Dissect\Lexer\Recognizer\RegexRecognizer;
use Dissect\Lexer\Recognizer\SimpleRecognizer;
use Dissect\Lexer\SimpleLexer;
use Dissect\Parser\Exception\UnexpectedTokenException;
use Dissect\Parser\Grammar;
use PHPUnit_Framework_TestCase;

class LALR1ParserTest extends PHPUnit_Framework_TestCase
{
    protected $lexer;
    protected $parser;

    protected function setUp()
    {
        $this->lexer = new SimpleLexer();
        $this->lexer->regex('INT', '/[1-9][0-9]*/');
        $this->lexer->token('(');
        $this->lexer->token(')');
        $this->lexer->token('+');
        $this->lexer->token('**');
        $this->lexer->token('*');
        $this->lexer->regex('WSP', "/[ \r\n\t]+/");
        $this->lexer->skip('WSP');

        $grammar = new Grammar();

        // AdditiveExpr
        $grammar->rule('AdditiveExpr', array('AdditiveExpr', '+', 'MultiplicativeExpr'))
            ->call(function ($left, $plus, $right) {
                return $left + $right;
            });
        $grammar->rule('AdditiveExpr', array('MultiplicativeExpr'));


        // MultiplicativeExpr
        $grammar->rule('MultiplicativeExpr', array('MultiplicativeExpr', '*', 'PowerExpr'))
            ->call(function ($left, $times, $right) {
                return $left * $right;
            });
        $grammar->rule('MultiplicativeExpr', array('PowerExpr'));


        // PowerExpr
        $grammar->rule('PowerExpr', array('PrimaryExpr', '**', 'PowerExpr'))
            ->call(function ($left, $pow, $right) {
                return pow($left, $right);
            });
        $grammar->rule('PowerExpr', array('PrimaryExpr'));


        // PrimaryExpr
        $grammar->rule('PrimaryExpr', array('(', 'AdditiveExpr', ')'))
            ->call(function ($lparen, $expr, $rparen) {
                return $expr;
            });
        $grammar->rule('PrimaryExpr', array('INT'))
            ->call(function ($value) {
                return (int)$value;
            });

        $grammar->start('AdditiveExpr');

        $this->parser = new LALR1Parser($grammar);
    }

    /**
     * @test
     */
    public function parseShouldProcessTheTokenStreamAndUseGrammarCallbacksForReducing()
    {
        $this->assertEquals(11664, $this->parser->parse($this->lexer->lex(
            '6 ** (1 + 1) ** 2 * (5 + 4)')));
    }

    /**
     * @test
     */
    public function parseShouldThrowAnExceptionOnInvalidInput()
    {
        try {
            $this->parser->parse($this->lexer->lex('6 ** ( * 5'));
            $this->fail('Expected an UnexpectedTokenException.');
        } catch (UnexpectedTokenException $e) {
            $this->assertEquals('*', $e->getToken()->getType());
            $this->assertEquals(array('(', 'INT'), $e->getExpected());
        }
    }
}
