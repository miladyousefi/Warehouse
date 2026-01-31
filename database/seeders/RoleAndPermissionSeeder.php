<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'units.view', 'units.create', 'units.edit', 'units.delete',
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'warehouses.view', 'warehouses.create', 'warehouses.edit', 'warehouses.delete',
            'stock.view', 'stock.movements.view', 'stock.in', 'stock.out', 'stock.transfer', 'stock.adjustment',
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.edit', 'purchase_orders.delete', 'purchase_orders.approve',
            'reports.view', 'reports.export',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all());

        $warehouseManager = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
        $warehouseManager->givePermissionTo([
            'dashboard.view',
            'products.view', 'products.create', 'products.edit',
            'categories.view', 'categories.create', 'categories.edit',
            'units.view', 'units.create', 'units.edit',
            'suppliers.view', 'suppliers.create', 'suppliers.edit',
            'warehouses.view', 'warehouses.create', 'warehouses.edit',
            'stock.view', 'stock.movements.view', 'stock.in', 'stock.out', 'stock.transfer', 'stock.adjustment',
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.edit', 'purchase_orders.approve',
            'reports.view', 'reports.export',
        ]);

        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->givePermissionTo([
            'dashboard.view',
            'products.view', 'categories.view', 'units.view', 'suppliers.view', 'warehouses.view',
            'stock.view', 'stock.movements.view', 'stock.in', 'stock.out',
            'purchase_orders.view',
        ]);
    }
}
