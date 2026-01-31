<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Package,
    FolderTree,
    Ruler,
    Truck,
    Warehouse,
    Boxes,
    ArrowRightLeft,
    ShoppingCart,
    BarChart3,
} from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { usePermission } from '@/composables/usePermission';
import { useI18n } from 'vue-i18n';
import AppLogo from './AppLogo.vue';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { index as categoriesIndex } from '@/actions/App/Http/Controllers/Warehouse/ProductCategoryController';
import { index as unitsIndex } from '@/actions/App/Http/Controllers/Warehouse/UnitController';
import { index as suppliersIndex } from '@/actions/App/Http/Controllers/Warehouse/SupplierController';
import { index as warehousesIndex } from '@/actions/App/Http/Controllers/Warehouse/WarehouseController';
import { index as stockIndex } from '@/actions/App/Http/Controllers/Warehouse/StockController';
import { index as stockMovementsIndex } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { index as purchaseOrdersIndex } from '@/actions/App/Http/Controllers/Warehouse/PurchaseOrderController';
import { index as reportsIndex } from '@/actions/App/Http/Controllers/Warehouse/ReportController';

const { can } = usePermission();
const { t } = useI18n();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (can('dashboard.view')) {
        items.push({
            title: t('nav.dashboard'),
            href: dashboard().url,
            icon: LayoutGrid,
        });
    }
    if (can('products.view')) {
        items.push({
            title: t('nav.products'),
            href: productsIndex.url(),
            icon: Package,
        });
    }
    if (can('categories.view')) {
        items.push({
            title: t('nav.categories'),
            href: categoriesIndex.url(),
            icon: FolderTree,
        });
    }
    if (can('units.view')) {
        items.push({
            title: t('nav.units'),
            href: unitsIndex.url(),
            icon: Ruler,
        });
    }
    if (can('suppliers.view')) {
        items.push({
            title: t('nav.suppliers'),
            href: suppliersIndex.url(),
            icon: Truck,
        });
    }
    if (can('warehouses.view')) {
        items.push({
            title: t('nav.warehouses'),
            href: warehousesIndex.url(),
            icon: Warehouse,
        });
    }
    if (can('stock.view')) {
        items.push({
            title: t('nav.stock'),
            href: stockIndex.url(),
            icon: Boxes,
        });
    }
    if (can('stock.movements.view')) {
        items.push({
            title: t('nav.stockMovements'),
            href: stockMovementsIndex.url(),
            icon: ArrowRightLeft,
        });
    }
    if (can('purchase_orders.view')) {
        items.push({
            title: t('nav.purchaseOrders'),
            href: purchaseOrdersIndex.url(),
            icon: ShoppingCart,
        });
    }
    if (can('reports.view')) {
        items.push({
            title: t('nav.reports'),
            href: reportsIndex.url(),
            icon: BarChart3,
        });
    }
    return items;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard().url">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
