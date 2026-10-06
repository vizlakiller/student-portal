<?php

namespace Database\Seeders;

use App\Models\Programme;
use Illuminate\Database\Seeder;

class ProgrammeSeeder extends Seeder
{
    public function run(): void
    {
        $programmes = [
            ['DCS', 'Diploma in Computer Science', 'Diploma'],
            ['DIT', 'Diploma in Information Technology', 'Diploma'],
            ['DEE', 'Diploma in Electronic Engineering', 'Diploma'],
            ['BCS', 'Bachelor of Computer Science (Software Engineering)', 'Bachelor'],
            ['CBM', 'Certificate in Business Management', 'Certificate'],
        ];

        foreach ($programmes as [$code, $name, $level]) {
            Programme::firstOrCreate(['code' => $code], ['name' => $name, 'level' => $level]);
        }
    }
}
