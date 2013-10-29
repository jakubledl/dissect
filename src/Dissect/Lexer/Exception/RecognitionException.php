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

    /**
     * Constructor.
     *
     * @param string $parameter The unrecognised parameter.
     * @param int position The character position within the current line where $parameter is located
     * @param int $line The line in the source where $parameter is located.
     */
    public function __construct($parameter, $position, $line)
    {
        $message = sprintf(
            'Invalid Parameter "%s" at line %d position %d',
            $parameter,
            $line,
            $position
        );

        parent::__construct($message);
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
}
