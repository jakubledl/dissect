<?php

namespace Dissect\Parser\LALR1\Dumper;

use Dissect\Parser\Grammar;

class ExampleGrammar extends Grammar
{
    public function __construct()
    {
        $this('S')
            ->is('a', 'S', 'b')
            ->is(/* empty */);

        $this->start('S');
    }
}
