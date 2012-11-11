<?php

namespace Dissect\Parser;

use LogicException;

/**
 * Represents a context-free grammar.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class Grammar
{
    /**
     * The name given to the rule the grammar is augmented by
     * when setStartRule() is called.
     */
    const START_RULE_NAME = '$start';

    /**
     * The epsilon symbol signifies an empty production.
     */
    const EPSILON = '$epsilon';

    /**
     * @var \Dissect\Parser\Rule[]
     */
    protected $rules = array();

    /**
     * @var string[]
     */
    protected $nonterminals = array();

    /**
     * @var int
     */
    protected $nextRuleNumber = 1;

    /**
     * @var int
     */
    protected $conflictsMode = self::SR_BY_SHIFT;

    /**
     * Signifies that the parser should not resolve any
     * grammar conflicts.
     */
    const NONE = 0;

    /**
     * Signifies that the parser should resolve
     * shift/reduce conflicts by always shifting.
     */
    const SR_BY_SHIFT = 1;

    /**
     * Signifies that the parser should resolve
     * reduce/reduce conflicts by reducing with
     * the longer rule.
     */
    const RR_BY_LONGER_RULE = 2;

    /**
     * Signifies that the parser should resolve
     * reduce/reduce conflicts by reducing
     * with the rule that was given earlier in
     * the grammar.
     */
    const RR_BY_EARLIER_RULE = 4;

    /**
     * Signifies that the parser should automatically
     * resolve all grammar conflicts.
     */
    const ALL = 7;

    /**
     * Adds a new rule to the grammar. The rules are numbered incrementally
     * from 1 up.
     *
     * @param string $name The name, or the left-hand side of the rule.
     * @param string[] $components The components, or the right-hand side of the rule.
     *
     * @return \Dissect\Parser\Rule The new rule.
     */
    public function rule($name, array $components)
    {
        $num = $this->nextRuleNumber++;

        if (!in_array($name, $this->nonterminals)) {
            $this->nonterminals[] = $name;
        }

        return $this->rules[$num] = new Rule($num, $name, $components);
    }

    /**
     * Returns the set of rules of this grammar.
     *
     * @return \Dissect\Parser\Rule[] The rules.
     */
    public function getRules()
    {
        return $this->rules;
    }

    public function getRule($number)
    {
        return $this->rules[$number];
    }

    /**
     * Returns the nonterminal symbols of this grammar.
     *
     * @return string[] The nonterminals.
     */
    public function getNonterminals()
    {
        return $this->nonterminals;
    }

    /**
     * Sets a start rule for this grammar.
     *
     * @param string The name of the start rule.
     */
    public function start($name)
    {
        $this->rules[0] = new Rule(0, self::START_RULE_NAME, array($name));
    }

    /**
     * Returns the augmented start rule. For internal use only.
     *
     * @return \Dissect\Parser\Rule The start rule.
     */
    public function getStartRule()
    {
        if (!isset($this->rules[0])) {
            throw new LogicException("No start rule specified.");
        }

        return $this->rules[0];
    }

    /**
     * Sets the mode of conflict resolution.
     *
     * @param int $mode The bitmask for the mode.
     */
    public function resolve($mode)
    {
        $this->conflictsMode = $mode;
    }

    /**
     * Returns the conflict resolution mode for this grammar.
     *
     * @return int The bitmask of the resolution mode.
     */
    public function getConflictsMode()
    {
        return $this->conflictsMode;
    }
}
