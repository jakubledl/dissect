<?php

namespace Dissect\Parser\LALR1\Analysis;

use Dissect\Parser\Grammar;
use Dissect\Parser\Parser;
use Dissect\Util\Util;

/**
 * Calculates the follow sets of the grammar's extended rules.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class FollowSetsCalculator
{
    /**
     * @var \Dissect\Parser\LALR1\Analysis\ExtendedRule[]
     */
    protected $rules;

    /**
     * @var string[]
     */
    protected $nonterminals;

    /**
     * Constructor.
     *
     * @param \Dissect\Parser\LALR1\Analysis\ExtendedRule[] $rules The rules.
     * @param string[] $nonterminals A set of nonterminal symbols.
     */
    public function __construct(array $rules, array $nonterminals)
    {
        $this->rules = $rules;
        $this->nonterminals = $nonterminals;
    }

    public function calculateFollowSets()
    {
        $firstSets = $this->calculateFirstSets();
        $followSets = array();

        foreach ($this->rules as $rule) {
            if ($rule->getOriginalName() === Grammar::START_RULE_NAME) {
                // EOF is automatically added to FOLLOW for all rules
                // that originate from the start rule
                $followSets[$rule->getName()] = array(Parser::EOF_TOKEN_TYPE);
            } else {
                $followSets[$rule->getName()] = array();
            }
        }

        do {
            $changes = false;

            foreach ($this->rules as $rule) {
                $components = $rule->getComponents();

                foreach ($components as $i => $component) {
                    if (in_array($component, $this->nonterminals)) {
                        // only calculate FOLLOW for nonterminal symbols

                        $new = array();

                        if ($i === count($components) - 1) {
                            // we're at the end of a rule.
                            // for rule like A -> B C D, add
                            // everything from FOLLOW(A) to FOLLOW(D)
                            $new = $followSets[$rule->getName()];
                        } else {
                            // start from the next component
                            $index = $i + 1;

                            while (true) {
                                $examinedComponent = $components[$index];

                                if (!in_array($examinedComponent, $this->nonterminals)) {
                                    // a terminal symbol. add it to
                                    // current FOLLOW set
                                    $new = Util::union($new, array($examinedComponent));

                                    break;
                                } else {
                                    // for A -> B C D at B, add
                                    // everything from FIRST(C) to
                                    // FOLLOW(B)
                                    $temp = $firstSets[$examinedComponent];

                                    if (in_array(Grammar::EPSILON, $temp)) {
                                        // we need to move forward
                                        // through the components as
                                        // long as we're hitting epsilon
                                        unset($temp[array_search(Grammar::EPSILON, $temp)]);

                                        if ($index === count($components) - 1) {
                                            // at the end. merge
                                            // together $new, $temp and
                                            // FOLLOW(this rule)

                                            $new = Util::union($new, $temp,
                                                $followSets[$rule->getName()]);

                                            break;
                                        } else {
                                            // merge $temp into $new and
                                            // continue
                                            $new = Util::union($new, $temp);
                                            $index++;
                                        }
                                    } else {
                                        // no epsilon, no worries
                                        $new = Util::union($new, $temp);

                                        break;
                                    }
                                }
                            }
                        }

                        if (Util::different($new, $followSets[$component])) {
                            $followSets[$component] = Util::union(
                                $followSets[$component],
                                $new
                            );

                            $changes = true;
                        }
                    }
                }
            }
        } while ($changes);

        return $followSets;
    }

    protected function calculateFirstSets()
    {
        $firstSets = array();

        // initialize the FIRST sets
        foreach ($this->rules as $rule) {
            $firstSets[$rule->getName()] = array();
        }

        do {
            $changes = false;

            foreach ($this->rules as $rule) {
                $components = $rule->getComponents();
                $name = $rule->getName();
                $new = array();

                if (empty($components)) {
                    // epsilon rule
                    $new = array(Grammar::EPSILON);
                } else {
                    foreach ($components as $i => $component) {
                        if (in_array($component, $this->nonterminals)) {
                            // if nonterminal, copy its first set to
                            // this rule's first set
                            $x = $firstSets[$component];

                            if (!in_array(Grammar::EPSILON, $x)) {
                                // if the component doesn't derive
                                // epsilon, merge the first sets and
                                // we're done
                                $new = Util::union($new, $x);

                                break;
                            } else {
                                // if all components derive epsilon,
                                // the rule itself derives epsilon

                                if ($i < (count($components) - 1)) {
                                    // more components ahead, remove epsilon
                                    unset($x[array_search(Grammar::EPSILON, $x)]);
                                }

                                $new = Util::union($new, $x);
                            }
                        } else {
                            // if terminal, simply add it to the first set
                            $new = Util::union($new, array($component));
                        }
                    }
                }

                if (Util::different($new, $firstSets[$name])) {
                    $firstSets[$name] = Util::union($firstSets[$name], $new);

                    $changes = true;
                }
            }
        } while ($changes);

        return $firstSets;
    }
}
