<?php

namespace Dissect\Lexer\Recognizer;

/**
 * The RegexRecognizer matches a string using a
 * regular expression.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class RegexRecognizer implements Recognizer
{
    protected $regex;

    /**
     * Constructor.
     *
     * @param string $regex The regex to use in the match.
     */
    public function __construct($regex)
    {
        $this->regex = $regex;
    }

    /**
     * {@inheritDoc}
     */
    public function match($string, &$result)
    {
        $r = preg_match($this->regex, $string, $match, PREG_OFFSET_CAPTURE);

        if ($r === 1 && $match[0][1] === 0) {
            $result = $match[0][0];

            return true;
        }

        return false;
    }
}
