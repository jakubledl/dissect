<?php

namespace Dissect\Lexer\TokenStream;

use Countable;
use IteratorAggregate;

/**
 * A common contract for all token stream classes.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
interface TokenStream extends Countable, IteratorAggregate
{
    /**
     * Returns the current position in the stream.
     *
     * @return int The current position in the stream.
     */
    public function getPosition();

    /**
     * Retrieves the current token.
     *
     * @return \Dissect\Lexer\Token The current token.
     */
    public function getCurrentToken();

    /**
     * Returns a look-ahead token. Negative values are allowed
     * and serve as look-behind.
     *
     * @param int $n The look-ahead.
     *
     * @throws \OutOfBoundsException If current position + $n is out of range.
     *
     * @return \Dissect\Lexer\Token The lookahead token.
     */
    public function lookAhead($n);

    /**
     * Returns the token at absolute position $n.
     *
     * @param int $n The position.
     *
     * @throws \OutOfBoundsException If $n is out of range.
     *
     * @return \Dissect\Lexer\Token The token at position $n.
     */
    public function get($n);

    /**
     * Moves the cursor to the absolute position $n.
     *
     * @param int $n The position.
     *
     * @throws \OutOfBoundsException If $n is out of range.
     */
    public function move($n);

    /**
     * Moves the cursor by $n, relative to the current position.
     *
     * @param int $n The seek.
     *
     * @throws \OutOfBoundsException If current position + $n is out of range.
     */
    public function seek($n);

    /**
     * Moves the cursor to the next token.
     *
     * @throws \OutOfBoundsException If at the end of the stream.
     */
    public function next();
}
