Goals
=====

1.1
---

- Optional operator precedence support (à la *yacc*, *bison*) - &#10004;
- A performance-oriented regex lexer (based on doctrine/lexer) - &#10004;
- An option to generate a hybrid recursive ascent parser - &#9633;

1.0
---

- Compute reduction lookahead by the channel algorithm from *yacc*
  instead of the current LALR-by-SLR algorithm - &#10004;
- Change the analyzer API to allow for grammar debugging
  (provide access to resolved conflicts, dumping the automaton to DOT ...) - &#10004;
- Provide classes for dumping the parse table to PHP (both the dev & prod version) - &#10004;
