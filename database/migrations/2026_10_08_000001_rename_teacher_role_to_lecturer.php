<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Teacher" is now called "Lecturer".
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'teacher')->update(['role' => 'lecturer']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'lecturer')->update(['role' => 'teacher']);
        DB::table('users')->where('role', 'management')->update(['role' => 'admin_staff']);
    }
};
