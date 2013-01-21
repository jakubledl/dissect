<?php

namespace Dissect\Parser\LALR1\Analysis\KernelSet;

use PHPUnit_Framework_TestCase;

class KernelSetTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function kernelsShouldBeProperlyHashedAndOrdered()
    {
        $this->assertEquals(array(1, 3, 6, 7), KernelSet::hashKernel(array(
            array(2, 1),
            array(1, 0),
            array(2, 0),
            array(3, 0),
        )));
    }

    /**
     * @test
     */
    public function insertShouldInsertANewNodeIfNoIdenticalKernelExists()
    {
        $set = new KernelSet();

        $this->assertEquals(0, $set->insert(array(
            array(2, 1),
        )));

        $this->assertEquals(1, $set->insert(array(
            array(2, 2),
        )));

        $this->assertEquals(2, $set->insert(array(
            array(1, 1),
        )));

        $this->assertEquals(0, $set->insert(array(
            array(2, 1),
        )));
    }
}
