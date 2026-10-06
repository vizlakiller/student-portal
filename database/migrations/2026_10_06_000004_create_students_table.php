<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_no', 20)->unique();   // matric number, e.g. DCS2025001
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('gender', 10);
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();

            // A programme cannot be deleted while students are still enrolled in it.
            $table->foreignId('programme_id')->constrained()->restrictOnDelete();

            $table->unsignedSmallInteger('intake_year');
            $table->unsignedTinyInteger('semester')->default(1);
            $table->string('status', 20)->default('Active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
