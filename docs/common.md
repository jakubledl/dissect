Describing common syntactic structures
======================================

This chapter of the documentation shows how to implement common
grammar patterns like lists & repetitions in a way that's most efficient
for a LALR(1) parser like Dissect.

List of 1 or more `Foo`s
------------------------

```php
$this('Foo+')
    ->is('Foo+', 'Foo')
    ->call(function ($list, $foo) {
        $list[] = $foo;

        return $list;
    })

    ->is('Foo')
    ->call(function ($foo) {
        return [$foo];
    });
```

With some practice, it's very easy to see how this works: when the
parser recognizes the first `Foo`, it reduces it to a single-item array
and for each following `Foo`, it just pushes it onto the array.

Note that `Foo+` is just a rule name, it could be equally well called
`Foos`, `ListOfFoo` or anything else you feel like.

List of 0 or more `Foo`s
------------------------

```php
$this('Foo*')
    ->is('Foo*', 'Foo')
    ->call(function ($list, $foo) {
        $list[] = $foo;

        return $list;
    })

    ->is(/* empty */)
    ->call(function () {
        return [];
    });
```

This works pretty much the same like the previous example, the only
difference being that we allow `Foo*` to match nothing.

A comma separated list
----------------------

The first example of this chapter is trivial to modify to include
commas between the `Foo`s. Just change the second line to:

```php
$this('Foo+')
    ->is('Foo+', ',', 'Foo')
...
```

The second example, however, cannot be modified so easily. We cannot
just put a comma in the first alternative:

```php
$this('Foo*')
    ->is('Foo*', ',', 'Foo')
...
```

since that would allow the list to start with a comma:

    , Foo , Foo , Foo

Instead, we say that a "list of zero or more `Foo`s
separated by commas" is actually "a list of one or more `Foo`s separated
by commas or nothing at all". So our rule now becomes:

```php
$this('Foo*')
    ->is('Foo+')

    ->is(/* empty */)
    ->call(function () {
        return [];
    });

$this('Foo+')
    ->is('Foo+', ',', 'Foo')
...
```

A note on left recursion
------------------------

One of the principal advantages of LR parsers over alternatives like LL
or recursive descent is the ability to handle left-recursive rules,
which are a natural expression of many grammar patterns. However, not
only do LR parsers handle left recursion, they actually work *better*
with left-recursive rules than with right-recursive ones in terms of
memory, since a left-recursive rule can be recognized using a constant
amount of memory, whereas for right-recursive rules, the amount of
memory required grows lineary with each round of recursion.

You may have noticed that all the examples above use left recursion for
two reasons: efficiency and naturalness (you read arrays from left to
right, not the other way around, right?).

In short, when you *can* comfortably express your rule using left recursion,
*do* so.

Expressions
-----------

A grammar for very basic mathematical expressions is described in the
[chapter on parsing][arith]. It would require some modifications to allow
for other operators, function calls, ternary operator(s), but there's a
lot of grammars for practical programming languages on the internet that
you can take inspiration from.

For a familiar (although slighty less readable) example, take a look
at [this grammar][php-grammar] for PHP itself.

[php-grammar]: https://github.com/php/php-src/blob/master/Zend/zend_language_parser.y
[arith]: parsing.md#example-parsing-mathematical-expressions
