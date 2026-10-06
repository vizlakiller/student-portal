<?php

/*
|--------------------------------------------------------------------------
| Grading scale
|--------------------------------------------------------------------------
|
| Each row is: minimum marks => [grade, grade point].
| Rows must stay ordered from the highest minimum to the lowest.
| Change this table to match your institution's scale; every page,
| GPA and CGPA in the system uses it automatically.
|
*/

return [

    'scale' => [
        80 => ['A', 4.00],
        75 => ['A-', 3.67],
        70 => ['B+', 3.33],
        65 => ['B', 3.00],
        60 => ['B-', 2.67],
        55 => ['C+', 2.33],
        50 => ['C', 2.00],
        45 => ['C-', 1.67],
        40 => ['D+', 1.33],
        35 => ['D', 1.00],
        0 => ['F', 0.00],
    ],

    // Lowest grade point that still counts as a pass.
    'pass_point' => 2.00,

];
