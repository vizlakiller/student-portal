<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a role to each user: "admin" can manage staff accounts,
     * "staff" can manage students, programmes, subjects and results.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('staff')->after('password');
        });

        // The first account ever created (usually yours) becomes the admin.
        $firstUserId = DB::table('users')->min('id');

        if ($firstUserId) {
            DB::table('users')->where('id', $firstUserId)->update(['role' => 'admin']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
