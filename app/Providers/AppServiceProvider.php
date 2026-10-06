<?php

namespace App\Providers;

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
        // one person's own work (a teacher's subjects, a student's results).
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin() && ! in_array($ability, config('roles.not_for_super_admin'), true)) {
                return true;
            }

            return null;   // carry on with the normal checks below
        });

        // Simple role-based permissions from config/roles.php
        foreach (config('roles.permissions') as $ability => $roles) {
            Gate::define($ability, fn (User $user) => in_array($user->role, $roles, true));
        }

        // A teacher may enter marks only for subjects assigned to them.
        Gate::define('enter-marks', fn (User $user, Subject $subject) => $user->hasRole('teacher') && $subject->teacher_id === $user->id);

        // Only the teacher of the subject may approve or reject a mark change.
        Gate::define('decide-mark-change', function (User $user, ResultChangeRequest $request) {
            return $request->isPending()
                && $user->hasRole('teacher')
                && $request->result->subject->teacher_id === $user->id;
        });

        // The HOD may ask for a change once marks exist and nothing is already waiting.
        Gate::define('request-mark-change', function (User $user, Result $result) {
            return $user->can('request-mark-changes') && $result->isMarked() && ! $result->pendingChange;
        });

        // Results and transcripts: staff who can see results, or the student themselves.
        Gate::define('view-student-results', function (User $user, Student $student) {
            return $user->can('view-results') || ($user->hasRole('student') && $student->user_id === $user->id);
        });
    }
}
