<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A result row now starts when the registrar registers a student for a
     * subject (marks empty). The teacher fills in the marks later.
     */
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->unsignedTinyInteger('marks')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Version 1 can't store a subject without marks, so those registrations are removed.
        DB::table('results')->whereNull('marks')->delete();

        Schema::table('results', function (Blueprint $table) {
            $table->unsignedTinyInteger('marks')->nullable(false)->default(0)->change();
        });
    }
};
