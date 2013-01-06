Building an AST
===============

Often, when parsing a language that's more complex than
[mathematical expressions][prev], you will want to represent
the input as an *abstract syntax tree*, or AST (for a real-life
example, see [Twig][twig-ast] or [Gherkin][gherkin-ast]).

Getting the AST of the input with Dissect is nothing special; the
callbacks in your grammar can return anything, so they might as well
return AST nodes. Dissect however helps you by providing a simple base
class for the different node types: `Dissect\Node\CommonNode`.

Let's say we want to create an AST for the mathematical expressions from
the previous chapter. Since the input can consist of binary operations
and integers, let's create a subclass for each case:

```php
use Dissect\Node\CommonNode;
use Dissect\Node\Node;

class BinaryExpressionNode extends CommonNode
{
    const PLUS = 1;
    const TIMES = 2;
    const POWER = 3;

    public function __construct(Node $left, $op, Node $right)
    {
        parent::__construct(['operator' => $op], [
            'left' => $left,
            'right' => $right,
        ]);
    }

    public function getLeft()
    {
        return $this->getNode('left');
    }

    public function getRight()
    {
        return $this->getNode('right');
    }

    public function getOperator()
    {
        return $this->getAttribute('operator');
    }
}

class IntNode extends CommonNode
{
    public function __construct($value)
    {
        parent::__construct(['value' => $value]);
    }

    public function getValue()
    {
        return $this->getAttribute('value');
    }
}
```

The original constructor has two parameters, an array of child nodes and
an array of node attributes. `Dissect\Node\Node` is an interface
describing common operations for an AST node.

We can now easily modify the original grammar to build the AST:

```php
$this('Additive')
    ->is('Additive', '+', 'Multiplicative')
    ->call(function ($l, $_, $r) {
        return new BinaryExpressionNode($l, BinaryExpressionNode::PLUS, $r);
    })

    ->is('Multiplicative');

$this('Multiplicative')
    ->is('Multiplicative', '*', 'Power')
    ->call(function ($l, $_, $r) {
        return new BinaryExpressionNode($l, BinaryExpressionNode::TIMES, $r);
    })

    ->is('Power');

$this('Power')
    ->is('Primary', '**', 'Power')
    ->call(function ($l, $_, $r) {
        return new BinaryExpressionNode($l, BinaryExpressionNode::POWER, $r);
    })

    ->is('Primary');

$this('Primary')
    ->is('(', 'Additive', ')')
    ->call(function ($_, $e, $_) {
        return $e;
    })

    ->is('INT')
    ->call(function ($int) {
        return new IntNode((int)$int->getValue());
    });
```

Traversing the AST
------------------

When we have the AST of our input, we want to interpret it somehow.
The most common way to do this is to create a *node visitor* (sometimes
called a *tree walker*). A trivial node visitor for our example could be
the following recursive function:

```php
function visit(Node $node)
{
    if ($node instanceof BinaryExpressionNode) {
        switch ($node->getOperator()) {
            case BinaryExpressionNode::PLUS:
                return visit($node->getLeft()) + visit($node->getRight());
            case BinaryExpressionNode::TIMES:
                return visit($node->getLeft()) * visit($node->getRight());
            case BinaryExpressionNode::POWER:
                return pow(visit($node->getLeft()), visit($node->getRight());
        }
    } elseif ($node instanceof IntNode) {
        return $node->getValue();
    } else {
        throw new \Exception("Unknown node type.");
    }
}

echo visit($parser->parse(...));
```

[prev]: parsing.md#example-parsing-mathematical-expressions
[twig-ast]: https://github.com/fabpot/Twig/tree/master/lib/Twig/Node
[gherkin-ast]: https://github.com/Behat/Gherkin/tree/master/src/Behat/Gherkin/Node
