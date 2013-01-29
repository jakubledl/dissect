<?php

namespace Dissect\Console\Command;

use Dissect\Parser\LALR1\Analysis\Exception\ConflictException;
use Dissect\Parser\LALR1\Analysis\Analyzer;
use Dissect\Parser\LALR1\Dumper\AutomatonDumper;
use Dissect\Parser\LALR1\Dumper\DebugTableDumper;
use Dissect\Parser\LALR1\Dumper\ProductionTableDumper;
use Dissect\Parser\Grammar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ReflectionClass;

class DissectCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('dissect')
            ->addArgument('grammar-class', InputArgument::REQUIRED, 'The grammar class.')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Writes the parse table in the debug format.')
            ->addOption('dfa', 'D', InputOption::VALUE_NONE, 'Exports the LALR(1) DFA as a Graphviz graph.')
            ->addOption('state', 's', InputOption::VALUE_REQUIRED, 'Exports only the specified state instead of the entire DFA.')
            ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Overrides the default output directory.')
            ->setHelp(<<<EOT
Analyzes the given grammar and, if successful, exports the parse table to a PHP
file.

By default, the output directory is taken to be the one in which the grammar is
defined. You can change that with the <info>--output-dir</info> option:

 <info>--output-dir=../some/other/dir</info>

The parse table is by default written with minimal whitespace to make it compact.
If you wish to inspect the table manually, you can export it in a readable and
well-commented way with the <info>--debug</info> option.

If you wish to inspect the handle-finding automaton for your grammar (perhaps
to aid with grammar debugging), use the <info>--dfa</info> option. When in use, Dissect
will create a file with the automaton exported as a Graphviz graph
in the output directory.

Additionally, you can use the <info>--state</info> option to export only the specified
state and any relevant transitions:

 <info>--dfa --state=5</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = strtr(
            $input->getArgument('grammar-class'),
            '/',
            '\\'
        );
        $formatter = $this->getHelperSet()->get('formatter');

        $output->writeln('<info>Analyzing...</info>');
        $output->writeln('');

        if (!class_exists($class)) {
            $output->writeln(array(
                $formatter->formatBlock(
                    sprintf('The class "%s" could not be found.', $class),
                    'error',
                    true
                ),
            ));

            return 1;
        }

        $grammar = new $class();

        if ($dir = $input->getOption('output-dir')) {
            $cwd = rtrim(getcwd(), DIRECTORY_SEPARATOR);

            $outputDir = $cwd . DIRECTORY_SEPARATOR . $dir;
        } else {
            $refl = new ReflectionClass($class);
            $outputDir = pathinfo($refl->getFileName(), PATHINFO_DIRNAME);
        }

        $analyzer = new Analyzer();
        $automaton = null;

        try {
            $result = $analyzer->analyze($grammar);
            $conflicts = $result->getResolvedConflicts();
            $automaton = $result->getAutomaton();
            $table = $result->getParseTable();

            if ($conflicts) {
                foreach ($conflicts as $conflict) {
                    $output->writeln($this->formatConflict($conflict));
                }

                $output->writeln(sprintf(
                    "<info><comment>%d</comment> conflicts in total",
                    count($conflicts)
                ));

                $output->writeln('');
            }

            $output->writeln('<info>Writing the parse table...</info>');

            $fileName = $outputDir . DIRECTORY_SEPARATOR . 'parse_table.php';

            if ($input->getOption('debug')) {
                $tableDumper = new DebugTableDumper($grammar);
            } else {
                $tableDumper = new ProductionTableDumper();
            }

            $code = $tableDumper->dump($table);

            $ret = @file_put_contents($fileName, $code);
            if ($ret === false) {
                $output->writeln('<error>Error writing the parse table</error>');
            } else {
                $output->writeln('<info>Parse table written</info>');
            }
        } catch(ConflictException $e) {
            $output->writeln(array(
                $formatter->formatBlock(
                    explode("\n", $e->getMessage()),
                    'error',
                    true
                ),
            ));

            $automaton = $e->getAutomaton();
        }

        if ($input->getOption('dfa')) {
            $output->writeln('');

            $automatonDumper = new AutomatonDumper($automaton);

            if ($input->getOption('state') === null) {
                $output->writeln('<info>Exporting the DFA...</info>');

                $dot = $automatonDumper->dump();
                $file = 'automaton.dot';
            } else {
                $state = (int)$input->getOption('state');

                if (!$automaton->hasState($state)) {
                    $output->writeln(array(
                        $formatter->formatBlock(
                            sprintf('The automaton has no state #%d', $state),
                            'error',
                            true
                        ),
                    ));

                    return 1;
                }

                $output->writeln(sprintf(
                    '<info>Exporting the DFA state <comment>%d</comment>...',
                    $state
                ));

                $dot = $automatonDumper->dumpState($state);
                $file = sprintf('state_%d.dot', $state);
            }

            $fileName = $outputDir . DIRECTORY_SEPARATOR . $file;
            $ret = @file_put_contents($fileName, $dot);

            if ($ret === false) {
                $output->writeln('<error>Error writing to the file</error>');
            } else {
                $output->writeln('<info>Successfully exported</info>');
            }
        }

        return 0;
    }

    protected function formatConflict(array $conflict)
    {
        $type = $conflict['resolution'] === Grammar::SHIFT
            ? 'shift/reduce'
            : 'reduce/reduce';

        return sprintf(
            "<info>Resolved a <comment>%s</comment> conflict in state <comment>%d</comment> on lookahead <comment>%s</comment></info>",
            $type,
            $conflict['state'],
            $conflict['lookahead']
        );
    }
}
