The command-line interface
==========================

Dissect provides you with a command-line tool for processing and
debugging your grammars. This chapted describes the tool and its
options.

Running the tool
----------------

Let's assume that the executable is located in a folder called `bin`.
The most basic way to invoke it is

    $ bin/dissect <grammar-class>

This will analyze the given grammar and, if successful, save the parse
table in a file `parse_table.php` in the same folder where you've
defined your grammar. You can use `/` instead of `\` as the namespace
separator or enclose the class name in quotes.

To change the directory in which the parse table will be saved, use the
`--output-dir` (or `-o`) option:

    $ bin/dissect <grammar-class> --output-dir=../dir

Dumping the parse table in the debug format
-------------------------------------------

By default, the parse table will be saved as a single line of PHP code,
with minimal whitespace. If you want to inspect the generated table
manually, you can use the `--debug` (or `-d`) option:

    $ bin/dissect <grammar-class> --debug

The parse table will then be written in a human-readable way and with
comments explaining the steps of the parser.

Dumping the handle-finding automaton
------------------------------------

If you have an understanding of the LR parsing process, being able to
inspect the LR automaton visually could be an aid in resolving potential
grammar conflicts. In order to dump the automaton as a Graphviz graph,
use the `--dfa` (or `-D`) option:

    $ bin/dissect <grammar-class> --dfa

This will create a file called `automaton.dot` in the output directory.
You can then run something like

    dot -Tpng automaton.dot > automaton.png

to render it as a PNG image.

Of course, for more complex grammars, the automaton will quickly become rather large
and unwieldy. You can then use the `--state` (or `-s`) option to dump
only the specified state:

    $ bin/dissect <grammar-class> --dfa --state=5

As an example, let's say we use the following grammar:

```php
class PalindromeGrammar extends Grammar
{
    public function __construct()
    {
        $this('S')
            ->is('a', 'S', 'a')
            ->is('b', 'S', 'b')
            ->is(/* empty */);

        $this->start('S');
    }
}
```

When running the command-line tool, we'll notice a list of resolved
conflicts in the output:

    Resolved a shift/reduce conflict in state 2 on lookahead a
    Resolved a shift/reduce conflict in state 3 on lookahead b

If we wanted to examine the conflict in state 3, we could run

    $ bin/dissect PalindromeGrammar --dfa --state=3

and then

    $ dot -Tpng state_3.dot > state_3.png

The result will be the following image:

![State 3](https://raw.github.com/jakubledl/dissect/develop/docs/state_3.png)

in which we can clearly see how the conflict arose: the state #3 calls
both for a shift and a reduction by the rule `S -> ` on
lookahead `b`.
