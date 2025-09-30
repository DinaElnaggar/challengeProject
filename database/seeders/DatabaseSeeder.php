<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Organization;
 
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Users: ensure at least 5
        if (DB::table('users')->count() < 5) {
            User::factory(5)->create();
        }

        // Seed roles
        $roles = ['owner', 'admin', 'member', 'auditor'];
        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['name' => $role], ['name' => $role]);
        }

        // Seed permissions
        $permissions = ['users.read','users.update','users.delete','users.invite','analytics.read'];
        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(['name' => $perm], ['name' => $perm]);
        }

        // Map permissions to roles
        $roleIdByName = DB::table('roles')->pluck('id','name');
        $permIdByName = DB::table('permissions')->pluck('id','name');

        $map = [
            'owner' => ['users.read','users.update','users.delete','users.invite','analytics.read'],
            'admin' => ['users.read','users.update','users.invite','analytics.read'],
            'member' => ['users.read','analytics.read'],
            'auditor' => ['users.read','analytics.read'],
        ];

        foreach ($map as $roleName => $perms) {
            $roleId = $roleIdByName[$roleName] ?? null;
            if (!$roleId) continue;
            foreach ($perms as $p) {
                $permId = $permIdByName[$p] ?? null;
                if (!$permId) continue;
                DB::table('role_permission')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permId],
                    ['role_id' => $roleId, 'permission_id' => $permId]
                );
            }
        }

        // Create a few organizations and attach owners/members
        $userIds = DB::table('users')->pluck('id')->all();

        if (! empty($userIds)) {
            $ownerRoleId = $roleIdByName['owner'] ?? DB::table('roles')->where('name','owner')->value('id');
            $memberRoleId = $roleIdByName['member'] ?? DB::table('roles')->where('name','member')->value('id');

            for ($i = 1; $i <= 3; $i++) {
                $name = 'Org '.Str::upper(Str::random(4));
                $ownerId = $userIds[array_rand($userIds)];
                $orgId = DB::table('organizations')->insertGetId([
                    'name' => $name,
                    'owner_id' => $ownerId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Owner role in org
                DB::table('organization_user_roles')->updateOrInsert(
                    ['organization_id' => $orgId, 'user_id' => $ownerId],
                    ['role_id' => $ownerRoleId, 'created_at' => now(), 'updated_at' => now()]
                );

                // Add two members
                shuffle($userIds);
                foreach (array_slice($userIds, 0, 2) as $uid) {
                    if ($uid === $ownerId) continue;
                    DB::table('organization_user_roles')->updateOrInsert(
                        ['organization_id' => $orgId, 'user_id' => $uid],
                        ['role_id' => $memberRoleId, 'created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }

        $orgs = Organization::factory()->count(3)->create();

        foreach ($orgs as $org) {
            // choose an owner
            $ownerId = $userIds[array_rand($userIds)];
            $org->owner_id = $ownerId;
            $org->save();
        
            // owner in pivot
            DB::table('organization_user_roles')->updateOrInsert(
                ['organization_id' => $org->id, 'user_id' => $ownerId],
                ['role_id' => $ownerRoleId, 'created_at' => now(), 'updated_at' => now()]
            );
        
            // add two members
            shuffle($userIds);
            foreach (array_slice($userIds, 0, 2) as $uid) {
                if ($uid === $ownerId) continue;
                DB::table('organization_user_roles')->updateOrInsert(
                    ['organization_id' => $org->id, 'user_id' => $uid],
                    ['role_id' => $memberRoleId, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
       
        

    }
}
