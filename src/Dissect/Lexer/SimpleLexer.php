<?php

namespace Dissect\Lexer;

use Dissect\Lexer\Recognizer\RegexRecognizer;
use Dissect\Lexer\Recognizer\SimpleRecognizer;
use Dissect\Util\Util;

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
     * Adds a new token definition. If given only one argument,
     * it assumes that the token type and recognized value are
     * identical.
     *
     * @param string $type The token type.
     * @param string $value The value to be recognized.
     *
     * @return \Dissect\Lexer\SimpleLexer This instance for fluent interface.
     */
    public function token($type, $value = null)
    {
        if ($value) {
            $this->recognizers[$type] = new SimpleRecognizer($value);
        } else {
            $this->recognizers[$type] = new SimpleRecognizer($type);
        }

        return $this;
    }

    /**
     * Adds a new regex token definition.
     *
     * @param string $type The token type.
     * @param string $regex The regular expression used to match the token.
     *
     * @return \Dissect\Lexer\SimpleLexer This instance for fluent interface.
     */
    public function regex($type, $regex)
    {
        $this->recognizers[$type] = new RegexRecognizer($regex);

        return $this;
    }

    /**
     * Marks the token types given as arguments to be skipped.
     *
     * @param mixed $type,... Unlimited number of token types.
     *
     * @return \Dissect\Lexer\SimpleLexer This instance for fluent interface.
     */
    public function skip()
    {
        $this->skipTokens = func_get_args();

        return $this;
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
                if ($value === null || Util::stringLength($v) > Util::stringLength($value)) {
                    $value = $v;
                    $type = $t;
                }
            }
        }

        if ($type !== null) {
            return new CommonToken($type, $value, $this->getCurrentLine());
        }

        return null;
    }
}
