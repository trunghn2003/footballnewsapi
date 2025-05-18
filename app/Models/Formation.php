<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formation
{
    private static $formations = [
        '4-4-2' => [
            ['position' => 'GK', 'grid' => '1:1', 'group' => 'G'],
            ['position' => 'LB', 'grid' => '2:1', 'group' => 'D'],
            ['position' => 'CB1', 'grid' => '2:2', 'group' => 'D'],
            ['position' => 'CB2', 'grid' => '2:3', 'group' => 'D'],
            ['position' => 'RB', 'grid' => '2:4', 'group' => 'D'],
            ['position' => 'LM', 'grid' => '3:1', 'group' => 'M'],
            ['position' => 'CM1', 'grid' => '3:2', 'group' => 'M'],
            ['position' => 'CM2', 'grid' => '3:3', 'group' => 'M'],
            ['position' => 'RM', 'grid' => '3:4', 'group' => 'M'],
            ['position' => 'CF1', 'grid' => '4:1', 'group' => 'F'],
            ['position' => 'CF2', 'grid' => '4:2', 'group' => 'F']
        ],
        '4-3-3' => [
            ['position' => 'GK', 'grid' => '1:1', 'group' => 'G'],
            ['position' => 'LB', 'grid' => '2:1', 'group' => 'D'],
            ['position' => 'CB1', 'grid' => '2:2', 'group' => 'D'],
            ['position' => 'CB2', 'grid' => '2:3', 'group' => 'D'],
            ['position' => 'RB', 'grid' => '2:4', 'group' => 'D'],
            ['position' => 'CM1', 'grid' => '3:1', 'group' => 'M'],
            ['position' => 'CM2', 'grid' => '3:2', 'group' => 'M'],
            ['position' => 'CM3', 'grid' => '3:3', 'group' => 'M'],
            ['position' => 'LW', 'grid' => '4:1', 'group' => 'F'],
            ['position' => 'CF', 'grid' => '4:2', 'group' => 'F'],
            ['position' => 'RW', 'grid' => '4:3', 'group' => 'F']
        ],
        '3-5-2' => [
            ['position' => 'GK', 'grid' => '1:1', 'group' => 'G'],
            ['position' => 'CB1', 'grid' => '2:1', 'group' => 'D'],
            ['position' => 'CB2', 'grid' => '2:2', 'group' => 'D'],
            ['position' => 'CB3', 'grid' => '2:3', 'group' => 'D'],
            ['position' => 'LWB', 'grid' => '3:1', 'group' => 'D'],
            ['position' => 'CM1', 'grid' => '3:2', 'group' => 'M'],
            ['position' => 'CM2', 'grid' => '3:3', 'group' => 'M'],
            ['position' => 'CM3', 'grid' => '3:4', 'group' => 'M'],
            ['position' => 'RWB', 'grid' => '3:5', 'group' => 'D'],
            ['position' => 'CF1', 'grid' => '4:1', 'group' => 'F'],
            ['position' => 'CF2', 'grid' => '4:2', 'group' => 'F']
        ],
    ];
    public static function getFormation($formation)
    {
        return self::$formations[$formation] ?? null;
    }
}
