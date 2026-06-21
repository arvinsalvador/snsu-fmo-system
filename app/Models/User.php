<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'uuid',
    'employee_no',
    'student_no',
    'name',
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'email',
    'username',
    'mobile_number',
    'password',
    'is_active',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->uuid ??= (string) Str::uuid();
            $user->name = self::resolveDisplayName($user);
        });
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'requestor_id');
    }

    public function workOrderApprovals(): HasMany
    {
        return $this->hasMany(WorkOrderApproval::class, 'approver_id');
    }

    public function workOrderAssignmentsMade(): HasMany
    {
        return $this->hasMany(WorkOrderAssignment::class, 'assigned_by');
    }

    public function workOrderUpdatesCreated(): HasMany
    {
        return $this->hasMany(WorkOrderUpdate::class, 'created_by');
    }

    public function workOrderUpdatePhotos(): HasMany
    {
        return $this->hasMany(WorkOrderUpdatePhoto::class, 'uploaded_by');
    }

    public static function resolveDisplayName(User $user): string
    {
        $nameParts = array_filter([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
            $user->suffix,
        ]);

        return $nameParts === [] ? $user->name : implode(' ', $nameParts);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
