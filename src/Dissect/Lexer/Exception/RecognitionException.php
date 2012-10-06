<?php

namespace Dissect\Lexer\Exception;

use RuntimeException;

/**
 * Thrown when a lexer is unable to extract another token.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class RecognitionException extends RuntimeException
{
    protected $sourceLine;
    protected $sourceOffset;

    /**
     * Constructor.
     *
     * @param int $line The line in the source.
     * @param int $offset The offset.
     */
    public function __construct($line, $offset)
    {
        $this->sourceLine = $line;
        $this->sourceOffset = $offset;
    }

    /**
     * Returns the source line number where the exception occured.
     *
     * @return int The source line number.
     */
    public function getSourceLine()
    {
        return $this->sourceLine;
    }

    /**
     * Returns the source offset from the line where the exception occured.
     *
     * @return in The source offset.
     */
    public function getSourceOffset()
    {
        return $this->sourceOffset;
    }
}
