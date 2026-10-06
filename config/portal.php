<?php

/*
|--------------------------------------------------------------------------
| Portal defaults
|--------------------------------------------------------------------------
|
| These are the starting values. The super admin can change the names,
| logo, colours and font from the Branding page; whatever they save there
| replaces the values below.
|
*/

return [

    // Shown in the sidebar, the login page and browser tab titles.
    'name' => env('PORTAL_NAME', 'Student Portal'),

    // Shown at the top of transcripts and receipts.
    'institution' => env('INSTITUTION_NAME', 'Demo College'),

    // Shown in front of amounts on fee pages and receipts.
    'currency' => env('PORTAL_CURRENCY', 'RM'),

    'colors' => [
        'sidebar' => '#16332d',   // sidebar background and headings
        'primary' => '#1d5c50',   // buttons and links
        'accent'  => '#b07d2b',   // logo ring, CGPA seal and focus outline
    ],

    // Fonts the super admin can choose from: display name => Google Fonts family.
    'fonts' => [
        'Atkinson Hyperlegible Next' => 'Atkinson+Hyperlegible+Next',
        'Inter'                      => 'Inter',
        'Plus Jakarta Sans'          => 'Plus+Jakarta+Sans',
        'Source Sans 3'              => 'Source+Sans+3',
        'Nunito Sans'                => 'Nunito+Sans',
        'IBM Plex Sans'              => 'IBM+Plex+Sans',
        'Lexend'                     => 'Lexend',
        'Poppins'                    => 'Poppins',
    ],

    'font' => 'Atkinson Hyperlegible Next',

];
