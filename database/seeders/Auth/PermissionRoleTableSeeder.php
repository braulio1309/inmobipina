<?php
namespace Database\Seeders\Auth;

use App\Models\Core\Auth\Role;
use App\Models\Core\Auth\Type;
use App\Models\Core\Auth\User;
use Database\Seeders\Traits\DisableForeignKeys;
use Illuminate\Database\Seeder;

/**
 * Class PermissionRoleTableSeeder.
 */
class PermissionRoleTableSeeder extends Seeder
{
    use DisableForeignKeys;

    /**
     * Run the database seed.
     */
    public function run()
    {
        $this->disableForeignKeys();
        Role::query()->truncate();
        // Create Roles
        $superAdmin = User::first();

        $appTypeId = Type::findByAlias('app')->id;

        $roles = [
            [
                'name' => config('access.users.app_admin_role'),
                'is_admin' => 1,
                'type_id' => $appTypeId,
                'created_by' => $superAdmin->id,
                'is_default' => 1
            ],
            [
                'name' => 'Asesor',
                'is_admin' => 0,
                'type_id' => $appTypeId,
                'created_by' => $superAdmin->id,
                'is_default' => 0
            ],
        ];

        Role::query()->insert($roles);

        $this->enableForeignKeys();
    }
}
