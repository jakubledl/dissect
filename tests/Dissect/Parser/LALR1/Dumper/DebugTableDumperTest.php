<?php

namespace Dissect\Parser\LALR1\Dumper;

use Dissect\Parser\LALR1\Analysis\Analyzer;
use PHPUnit_Framework_TestCase;

class DebugTableDumperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itDumpsAHumanReadableParseTableWithExplainingComments()
    {
        $grammar = new ExampleGrammar();
        $analyzer = new Analyzer();
        $result = $analyzer->analyze($grammar);

        $dumper = new DebugTableDumper($grammar);
        $dumped = $dumper->dump($result->getParseTable());

        $this->assertStringEqualsFile(__DIR__ . '/res/table/debug.php', $dumped);
    }
}
