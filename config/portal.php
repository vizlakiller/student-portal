<?php

/*
|--------------------------------------------------------------------------
| Portal branding
|--------------------------------------------------------------------------
|
| Change these in your .env file for each client, for example:
|   PORTAL_NAME="Student Portal"
|   INSTITUTION_NAME="Kolej Teknologi Melaka"
|
*/

return [

    // Shown in the sidebar, the login page and browser tab titles.
    'name' => env('PORTAL_NAME', 'Student Portal'),

    // Shown at the top of printed transcripts.
    'institution' => env('INSTITUTION_NAME', 'Demo College'),

];
