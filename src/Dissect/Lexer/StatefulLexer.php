<?php

namespace Dissect\Lexer;

use Dissect\Lexer\Recognizer\Recognizer;
use InvalidArgumentException;
use LogicException;

/**
 * The StatefulLexer works like SimpleLexer,
 * but internally keeps notion of current lexer state.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class StatefulLexer extends AbstractLexer
{
    protected $states = array();
    protected $stateStack = array();

    /**
     * Signifies that no action should be taken on encountering a token.
     */
    const NO_ACTION = 0;

    /**
     * Indicates that a state should be popped of the state stack on
     * encountering a token.
     */
    const POP_STATE = 1;

    /**
     * Adds a new recognizer to a given lexer state.
     *
     * @param string $type The token type for this recognizer.
     * @param \Dissect\Lexer\Recognizer\Recognizer The recognizer.
     * @param string $state The lexer state this recognizer belongs to.
     * @param mixed $action The action the lexer should take when
     * encountering this token (defaults to no action).
     *
     * @throws \InvalidArgumentException When the specified state is not
     * registered in the lexer.
     */
    public function addRecognizer($type, Recognizer $recognizer, $state, $action = self::NO_ACTION)
    {
        if (!isset($this->states[$state])) {
            throw new InvalidArgumentException(sprintf(
                'The state "%s" is not defined.',
                $state
            ));
        }

        $this->states[$state]['recognizers'][$type] = $recognizer;
        $this->states[$state]['actions'][$type] = $action;
    }

    /**
     * Marks certain token types to be skipped in given state.
     *
     * @param string $state The state this applies to.
     * @param string[] $types The token types to be skipped.
     */
    public function skipTokens($state, array $types)
    {
        $this->states[$state]['skip_tokens'] = $types;
    }

    /**
     * Registers a new lexer state.
     *
     * @param string $state The new state name.
     *
     * @throws \InvalidArgumentException When the state is already defined.
     */
    public function addState($state)
    {
        if (isset($this->states[$state])) {
            throw new InvalidArgumentException(sprintf(
                'State "%s" is already defined',
            $state));
        }

        $this->states[$state] = array(
            'recognizers' => array(),
            'actions' => array(),
            'skip_tokens' => array(),
        );
    }

    /**
     * Sets the starting state for the lexer.
     *
     * @param string $state The name of the starting state.
     */
    public function setStartingState($state)
    {
        $this->stateStack[] = $state;
    }

    /**
     * {@inheritDoc}
     */
    protected function shouldSkipToken(Token $token)
    {
        $state = $this->states[$this->stateStack[count($this->stateStack) - 1]];

        return in_array($token->getType(), $state['skip_tokens']);
    }

    /**
     * {@inheritDoc}
     */
    protected function extractToken($string)
    {
        if (empty($this->stateStack)) {
            throw new LogicException("You must set a starting state before lexing.");
        }

        $value = $type = $action = null;
        $state = $this->states[$this->stateStack[count($this->stateStack) - 1]];

        foreach ($state['recognizers'] as $t => $recognizer) {
            if ($recognizer->match($string, $v)) {
                if ($value === null || $this->stringLength($v) > $this->stringLength($value)) {
                    $value = $v;
                    $type = $t;
                    $action = $state['actions'][$type];
                }
            }
        }

        if ($type !== null) {
            if (is_string($action)) { // enter new state
                $this->stateStack[] = $action;
            } elseif ($action === self::POP_STATE) {
                array_pop($this->stateStack);
            }

            return new CommonToken($type, $value, $this->getCurrentLine(), $this->getCurrentOffset());
        }

        return null;
    }
}
