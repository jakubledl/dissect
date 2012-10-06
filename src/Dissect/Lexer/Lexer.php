<?php

namespace Dissect\Lexer;

/**
 * A lexer takes an input string and processes
 * it into a token stream.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
interface Lexer
{
    /**
     * Lexes the given string, returning a token stream.
     *
     * @param string $string The string to lex.
     *
     * @throws \Dissect\Lexer\Exception\RecognitionException
     * When unable to extract more tokens from the string.
     *
     * @return \Dissect\Lexer\TokenStream\TokenStream The resulting token stream.
     */
    public function lex($string);
}
