<?php

namespace Dissect\Parser\LALR1\Analysis\Exception;

use Dissect\Parser\Rule;
use LogicException;

/**
 * Thrown when a grammar is not LALR(1) and exhibits
 * a shift/reduce conflict.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class ShiftReduceConflictException extends LogicException
{
    /**
     * The exception message template.
     */
    const MESSAGE = <<<EOT
The grammar exhibits a shift/reduce conflict on rule:

  %d. %s -> %s

(on lookahead "%s" in state %d). Restructure your grammar or choose a conflict resolution mode to prevent this.
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
     * @var int
     */
    protected $state;

    /**
     * Constructor.
     *
     * @param \Dissect\Parser\Rule $rule The conflicting grammar rule.
     * @param string $lookahead The conflicting lookahead to shift.
     */
    public function __construct($state, Rule $rule, $lookahead)
    {
        parent::__construct(sprintf(
            self::MESSAGE,
            $rule->getNumber(),
            $rule->getName(),
            implode(' ', $rule->getComponents()),
            $lookahead,
            $state
        ));

        $this->rule = $rule;
        $this->lookahead = $lookahead;
        $this->state = $state;
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

    /**
     * Returns the number of the inadequate state.
     *
     * @return int
     */
    public function getStateNumber()
    {
        return $this->state;
    }
}
