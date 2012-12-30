<?php

namespace Dissect\Parser\LALR1\Dumper;

use Dissect\Parser\LALR1\Analysis\Analyzer;
use PHPUnit_Framework_TestCase;

class ProductionTableDumperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function theWrittenTableShouldBeAsCompactAsPossible()
    {
        $grammar = new ExampleGrammar();
        $analyzer = new Analyzer();
        $table = $analyzer->analyze($grammar)->getParseTable();

        $dumper = new ProductionTableDumper();
        $dumped = $dumper->dump($table);

        $this->assertStringEqualsFile(__DIR__ . '/res/table/production.php', $dumped);
    }
}
