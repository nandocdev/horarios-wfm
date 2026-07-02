<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\PermissionRegistrar;

class EloquentUser extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'force_password_change',
        'last_login_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'force_password_change' => 'boolean',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function roles(): BelongsToMany
    {
        $permissionClass = app(PermissionRegistrar::class)->getRoleClass();

        return $this->morphToMany(
            $permissionClass,
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            app(PermissionRegistrar::class)->pivotRole,
        );
    }
}
