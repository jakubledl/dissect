<?php

namespace Dissect\Parser;

/**
 * Represents a rule in a context-free grammar.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class Rule
{
    /**
     * @var int
     */
    protected $number;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string[]
     */
    protected $components;

    /**
     * @var callable
     */
    protected $callback = null;

    /**
     * Constructor.
     *
     * @param int $number The number of the rule in the grammar.
     * @param string $name The name (lhs) of the rule ("A" in "A -> a b c")
     * @param string[] $components The components of this rule.
     */
    public function __construct($number, $name, array $components)
    {
        $this->number = $number;
        $this->name = $name;
        $this->components = $components;
    }

    /**
     * Returns the number of this rule.
     *
     * @return int The number of this rule.
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Returns the name of this rule.
     *
     * @return string The name of this rule.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the components of this rule.
     *
     * @return string[] The components of this rule.
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * Returns a component at index $index or null
     * if index is out of range.
     *
     * @param int $index The index.
     *
     * @return string The component at index $index.
     */
    public function getComponent($index)
    {
        if (!isset($this->components[$index])) {
            return null;
        }

        return $this->components[$index];
    }

    /**
     * Sets the callback (the semantic value) of the rule.
     *
     * @param callable $callback The callback.
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    public function getCallback()
    {
        return $this->callback;
    }
}
