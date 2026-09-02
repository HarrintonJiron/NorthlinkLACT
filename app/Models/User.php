<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Permission;
use App\Modules\Admin\Models\Plant;
use App\Modules\Admin\Models\Role;
use App\Modules\Personnel\Models\Employee;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['employee_id', 'username', 'name', 'email', 'password', 'pin', 'phone', 'active'])]
#[Hidden([
    'password',
    'pin',
    'remember_token',
    'failed_login_attempts',
    'locked_until',
    'last_failed_login_at',
    'password_changed_at',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_admin' => 'boolean',
            'email_verified_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_failed_login_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('plant_id')
            ->withTimestamps();
    }

    public function plants()
    {
        return $this->belongsToMany(Plant::class, 'company_user')
            ->withPivot('company_id')
            ->withTimestamps();
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', $permission))
            ->exists();
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId));
    }

    public function scopeForPlant($query, $plantId)
    {
        return $query->whereHas('plants', fn ($q) => $q->where('plants.id', $plantId));
    }
}
