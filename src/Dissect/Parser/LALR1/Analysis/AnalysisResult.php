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
     * @var array
     */
    protected $parseTable;

    /**
     * @var array
     */
    protected $resolvedConflicts;

    /**
     * Constructor.
     *
     * @param array $parseTable The parse table.
     * @param \Dissect\Parser\LALR1\Analysis\Automaton $automaton
     * @param array $conflicts An array of conflicts resolved during parse table
     * construction.
     */
    public function __construct(array $parseTable, Automaton $automaton, array $conflicts)
    {
        $this->parseTable = $parseTable;
        $this->automaton = $automaton;
        $this->resolvedConflicts = $conflicts;
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

    /**
     * Returns the resulting parse table.
     *
     * @return array The parse table.
     */
    public function getParseTable()
    {
        return $this->parseTable;
    }

    /**
     * Returns an array of resolved parse table conflicts.
     *
     * @return array The conflicts.
     */
    public function getResolvedConflicts()
    {
        return $this->resolvedConflicts;
    }
}
