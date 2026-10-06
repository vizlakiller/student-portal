<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Programme;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Departments, their heads, and which programmes and subjects belong to them.
 * One head leads two departments; the Department of Business has no head yet.
 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $hod = User::where('email', 'hod@example.com')->value('id');
        $hod2 = User::where('email', 'hod2@example.com')->value('id');

        $departments = [
            ['DCOMP', 'Department of Computing', $hod,
                ['DCS', 'DIT', 'BCS'], ['CSC1013', 'ITC1033', 'CSC1043', 'DBS1053', 'NET1063', 'WEB2013', 'SEN2023', 'PRJ2044']],
            ['DENG', 'Department of Engineering', $hod2, ['DEE'], []],
            ['DGEN', 'Centre for General Studies', $hod2, [], ['MAT1023', 'ENG1012', 'MPU2032']],
            ['DBUS', 'Department of Business', null, ['CBM'], []],
        ];

        foreach ($departments as [$code, $name, $hodId, $programmes, $subjects]) {
            $department = Department::firstOrCreate(['code' => $code], ['name' => $name, 'hod_id' => $hodId]);

            // Only fill in programmes and subjects that don't have a department yet
            Programme::whereIn('code', $programmes)->whereNull('department_id')->update(['department_id' => $department->id]);
            Subject::whereIn('code', $subjects)->whereNull('department_id')->update(['department_id' => $department->id]);
        }
    }
}
