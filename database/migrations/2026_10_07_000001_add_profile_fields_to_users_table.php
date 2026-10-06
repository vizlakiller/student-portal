<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Version 2 roles: super_admin, admin_staff, hod, registrar, teacher,
     * accountant and student (see config/roles.php).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_no', 20)->nullable()->after('email');
            $table->string('phone', 20)->nullable()->after('staff_no');
            $table->boolean('must_change_password')->default(false)->after('role');
        });

        // Old roles from version 1
        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
        DB::table('users')->where('role', 'staff')->update(['role' => 'registrar']);
    }

    public function down(): void
    {
        // Version 1 has no student logins, so they are removed (never turned into staff).
        DB::table('users')->where('role', 'student')->delete();
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
        DB::table('users')->where('role', '!=', 'admin')->update(['role' => 'staff']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['staff_no', 'phone', 'must_change_password']);
        });
    }
};
