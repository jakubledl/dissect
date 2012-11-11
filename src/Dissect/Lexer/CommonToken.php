<?php

namespace Dissect\Lexer;

/**
 * A simple token representation.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class CommonToken implements Token
{
    /**
     * @var mixed
     */
    protected $type;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var int
     */
    protected $line;

    /**
     * Constructor.
     *
     * @param mixed $type The type of the token.
     * @param string $value The token value.
     * @param int $line The line.
     */
    public function __construct($type, $value, $line)
    {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getLine()
    {
        return $this->line;
    }
}
