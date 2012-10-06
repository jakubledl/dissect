<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\LALR1\ItemSet;
use Dissect\Parser\LALR1\Item;
use Dissect\Parser\Grammar;

/**
 * Calculates item sets for a given grammar.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class ItemSetCalculator
{
    /**
     * @var \Dissect\Parser\Grammar
     */
    protected $grammar;

    /**
     * @var \Dissect\Parser\LALR1\ItemSet[]
     */
    protected $itemSets;

    /**
     * @var array
     */
    protected $transitionTable;

    /**
     * @var int
     */
    protected $nextItemSetNumber;

    /**
     * @var array
     */
    protected $rulesByName;

    /**
     * @var array
     */
    protected $itemSetsOrigins;

    /**
     * Constructor.
     *
     * @param \Dissect\Parser\Grammar $grammar The grammar to calculate the item sets for.
     */
    public function __construct(Grammar $grammar)
    {
        $this->grammar = $grammar;

        foreach ($grammar->getRules() as $rule) {
            $this->rulesByName[$rule->getName()][] = $rule;
        }
    }

    /**
     * Calculates the item sets.
     *
     * @return array An array with the item sets at position 0 and the transition table at 1.
     */
    public function calculateItemSets()
    {
        $this->init();

        $this->createItemSet(array(
            new Item($this->grammar->getStartRule(), 0),
        ));

        for ($i = 0; $i < count($this->itemSets); $i++) {
            // item sets are added during iteration, count must be recalculated
            // each time
            $itemSet = $this->itemSets[$i];

            $this->calculateClosureItems($itemSet);
            $this->calculateTransitions($itemSet);
        }

        return array($this->itemSets, $this->transitionTable);
    }

    protected function init()
    {
        $this->itemSets = array();
        $this->transitionTable = array();
        $this->nextItemSetNumber = 0;
        $this->itemSetsOrigins = array();
    }

    protected function createItemSet(array $items)
    {
        $number = $this->nextItemSetNumber++;

        return $this->itemSets[$number] = new ItemSet(
            $number,
            $items
        );
    }

    protected function calculateClosureItems(ItemSet $set)
    {
        // the rule components we already processed
        $processed = array();
        $items = $set->all();

        for ($i = 0; $i < count($items); $i++) {
            $item = $items[$i];
            $component = $item->getComponentAtDotIndex();

            // if nonterminal and not yet processed
            if (in_array($component, $this->grammar->getNonterminals()) &&
                !in_array($component, $processed)) {

                // for each rule | $component -> ... | add the rule to the item set
                foreach ($this->rulesByName[$component] as $rule) {
                    $newItem = new Item($rule, 0);
                    $items[] = $newItem;
                    $set->add($newItem);
                }

                // mark the component as processed
                $processed[] = $component;
            }
        }
    }

    protected function calculateTransitions(ItemSet $set)
    {
        $origins = array();
        $items = $set->all();

        foreach ($items as $item) {
            $dotIndex = $item->getDotIndex();
            $component = $item->getComponentAtDotIndex();

            if ($component === null) {
                continue;
            }

            // the origin of an item set is a set of tuples of form
            // ($ruleNumber, $dotIndex)
            $origins[$component]['items'][] = $item;
            $origins[$component]['tuples'][] = array($item->getRule()->getNumber(), $dotIndex);
        }

        foreach ($origins as $component => $origin) {
            $itemSetNumber = $this->getItemSetNumberByOrigin($origin['tuples']);

            // if no item set with this origin exists yet
            if ($itemSetNumber === null) {

                // create it, with the dot in origin rules moved by 1
                $itemSet = $this->createItemSet(array_map(function (Item $item) {
                    return new Item($item->getRule(), $item->getDotIndex() + 1);
                }, $origin['items']));

                // update the origins
                $this->itemSetsOrigins[$itemSet->getNumber()] = $origin['tuples'];
                $this->transitionTable[$set->getNumber()][$component] = $itemSet->getNumber();
            } else {
                // just assign the number to the transition table
                $this->transitionTable[$set->getNumber()][$component] = $itemSetNumber;
            }
        }
    }

    protected function getItemSetNumberByOrigin(array $origin)
    {
        foreach ($this->itemSetsOrigins as $num => $orig) {
            if ($orig == $origin) {
                return $num;
            }
        }

        return null;
    }
}
