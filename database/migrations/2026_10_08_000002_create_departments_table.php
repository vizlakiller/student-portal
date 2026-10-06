<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Departments. Each has one head (HOD); one HOD may head several departments.
     * Programmes and subjects belong to a department.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->foreignId('hod_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('programmes', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('level')->constrained()->nullOnDelete();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('credit_hours')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subjects', fn (Blueprint $table) => $table->dropConstrainedForeignId('department_id'));
        Schema::table('programmes', fn (Blueprint $table) => $table->dropConstrainedForeignId('department_id'));
        Schema::dropIfExists('departments');
    }
};
