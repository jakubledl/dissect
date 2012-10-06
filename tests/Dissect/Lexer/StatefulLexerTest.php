<?php

namespace Dissect\Lexer;

use Dissect\Lexer\Recognizer\RegexRecognizer;
use Dissect\Lexer\Recognizer\SimpleRecognizer;
use PHPUnit_Framework_TestCase;

class StatefulLexerTest extends PHPUnit_Framework_TestCase
{
    protected $lexer;

    protected function setUp()
    {
        $this->lexer = new StatefulLexer();
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The state "non-existent-state" is not defined.
     */
    public function addRecognizerShouldThrowAnExceptionForNonexistentState()
    {
        $this->lexer->addRecognizer('WORD', new RegexRecognizer('/[a-z]+/'),
            'non-existent-state');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function addStateShouldThrowAnExceptionWhenTheStateAlreadyExists()
    {
        $this->lexer->addState('root');
        $this->lexer->addState('root');
    }

    /**
     * @test
     * @expectedException LogicException
     */
    public function anExceptionShouldBeThrownOnLexingWithoutAStartingState()
    {
        $this->lexer->addState('root');

        $this->lexer->lex('foo');
    }

    /**
     * @test
     */
    public function theStateMechanismShouldCorrectlyPushAndPopStatesFromTheStack()
    {
        $this->lexer->addState('root');
        $this->lexer->addState('string');

        // root recognizers
        $this->lexer->addRecognizer('WORD', new RegexRecognizer('/[a-z]+/'), 'root');
        $this->lexer->addRecognizer('WS', new RegexRecognizer("/[ \r\n\t]+/"), 'root');
        $this->lexer->addRecognizer('QUOTE', new SimpleRecognizer('"'), 'root', 'string');
        $this->lexer->skipTokens('root', array('WS'));

        // string recognizers
        $this->lexer->addRecognizer('STRING_CONTENTS', new RegexRecognizer(
            '/(\\\\"|[^"])*/'), 'string');
        $this->lexer->addRecognizer('QUOTE', new SimpleRecognizer('"'), 'string',
            StatefulLexer::POP_STATE);

        $this->lexer->setStartingState('root');

        $stream = $this->lexer->lex('foo bar "long \\" string" baz quux');

        $this->assertCount(8, $stream);
        $this->assertEquals('STRING_CONTENTS', $stream->get(3)->getType());
        $this->assertEquals('long \\" string', $stream->get(3)->getValue());
        $this->assertEquals('quux', $stream->get(6)->getValue());
    }

    /**
     * @test
     */
    public function defaultActionShouldBeNop()
    {
        $this->lexer->addState('root');
        $this->lexer->addState('string');

        $this->lexer->addRecognizer('WORD', new RegexRecognizer('/[a-z]+/'), 'root');
        $this->lexer->addRecognizer('WS', new RegexRecognizer("/[ \r\n\t]+/"), 'root');

        $this->lexer->skipTokens('root', array('WS'));

        $this->lexer->setStartingState('root');

        $stream = $this->lexer->lex('foo bar');
        $this->assertEquals(3, $stream->count());
    }
}
