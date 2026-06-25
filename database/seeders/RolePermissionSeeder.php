<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
  public const PERMISSIONS = [
    'manage users',
    'manage roles',
    'manage permissions',
    'manage inventory',
    'view audit logs',
    'manage system settings',
    'create item',
    'update item',
    'delete item',
    'manage categories',
    'manage suppliers',
    'manage stock adjustments',
    'view reports',
    'view inventory',
    'receive stocks',
    'release stocks',
    'create inventory transactions',
    'view transactions',
    'generate reports',
    'read only access',
  ];

  public function run(): void
  {
    foreach (self::PERMISSIONS as $permission) {
      Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
    }

    $superAdmin = Role::firstOrCreate(['name' => 'Super Administrator', 'guard_name' => 'api']);
    $superAdmin->syncPermissions(Permission::where('guard_name', 'api')->pluck('name')->all());

    $inventoryManager = Role::firstOrCreate(['name' => 'Inventory Manager', 'guard_name' => 'api']);
    $inventoryManager->syncPermissions([
      'create item',
      'update item',
      'delete item',
      'manage categories',
      'manage suppliers',
      'manage stock adjustments',
      'view reports',
      'view inventory',
    ]);

    $warehouseStaff = Role::firstOrCreate(['name' => 'Warehouse Staff', 'guard_name' => 'api']);
    $warehouseStaff->syncPermissions([
      'view inventory',
      'receive stocks',
      'release stocks',
      'create inventory transactions',
    ]);

    $auditor = Role::firstOrCreate(['name' => 'Auditor', 'guard_name' => 'api']);
    $auditor->syncPermissions([
      'view inventory',
      'view transactions',
      'view audit logs',
      'generate reports',
      'view reports',
    ]);

    $viewer = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'api']);
    $viewer->syncPermissions(['read only access', 'view inventory']);
  }
}
