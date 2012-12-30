<?php

namespace Dissect\Parser\LALR1\Dumper;

/**
 * A common contract for parse table dumpers.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
interface TableDumper
{
    /**
     * Dumps the parse table.
     *
     * @param array $table The parse table.
     *
     * @return string The resulting string representation of the table.
     */
    public function dump(array $table);
}
