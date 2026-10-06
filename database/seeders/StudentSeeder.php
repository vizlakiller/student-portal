<?php

namespace Database\Seeders;

use App\Models\Programme;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Which subjects are taken in which semester (by subject code).
     */
    private const SEMESTER_SUBJECTS = [
        1 => ['CSC1013', 'MAT1023', 'ITC1033', 'ENG1012'],
        2 => ['CSC1043', 'DBS1053', 'NET1063'],
        3 => ['WEB2013', 'SEN2023', 'MPU2032'],
        4 => ['PRJ2044'],
    ];

    public function run(): void
    {
        // Fixed seed: the same "random" marks every time you run the seeder.
        mt_srand(2026);

        $programmes = Programme::pluck('id', 'code');
        $subjects = Subject::pluck('id', 'code');
        $towns = ['Melaka', 'Putrajaya', 'Shah Alam', 'Kota Kinabalu', 'Johor Bahru', 'Seremban', 'Ipoh', 'Kuching'];

        // [name, gender, programme, intake year, current semester, status]
        $students = [
            ['Ahmad Faiz bin Hassan', 'Male', 'DCS', 2024, 4, 'Active'],
            ['Nur Aisyah binti Rahman', 'Female', 'DCS', 2024, 4, 'Active'],
            ['Tan Wei Ming', 'Male', 'DCS', 2024, 4, 'Active'],
            ['Priya a/p Ramasamy', 'Female', 'DCS', 2025, 2, 'Active'],
            ['Muhammad Hafiz bin Ismail', 'Male', 'DCS', 2025, 2, 'Active'],
            ['Lim Mei Ling', 'Female', 'DCS', 2025, 2, 'Active'],
            ['Siti Nurhaliza binti Abdullah', 'Female', 'DCS', 2026, 1, 'Active'],
            ['Arjun a/l Subramaniam', 'Male', 'DCS', 2023, 5, 'Graduated'],
            ['Nurul Izzah binti Kamal', 'Female', 'DIT', 2024, 4, 'Active'],
            ['Wong Jun Hao', 'Male', 'DIT', 2024, 4, 'Active'],
            ['Amirul Hakim bin Zainal', 'Male', 'DIT', 2024, 3, 'Deferred'],
            ['Kavitha a/p Muniandy', 'Female', 'DIT', 2025, 2, 'Active'],
            ['Chong Kar Wai', 'Male', 'DIT', 2025, 2, 'Active'],
            ['Farah Hanim binti Yusof', 'Female', 'DIT', 2026, 1, 'Active'],
            ['Mohd Danial bin Roslan', 'Male', 'DIT', 2023, 5, 'Graduated'],
            ['Ng Siew Ling', 'Female', 'DIT', 2025, 2, 'Withdrawn'],
            ['Haziq Iskandar bin Azman', 'Male', 'DEE', 2024, 4, 'Active'],
            ['Aina Sofea binti Mazlan', 'Female', 'DEE', 2024, 4, 'Active'],
            ['Rajesh a/l Krishnan', 'Male', 'DEE', 2025, 2, 'Active'],
            ['Lee Chee Keong', 'Male', 'DEE', 2025, 2, 'Active'],
            ['Nur Syafiqah binti Hamzah', 'Female', 'DEE', 2026, 1, 'Active'],
            ['Goh Yi Xuan', 'Female', 'DEE', 2023, 5, 'Graduated'],
            ['Izzat Hakimi bin Salleh', 'Male', 'BCS', 2024, 4, 'Active'],
            ['Deepa a/p Selvaraj', 'Female', 'BCS', 2024, 4, 'Active'],
            ['Ong Zhi Wei', 'Male', 'BCS', 2024, 3, 'Active'],
            ['Nur Batrisyia binti Faizal', 'Female', 'BCS', 2025, 2, 'Active'],
            ['Syed Ammar bin Syed Ali', 'Male', 'BCS', 2025, 2, 'Active'],
            ['Teoh Hui Min', 'Female', 'BCS', 2026, 1, 'Active'],
            ['Vinod a/l Ganesan', 'Male', 'BCS', 2026, 1, 'Active'],
            ['Alya Natasha binti Rosli', 'Female', 'BCS', 2025, 2, 'Deferred'],
            ['Khairul Anwar bin Jamil', 'Male', 'CBM', 2025, 2, 'Active'],
            ['Yap Pei Shan', 'Female', 'CBM', 2025, 2, 'Active'],
            ['Nadia Sabrina binti Omar', 'Female', 'CBM', 2026, 1, 'Active'],
            ['Thinesh a/l Rajan', 'Male', 'CBM', 2026, 1, 'Active'],
            ['Liew Jia Hui', 'Female', 'CBM', 2024, 3, 'Active'],
            ['Aiman Haikal bin Nordin', 'Male', 'CBM', 2024, 3, 'Active'],
        ];

        foreach ($students as $i => [$name, $gender, $code, $intake, $semester, $status]) {
            $seq = str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $email = strtolower($code.$intake.$seq).'@student.example.com';

            $student = Student::firstOrCreate(
                ['student_no' => $code.$intake.$seq],
                [
                    'name' => $name,
                    'email' => $email,
                    'phone' => sprintf('01%d-%07d', mt_rand(1, 9), mt_rand(1000000, 9999999)),
                    'gender' => $gender,
                    'date_of_birth' => sprintf('%d-%02d-%02d', $intake - 19, mt_rand(1, 12), mt_rand(1, 28)),
                    'address' => sprintf('No. %d, Jalan Mawar %d, Taman Mawar, %s', mt_rand(1, 99), mt_rand(1, 12), $towns[$i % count($towns)]),
                    'programme_id' => $programmes[$code],
                    'intake_year' => $intake,
                    'semester' => $semester,
                    'status' => $status,
                ],
            );

            // The student's login: their email, first password = student number.
            if (! $student->user_id) {
                $user = User::firstOrCreate(['email' => $student->email], [
                    'name'                 => $student->name,
                    'password'             => $student->student_no,
                    'role'                 => 'student',
                    'must_change_password' => true,
                ]);
                $student->user()->associate($user)->save();
            }

            // Marks for every semester already completed. Active students are
            // also registered for this semester's subjects, waiting for marks.
            $ability = mt_rand(50, 85);   // each student has a typical level

            foreach (self::SEMESTER_SUBJECTS as $sem => $codes) {
                $current = $sem === $semester && $status === 'Active';

                if ($sem > $semester || ($sem === $semester && ! $current)) {
                    break;
                }

                foreach ($codes as $subjectCode) {
                    $marks = max(30, min(98, $ability + mt_rand(-15, 15)));   // always drawn, keeps the sequence stable

                    $student->results()->firstOrCreate(
                        ['subject_id' => $subjects[$subjectCode]],
                        ['semester' => $sem, 'marks' => $current ? null : $marks],
                    );
                }
            }
        }
    }
}
