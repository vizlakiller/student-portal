<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A head of department's request to change a mark. It only takes effect
     * when the subject's teacher approves it.
     */
    public function up(): void
    {
        Schema::create('result_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('old_marks')->nullable();
            $table->unsignedTinyInteger('new_marks');
            $table->string('reason', 500);
            $table->string('status', 20)->default('pending')->index();   // pending, approved, rejected
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_change_requests');
    }
};
