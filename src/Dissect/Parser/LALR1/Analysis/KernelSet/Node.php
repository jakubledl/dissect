<?php

namespace Dissect\Parser\LALR1\Analysis\KernelSet;

class Node
{
    public $kernel;
    public $number;

    public $left = null;
    public $right = null;

    public function __construct(array $hashedKernel, $number)
    {
        $this->kernel = $hashedKernel;
        $this->number = $number;
    }
}
