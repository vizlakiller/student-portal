<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A session is one group of a subject in a term (e.g. CSC1043 Group B),
     * with its own lecturer(s), students and weekly class times.
     */
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('name', 60);                         // e.g. Group A
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['term_id', 'subject_id', 'name']);
        });

        Schema::create('class_session_lecturer', function (Blueprint $table) {
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['class_session_id', 'user_id']);
        });

        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day');                 // 1 = Monday ... 6 = Saturday
            $table->time('starts_at');
            $table->time('ends_at');
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->index(['day', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
        Schema::dropIfExists('class_session_lecturer');
        Schema::dropIfExists('class_sessions');
    }
};
