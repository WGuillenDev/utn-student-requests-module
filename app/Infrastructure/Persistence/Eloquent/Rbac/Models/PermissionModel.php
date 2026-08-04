<?php

namespace App\Infrastructure\Persistence\Eloquent\Rbac\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermissionModel extends Model
{
    protected $table = 'permissions';

    protected $fillable = ['name', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'permission_role', 'permission_id', 'role_id')
            ->withPivot('created_at');
    }

    /**
     * Users granted this permission directly, in addition to those who get it via a role.
     */
    public function usersWithDirectAccess(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'permission_user', 'permission_id', 'user_id')
            ->withPivot('otorgado_por', 'created_at');
    }
}
