<?php

namespace App\Infrastructure\Persistence\Eloquent\Rbac\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoleModel extends Model
{
    protected $table = 'roles';

    protected $fillable = ['name', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')
            ->withPivot('created_at');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionModel::class, 'permission_role', 'role_id', 'permission_id')
            ->withPivot('created_at');
    }
}
