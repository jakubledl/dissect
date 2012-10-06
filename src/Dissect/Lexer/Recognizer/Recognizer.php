<?php

namespace Dissect\Lexer\Recognizer;

/**
 * Recognizers are used by the lexer to process
 * the input string.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
interface Recognizer
{
    /**
     * Returns a boolean value specifying whether
     * the string matches or not and if it does,
     * returns the match in the second variable.
     *
     * @param string $string The string to match.
     * @param string $result The variable that gets set to the value of the match.
     *
     * @return boolean Whether the match was successful or not.
     */
    public function match($string, &$result);
}
