<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();

            // Deleting a student also deletes their results.
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            // A subject cannot be deleted while results still use it.
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();

            $table->unsignedTinyInteger('semester');
            $table->unsignedTinyInteger('marks');     // 0 to 100; grade is worked out from this
            $table->timestamps();

            // A student has only one result per subject.
            $table->unique(['student_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
