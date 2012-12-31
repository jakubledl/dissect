<?php

namespace Dissect\Parser\LALR1;

use Dissect\Parser\Grammar;

class ArithGrammar extends Grammar
{
    public function __construct()
    {
        $this('Additive')
            ->is('Additive', '+', 'Multiplicative')
            ->call(function ($l, $_, $r) {
                return $l + $r;
            })

            ->is('Multiplicative');

        $this('Multiplicative')
            ->is('Multiplicative', '*', 'Power')
            ->call(function ($l, $_, $r) {
                return $l * $r;
            })

            ->is('Power');

        $this('Power')
            ->is('Primary', '**', 'Power')
            ->call(function ($l, $_, $r) {
                return pow($l, $r);
            })

            ->is('Primary');

        $this('Primary')
            ->is('INT')
            ->call(function ($i) {
                return (int)$i->getValue();
            })

            ->is('(', 'Additive', ')')
            ->call(function ($_, $e, $_) {
                return $e;
            });

        $this->start('Additive');
    }
}
