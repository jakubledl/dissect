<?php

namespace Dissect\Lexer;

use Dissect\Lexer\Recognizer\Recognizer;

/**
 * SimpleLexer uses specified recognizers
 * without keeping track of state.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class SimpleLexer extends AbstractLexer
{
    /**
     * @var array
     */
    protected $skipTokens = array();

    /**
     * @var array
     */
    protected $recognizers = array();

    /**
     * Marks certain token types to be skipped.
     *
     * @param string[] $types The token types to be skipped.
     */
    public function skipTokens(array $types)
    {
        $this->skipTokens = $types;
    }

    /**
     * Adds a recognizer.
     *
     * @param string $type The token type for this recognizer.
     * @param \Dissect\Lexer\Recognizer\Recognizer $recognizer The recognizer.
     */
    public function addRecognizer($type, Recognizer $recognizer)
    {
        $this->recognizers[$type] = $recognizer;
    }

    /**
     * {@inheritDoc}
     */
    protected function shouldSkipToken(Token $token)
    {
        return in_array($token->getType(), $this->skipTokens);
    }

    /**
     * {@inheritDoc}
     */
    protected function extractToken($string)
    {
        $value = $type = null;

        foreach ($this->recognizers as $t => $recognizer) {
            if ($recognizer->match($string, $v)) {
                if ($value === null || $this->stringLength($v) > $this->stringLength($value)) {
                    $value = $v;
                    $type = $t;
                }
            }
        }

        if ($type !== null) {
            return new CommonToken($type, $value, $this->getCurrentLine(), $this->getCurrentOffset());
        }

        return null;
    }
}
