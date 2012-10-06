<?php

namespace Dissect\Parser\LALR1;

use Dissect\Parser\Rule;

/**
 * A LALR(1) item.
 *
 * An item represents a state where a part of
 * a grammar rule has been recognized. The current
 * position is marked by a dot:
 *
 * <pre>
 * A -> a . b c
 * </pre>
 *
 * This means that within this item, a has been recognized
 * and b is expected. If the dot is at the very end of the
 * rule:
 *
 * <pre>
 * A -> a b c .
 * </pre>
 *
 * it means that the whole rule has been recognized and
 * can be reduced.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class Item
{
    /**
     * @var \Dissect\Parser\Rule
     */
    protected $rule;

    /**
     * @var int
     */
    protected $dotIndex;

    /**
     * Constructor.
     *
     * @param \Dissect\Parser\Rule $rule The rule of this item.
     * @param int $dotIndex The index of the dot in this item.
     */
    public function __construct(Rule $rule, $dotIndex)
    {
        $this->rule = $rule;
        $this->dotIndex = $dotIndex;
    }

    /**
     * Returns the dot index of this item.
     *
     * @return int The dot index.
     */
    public function getDotIndex()
    {
        return $this->dotIndex;
    }

    /**
     * Returns the currently expected component.
     *
     * If the item is:
     *
     * <pre>
     * A -> a . b c
     * </pre>
     *
     * then this method returns the component "b".
     *
     * @return string The component.
     */
    public function getComponentAtDotIndex()
    {
        return $this->rule->getComponent($this->dotIndex);
    }

    /**
     * Returns the rule of this item.
     *
     * @return \Dissect\Parser\Rule The rule.
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Determines whether this item is a reduction item.
     *
     * An item is a reduction item if the dot is at the very end:
     *
     * <pre>
     * A -> a b c .
     * </pre>
     *
     * @return boolean Whether this item is a reduction item.
     */
    public function isReductionItem()
    {
        return $this->dotIndex === count($this->rule->getComponents());
    }
}
