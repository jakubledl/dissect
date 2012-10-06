<?php

namespace Dissect\Lexer\TokenStream;

use ArrayIterator;
use OutOfBoundsException;

/**
 * A simple array based implementation of a token stream.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class ArrayTokenStream implements TokenStream
{
    /**
     * @var \Dissect\Lexer\Token[]
     */
    protected $tokens;

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * Constructor.
     *
     * @param \Dissect\Lexer\Token[] $tokens The tokens in this stream.
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * {@inheritDoc}
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentToken()
    {
        return $this->tokens[$this->position];
    }

    /**
     * {@inheritDoc}
     */
    public function lookAhead($n)
    {
        if (isset($this->tokens[$this->position + $n])) {
            return $this->tokens[$this->position + $n];
        }

        throw new OutOfBoundsException('Invalid look-ahead.');
    }

    /**
     * {@inheritDoc}
     */
    public function get($n)
    {
        if (isset($this->tokens[$n])) {
            return $this->tokens[$n];
        }

        throw new OutOfBoundsException('Invalid index.');
    }

    /**
     * {@inheritDoc}
     */
    public function move($n)
    {
        if (!isset($this->tokens[$n])) {
            throw new OutOfBoundsException('Invalid index to move to.');
        }

        $this->position = $n;
    }

    /**
     * {@inheritDoc}
     */
    public function seek($n)
    {
        if (!isset($this->tokens[$this->position + $n])) {
            throw new OutOfBoundsException('Invalid seek.');
        }

        $this->position += $n;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        if (!isset($this->tokens[$this->position + 1])) {
            throw new OutOfBoundsException('Attempting to move beyond the end of the stream.');
        }

        $this->position++;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->tokens);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->tokens);
    }
}
