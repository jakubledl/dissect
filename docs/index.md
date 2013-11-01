Welcome to Dissect!
===================

Dissect is a set of tools for lexical and syntactical analysis
written in pure PHP.

This guide assumes that you're already familiar with basic concepts
of parsing. Explaining them is beyond the scope of this simple guide,
so if you're not, see, for example, [this article][parsing].
This page serves as an index for individual documentation pages.

1. [Lexical analysis with Dissect](lexing.md)
    1. [SimpleLexer](lexing.md#simplelexer)
    2. [StatefulLexer](lexing.md#statefullexer)
    3. [Improving lexer performance](lexing.md#improving-lexer-performance)
    4. [RegexLexer](lexing.md#regexlexer)
2. [Parsing with Dissect](parsing.md)
    1. [Why an LALR(1) parser?](parsing.md#why-an-lalr1-parser)
    2. [Writing a grammar](parsing.md#writing-a-grammar)
    3. [Example: Parsing mathematical expressions](parsing.md#example-parsing-mathematical-expressions)
    4. [Invalid input](parsing.md#invalid-input)
    5. [Precomputing the parse table](parsing.md#precomputing-the-parse-table)
    6. [Resolving conflicts](parsing.md#resolving-conflicts)
3. [Building an AST](ast.md)
    1. [Travesing the AST](ast.md#traversing-the-ast)
4. [Describing common syntactic structures](common.md)
    1. [List of 1 or more `Foo`s](common.md#list-of-1-or-more-foos)
    2. [List of 0 or more `Foo`s](common.md#list-of-0-or-more-foos)
    3. [A comma separated list](common.md#a-comma-separated-list)
    4. [Expressions](common.md#expressions)
5. [The command-line interface](cli.md)
    1. [Running the tool](cli.md#running-the-tool)
    2. [Dumping the parse table in the debug format](cli.md#dumping-the-parse-table-in-the-debug-format)
    3. [Dumping the handle-finding automaton](cli.md#dumping-the-handle-finding-automaton)

[parsing]: http://en.wikipedia.org/wiki/Parsing
