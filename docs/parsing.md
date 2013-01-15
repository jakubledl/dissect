Parsing with Dissect
====================

Why an LALR(1) parser?
----------------------

Parsing is a task that's needed more often than one would think;
for examples in some famous PHP projects, see [this parser][twigparser]
from [Twig][twig] and [these][annotationsparser] [two][dqlparser] from
[Doctrine][doctrine]. Chances are you've written one; if you did, it was
most likely a [recursive descent parser][rdparser], just like the
examples above. Now, such parsers have several disadvantages: first,
they obviously have to be manually written.  Second, they're *recursive*,
which means one thing: nest the input deep enough (like an
annotation, which has another annotation as a parameter, that annotation
has another annotation as a parameter ...) and your PHP process blows up
because of stack overflow (to be fair, you'd have to nest pretty deep).
And third, such parsers belong to a class of parsers known as
[LL(k)][llk], which means they're generally not as powerful as [LR(k)][lrk]
parsers. For instance, they cannot handle left-recursive rules
(rules like `A -> A ...`), which are probably the only sane way of
expressing left-associative binary operators (like addition, for
example).

But let's get to actually parsing something.

Writing a grammar
-----------------

A grammar is represented by a subclass of `Dissect\Parser\Grammar`.

```php
use Dissect\Parser\Grammar;

class ArithGrammar extends Grammar
{
    public function __construct()
    {
        // rule definitions
    }
}
```

First, you tell Dissect what rule are you describing. Let's say we want
to describe a rule for a `Sum`:

```php
$this('Sum')
```

and then you specify what the rule actually `is`:

```php
$this('Sum')
    ->is('int', '+', 'int');
```

A rule can of course have many alternatives:

```php
$this('Sum')
    ->is('int', '+', 'int')
    ->is('string', '+', 'string');
```

and you will probably want to specify how to evalute the rule:

```php
$this('Sum')
    ->is('int', '+', 'int')
    ->call(function ($l, $_, $r) {
        return $l + $r;
    })

    ->is('string', '+', 'string')
    ->call(function ($l, $_, $r) {
        return $l . $r;
    });
```

> The number of arguments to the callback function is always equal
> to the length of the rule to which it belongs.

### Empty rules

A grammar can (and many times will) contain empty rules, that is, rules that
can match 0 tokens of the input. This is useful when, for example,
describing a list of function arguments, which can be either empty or a list of
values separated by commas.

An empty rule is defined simply by calling `is` with 0 arguments:

```php
$this('Empty')
    ->is();
```

If you find this notation unclear, you can explicitly mark empty rules
with a comment:

```php
$this('Empty')
    ->is(/* empty */);
```

> **Beware:** When you don't specify a callback for a rule, Dissect
> will default to returing the leftmost (first) component of the rule. You
> are, however, required to specify a callback for an empty rule, since
> in a rule with zero components, there is obviously no leftmost one.

Example: Parsing mathematical expressions
-----------------------------------------

In the chapter on lexing, we've created a lexer we will now use to
process our expressions:

```php
class ArithLexer extends SimpleLexer
{
    public function __construct()
    {
        $this->regex('INT', '/^[1-9][0-9]*/');
        $this->token('(');
        $this->token(')');
        $this->token('+');
        $this->token('*');
        $this->token('**');

        $this->regex('WSP', "/^[ \r\n\t]+/");
        $this->skip('WSP');
    }
}

$lexer = new ArithLexer();
```

There's more to specifying mathematical expression than would seem,
because there are two concepts to consider:

1. Operator precedence
2. Operator associativity

The operator problem is usually solved in these steps:

1. Create a hierarchy of your operators.
2. Start creating rules from the lowest-precedence one to the
   highest-precedence one, each "level" will reference rules
   in the one above it.
3. The highest operator will reference an atomic, nondividable
   expression, which in our case is an `INT` or a parenthesised
   expression.

The lowest-precedence operator in our grammar is `+`, so we will start
with two rules for `Additive`:

```php
$this('Additive')
    ->is('Additive', '+', 'Multiplicative')
    ->call(function ($l, $_, $r) {
        return $l + $r;
    })

    ->is('Multiplicative');
```

Here we say "an additive expression is an additive expression plus a
multiplicative expression, or simply a multiplicative expression.
Note that we've taken care of associativity too: the first rule for
`Additive` is left-recursive, which means that an input like this:

    2 + 7 + 3

will be interpreted as

    (2 + 7) + 3

which is exactly what we want to achieve.

Let's take care of `Multiplicative` the same way:

```php
$this('Multiplicative')
    ->is('Multiplicative', '*', 'Power')
    ->call(function ($l, $_, $r) {
        return $l * $r;
    })

    ->is('Power');
```

Again, we'll do the same for `Power`, but notice that we've made it
right-recursive, since when we say

    2 ** 3 ** 4

we want it to mean

    2 ** (3 ** 4)

```php
$this('Power')
    ->is('Primary', '**', 'Power')
    ->call(function ($l, $_, $r) {
        return pow($left, $right);
    })

    ->is('Primary');
```

We've reached the highest-precedence operator, so now we have to define
what a `Primary` expression is:

```php
$this('Primary')
    ->is('(', 'Additive', ')')
    ->call(function ($_, $e, $_) {
        return $e;
    })

    ->is('INT')
    ->call(function ($int) {
        return (int)$int->getValue();
    });
```

Note that the callback for the last rule recieves a token, that is, a
`Dissect\Lexer\Token` object, so we have to "unwrap" the value from it.

Now we just specify a start rule:

```php
$this->start('Additive');
```

and parse away:

```php
use Dissect\Parser\LALR1\Parser;

$parser = new Parser(new ArithGrammar());
$stream = $lexer->lex('6 ** (1 + 1) ** 2 * (5 + 4)');
echo $parser->parse($stream);
// => 11664
```

### Describing common syntactic structures

To see how to describe commonly used syntactic structures such as
repetitions and lists, see the [dedicated documentation section][common].

Invalid input
-------------

When the parser encounters a syntactical error, it stops dead and
throws a `Dissect\Parser\Exception\UnexpectedTokenException`.
The exception gives you programmatic access to information about the
problem: `getToken()` returns a `Dissect\Lexer\Token` representing the
invalid token and `getExpected()` returns an array of token types the parser
expected to encounter.

Precomputing the parse table
----------------------------

The parser needs a *parse table* to decide what to do based on given
input. That parse table is created from the grammar and, if we give the
parser only the grammar, needs to be computed every time we instantiate
the parser.

Grammar analysis is costly; if you need the speed, a far better choice
would be to precompute the table beforehand (perhaps as a part of your
build process) like this:

```php
use Dissect\Parser\LALR1\Analysis\Analyzer;

$analyzer = new Analyzer();
$parseTable = $analyzer->analyze($grammar)->getParseTable();
```

Now that we've got the parse table, we can dump it to a string which
we then save to a file. To do this, we can use either
`Dissect\Parser\LALR1\Dumper\ProductionTableDumper`:

```php
$dumper = new ProductionTableDumper();
$php = $dumper->dump($parseTable);
```

which produces very compact, whitespace-free and absolutely unreadable
code, or `Dissect\Parser\LALR1\Dumper\DebugTableDumper`:

```php
$dumper = new DebugTableDumper($grammar);
$php = $dumper->dump($parseTable);
```

which produces indented, readable representation with comments
explaining each step the parser takes when processing the input.

### Using the dumped parse table

To use the dumped parse table, just write

```php
$parser = new Parser($grammar, require $parseTableFile);
```

You still need to pass the grammar, since it contains the callbacks
used to evalute the input.

> If you intend to use Dissect more like a traditional parser generator,
> you don't actually need to do any of this, of course. Dissect provides a
> command-line interface you can use to process and debug your grammars.
> It's described in its own [documentation section][cli].

Resolving conflicts
-------------------

*Caution, this is advanced stuff. You probably won't ever need to worry
about this.*

LALR(1) is generally a very poweful parsing algorithm. However, there
are practical grammars that are, unfortunately, almost-but-not-quite
LALR(1). When running an LALR(1) analyzer on such grammars, one sees
that they contain 2 types of conflicts:

- **Shift/Reduce conflicts** - the parser doesn't know whether to shift
  another token or reduce what's on the stack.

- **Reduce/Reduce conflicts** - the parser can reduce by multiple
  grammar rules.

There are 3 commonly used ways of resolving such conflicts and Dissect allows you to
combine them any way you want:

1. On a shift/reduce conflict, always shift. This is represented by
   the constant `Grammar::SHIFT` and is so common that Dissect enables
   it by default.

2. On a reduce/reduce conflict, reduce using the longer rule.
   Represented by `Grammar::LONGER_REDUCE`. Both this and the previous
   way represent the same philosophy: take the largest bite possible.
   This is usually what the user intended to express.

3. On a reduce/reduce conflict, reduce using the rule that was
   declared earlier in the grammar. Represented by
   `Grammar::EARLIER_REDUCE`.

To specify precisely how should Dissect resolve parse table conflicts,
call `resolve` on your grammar:

```php
$this->resolve(Grammar::SHIFT | Grammar::LONGER_REDUCE);
```

There are two other constants: `Grammar::NONE` that forbids any
conflicts in the grammar and `Grammar::ALL`, which is a combination
of all the 3 above methods defined simply for convenience.

[twigparser]: https://github.com/fabpot/Twig/blob/master/lib/Twig/Parser.php
[twig]: https://github.com/fabpot/Twig
[annotationsparser]: https://github.com/doctrine/common/blob/master/lib/Doctrine/Common/Annotations/DocParser.php
[dqlparser]: https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/Query/Parser.php
[doctrine]: https://github.com/doctrine
[rdparser]: http://en.wikipedia.org/wiki/Recursive_descent_parser
[llk]: http://en.wikipedia.org/wiki/LL_parser
[lrk]: http://en.wikipedia.org/wiki/LR_parser
[cli]: cli.md
[common]: common.md
