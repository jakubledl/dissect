<?php

namespace Dissect\Lexer\Recognizer;

/**
 * SimpleRecognizer matches a string by a simple
 * strpos match.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class SimpleRecognizer implements Recognizer
{
    protected $string;

    /**
     * Constructor.
     *
     * @param string $string The string to match by.
     */
    public function __construct($string)
    {
        $this->string = $string;
    }

    /**
     * {@inheritDoc}
     */
    public function match($string, &$result)
    {
        if (strncmp($string, $this->string, strlen($this->string)) === 0) {
            $result = $this->string;

            return true;
        }

        return false;
    }
}
