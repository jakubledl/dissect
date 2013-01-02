<?php

namespace Dissect\Parser\LALR1\Analysis\Exception;

use Dissect\Parser\LALR1\Analysis\Automaton;
use Dissect\Parser\Rule;

/**
 * Thrown when a grammar is not LALR(1) and exhibits
 * a shift/reduce conflict.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class ShiftReduceConflictException extends ConflictException
{
    /**
     * The exception message template.
     */
    const MESSAGE = <<<EOT
The grammar exhibits a shift/reduce conflict on rule:

  %d. %s -> %s

(on lookahead "%s" in state %d). Restructure your grammar or choose a conflict resolution mode.
EOT;

    /**
     * @var \Dissect\Parser\Rule
     */
    protected $rule;

    /**
     * @var string
     */
    protected $lookahead;

    /**
     * Constructor.
     *
     * @param \Dissect\Parser\Rule $rule The conflicting grammar rule.
     * @param string $lookahead The conflicting lookahead to shift.
     * @param \Dissect\Parser\LALR1\Analysis\Automaton $automaton The faulty automaton.
     */
    public function __construct($state, Rule $rule, $lookahead, Automaton $automaton)
    {
        $components = $rule->getComponents();

        parent::__construct(
            sprintf(
                self::MESSAGE,
                $rule->getNumber(),
                $rule->getName(),
                empty($components) ? '/* empty */' : implode(' ', $components),
                $lookahead,
                $state
            ),
            $state,
            $automaton
        );

        $this->rule = $rule;
        $this->lookahead = $lookahead;
    }

    /**
     * Returns the conflicting rule.
     *
     * @return \Dissect\Parser\Rule The conflicting rule.
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Returns the conflicting lookahead.
     *
     * @return string The conflicting lookahead.
     */
    public function getLookahead()
    {
        return $this->lookahead;
    }
}
