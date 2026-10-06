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
            ['admin@example.com',      'System Administrator',                   'super_admin', 'ADM001'],
            ['director@example.com',   "Dato' Dr. Hamid bin Osman",              'management',  'MGT001'],
            ['adminstaff@example.com', 'Nurul Huda binti Ahmad',                 'admin_staff', 'ADM014'],
            ['hod@example.com',        'Dr. Rahim bin Abdullah',                 'hod',         'ACD002'],
            ['hod2@example.com',       'Prof. Madya Dr. Norazlina binti Yusof',  'hod',         'ACD005'],
            ['registrar@example.com',  'Puan Zarina binti Hashim',               'registrar',   'REG003'],
            ['accountant@example.com', 'Encik Hakim bin Razak',                  'accountant',  'FIN004'],
            ['lecturer@example.com',   'Encik Faizal bin Omar',                  'lecturer',    'LEC101'],
            ['lecturer2@example.com',  'Puan Kavitha a/p Raman',                 'lecturer',    'LEC102'],
            ['lecturer3@example.com',  'Mr. Tan Boon Huat',                      'lecturer',    'LEC103'],
            ['lecturer4@example.com',  'Dr. Lim Siew Mei',                       'lecturer',    'LEC104'],
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
            DepartmentSeeder::class,
        ]);

        // Who teaches which subject. Only fills subjects that have no lecturers yet.
        $teaching = [
            'CSC1013' => ['lecturer@example.com', 'lecturer4@example.com'],   // taught together
            'CSC1043' => ['lecturer@example.com', 'lecturer4@example.com'],   // one group each
            'WEB2013' => ['lecturer@example.com'],
            'SEN2023' => ['lecturer@example.com'],
            'PRJ2044' => ['lecturer@example.com'],
            'MAT1023' => ['lecturer2@example.com'],
            'DBS1053' => ['lecturer2@example.com'],
            'NET1063' => ['lecturer2@example.com'],
            'ITC1033' => ['lecturer3@example.com'],
            'ENG1012' => ['lecturer3@example.com'],
            'MPU2032' => ['lecturer3@example.com'],
        ];

        foreach ($teaching as $code => $emails) {
            $subject = Subject::where('code', $code)->first();

            if ($subject && ! $subject->lecturers()->exists()) {
                $subject->lecturers()->sync(User::whereIn('email', $emails)->pluck('id'));
            }
        }

        $this->call([
            TermAndClassroomSeeder::class,
            StudentSeeder::class,
            TimetableSeeder::class,
            FinanceSeeder::class,
            MarkChangeSeeder::class,
        ]);
    }
}
