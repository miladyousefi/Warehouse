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
    ArrowDownToLine,
    ArrowUpFromLine,
    ShoppingCart,
    BarChart3,
    ClipboardList,
    Users,
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
import { usePage } from '@inertiajs/vue3';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { index as categoriesIndex } from '@/actions/App/Http/Controllers/Warehouse/ProductCategoryController';
import { index as unitsIndex } from '@/actions/App/Http/Controllers/Warehouse/UnitController';
import { index as suppliersIndex } from '@/actions/App/Http/Controllers/Warehouse/SupplierController';
import { index as warehousesIndex } from '@/actions/App/Http/Controllers/Warehouse/WarehouseController';
import { index as stockIndex } from '@/actions/App/Http/Controllers/Warehouse/StockController';
import { index as stockMovementsIndex, create as stockMovementsCreate } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { index as purchaseOrdersIndex } from '@/actions/App/Http/Controllers/Warehouse/PurchaseOrderController';
import { index as reportsIndex } from '@/actions/App/Http/Controllers/Warehouse/ReportController';
import { index as activityLogsIndex } from '@/actions/App/Http/Controllers/Warehouse/ActivityLogController';
import { index as usersIndex } from '@/actions/UserController';

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
    // Input/Output first - main warehouse actions for restaurant
    if (can('stock.in')) {
        items.push({
            title: t('nav.input'),
            href: stockMovementsCreate.url({ query: { type: 'in' } }),
            icon: ArrowDownToLine,
            iconClass: 'text-emerald-600 dark:text-emerald-400',
        });
    }
    if (can('stock.out')) {
        items.push({
            title: t('nav.output'),
            href: stockMovementsCreate.url({ query: { type: 'out' } }),
            icon: ArrowUpFromLine,
            iconClass: 'text-rose-600 dark:text-rose-400',
        });
    }
    if (can('stock.movements.view')) {
        items.push({
            title: t('nav.movements'),
            href: stockMovementsIndex.url(),
            icon: ArrowRightLeft,
        });
    }
    if (can('stock.view')) {
        items.push({
            title: t('nav.stock'),
            href: stockIndex.url(),
            icon: Boxes,
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
    if (can('purchase_orders.view')) {
        items.push({
            title: t('nav.orders'),
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
    if (can('activity_logs.view')) {
        items.push({
            title: t('nav.activityLogs'),
            href: activityLogsIndex.url(),
            icon: ClipboardList,
        });
    }
    if (can('users.view')) {
        items.push({
            title: t('nav.admins'),
            href: usersIndex.url(),
            icon: Users,
        });
    }
    return items;
});

const footerNavItems: NavItem[] = [];
const page = usePage();
const locale = computed(() => (page.props.locale as string) ?? 'tr');
const otherLocale = computed(() => (locale.value === 'tr' ? 'en' : 'tr'));
const switchLabel = computed(() => otherLocale.value.toUpperCase());
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
            <div class="px-4 mt-2">
                <a :href="`/locale/${otherLocale}`" class="inline-flex items-center rounded-md px-2 py-1 text-sm font-medium hover:bg-accent transition-colors">
                    🌐 {{ switchLabel }}
                </a>
            </div>
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
