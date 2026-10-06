<?php

namespace App\Providers;

use App\Models\ClassSession;
use App\Models\Result;
use App\Models\ResultChangeRequest;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Abilities that depend on a record's state (e.g. "is the request still
     * waiting?"). Their rules apply to the super admin too.
     */
    private const CHECKED_FOR_EVERYONE = ['request-mark-change', 'decide-mark-change'];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->definePermissions();

        // Login attempts: 5 per minute for each email address from each network.
        // (Counting per email, not just per network, means staff who share the
        // college internet connection don't block each other.)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        // Use our own pagination links (resources/views/partials/pagination.blade.php).
        Paginator::defaultView('partials.pagination');
    }

    /**
     * Who may do what. Used by the "can:..." route middleware, by
     * Gate::authorize() in controllers and by @can(...) in the views.
     */
    private function definePermissions(): void
    {
        // The super admin can do everything, except abilities that belong to
        // one person's own work (a lecturer's sessions, a student's results).
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()
                && ! in_array($ability, config('roles.not_for_super_admin'), true)
                && ! in_array($ability, self::CHECKED_FOR_EVERYONE, true)) {
                return true;
            }

            return null;   // carry on with the normal checks below
        });

        // Simple role-based permissions from config/roles.php
        foreach (config('roles.permissions') as $ability => $roles) {
            Gate::define($ability, fn (User $user) => in_array($user->role, $roles, true));
        }

        // ----- Limited by department: a head of department manages only the departments they head -----

        // Add, edit or delete a subject, and choose its lecturers.
        Gate::define('manage-subject', fn (User $user, Subject $subject) => $user->can('manage-subjects') && $user->headsDepartment($subject->department_id));
        Gate::define('assign-subject-lecturers', fn (User $user, Subject $subject) => $user->can('assign-lecturers') && $user->headsDepartment($subject->department_id));

        // Sessions and class times of a subject.
        Gate::define('manage-subject-timetable', fn (User $user, Subject $subject) => $user->can('manage-timetable') && $user->headsDepartment($subject->department_id));

        // Ask for a mark change: marks must exist, nothing already waiting, subject in the HOD's department.
        // (The super admin changes marks directly instead.)
        Gate::define('request-mark-change', function (User $user, Result $result) {
            return $user->can('request-mark-changes')
                && $result->isMarked()
                && ! $result->pendingChange
                && $user->headsDepartment($result->subject->department_id);
        });

        // ----- Lecturers: only their own sessions -----

        // Enter marks for the students of a session.
        Gate::define('enter-marks', fn (User $user, ClassSession $session) => $user->hasRole('lecturer') && $session->hasLecturer($user));

        // Approve or reject a mark change: a lecturer of the result's session
        // (or of the subject, for older results without a session).
        // The super admin may also decide (e.g. when no lecturer is assigned).
        Gate::define('decide-mark-change', function (User $user, ResultChangeRequest $request) {
            return $request->isPending()
                && ($user->isSuperAdmin()
                    || ($user->hasRole('lecturer') && $request->result->responsibleLecturers()->contains('id', $user->id)));
        });

        // Results and transcripts: staff who can see results, or the student themselves.
        Gate::define('view-student-results', function (User $user, Student $student) {
            return $user->can('view-results') || ($user->hasRole('student') && $student->user_id === $user->id);
        });
    }
}
