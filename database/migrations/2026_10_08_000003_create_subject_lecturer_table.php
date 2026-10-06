<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A subject can now have several lecturers (replaces subjects.teacher_id).
     */
    public function up(): void
    {
        Schema::create('subject_lecturer', function (Blueprint $table) {
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['subject_id', 'user_id']);
        });

        // Keep each subject's existing teacher as its first lecturer.
        foreach (DB::table('subjects')->whereNotNull('teacher_id')->get(['id', 'teacher_id']) as $subject) {
            DB::table('subject_lecturer')->insert(['subject_id' => $subject->id, 'user_id' => $subject->teacher_id]);
        }

        Schema::table('subjects', fn (Blueprint $table) => $table->dropConstrainedForeignId('teacher_id'));
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('credit_hours')->constrained('users')->nullOnDelete();
        });

        foreach (DB::table('subject_lecturer')->get() as $row) {
            DB::table('subjects')->where('id', $row->subject_id)->whereNull('teacher_id')->update(['teacher_id' => $row->user_id]);
        }

        Schema::dropIfExists('subject_lecturer');
    }
};
