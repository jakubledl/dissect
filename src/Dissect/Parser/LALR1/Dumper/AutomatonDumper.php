<?php

namespace Dissect\Parser\LALR1\Dumper;

use Dissect\Parser\LALR1\Analysis\Automaton;
use Dissect\Parser\LALR1\Analysis\Item;
use Dissect\Parser\LALR1\Analysis\State;

/**
 * Dumps the handle-finding FSA in the
 * format used by Graphviz.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class AutomatonDumper
{
    protected $automaton;

    /**
     * Constructor.
     *
     * @param \Dissect\Parser\LALR1\Analysis\Automaton $automaton
     */
    public function __construct(Automaton $automaton)
    {
        $this->automaton = $automaton;
    }

    /**
     * Dumps the entire automaton.
     *
     * @return string The automaton encoded in DOT.
     */
    public function dump()
    {
        $writer = new StringWriter();

        $this->writeHeader($writer);
        $writer->writeLine();

        foreach ($this->automaton->getStates() as $state) {
            $this->writeState($writer, $state);
        }

        $writer->writeLine();

        foreach ($this->automaton->getTransitionTable() as $num => $map) {
            foreach ($map as $trigger => $destination) {
                $writer->writeLine(sprintf(
                    '%d -> %d [label="%s"];',
                    $num,
                    $destination,
                    $trigger
                ));
            }
        }

        $writer->outdent();
        $this->writeFooter($writer);

        return $writer->get();
    }

    /**
     * Dumps only the specified state + any relevant
     * transitions.
     *
     * @param int $n The number of the state.
     *
     * @return string The output in DOT format.
     */
    public function dumpState($n)
    {
        $writer = new StringWriter();

        $this->writeHeader($writer, $n);
        $writer->writeLine();

        $this->writeState($writer, $this->automaton->getState($n));

        $table = $this->automaton->getTransitionTable();
        $row = isset($table[$n]) ? $table[$n] : array();

        foreach ($row as $dest) {
            if ($dest !== $n) {
                $this->writeState($writer, $this->automaton->getState($dest), false);
            }
        }

        $writer->writeLine();

        foreach ($row as $trigger => $dest) {
            $writer->writeLine(sprintf(
                '%d -> %d [label="%s"];',
                $n,
                $dest,
                $trigger
            ));
        }

        $writer->outdent();
        $this->writeFooter($writer);

        return $writer->get();
    }

    protected function writeHeader(StringWriter $writer, $stateNumber = null)
    {
        $writer->writeLine(sprintf(
            'digraph %s {',
            $stateNumber ? 'State' . $stateNumber : 'Automaton'
        ));

        $writer->indent();
        $writer->writeLine('rankdir="LR";');
    }

    protected function writeState(StringWriter $writer, State $state, $full = true)
    {
        $n = $state->getNumber();

        $string = sprintf(
            '%d [label="State %d',
            $n,
            $n
        );

        if ($full) {
            $string .= '\n\n';
            $items = array();

            foreach ($state->getItems() as $item) {
                $items[] = $this->formatItem($item);
            }

            $string .= implode('\n', $items);
        }

        $string .= '"];';

        $writer->writeLine($string);
    }

    protected function formatItem(Item $item)
    {
        $rule = $item->getRule();
        $components = $rule->getComponents();

        // the dot
        array_splice($components, $item->getDotIndex(), 0, array('&bull;'));

        if ($rule->getNumber() === 0) {
            $string = '';
        } else {
            $string = sprintf("%s &rarr; ", $rule->getName());
        }

        $string .= implode(' ', $components);

        if ($item->isReduceItem()) {
            $string .= sprintf(
                ' [%s]',
                implode(' ', $item->getLookahead())
            );
        }

        return $string;
    }

    protected function writeFooter(StringWriter $writer)
    {
        $writer->writeLine('}');
    }
}
