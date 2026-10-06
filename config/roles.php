<?php

/*
|--------------------------------------------------------------------------
| Roles and permissions
|--------------------------------------------------------------------------
|
| 'roles' lists every role and the name shown on screen.
|
| 'permissions' says which roles may do what. The super admin can always do
| everything, so it is not listed. To change what a role can do for a client,
| add or remove the role here. Pages, menus and buttons all follow this file.
|
| Some permissions are also limited by department: a head of department can
| only manage subjects, sessions, timetable and mark changes in the
| departments they head (see AppServiceProvider).
|
*/

return [

    'roles' => [
        'super_admin' => 'Super admin',
        'management'  => 'Management (Director / COO / CEO)',
        'admin_staff' => 'Admin staff',
        'hod'         => 'Head of department',
        'registrar'   => 'Registrar',
        'lecturer'    => 'Lecturer',
        'accountant'  => 'Accountant',
        'student'     => 'Student',
    ],

    'permissions' => [

        // Overview
        'view-statistics'        => ['management'],

        // Students
        'view-students'          => ['management', 'admin_staff', 'hod', 'registrar', 'accountant'],
        'create-students'        => ['registrar'],
        'edit-student-contact'   => ['admin_staff', 'registrar'],   // name, email, phone, address...
        'edit-student-enrolment' => ['registrar'],                  // student no., programme, semester, status
        'delete-students'        => [],                             // super admin only
        'reset-student-password' => ['registrar'],

        // Results
        'view-results'           => ['management', 'hod', 'registrar'],
        'register-subjects'      => ['registrar'],                  // put a student into a subject session
        'request-mark-changes'   => ['hod'],                        // own departments; the session lecturer must approve
        'view-mark-changes'      => ['hod', 'lecturer'],
        'edit-marks-directly'    => [],                             // super admin only
        'teach'                  => ['lecturer'],                   // "My sessions", entering marks, "My timetable"

        // Lecturers, subjects, programmes and departments
        'view-lecturers'         => ['management', 'admin_staff', 'hod', 'registrar'],
        'view-subjects'          => ['management', 'admin_staff', 'hod', 'registrar'],
        'manage-subjects'        => ['hod'],                        // own departments
        'assign-lecturers'       => ['hod'],                        // own departments
        'view-programmes'        => ['management', 'hod', 'registrar'],
        'manage-programmes'      => ['registrar'],
        'view-departments'       => ['management', 'admin_staff', 'hod', 'registrar'],
        'manage-departments'     => [],                             // super admin only

        // Timetable
        'view-timetable'         => ['management', 'admin_staff', 'hod', 'registrar', 'lecturer'],
        'manage-timetable'       => ['hod'],                        // sessions and class times, own departments
        'manage-classrooms'      => ['admin_staff'],
        'manage-terms'           => [],                             // super admin only

        // Fees and payments
        'view-finance'           => ['management', 'accountant'],
        'manage-finance'         => ['accountant'],                 // bill, record payments, receipts
        'delete-payments'        => [],                             // super admin only

        // Students' own pages
        'view-own-results'       => ['student'],

        // System
        'manage-users'           => [],                             // super admin only
        'manage-branding'        => [],                             // super admin only
    ],

    // Abilities the super admin does NOT get automatically (they belong to one person's own work).
    'not_for_super_admin' => ['teach', 'view-own-results'],

];
