Parsing with Dissect
====================

Few thoughts before we start
----------------------------

Parsing is a task that's needed more often than one would think;
for examples in some famous PHP projects, see [this parser][twigparser]
from [Twig][twig] and [these][annotationsparser] [two][dqlparser] from
[Doctrine][doctrine]. Chances are you've written one; if you did, it was
most likely a [recursive descent parser][rdparser], just like the
examples above. Now, such parsers have several disadvantages: first,
they have to be manually written. That's a lot of code to write.
Second, they're *recursive*,
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

A grammar is represented by an instance of `Dissect\Parser\Grammar`.
There are two methods of interest here: `rule`, which is used to add new
rules to the grammar like this:

```php
$grammar->rule('Sum', ['int', '+', 'int']);
// corresponds to BNF rule Sum -> int + int
```

and `start`, which is used to set the starting rule for the grammar:

```php
$grammar->start('Sum');
```

The `rule` method returns an instance of `Dissect\Parser\Rule`, which
has another important method, `call`, which is used to set the callback
used to evalute the whole rule:

```php
$grammar->rule('Sum', ['int', '+', 'int'])
    ->call(function ($left, $plus, $right) {
        return $left + $right;
    });
```

Example: Parsing mathematical expressions
-----------------------------------------

In the chapter on lexing, we've created a lexer we will now use to
process our expressions:

```php
$lexer = new SimpleLexer();

$lexer->regex('INT', '/[1-9][0-9]*/');
$lexer->token('(');
$lexer->token(')');
$lexer->token('+');
$lexer->token('*');
$lexer->token('**'); // power operator

$lexer->regex('WSP', "/[ \r\n\t]+/");
$lexer->skip('WSP');
```

Even though mathematical expressions seem trivial, defining them in a
grammar is not, because we have to consider two things:

1. Operator precedence
2. Operator associativity

The operator problem is usually solved in these steps:

1. Create a hierarchy of your operators.
2. Start creating rules from the lowest-precedence one to the
   highest-precedence one.
3. The highest operator will reference an atomic, nondividable
   expression, which in our case is an `INT` or a parenthesised
   expression.

The lowest-precedence operator in our grammar is `+`, so we will start
with two rules for `Additive`:

```php
$grammar->rule('Additive', ['Additive', '+', 'Multiplicative'])
    ->call(function ($left, $plus, $right) {
        return $left + $right;
    });
$grammar->rule('Additive', ['Multiplicative']);
```

Note that we've taken care of associativity too: the first rule for
`Additive` is left-recursive, which means left associativity.

Let's take care of `Multiplicative` the same way:

```php
$grammar->rule('Multiplicative', ['Multiplicative', '*', 'Power'])
    ->call(function ($left, $times, $right) {
        return $left * $right;
    });
$grammar->rule('Multiplicative', ['Power']);
```

Again, we'll do the same for `Power`, but notice that we've made it
right-recursive, since we want our power operator to be right-associative.

```php
$grammar->rule('Power', ['Primary', '**', 'Power'])
    ->call(function ($left, $pow, $right) {
        return pow($left, $right);
    });
$grammar->rule('Power', ['Primary']);
```

We've reached the highest-precedence operator, so now we have to define
what is a `Primary` expression:

```php
$grammar->rule('Primary', ['(', 'Additive', ')']) // we loop back to additive
    ->call(function ($l, $expr, $r) {
        return $expr;
    });
$grammar->rule('Primary', ['INT'])
    ->call(function ($value) {
        return (int)$value;
    });
```

Now we only have to specify a starting rule:

```php
$grammar->start('Additive');
```

and parse away:

```php
use Dissect\Parser\LALR1\LALR1Parser;

$parser = new LALR1Parser($grammar);
$stream = $lexer->lex('6 ** (1 + 1) ** 2 * (5 + 4)');
echo $parser->parse($stream);
// => 11664
```

Invalid input
-------------

When the parser encounters a syntactical error, it stops dead and
throws a `Dissect\Parser\Exception\UnexpectedTokenException`.
The exception gives you programmatic access to information about the
problem: `getToken` returns a `Dissect\Lexer\Token` representing the
invalid token and `getExpected` returns an array of token types the parser
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
$parseTable = $analyzer->createParseTable($grammar);
```

The table is just a regular PHP array, so it can be serialized,
`var_export`ed or whatever you need to do to save it to a file.

You can then pass the table to the parser as the second constructor
argument. The grammar still needs to be passed, since it contains the
callbacks used to evaluate the rules.

Resolving conflicts
-------------------

(*Caution, this is advanced stuff. You probably won't ever need to worry
about this.*)

LALR(1) is generally quite a powerful parsing algorithm. However, there
are practical grammars that are, unfortunately, almost-but-not-quite
LALR(1). When running the LALR(1) analyzer on such grammars, one sees
that they contain 2 types of conflicts:

- **Shift/Reduce conflicts** - the parser doesn't know whether to shift
  another token or reduce what's on the stack.

- **Reduce/Reduce conflicts** - the parser can reduce by multiple
  grammar rules.

There are 3 ways of resolving such conflicts and Dissect allows you to
combine them any way you want:

1. On a shift/reduce conflict, always shift. This is represented by
   the constant `Grammar::SR_BY_SHIFT` and is such a
   common way of resolving conflicts that it's enabled by default.

2. On a reduce/reduce conflict, reduce using the longer rule.
   Represented by `Grammar::RR_BY_LONGER_RULE`.

3. On a reduce/reduce conflict, reduce using the rule that was
   declared earlier in the grammar. Represented by
   `Grammar::RR_BY_EARLIER_RULE`.

To specify how precisely should Dissect resolve parse table conflicts,
call `resolve` on your grammar:

```php
$grammar->resolve(Grammar::SR_BY_SHIFT | Grammar::RR_BY_LONGER_RULE);
```

There are two other constants: `Grammar::NONE` that forbids any
conflicts in the grammar (useful when you want to see the shift/reduce
conflicts of your grammar) and `Grammar::ALL`, which is a combination
of all the 3 above methods defined simply for convenience.

[twigparser]: https://github.com/fabpot/Twig/blob/master/lib/Twig/Parser.php
[twig]: https://github.com/fabpot/Twig
[annotationsparser]: https://github.com/doctrine/common/blob/master/lib/Doctrine/Common/Annotations/DocParser.php
[dqlparser]: https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/Query/Parser.php
[doctrine]: https://github.com/doctrine
[rdparser]: http://en.wikipedia.org/wiki/Recursive_descent_parser
[llk]: http://en.wikipedia.org/wiki/LL_parser
[lrk]: http://en.wikipedia.org/wiki/LR_parser
