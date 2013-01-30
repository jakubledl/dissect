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
     * The name given to the rule the grammar is augmented with
     * when start() is called.
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
     * @var array
     */
    protected $groupedRules = array();

    /**
     * @var int
     */
    protected $nextRuleNumber = 1;

    /**
     * @var int
     */
    protected $conflictsMode = 9; // SHIFT | OPERATORS

    /**
     * @var string
     */
    protected $currentNonterminal;

    /**
     * @var \Dissect\Parser\Rule
     */
    protected $currentRule;

    /**
     * @var array
     */
    protected $operators = array();

    /**
     * @var array
     */
    protected $currentOperators;

    /**
     * Signifies that the parser should not resolve any
     * grammar conflicts.
     */
    const NONE = 0;

    /**
     * Signifies that the parser should resolve
     * shift/reduce conflicts by always shifting.
     */
    const SHIFT = 1;

    /**
     * Signifies that the parser should resolve
     * reduce/reduce conflicts by reducing with
     * the longer rule.
     */
    const LONGER_REDUCE = 2;

    /**
     * Signifies that the parser should resolve
     * reduce/reduce conflicts by reducing
     * with the rule that was given earlier in
     * the grammar.
     */
    const EARLIER_REDUCE = 4;

    /**
     * Signifies that the conflicts should be
     * resolved by taking operator precendence
     * into account.
     */
    const OPERATORS = 8;

    /**
     * Signifies that the parser should automatically
     * resolve all grammar conflicts.
     */
    const ALL = 15;

    /**
     * Left operator associativity.
     */
    const LEFT = 0;

    /**
     * Right operator associativity.
     */
    const RIGHT = 1;

    /**
     * The operator is nonassociative.
     */
    const NONASSOC = 2;

    public function __invoke($nonterminal)
    {
        $this->currentNonterminal = $nonterminal;

        return $this;
    }

    /**
     * Defines an alternative for a grammar rule.
     *
     * @param string... The components of the rule.
     *
     * @return \Dissect\Parser\Grammar This instance.
     */
    public function is()
    {
        if ($this->currentNonterminal === null) {
            throw new LogicException(
                'You must specify a name of the rule first.'
            );
        }

        $num = $this->nextRuleNumber++;

        $rule = new Rule($num, $this->currentNonterminal, func_get_args());

        $this->rules[$num] =
            $this->currentRule =
            $this->groupedRules[$this->currentNonterminal][] =
            $rule;

        return $this;
    }

    /**
     * Sets the callback for the current rule.
     *
     * @param callable $callback The callback.
     *
     * @return \Dissect\Parser\Grammar This instance.
     */
    public function call($callback)
    {
        if ($this->currentRule === null) {
            throw new LogicException(
                'You must specify a rule first.'
            );
        }

        $this->currentRule->setCallback($callback);

        return $this;
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
     * Returns rules grouped by nonterminal name.
     *
     * @return array The rules grouped by nonterminal name.
     */
    public function getGroupedRules()
    {
        return $this->groupedRules;
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

    /**
     * Does a nonterminal $name exist in the grammar?
     *
     * @param string $name The name of the nonterminal.
     *
     * @return boolean
     */
    public function hasNonterminal($name)
    {
        return array_key_exists($name, $this->groupedRules);
    }

    /**
     * Defines a group of operators.
     *
     * @param string,... Any number of tokens that serve as the operators.
     *
     * @return \Dissect\Parser\Grammar This instance for fluent interface.
     */
    public function operators()
    {
        $ops = func_get_args();

        $this->currentOperators = $ops;

        foreach ($ops as $op) {
            $this->operators[$op] = array(
                'prec' => 1,
                'assoc' => self::LEFT,
            );
        }

        return $this;
    }

    /**
     * Marks the current group of operators as left-associative.
     *
     * @return \Dissect\Parser\Grammar This instance for fluent interface.
     */
    public function left()
    {
        return $this->assoc(self::LEFT);
    }

    /**
     * Marks the current group of operators as right-associative.
     *
     * @return \Dissect\Parser\Grammar This instance for fluent interface.
     */
    public function right()
    {
        return $this->assoc(self::RIGHT);
    }

    /**
     * Marks the current group of operators as nonassociative.
     *
     * @return \Dissect\Parser\Grammar This instance for fluent interface.
     */
    public function nonassoc()
    {
        return $this->assoc(self::NONASSOC);
    }

    /**
     * Explicitly sets the associatity of the current group of operators.
     *
     * @param int $a One of Grammar::LEFT, Grammar::RIGHT, Grammar::NONASSOC
     *
     * @return \Dissect\Parser\Grammar This instance for fluent interface.
     */
    public function assoc($a)
    {
        if (!$this->currentOperators) {
            throw new LogicException('Define a group of operators first.');
        }

        foreach ($this->currentOperators as $op) {
            $this->operators[$op]['assoc'] = $a;
        }

        return $this;
    }

    /**
     * Sets the precedence (as an integer) of the current group of operators.
     * If no group of operators is being specified, sets the precedence
     * of the currently described rule.
     *
     * @param int $i The precedence as an integer.
     *
     * @return \Dissect\Parser\Grammar This instance for fluent interface.
     */
    public function prec($i)
    {
        if (!$this->currentOperators) {
            if (!$this->currentRule) {
                throw new LogicException('Define a group of operators first.');
            } else {
                $this->currentRule->setPrecedence($i);
            }
        } else {
            foreach ($this->currentOperators as $op) {
                $this->operators[$op]['prec'] = $i;
            }
        }

        return $this;
    }

    /**
     * Is the passed token an operator?
     *
     * @param string $token The token type.
     *
     * @return boolean
     */
    public function hasOperator($token)
    {
        return array_key_exists($token, $this->operators);
    }

    public function getOperatorInfo($token)
    {
        return $this->operators[$token];
    }
}
