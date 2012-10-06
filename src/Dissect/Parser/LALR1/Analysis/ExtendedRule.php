<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\Rule;

/**
 * A grammar rule extended with state transition informations.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class ExtendedRule extends Rule
{
    /**
     * @var string
     */
    protected $originalName;

    /**
     * @var int
     */
    protected $finalSetNumber;

    /**
     * @var int
     */
    protected $originalNumber;

    /**
     * Constructor.
     *
     * @param int $number The number of this rule in the extended grammar.
     * @param int $originalNumber The number of the original rule.
     * @param string $name The name of this extended rule.
     * @param string[] $components The components of this extended rule.
     * @param string $originalName The name of the original rule.
     * @param int $finalSetNumber The number of the final set.
     */
    public function __construct(
        $number,
        $originalNumber,
        $name,
        array $components,
        $originalName,
        $finalSetNumber
    )
    {
        $this->originalName = $originalName;
        $this->originalNumber = $originalNumber;
        $this->finalSetNumber = $finalSetNumber;

        parent::__construct($number, $name, $components);
    }

    /**
     * Returns the name of the original rule.
     *
     * @return string The name of the original rule.
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Returns the number of the original rule.
     *
     * @return int The number of the original rule.
     */
    public function getOriginalNumber()
    {
        return $this->originalNumber;
    }

    /**
     * Returns the number of the state reached by the final transition.
     *
     * @return int The state number.
     */
    public function getFinalSetNumber()
    {
        return $this->finalSetNumber;
    }
}
