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
2. [Parsing with Dissect](parsing.md)
    1. [Few thoughts before we start](parsing.md#few-thoughts-before-we-start)
    2. [Writing a grammar](parsing.md#writing-a-grammar)
    3. [Example: Parsing mathematical expressions](parsing.md#example-parsing-mathematical-expressions)
    4. [Invalid input](parsing.md#invalid-input)
    5. [Precomputing the parse table](parsing.md#precomputing-the-parse-table)
    6. [Resolving conflicts](parsing.md#resolving-conflicts)
3. [Building an AST](node.md)

[parsing]: http://en.wikipedia.org/wiki/Parsing
