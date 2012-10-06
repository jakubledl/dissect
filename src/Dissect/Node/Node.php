<?php

namespace Dissect\Node;

use Countable;
use IteratorAggregate;

/**
 * A basic contract for a node in an AST.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
interface Node extends Countable, IteratorAggregate
{
    /**
     * Returns the children of this node.
     *
     * @return array The children belonging to this node.
     */
    public function getNodes();

    /**
     * Checks for existence of child node named $name.
     *
     * @param string $name The name of the child node.
     *
     * @return boolean If the node exists.
     */
    public function hasNode($name);

    /**
     * Returns a child node specified by $name.
     *
     * @param int|string $name The name of the node.
     *
     * @return \Dissect\Node\Node The child node specified by $name.
     *
     * @throws \RuntimeException When no child node named $name exists.
     */
    public function getNode($name);

    /**
     * Sets a child node.
     *
     * @param string $name The name.
     * @param \Dissect\Node\Node $node The new child node.
     */
    public function setNode($name, Node $child);

    /**
     * Removes a child node by name.
     *
     * @param string $name The name.
     */
    public function removeNode($name);

    /**
     * Returns all attributes of this node.
     *
     * @return array The attributes.
     */
    public function getAttributes();

    /**
     * Determines whether this node has an attribute
     * under $key.
     *
     * @param string $key The key.
     * @return boolean Whether there's an attribute under $key.
     */
    public function hasAttribute($key);

    /**
     * Gets an attribute by key.
     *
     * @param string $key The key.
     * @return mixed The attribute value.
     *
     * @throws \RuntimeException When no attribute exists under $key.
     */
    public function getAttribute($key);

    /**
     * Sets an attribute by key.
     *
     * @param string $key The key.
     * @param mixed $value The new value.
     */
    public function setAttribute($key, $value);

    /**
     * Removes an attribute by key.
     *
     * @param string $key The key.
     */
    public function removeAttribute($key);
}
