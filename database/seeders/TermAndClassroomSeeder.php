<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Term;
use Illuminate\Database\Seeder;

class TermAndClassroomSeeder extends Seeder
{
    public function run(): void
    {
        // Two terms on a fresh install. An upgraded system keeps the term it already has.
        if (! Term::exists()) {
            Term::create(['name' => 'March 2026', 'starts_on' => '2026-03-02', 'ends_on' => '2026-07-17']);
            Term::create(['name' => 'September 2026', 'starts_on' => '2026-09-07', 'ends_on' => '2027-01-22'])->makeCurrent();
        } elseif (! Term::current()) {
            Term::latest('id')->first()->makeCurrent();
        }

        $rooms = [
            ['BK1-101', 'Lecture Room 1', 'Blok Kuliah 1', 'Lecture room', 60],
            ['BK1-102', 'Lecture Room 2', 'Blok Kuliah 1', 'Lecture room', 40],
            ['BK2-110', 'Tutorial Room 110', 'Blok Kuliah 2', 'Tutorial room', 25],
            ['MK-201', 'Computer Lab A', 'Blok Makmal', 'Computer lab', 35],
            ['MK-202', 'Computer Lab B', 'Blok Makmal', 'Computer lab', 35],
            ['EL-01', 'Electronics Lab', 'Blok Kejuruteraan', 'Science lab', 30],
        ];

        foreach ($rooms as [$code, $name, $building, $type, $capacity]) {
            Classroom::firstOrCreate(['code' => $code], compact('name', 'building', 'type', 'capacity'));
        }
    }
}
