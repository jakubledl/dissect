<?php

namespace Dissect\Lexer;

use Dissect\Lexer\Exception\RecognitionException;
use Dissect\Lexer\TokenStream\ArrayTokenStream;
use Dissect\Parser\Parser;
use Dissect\Util\Util;

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
     * Returns the current line.
     *
     * @return int The current line.
     */
    protected function getCurrentLine()
    {
        return $this->line;
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
        $originalLength = Util::stringLength($string);

        while (true) {
            $token = $this->extractToken($string);

            if ($token === null) {
                break;
            }

            if (!$this->shouldSkipToken($token)) {
                $tokens[] = $token;
            }

            $shift = Util::stringLength($token->getValue());

            $position += $shift;

            // update line + offset
            if ($position > 0) {
                $this->line = substr_count($originalString, "\n", 0, $position) + 1;
            }

            $string = Util::substring($string, $shift);
        }

        if ($position !== $originalLength) {
            throw new RecognitionException($this->line);
        }

        $tokens[] = new CommonToken(Parser::EOF_TOKEN_TYPE, '', $this->line);

        return new ArrayTokenStream($tokens);
    }
}
