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

As for the grammar, let's start out slow, with only a single operator:

```php
$this('Expr')
    ->is('Expr', '+', 'Expr')
    ->call(function ($l, $_, $r) {
        return $l + $r;
    })

    ->is('INT')
    ->call(function ($i) {
        return (int)$i->getValue();
    });

$this->start('Expr');
```

These two rule specify an expression to be either two expression
separated by a plus or simply an integer. The call to `start()`
sets the starting rule of the grammar.

Now, we can simply pass the grammar to a parser object:

```php
use Dissect\Parser\LALR1\Parser;

$parser = new Parser(new ArithGrammar());
$stream = $lexer->lex('1 + 2 + 3');
echo $parser->parse($stream);
// => 6
```

and yay, it works!

### Operator associativity

Actually, it doesn't. It *seems* to work because addition happens to be
commutative, but a problem appears once we add another rule to the
grammar to represent subtraction:

```php
$this('Expr')
    ->is('Expr', '+', 'Expr') ...

    ->is('Expr', '-', 'Expr')
    ->call(function ($l, $_, $r) {
        return $l - $r;
    })

    ->is('INT') ...
```

The result looks like this:

```php
$stream = $lexer->lex('3 - 5 - 2');
echo $parser->parse($stream);
// => 0
```

Well, that's certainly incorrect. The problem is that our grammar
actually contains a conflict (a *shift/reduce* conflict, if you're a fan
of termini technici. See the [section on conflict resolution](#resolving-conflicts).)
which Dissect automatically resolves in a way that makes our `+` and `-`
operators right-associative. The problem is fortunately easy to solve:
we have to mark them as left-associative operators:

```php
    ->is('INT') ...

$this->operators('+', '-')->left();
```

This makes Dissect treat the two tokens in a special way, the conflict
is resolved to represent left-associativity and the parser works correctly:

```php
$stream = $lexer->lex('3 - 5 - 2');
echo $parser->parse($stream);
// => -4
```

### Operator precedence

Unfortunately, we're not out of the woods yet. When we add another two
rules to represent multiplication and division, we see that the parser
still makes mistakes:

```php
$this('Expr')
    ...

    ->is('Expr', '*', 'Expr')
    ->call(function ($l, $_, $r) {
        return $l * $r;
    })

    ->is('Expr', '/', 'Expr')
    ->call(function ($l, $_, $r) {
        return $l / $r;
    })

    ...

    $this->operators('*', '/')->left();
...

$stream = $lexer->lex('2 + 3 * 5');
echo $parser->parse($stream);
// => 25
```

The problem is that Dissect doesn't know anything about the precedence
of our operators. But we can, of course, provide the necessary information:

```php
$this->operators('+', '-')->left()->prec(1);
$this->operators('*', '/')->left()->prec(2);

...

$stream = $lexer->lex('2 + 3 * 5');
echo $parser->parse($stream);
// => 17
```

The higher the integer passed to the `prec()` method, the higher the
precedence of the specified operators.

And we have the basic grammar for mathematical expressions in place!
As an exercise, try to handle the rest of the tokens defined in the lexer:

- Create a rule to handle parentheses around expressions.
- Create a rule for the final operator, `**`, which represents
  exponentiation. Give it the highest precedence and make it
  *right-associative* (the method is, shockingly, called `right()`).

### Specifying precedences on rules instead of operators

As a final touch, we'd like to add a unary minus operator to our grammar:

```php
$this('Expr')
    ...

    ->is('-', 'Expr')
    ->call(function ($_, $e) {
        return -$e;
    })
    ...
```

But you might feel that something is amiss. Unary minus should have the
highest precedence, but we've specified the precedence of `-` to be the
lowest, actually. But don't worry, we can assign precedences directly to
rules:

```php
$this('Expr')
    ...

    ->is('-', 'Expr')->prec(4) // higher than everything
    ->call(function ($_, $e) {
        return -$e;
    })
    ...
```

### Nonassociativity

Apart from being left- or right-associative, operators can be
nonassociative, which means that for an operator `op`, the input
`a op b op c` means neither `(a op b) op c` or `a op (b op c)`,
but is considered a syntax error.

This has certain use cases; for instance, one of the nonassociative
operators in the grammar for PHP is `<`: when parsing `1 < 2 < 3`,
the PHP parser reports a syntax error.

The corresponding method in Dissect grammars is `nonassoc()`:

```php
$this->operators('<', '>')->nonassoc()->prec(...);
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

There are 4 commonly used ways of resolving such conflicts and Dissect allows you to
combine them any way you want:

1. On a shift/reduce conflict, consult the operators precedence
   and associativity information. The rules for resolution are a little
   complicated, but the conflict may be resolved as a reduce (either the
   precedence of the rule is higher than that of the shifted token or the
   token is left-associative), a shift (the rule precedence is lower or the
   token is right-associative) or even as an error (when the token is
   nonassociative). Note that Dissect doesn't report conflicts resolved
   using this technique, since they were intentionally created by the user
   and therefore are not really conflicts. Represented by the
   constant `Grammar::OPERATORS`.

2. On a shift/reduce conflict, always shift. This is represented by
   the constant `Grammar::SHIFT` and, together with the above method,
   is enabled by default.

3. On a reduce/reduce conflict, reduce using the longer rule.
   Represented by `Grammar::LONGER_REDUCE`. Both this and the previous
   way represent the same philosophy: take the largest bite possible.
   This is usually what the user intended to express.

4. On a reduce/reduce conflict, reduce using the rule that was
   declared earlier in the grammar. Represented by
   `Grammar::EARLIER_REDUCE`.

To specify precisely how should Dissect resolve parse table conflicts,
call `resolve` on your grammar:

```php
$this->resolve(Grammar::SHIFT | Grammar::OPERATORS | Grammar::LONGER_REDUCE);
```

There are two other constants: `Grammar::NONE` that forbids any
conflicts in the grammar (even the operators-related ones) and
`Grammar::ALL`, which is a combination of all the 4 above methods
defined simply for convenience.

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
