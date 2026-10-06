<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which session (group) a student registration belongs to.
     * Older results (from before sessions existed) have none.
     */
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->foreignId('class_session_id')->nullable()->after('subject_id')->constrained()->nullOnDelete();
        });

        // Subjects still waiting for marks are put into a "Group A" session of
        // a current term, so their lecturers can carry on entering marks.
        $waiting = DB::table('results')->whereNull('marks')->distinct()->pluck('subject_id');

        if ($waiting->isEmpty()) {
            return;
        }

        $termId = DB::table('terms')->where('is_current', true)->value('id')
            ?? DB::table('terms')->insertGetId(['name' => 'Current term', 'is_current' => true, 'created_at' => now(), 'updated_at' => now()]);

        foreach ($waiting as $subjectId) {
            $sessionId = DB::table('class_sessions')->insertGetId([
                'term_id' => $termId, 'subject_id' => $subjectId, 'name' => 'Group A',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach (DB::table('subject_lecturer')->where('subject_id', $subjectId)->pluck('user_id') as $lecturerId) {
                DB::table('class_session_lecturer')->insert(['class_session_id' => $sessionId, 'user_id' => $lecturerId]);
            }

            DB::table('results')->where('subject_id', $subjectId)->whereNull('marks')->update(['class_session_id' => $sessionId]);
        }
    }

    public function down(): void
    {
        Schema::table('results', fn (Blueprint $table) => $table->dropConstrainedForeignId('class_session_id'));
    }
};
