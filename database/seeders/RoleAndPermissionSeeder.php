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
            'activity_logs.view',
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
            'units.view',
            'units.create',
            'units.edit',
            'units.delete',
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',
            'stock.view',
            'stock.in',
            'stock.out',
            'stock.transfer',
            'stock.adjustment',
            'stock_movements.view',
            'stock_movements.edit',
            'stock_movements.delete',
            'stock_movements.export',
            'purchase_orders.view',
            'purchase_orders.create',
            'purchase_orders.edit',
            'purchase_orders.delete',
            'purchase_orders.approve',
            'purchase_orders.export',
            'reports.view',
            'reports.export',
            'accounting.view',
            'accounting.create',
            'accounting.edit',
            'accounting.delete',
            'task.view',
            'task.create',
            'task.edit',
            'task.delete',
            'task.assign',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'settings.view',
            'settings.edit',
            'products.export',
            'categories.export',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $warehouseManager = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
        $warehouseManager->givePermissionTo([
            'dashboard.view',
            'activity_logs.view',
            'products.view',
            'products.create',
            'products.edit',
            'categories.view',
            'categories.create',
            'categories.edit',
            'units.view',
            'units.create',
            'units.edit',
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',
            'stock.view',
            'stock_movements.view',
            'stock.in',
            'stock.out',
            'stock.transfer',
            'stock.adjustment',
            'stock_movements.edit',
            'stock_movements.delete',
            'purchase_orders.view',
            'purchase_orders.create',
            'purchase_orders.edit',
            'purchase_orders.approve',
            'reports.view',
            'reports.export',
            'accounting.view',
            'accounting.create',
            'accounting.edit',
            'task.view',
            'task.create',
            'task.edit',
            'task.assign',
        ]);

        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->givePermissionTo([
            'dashboard.view',
            'products.view',
            'categories.view',
            'units.view',
            'task.view',
            'suppliers.view',
            'warehouses.view',
            'stock.view',
            'stock_movements.view',
            'stock.in',
            'stock.out',
            'purchase_orders.view',
            'accounting.view',
        ]);

        // Input only - can record stock inputs (e.g. receiving goods)
        $inputOnly = Role::firstOrCreate(['name' => 'input_only', 'guard_name' => 'web']);
        $inputOnly->givePermissionTo([
            'dashboard.view',
            'products.view',
            'categories.view',
            'units.view',
            'stock.view',
            'stock_movements.view',
            'stock.in',
        ]);

        // Output only - can record stock outputs (e.g. for cooking)
        $outputOnly = Role::firstOrCreate(['name' => 'output_only', 'guard_name' => 'web']);
        $outputOnly->givePermissionTo([
            'dashboard.view',
            'products.view',
            'categories.view',
            'units.view',
            'stock.view',
            'stock_movements.view',
            'stock.out',
        ]);

        // Product manager - can add/edit products, input/output
        $productManager = Role::firstOrCreate(['name' => 'product_manager', 'guard_name' => 'web']);
        $productManager->givePermissionTo([
            'dashboard.view',
            'products.view',
            'products.create',
            'products.edit',
            'categories.view',
            'units.view',
            'warehouses.view',
            'stock.view',
            'stock_movements.view',
            'stock.in',
            'stock.out',
            'purchase_orders.view',
            'accounting.view',
        ]);
    }
}
