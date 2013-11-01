<?php

namespace Dissect\Parser\LALR1;

use Dissect\Parser\Grammar;

class ArithGrammar extends Grammar
{
    public function __construct()
    {
        $this('Expr')
            ->is('Expr', '+', 'Expr')
            ->call(function ($l, $_, $r) {
                return $l + $r;
            })

            ->is('Expr', '-', 'Expr')
            ->call(function ($l, $_, $r) {
                return $l - $r;
            })

            ->is('Expr', '*', 'Expr')
            ->call(function ($l, $_, $r) {
                return $l * $r;
            })

            ->is('Expr', '/', 'Expr')
            ->call(function ($l, $_, $r) {
                return $l / $r;
            })

            ->is('Expr', '**', 'Expr')
            ->call(function ($l, $_, $r) {
                return pow($l, $r);
            })

            ->is('(', 'Expr', ')')
            ->call(function ($_, $e, $_) {
                return $e;
            })

            ->is('-', 'Expr')->prec(4)
            ->call(function ($_, $e) {
                return -$e;
            })

            ->is('INT')
            ->call(function ($i) {
                return (int)$i->getValue();
            });

        $this->operators('+', '-')->left()->prec(1);
        $this->operators('*', '/')->left()->prec(2);
        $this->operators('**')->right()->prec(3);

        $this->start('Expr');
    }
}
