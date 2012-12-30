<?php

return array(
    'action' => array(
        0 => array(
            // on a shift and go to state 2
            'a' => 2,

            // on $eof reduce by rule S -> /* empty */
            '$eof' => -2,

        ),

        1 => array(
            // on $eof accept the input
            '$eof' => 0,

        ),

        2 => array(
            // on a shift and go to state 2
            'a' => 2,

            // on b reduce by rule S -> /* empty */
            'b' => -2,

        ),

        3 => array(
            // on b shift and go to state 4
            'b' => 4,

        ),

        4 => array(
            // on $eof reduce by rule S -> a S b
            '$eof' => -1,

            // on b reduce by rule S -> a S b
            'b' => -1,

        ),

    ),

    'goto' => array(
        0 => array(
            // on S go to state 1
            'S' => 1,

        ),

        2 => array(
            // on S go to state 3
            'S' => 3,

        ),

    ),
);
