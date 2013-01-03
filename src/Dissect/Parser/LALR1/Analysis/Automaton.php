<?php

namespace Dissect\Parser\LALR1\Analysis;

/**
 * A finite-state automaton for recognizing
 * grammar productions.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class Automaton
{
    /**
     * @var array
     */
    protected $states = array();

    /**
     * @var array
     */
    protected $transitionTable = array();

    /**
     * Adds a new automaton state.
     *
     * @param \Dissect\Parser\LALR1\Analysis\State $state The new state.
     */
    public function addState(State $state)
    {
        $this->states[$state->getNumber()] = $state;
    }

    /**
     * Adds a new transition in the FSA.
     *
     * @param int $origin The number of the origin state.
     * @param string $label The symbol that triggers this transition.
     * @param int $dest The destination state number.
     */
    public function addTransition($origin, $label, $dest)
    {
        $this->transitionTable[$origin][$label] = $dest;
    }

    /**
     * Returns a state by its number.
     *
     * @param int $number The state number.
     *
     * @return \Dissect\Parser\LALR1\Analysis\State The requested state.
     */
    public function getState($number)
    {
        return $this->states[$number];
    }

    /**
     * Does this automaton have a state identified by $number?
     *
     * @return boolean
     */
    public function hasState($number)
    {
        return isset($this->states[$number]);
    }

    /**
     * Returns all states in this FSA.
     *
     * @return array The states of this FSA.
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * Returns the transition table for this automaton.
     *
     * @return array The transition table.
     */
    public function getTransitionTable()
    {
        return $this->transitionTable;
    }
}
