Goals for 1.0
=============

- Compute reduction lookahead by the channel algorithm from *yacc*
  instead of the current LALR-by-SLR algorithm.
- Change the analyzer API to allow for grammar debugging
  (provide access to resolved conflicts, dumping the automaton to DOT ...).
- Provide classes for dumping the parse table to PHP (both the dev & prod version).
