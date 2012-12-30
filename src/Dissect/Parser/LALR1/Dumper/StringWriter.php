<?php

namespace Dissect\Parser\LALR1\Dumper;

/**
 * A string writer.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class StringWriter
{
    protected $indent = 0;
    protected $string = '';

    /**
     * Appends the given string.
     *
     * @param string $string The string to write.
     */
    public function write($string)
    {
        $this->string .= $string;
    }

    /**
     * Gets the string as written so far.
     *
     * @return string The string.
     */
    public function get()
    {
        return $this->string;
    }

    /**
     * Adds a level of indentation.
     */
    public function indent()
    {
        $this->indent++;
    }

    /**
     * Removes a level of indentation.
     */
    public function outdent()
    {
        $this->indent--;
    }

    /**
     * If a string is given, it writes
     * it with correct indentation and
     * a newline appended. When no string
     * is given, it adheres to the rule
     * that empty lines should be whitespace-free
     * (like vim) and doesn't append any
     * indentation.
     *
     * @param string $string The string to write.
     */
    public function writeLine($string = null)
    {
        if ($string) {
            $this->write(sprintf(
                "%s%s\n",
                str_repeat(' ', $this->indent * 4),
                $string
            ));
        } else {
            $this->write("\n");
        }
    }
}
