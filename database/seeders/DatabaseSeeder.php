<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Safe to run more than once: existing records are kept, not duplicated.
     * Every demo account's password is "password" (students: their student number).
     */
    public function run(): void
    {
        $accounts = [
            ['admin@example.com',      'System Administrator',         'super_admin', 'ADM001'],
            ['adminstaff@example.com', 'Nurul Huda binti Ahmad',       'admin_staff', 'ADM014'],
            ['hod@example.com',        'Dr. Rahim bin Abdullah',       'hod',         'ACD002'],
            ['registrar@example.com',  'Puan Zarina binti Hashim',     'registrar',   'REG003'],
            ['accountant@example.com', 'Encik Hakim bin Razak',        'accountant',  'FIN004'],
            ['teacher@example.com',    'Encik Faizal bin Omar',        'teacher',     'LEC101'],
            ['teacher2@example.com',   'Puan Kavitha a/p Raman',       'teacher',     'LEC102'],
            ['teacher3@example.com',   'Mr. Tan Boon Huat',            'teacher',     'LEC103'],
        ];

        foreach ($accounts as $i => [$email, $name, $role, $staffNo]) {
            User::firstOrCreate(['email' => $email], [
                'name'     => $name,
                'password' => 'password',
                'role'     => $role,
                'staff_no' => $staffNo,
                'phone'    => sprintf('03-%04d %04d', 8800 + $i, 1200 + $i * 7),
            ]);
        }

        $this->call([
            ProgrammeSeeder::class,
            SubjectSeeder::class,
        ]);

        // Which teacher teaches which subjects (only fills subjects without a teacher).
        $teaching = [
            'teacher@example.com'  => ['CSC1013', 'CSC1043', 'WEB2013', 'SEN2023', 'PRJ2044'],
            'teacher2@example.com' => ['MAT1023', 'DBS1053', 'NET1063'],
            'teacher3@example.com' => ['ITC1033', 'ENG1012', 'MPU2032'],
        ];

        foreach ($teaching as $email => $codes) {
            $teacherId = User::where('email', $email)->value('id');
            Subject::whereIn('code', $codes)->whereNull('teacher_id')->update(['teacher_id' => $teacherId]);
        }

        $this->call([
            StudentSeeder::class,
            FinanceSeeder::class,
            MarkChangeSeeder::class,
        ]);
    }
}
