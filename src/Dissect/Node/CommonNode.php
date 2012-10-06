<?php

namespace Dissect\Node;

use RuntimeException;

/**
 * An AST node.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class CommonNode implements Node
{
    /**
     * @var array
     */
    protected $nodes;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * Constructor.
     *
     * @param array $attributes The attributes of this node.
     * @param array $children The children of this node.
     */
    public function __construct(array $attributes = array(), array $nodes = array())
    {
        $this->attributes = $attributes;
        $this->nodes = $nodes;
    }

    /**
     * {@inheritDoc}
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * {@inheritDoc}
     */
    public function hasNode($key)
    {
        return isset($this->nodes[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function getNode($key)
    {
        if (!isset($this->children[$key])) {
            throw new RuntimeException(sprintf('No child node "%s" exists.', $key));
        }

        return $this->nodes[$key];
    }

    /**
     * {@inheritDoc}
     */
    public function setNode($key, Node $child)
    {
        $this->children[$key] = $child;
    }

    /**
     * {@inheritDoc}
     */
    public function removeNode($key)
    {
        unset($this->children[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAttribute($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($key)
    {
        if (!isset($this->attributes[$key])) {
            throw new RuntimeException(sprintf('No attribute "%s" exists.', $key));
        }

        return $this->attributes[$key];
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function removeAttribute($key)
    {
        unset($this->attributes[$key]);
    }

    public function count()
    {
        return count($this->children);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->children);
    }
}
