Lexical analysis with Dissect
=============================

There are two classes for lexical analysis in Dissect, both under the
namespace `Dissect\Lexer`: `SimpleLexer` and `StatefulLexer`.

SimpleLexer
-----------

`SimpleLexer` simply accepts some token definitions and applies them on
the input. Let's create a new instance for this chapter:

```php
use Dissect\Lexer\SimpleLexer;

$lexer = new SimpleLexer();
```

### Defining tokens

There are 3 ways to define a token. The simplest one looks like this:

```php
$lexer->token('+');
```

This definition will simply match a plus symbol, using `+` both as the
name and value of the token. You can use 2 arguments:

```php
$lexer->token('CLASS', 'class');
```

if you want the token name (first argument) to differ from what will actually be
recognized (second argument).

The final way defines a token by a regular expression:

```php
$lexer->regex('INT', '/[1-9][0-9]*/');
```

Let's now define some tokens we will use in the next chapter:

```php
$lexer->regex('INT', '/[1-9][0-9]*/');
$lexer->token('(');
$lexer->token(')');
$lexer->token('+');
$lexer->token('*');
$lexer->token('**');
```

> **Tip**: You can also chain the method calls using a fluent interface.

### Skipping tokens

Some tokens have to be recognized, but we don't want them cluttering the
output. The best example are probably whitespace tokens: the lexer has
to recognize them, but they carry no meaning or value, so we can tell
the lexer to `skip` them:

```php
$lexer->regex('WSP', "/[ \r\n\t]+/");
$lexer->skip('WSP');
```

> You can pass any number of token names to the `skip` method.

### Lexing

Now that we've defined our tokens, we can simply call:

```php
$stream = $lexer->lex($input);
```

The return value is an object implementing the
`Dissect\Lexer\TokenStream\TokenStream` interface. The interface defines
several methods you can use to inspect and move through the token
stream. See [TokenStream.php][tokenstream] for all the methods you can
use.

> If you `count` the token stream, you may be surprised to find out that
> for input like `5 + 3`, it actually contains 4 tokens. That's because,
> as the last step of lexing, a special token called `$eof` is appended
> to mark the end of input. This is crucial to the parsing process, so
> please, never define a token called `$eof` yourself. It could lead to
> some pretty strange errors. Another forbidden token names are `$start`
> and `$epsilon`.

StatefulLexer
-------------

`SimpleLexer` should work fine for general use cases. However, let's
imagine we're lexing a very simple templating language:

    Outer content, {{ variable_name }}, other outer content

`SimpleLexer` falls short here, because the outer content can be pretty
much anything, while the content inside the tags has to be strictly
intepreted. Furthermore, if we were to work with this template, we'd
want to skip the whitespace inside tags, but keep it in the outer
content.

That's where `StatefulLexer` comes in; during lexing, it maintains a
stack of states with the top one being the current one, and for each
token, you can define the action the lexer should take after recognizing
it. Let's see an example for our templating language:

```php
use Dissect\Lexer\StatefulLexer;

$lexer = new StatefulLexer();

$lexer->state('outside')
    ->regex('CONTENT', '/[^"{{"]+/')
    ->token('{{')->action('tag');

$lexer->state('tag')
    ->regex('WSP', "/[ \r\n\t]+/")
    ->regex('VAR', '/[a-zA-Z_]+/')
    ->token('}}')->action(StatefulLexer::POP_STATE)
    ->skip('WSP');

$lexer->start('outside');
```

Please note that before defining any tokens, we have to define a state.
For the tokens that cause the state transition, we call `action` to
specify what should the lexer do. The action can be either a string, in
which case the lexer goes to the state specified by the string, or
`StatefulLexer::POP_STATE`, which causes the lexer to pop the current
state of the stack, essentialy going back to previous state.
Finally, we tell the lexer in which state to start by calling `start`.

Continue
--------

Now that we've demonstrated how to perform lexical analysis with
Dissect, we can move onto syntactical analysis, commonly known as
[parsing][parsing].

[tokenstream]: https://github.com/jakubledl/dissect/blob/master/src/Dissect/Lexer/TokenStream/TokenStream.php
[parsing]: parsing.md
