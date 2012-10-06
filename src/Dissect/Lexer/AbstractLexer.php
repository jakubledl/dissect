<?php

namespace Dissect\Lexer;

use Dissect\Lexer\Exception\RecognitionException;
use Dissect\Lexer\TokenStream\ArrayTokenStream;
use Dissect\Parser\Parser;

/**
 * A base class for a lexer. A superclass simply
 * has to implement the extractToken and shouldSkipToken methods. Both
 * SimpleLexer and StatefulLexer extend this class.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
abstract class AbstractLexer implements Lexer
{
    /**
     * @var int
     */
    private $line = 1;

    /**
     * @var int
     */
    private $offset = 1;

    /**
     * Returns the current line.
     *
     * @return int The current line.
     */
    protected function getCurrentLine()
    {
        return $this->line;
    }

    /**
     * Returns the current offset.
     *
     * @return int The current offset.
     */
    protected function getCurrentOffset()
    {
        return $this->offset;
    }

    /**
     * Attempts to extract another token from the string.
     * Returns the token on success or null on failure.
     *
     * @param string $string The string to extract the token from.
     *
     * @return \Dissect\Lexer\Token|null The extracted token or null.
     */
    abstract protected function extractToken($string);

    /**
     * Should given token be skipped?
     *
     * @param \Dissect\Lexer\Token $token The token to evaluate.
     *
     * @return boolean Whether to skip the token.
     */
    abstract protected function shouldSkipToken(Token $token);

    /**
     * {@inheritDoc}
     */
    public function lex($string)
    {
        // normalize line endings
        $string = strtr($string, array("\r\n" => "\n", "\r" => "\n"));

        $tokens = array();
        $position = 0;
        $originalString = $string;
        $originalLength = $this->stringLength($string);

        while (true) {
            $token = $this->extractToken($string);

            if ($token === null) {
                break;
            }

            if (!$this->shouldSkipToken($token)) {
                $tokens[] = $token;
            }

            $shift = $this->stringLength($token->getValue());

            $position += $shift;

            // update line + offset
            if ($position > 0) {
                $this->line = substr_count($originalString, "\n", 0, $position) + 1;

                $this->offset = $this->line > 1
                    ? $position - strrpos($this->substring($originalString, 0, $position), "\n")
                    : $position + 1;
            }

            $string = $this->substring($string, $shift);
        }

        if ($position !== $originalLength) {
            throw new RecognitionException($this->line, $this->offset);
        }

        $tokens[] = new CommonToken(Parser::EOF_TOKEN_TYPE, '', $this->line, $this->offset);

        return new ArrayTokenStream($tokens);
    }

    /**
     * Determines length of a UTF-8 string.
     *
     * @param string $str The string in UTF-8 encoding.
     *
     * @return int The length.
     */
    protected function stringLength($str)
    {
        return strlen(utf8_decode($str));
    }

    /**
     * Extracts a substring of a UTF-8 string.
     *
     * @param string $str The string to extract the substring from.
     * @param int $position The position from which to start extracting.
     * @param int $length The length of the substring.
     *
     * @return string The substring.
     */
    protected function substring($str, $position, $length = null)
    {
        if ($length === null) {
            $length = $this->stringLength($str);
        }

        if (function_exists('mb_substr')) {
            return mb_substr($str, $position, $length, 'UTF-8');
        } else {
            return iconv_substr($str, $position, $length, 'UTF-8');
        }
    }
}
