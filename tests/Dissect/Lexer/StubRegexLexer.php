<?php

namespace Dissect\Lexer;

use RuntimeException;

class StubRegexLexer extends RegexLexer
{
    protected $operators = array('+', '-');

    protected function getCatchablePatterns()
    {
        return array('[1-9][0-9]*');
    }

    protected function getNonCatchablePatterns()
    {
        return array('\s+');
    }

    protected function getType(&$value)
    {
        if (is_numeric($value)) {
            $value = (int)$value;

            return 'INT';
        } elseif (in_array($value, $this->operators)) {
            return $value;
        } else {
            throw new RuntimeException(sprintf('Invalid token "%s"', $value));
        }
    }
}
