<?php

namespace Dissect\Parser\LALR1\Dumper;

/**
 * A table dumper for production
 * environment - the dumped table
 * is compact, whitespace-free and
 * without any comments.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class ProductionTableDumper implements TableDumper
{
    /**
     * {@inheritDoc}
     */
    public function dump(array $table)
    {
        $writer = new StringWriter();

        $this->writeIntro($writer);

        foreach ($table['action'] as $num => $state) {
            $this->writeState($writer, $num, $state);
            $writer->write(',');
        }

        $this->writeMiddle($writer);

        foreach($table['goto'] as $num => $map) {
            $this->writeGoto($writer, $num, $map);
            $writer->write(',');
        }

        $this->writeOutro($writer);

        $writer->write("\n"); // eof newline

        return $writer->get();
    }

    protected function writeIntro(StringWriter $writer)
    {
        $writer->write("<?php return array('action'=>array(");
    }

    protected function writeState(StringWriter $writer, $num, $state)
    {
        $writer->write((string)$num . '=>array(');

        foreach ($state as $trigger => $action) {
            $this->writeAction($writer, $trigger, $action);
            $writer->write(',');
        }

        $writer->write(')');
    }

    protected function writeAction(StringWriter $writer, $trigger, $action)
    {
        $writer->write(sprintf(
            "'%s'=>%d",
            $trigger,
            $action
        ));
    }

    protected function writeMiddle(StringWriter $writer)
    {
        $writer->write("),'goto'=>array(");
    }

    protected function writeGoto(StringWriter $writer, $num, $map)
    {
        $writer->write((string)$num . '=>array(');

        foreach ($map as $trigger => $destination) {
            $writer->write(sprintf(
                "'%s'=>%d",
                $trigger,
                $destination
            ));

            $writer->write(',');
        }

        $writer->write(')');
    }

    protected function writeOutro(StringWriter $writer)
    {
        $writer->write('));');
    }
}
