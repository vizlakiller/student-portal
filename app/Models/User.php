<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'staff_no', 'phone', 'password', 'role', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Remembers headedDepartmentIds() during one request. */
    private ?array $headedDepartmentIdsCache = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * All roles as [key => label], from config/roles.php.
     *
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return config('roles.roles');
    }

    /**
     * Staff roles only (everything except "student").
     *
     * @return array<string, string>
     */
    public static function staffRoles(): array
    {
        return array_diff_key(self::roles(), ['student' => true]);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function roleLabel(): string
    {
        return self::roles()[$this->role] ?? ucfirst($this->role);
    }

    /** Subjects this user lectures (lecturers only). */
    public function lecturedSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_lecturer')->orderBy('code');
    }

    /** Sessions (groups) this user lectures, in any term. */
    public function classSessions(): BelongsToMany
    {
        return $this->belongsToMany(ClassSession::class, 'class_session_lecturer');
    }

    /** Departments this user heads (heads of department only). */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'hod_id');
    }

    /**
     * Does this user head the given department? Used to limit what a head of
     * department can manage to their own departments.
     */
    public function headsDepartment(?int $departmentId): bool
    {
        return $departmentId && $this->hasRole('hod') && in_array($departmentId, $this->headedDepartmentIds(), true);
    }

    /** Ids of the departments this user heads (remembered for the rest of the request). */
    public function headedDepartmentIds(): array
    {
        return $this->headedDepartmentIdsCache ??= $this->departments()->pluck('id')->all();
    }

    /** The student record linked to this login (students only). */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }
}
