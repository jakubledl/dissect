<?php

namespace Dissect\Parser\LALR1\Analysis;

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
     * @var array
     */
    protected $lookahead = array();

    /**
     * @var array
     */
    protected $connected = array();

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
    public function getActiveComponent()
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
     * Determines whether this item is a reduce item.
     *
     * An item is a reduce item if the dot is at the very end:
     *
     * <pre>
     * A -> a b c .
     * </pre>
     *
     * @return boolean Whether this item is a reduce item.
     */
    public function isReduceItem()
    {
        return $this->dotIndex === count($this->rule->getComponents());
    }

    /**
     * Connects two items with a lookahead pumping channel.
     *
     * @param \Dissect\Parser\LALR1\Analysis\Item $i The item.
     */
    public function connect(Item $i)
    {
        $this->connected[] = $i;
    }

    /**
     * Pumps a lookahead token to this item and all items connected
     * to it.
     *
     * @param string $lookahead The lookahead token name.
     */
    public function pump($lookahead)
    {
        if (!in_array($lookahead, $this->lookahead)) {
            $this->lookahead[] = $lookahead;

            foreach ($this->connected as $item) {
                $item->pump($lookahead);
            }
        }
    }

    /**
     * Pumps several lookahead tokens.
     *
     * @param array $lookahead The lookahead tokens.
     */
    public function pumpAll(array $lookahead)
    {
        foreach ($lookahead as $l) {
            $this->pump($l);
        }
    }

    /**
     * Returns the computed lookahead for this item.
     *
     * @return string[] The lookahead symbols.
     */
    public function getLookahead()
    {
        return $this->lookahead;
    }

    /**
     * Returns all components that haven't been recognized
     * so far.
     *
     * @return array The unrecognized components.
     */
    public function getUnrecognizedComponents()
    {
        return array_slice($this->rule->getComponents(), $this->dotIndex + 1);
    }
}
