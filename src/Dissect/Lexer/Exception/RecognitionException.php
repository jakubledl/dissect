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
    protected $parameter;
    protected $position;
    protected $sourceLine;

    /**
     * Constructor.
     *
     * @param string $parameter The unrecognised parameter.
     * @param int position The character position within the current line where $parameter is located.
     * @param int $line The line in the source where $parameter is located.
     */
    public function __construct($parameter, $position, $line)
    {
        $this->parameter = $parameter;
        $this->position = $position;
        $this->sourceLine = $line;
        $message = sprintf(
            'Invalid Parameter "%s" at line %d position %d',
            $parameter,
            $line,
            $position
        );

        parent::__construct($message);
    }

    public function getParameter()
    {
        return $this->parameter();
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getSourceLine()
    {
        return $this->sourceLine;
    }
}
