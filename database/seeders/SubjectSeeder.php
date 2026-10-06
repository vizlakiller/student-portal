<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['CSC1013', 'Programming Fundamentals', 3],
            ['MAT1023', 'Discrete Mathematics', 3],
            ['ITC1033', 'Introduction to Computing', 3],
            ['ENG1012', 'English for Communication', 2],
            ['CSC1043', 'Object-Oriented Programming', 3],
            ['DBS1053', 'Database Systems', 3],
            ['NET1063', 'Computer Networks', 3],
            ['WEB2013', 'Web Application Development', 3],
            ['SEN2023', 'Software Engineering', 3],
            ['MPU2032', 'Entrepreneurship', 2],
            ['PRJ2044', 'Final Year Project', 4],
        ];

        foreach ($subjects as [$code, $name, $credits]) {
            Subject::firstOrCreate(['code' => $code], ['name' => $name, 'credit_hours' => $credits]);
        }
    }
}
