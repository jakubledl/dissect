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
        $grammar->addRule('AdditiveExpr', array('AdditiveExpr', '+', 'MultiplicativeExpr'))
            ->setCallback(function ($left, $plus, $right) {
                return $left + $right;
            });
        $grammar->addRule('AdditiveExpr', array('MultiplicativeExpr'));


        // MultiplicativeExpr
        $grammar->addRule('MultiplicativeExpr', array('MultiplicativeExpr', '*', 'PowerExpr'))
            ->setCallback(function ($left, $times, $right) {
                return $left * $right;
            });
        $grammar->addRule('MultiplicativeExpr', array('PowerExpr'));


        // PowerExpr
        $grammar->addRule('PowerExpr', array('PrimaryExpr', '**', 'PowerExpr'))
            ->setCallback(function ($left, $pow, $right) {
                return pow($left, $right);
            });
        $grammar->addRule('PowerExpr', array('PrimaryExpr'));


        // PrimaryExpr
        $grammar->addRule('PrimaryExpr', array('(', 'AdditiveExpr', ')'))
            ->setCallback(function ($lparen, $expr, $rparen) {
                return $expr;
            });
        $grammar->addRule('PrimaryExpr', array('INT'))
            ->setCallback(function ($value) {
                return (int)$value;
            });

        $grammar->setStartRule('AdditiveExpr');

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
