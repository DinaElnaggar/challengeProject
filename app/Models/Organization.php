<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Organization extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'owner_id'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user_roles')->withPivot('role_id')->withTimestamps();
    }

    public static function userHasPermission(int $userId, int $organizationId, string $permission): bool
    {
        return DB::table('organization_user_roles as our')
            ->join('role_permission as rp', 'rp.role_id', '=', 'our.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('our.user_id', $userId)
            ->where('our.organization_id', $organizationId)
            ->where('p.name', $permission)
            ->exists();
    }
}
