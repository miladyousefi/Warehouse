<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Package,
    FolderTree,
    Ruler,
    Truck,
    Warehouse,
    ArrowRightLeft,
    ArrowDownToLine,
    ArrowUpFromLine,
    ShoppingCart,
    ClipboardList,
    Users,
    Globe,
    Calculator,
    ShieldCheck,
    PlusCircle,
    UtensilsCrossed,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { index as activityLogsIndex } from '@/actions/App/Http/Controllers/Warehouse/ActivityLogController';
import { index as categoriesIndex } from '@/actions/App/Http/Controllers/Warehouse/ProductCategoryController';
import { index as purchaseOrdersIndex } from '@/actions/App/Http/Controllers/Warehouse/PurchaseOrderController';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { index as suppliersIndex } from '@/actions/App/Http/Controllers/Warehouse/SupplierController';
import { index as unitsIndex } from '@/actions/App/Http/Controllers/Warehouse/UnitController';
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
import AppLogo from './AppLogo.vue';
import { index as warehousesIndex } from '@/actions/App/Http/Controllers/Warehouse/WarehouseController';
import { index as stockMovementsIndex, create as stockMovementsCreate } from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Warehouse/UserController';
import { index as tasksIndex } from '@/actions/App/Http/Controllers/Warehouse/TaskController';

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
    if (can('stock_movements.view')) {
        items.push({
            title: t('nav.movements'),
            href: stockMovementsIndex.url(),
            icon: ArrowRightLeft,
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
    if (can('activity_logs.view')) {
        items.push({
            title: t('nav.activityLogs'),
            href: activityLogsIndex.url(),
            icon: ClipboardList,
        });
    }
    if (can('task.view')) {
        items.push({
            title: t('nav.tasks'),
            href: tasksIndex.url(),
            icon: ClipboardList,
        });
    }
    if (can('accounting.view')) {
        items.push({
            title: t('nav.accounting'),
            href: route('warehouse.accounting.index'),
            icon: Calculator,
        });
    }
    if (can('restaurant_menu.view')) {
        items.push({
            title: t('nav.restaurantMenu'),
            href: route('warehouse.restaurant-menu.index'),
            icon: UtensilsCrossed,
        });
    }
    if (can('restaurant_orders.view')) {
        items.push({
            title: t('nav.restaurantOrders'),
            href: route('warehouse.restaurant-orders.index'),
            icon: ClipboardList,
        });
    }
    if (can('restaurant_orders.take_order')) {
        items.push({
            title: t('nav.takeOrder'),
            href: route('warehouse.restaurant-orders.manual.create'),
            icon: PlusCircle,
        });
    }
    if (can('users.view')) {
        items.push({
            title: t('nav.admins'),
            href: usersIndex.url(),
            icon: Users,
        });
    }
    if (can('roles.view')) {
        items.push({
            title: t('permissions.title'),
            href: '/warehouse/roles',
            icon: ShieldCheck,
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
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <div class="px-2 pb-2">
                <a :href="`/locale/${otherLocale}`" class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-sidebar-border px-2 py-1.5 text-sm font-medium hover:bg-accent transition-colors">
                    <Globe class="h-4 w-4" />
                    {{ switchLabel }}
                </a>
            </div>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
