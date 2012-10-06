<?php

namespace Dissect\Parser;

use Dissect\Lexer\TokenStream\TokenStream;

/**
 * The parser interface.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
interface Parser
{
    /**
     * The token type that represents an EOF.
     */
    const EOF_TOKEN_TYPE = '$eof';

    /**
     * Parses a token stream and returns the semantical value
     * of the input.
     *
     * @param \Dissect\Lexer\TokenStream\TokenStream $stream The token stream.
     *
     * @return mixed The semantical value of the input.
     */
    public function parse(TokenStream $stream);
}
