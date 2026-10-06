<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Academic terms, e.g. "September 2026". One is marked as the current term.
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();         // e.g. MK-201
            $table->string('name');                       // e.g. Computer Lab A
            $table->string('building')->nullable();
            $table->string('type', 30);                   // Lecture room, Computer lab...
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('terms');
    }
};
