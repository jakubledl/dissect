<?php

namespace Dissect\Parser\LALR1;

/**
 * A LALR(1) item set.
 *
 * An item set is a collection of items that together
 * form a state in a LALR(1) automaton.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class ItemSet
{
    /**
     * @var int
     */
    protected $number;

    /**
     * @var \Dissect\Parser\LALR1\Item[]
     */
    protected $items;

    /**
     * Constructor.
     *
     * @param int The number of this item set.
     * @param \Dissect\Parser\LALR1\Item[] $items The items of this set.
     */
    public function __construct($number, array $items)
    {
        $this->number = $number;
        $this->items = $items;
    }

    /**
     * Returns the items of this set.
     *
     * @return \Dissect\Parser\LALR1\Item[] The items.
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Adds an item to the item set.
     *
     * @param \Dissect\Parser\LALR1\Item $item The item to add.
     */
    public function add(Item $item)
    {
        $this->items[] = $item;
    }

    /**
     * Returns the number that identifies this item set.
     *
     * @return int The number of this item set.
     */
    public function getNumber()
    {
        return $this->number;
    }
}
