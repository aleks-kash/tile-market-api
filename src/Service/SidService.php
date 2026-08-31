<?php

namespace App\Service;

class SidService
{
    /**
     * Cache for lists of constants in all accessed SID classes.
     *
     * Dimension #0 is name of the class.
     *
     * Dimension #1 is numeric value of a SID constant.
     *
     * Value is a string value of a SID constant.
     *
     * @var string[][]
     */
    public static array $all = [];
}
