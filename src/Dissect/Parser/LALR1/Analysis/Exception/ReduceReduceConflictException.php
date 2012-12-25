<?php

namespace Dissect\Parser\LALR1\Analysis\Exception;

use Dissect\Parser\Rule;
use LogicException;

/**
 * Thrown when a grammar is not LALR(1) and exhibits
 * a reduce/reduce conflict.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class ReduceReduceConflictException extends LogicException
{
    /**
     * The exception message template.
     */
    const MESSAGE = <<<EOT
The grammar exhibits a reduce/reduce conflict on rules:

  %d. %s -> %s

vs:

  %d. %s -> %s

(on lookahead "%s" in state %d). Restructure your grammar or choose a conflict resolution mode to prevent this.
EOT;

    /**
     * @var \Dissect\Parser\Rule
     */
    protected $firstRule;

    /**
     * @var \Dissect\Parser\Rule
     */
    protected $secondRule;

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
     * @param int $state The number of the inadequate state.
     * @param \Dissect\Parser\Rule $firstRule The first conflicting grammar rule.
     * @param \Dissect\Parser\Rule $secondRule The second conflicting grammar rule.
     * @param string $lookahead The conflicting lookahead.
     */
    public function __construct($state, Rule $firstRule, Rule $secondRule, $lookahead)
    {
        parent::__construct(sprintf(
            self::MESSAGE,
            $firstRule->getNumber(),
            $firstRule->getName(),
            implode(' ', $firstRule->getComponents()),
            $secondRule->getNumber(),
            $secondRule->getName(),
            implode(' ', $secondRule->getComponents()),
            $lookahead,
            $state
        ));

        $this->firstRule = $firstRule;
        $this->secondRule = $secondRule;
        $this->lookahead = $lookahead;
        $this->state = $state;
    }

    /**
     * Returns the first conflicting rule.
     *
     * @return \Dissect\Parser\Rule The first conflicting rule.
     */
    public function getFirstRule()
    {
        return $this->firstRule;
    }

    /**
     * Returns the second conflicting rule.
     *
     * @return \Dissect\Parser\Rule The second conflicting rule.
     */
    public function getSecondRule()
    {
        return $this->secondRule;
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
