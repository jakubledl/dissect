<?php

namespace Dissect\Parser\LALR1\Analysis;

/**
 * The result of a grammar analysis.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class AnalysisResult
{
    /**
     * @var \Dissect\Parser\LALR1\Analysis\Automaton
     */
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
     * Returns the handle-finding FSA.
     *
     * @return \Dissect\Parser\LALR1\Analysis\Automaton
     */
    public function getAutomaton()
    {
        return $this->automaton;
    }
}
