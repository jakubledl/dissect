<?php

namespace Dissect\Lexer\Recognizer;

use PHPUnit_Framework_TestCase;

class RegexRecognizerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function recognizerShouldMatchAndPassTheValueByReference()
    {
        $recognizer = new RegexRecognizer('/[a-z]+/');
        $result = $recognizer->match('lorem ipsum', $value);

        $this->assertTrue($result);
        $this->assertNotNull($value);
        $this->assertEquals('lorem', $value);
    }

    /**
     * @test
     */
    public function recognizerShouldFailAndTheValueShouldStayNull()
    {
        $recognizer = new RegexRecognizer('/[a-z]+/');
        $result = $recognizer->match('123 456', $value);

        $this->assertFalse($result);
        $this->assertNull($value);
    }

    /**
     * @test
     */
    public function recognizerShouldFailIfTheMatchIsNotAtTheBeginningOfTheString()
    {
        $recognizer = new RegexRecognizer('/[a-z]+/');
        $result = $recognizer->match('234 class', $value);

        $this->assertFalse($result);
        $this->assertNull($value);
    }
}
