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
*/

return [

    'roles' => [
        'super_admin' => 'Super admin',
        'admin_staff' => 'Admin staff',
        'hod'         => 'Head of department',
        'registrar'   => 'Registrar',
        'teacher'     => 'Teacher',
        'accountant'  => 'Accountant',
        'student'     => 'Student',
    ],

    'permissions' => [

        // Students
        'view-students'          => ['admin_staff', 'hod', 'registrar', 'accountant'],
        'create-students'        => ['registrar'],
        'edit-student-contact'   => ['admin_staff', 'registrar'],   // name, email, phone, address...
        'edit-student-enrolment' => ['registrar'],                  // student no., programme, semester, status
        'delete-students'        => [],                             // super admin only
        'reset-student-password' => ['registrar'],

        // Results
        'view-results'           => ['hod', 'registrar'],
        'register-subjects'      => ['registrar'],                  // put a student into a subject
        'request-mark-changes'   => ['hod'],                        // needs the subject teacher's approval
        'view-mark-changes'      => ['hod', 'teacher'],
        'edit-marks-directly'    => [],                             // super admin only
        'teach'                  => ['teacher'],                    // "My subjects" and entering marks

        // Teachers, subjects and programmes
        'view-teachers'          => ['admin_staff', 'hod', 'registrar'],
        'view-subjects'          => ['admin_staff', 'hod', 'registrar'],
        'manage-subjects'        => ['hod'],
        'assign-teachers'        => ['hod'],
        'view-programmes'        => ['hod', 'registrar'],
        'manage-programmes'      => ['registrar'],

        // Fees and payments
        'manage-finance'         => ['accountant'],
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
