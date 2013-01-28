Lexical analysis with Dissect
=============================

There are three classes for lexical analysis in Dissect, all under the
namespace `Dissect\Lexer`: `SimpleLexer`, `StatefulLexer` and `RegexLexer`.

SimpleLexer
-----------

`SimpleLexer` simply accepts some token definitions and applies them on
the input. Let's create a subclass for this chapter:

```php
use Dissect\Lexer\SimpleLexer;

class ArithLexer extends SimpleLexer
{
    public function __construct()
    {
        // token definitions
    }
}
```

### Defining tokens

There are 3 ways to define a token. The simplest one looks like this:

```php
$this->token('+');
```

This definition will simply match a plus symbol, using `+` both as the
name and value of the token. You can use 2 arguments:

```php
$this->token('CLASS', 'class');
```

if you want the token name (first argument) to differ from what will actually be
recognized (second argument).

The final way defines a token by a regular expression:

```php
$this->regex('INT', '/^[1-9][0-9]*/');
```

Let's now define some tokens we will use in the next chapter:

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
    }
}
```

> **Tip**: You can also chain the method calls using a fluent interface.

### Skipping tokens

Some tokens have to be recognized, but we don't want them cluttering the
output. The best example are probably whitespace tokens: the lexer has
to recognize them, but they carry no meaning or value, so we can tell
the lexer to `skip` them:

```php
class ArithLexer extends SimpleLexer
{
    public function __construct()
    {
        $this->regex('INT', '/[1-9][0-9]*/');
        $this->token('(');
        $this->token(')');
        $this->token('+');
        $this->token('*');
        $this->token('**');

        $this->regex('WSP', "/^[ \r\n\t]+/");
        $this->skip('WSP');
    }
}
```

> You can pass any number of token names to the `skip` method.

### Lexing

Now that we've defined our tokens, we can simply call:

```php
$lexer = new ArithLexer();
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

class TemplateLexer extends StatefulLexer
{
    public function __construct()
    {
        $lexer->state('outside')
            ->regex('CONTENT', '/^[^"{{"]*/')
            ->token('{{')->action('tag');

        $lexer->state('tag')
            ->regex('WSP', "/^[ \r\n\t]+/")
            ->regex('VAR', '/^[a-zA-Z_]+/')
            ->token('}}')->action(StatefulLexer::POP_STATE)
            ->skip('WSP');

        $lexer->start('outside');
    }
}
```

Please note that before defining any tokens, we have to define a state.
For the tokens that cause the state transition, we call `action` to
specify what should the lexer do. The action can be either a string, in
which case the lexer goes to the state specified by the string, or
`StatefulLexer::POP_STATE`, which causes the lexer to pop the current
state of the stack, essentialy going back to previous state.
Finally, we tell the lexer in which state to start by calling `start`.

Improving lexer performance
---------------------------

There's one important trick to improve the performance of your lexers.
The documentation uses it implicitly, but it requires an explicit mention:

When using one of the lexer classes documented above and defining tokens
using regular expressions, *always* anchor the regex at the beginning
using `^` like this:

```php
$this->regex('INT', '/^[1-9][0-9]*/');
```

This little optimization will lead to substantial performance gains on
any but the shortest input strings, since without anchoring, the PCRE
engine would always look for matches throughout the entire remaining
input string, which would be incredibly wasteful for long inputs.

RegexLexer
----------

When designing the lexer classes, my goal was not to sacrifice
user-friendliness for performance. However, I'm well aware that there
are use cases that require the highest performace possible. That's
why I adapted the highly performant but slightly less user-friendly
[lexer][doctrinelexer] from [doctrine][doctrine] into Dissect.

The usage is almost identical to the original class, writing a lexer
for the arithmetic expressions could look something like this:

```php
use Dissect\Lexer\RegexLexer;
use RuntimeException;

class ArithLexer extends RegexLexer
{
    protected $tokens = ['+', '*', '**', '(', ')'];

    protected function getCatchablePatterns()
    {
        return ['[1-9][0-9]*'];
    }

    protected function getNonCatchablePatterns()
    {
        return ['\s+'];
    }

    protected function getType(&$value)
    {
        if (is_numeric($value)) {
            $value = (int)$value;

            return 'INT';
        } elseif (in_array($value, $this->tokens)) {
            // the types of the simple tokens equal their values here
            return $value;
        } else {
            throw new RuntimeException(sprintf('Invalid token "%s"', $value));
        }
    }
}
```

Continue
--------

Now that we've demonstrated how to perform lexical analysis with
Dissect, we can move onto syntactical analysis, commonly known as
[parsing][parsing].

[tokenstream]: ../src/Dissect/Lexer/TokenStream/TokenStream.php
[parsing]: parsing.md
[doctrinelexer]: https://github.com/doctrine/lexer/blob/master/lib/Doctrine/Common/Lexer/AbstractLexer.php
[doctrine]: https://github.com/doctrine/lexer
