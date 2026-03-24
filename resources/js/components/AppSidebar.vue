<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
    Calculator,
    ShieldCheck,
    PlusCircle,
    UtensilsCrossed,
    MessageSquare,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { index as activityLogsIndex } from '@/actions/App/Http/Controllers/Warehouse/ActivityLogController';
import { index as categoriesIndex } from '@/actions/App/Http/Controllers/Warehouse/ProductCategoryController';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Warehouse/ProductController';
import { index as purchaseOrdersIndex } from '@/actions/App/Http/Controllers/Warehouse/PurchaseOrderController';
import {
    index as stockMovementsIndex,
    create as stockMovementsCreate,
} from '@/actions/App/Http/Controllers/Warehouse/StockMovementController';
import { index as suppliersIndex } from '@/actions/App/Http/Controllers/Warehouse/SupplierController';
import { index as tasksIndex } from '@/actions/App/Http/Controllers/Warehouse/TaskController';
import { index as unitsIndex } from '@/actions/App/Http/Controllers/Warehouse/UnitController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Warehouse/UserController';
import { index as warehousesIndex } from '@/actions/App/Http/Controllers/Warehouse/WarehouseController';
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
import { usePermission } from '@/composables/usePermission';
import { dashboard } from '@/routes';
import { index as aiChatIndex } from '@/routes/ai/chat';
import { type NavGroup, type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const { can } = usePermission();
const { t } = useI18n();

type RouteHelper = {
    url: (options?: unknown) => string;
};

type RouteHelperMap = Record<string, RouteHelper>;

const resolveRouteUrl = (
    routeEntry: RouteHelper | RouteHelperMap,
    preferredPath: string,
    options?: unknown,
) => {
    if ('url' in routeEntry && typeof routeEntry.url === 'function') {
        return routeEntry.url(options);
    }

    const selectedRoute = routeEntry[preferredPath] ?? Object.values(routeEntry)[0];

    if (!selectedRoute || typeof selectedRoute.url !== 'function') {
        return preferredPath;
    }

    return selectedRoute.url(options);
};

const mainNavGroups = computed<NavGroup[]>(() => {
    const quickActions: NavItem[] = [];
    const inventory: NavItem[] = [];
    const management: NavItem[] = [];
    const restaurant: NavItem[] = [];
    const administration: NavItem[] = [];

    if (can('dashboard.view')) {
        management.push({
            title: t('nav.dashboard'),
            href: dashboard().url,
            icon: LayoutGrid,
        });
    }

    // Quick actions
    if (can('stock.in')) {
        quickActions.push({
            title: t('nav.input'),
            href: stockMovementsCreate.url({ query: { type: 'in' } }),
            icon: ArrowDownToLine,
            iconClass: 'text-emerald-600 dark:text-emerald-400',
        });
    }
    if (can('stock.out')) {
        quickActions.push({
            title: t('nav.output'),
            href: stockMovementsCreate.url({ query: { type: 'out' } }),
            icon: ArrowUpFromLine,
            iconClass: 'text-rose-600 dark:text-rose-400',
        });
    }
    if (can('restaurant_orders.take_order')) {
        quickActions.push({
            title: t('nav.takeOrder'),
            href: route('warehouse.restaurant-orders.manual.create'),
            icon: PlusCircle,
        });
    }

    // Inventory / catalog
    if (can('stock_movements.view')) {
        inventory.push({
            title: t('nav.movements'),
            href: resolveRouteUrl(
                stockMovementsIndex,
                '/warehouse/stock-movements',
            ),
            icon: ArrowRightLeft,
        });
    }
    if (can('products.view')) {
        inventory.push({
            title: t('nav.products'),
            href: resolveRouteUrl(productsIndex, '/warehouse/products'),
            icon: Package,
        });
    }
    if (can('categories.view')) {
        inventory.push({
            title: t('nav.categories'),
            href: resolveRouteUrl(categoriesIndex, '/warehouse/categories'),
            icon: FolderTree,
        });
    }
    if (can('units.view')) {
        inventory.push({
            title: t('nav.units'),
            href: resolveRouteUrl(unitsIndex, '/warehouse/units'),
            icon: Ruler,
        });
    }
    if (can('suppliers.view')) {
        inventory.push({
            title: t('nav.suppliers'),
            href: resolveRouteUrl(suppliersIndex, '/warehouse/suppliers'),
            icon: Truck,
        });
    }
    if (can('warehouses.view')) {
        inventory.push({
            title: t('nav.warehouses'),
            href: resolveRouteUrl(warehousesIndex, '/warehouse/warehouses'),
            icon: Warehouse,
        });
    }

    // Management
    if (can('purchase_orders.view')) {
        management.push({
            title: t('nav.orders'),
            href: resolveRouteUrl(
                purchaseOrdersIndex,
                '/warehouse/purchase-orders',
            ),
            icon: ShoppingCart,
        });
    }
    if (can('task.view')) {
        management.push({
            title: t('nav.tasks'),
            href: tasksIndex.url(),
            icon: ClipboardList,
        });
    }
    if (can('activity_logs.view')) {
        management.push({
            title: t('nav.activityLogs'),
            href: resolveRouteUrl(
                activityLogsIndex,
                '/warehouse/activity-logs',
            ),
            icon: ClipboardList,
        });
    }
    if (can('accounting.view')) {
        management.push({
            title: t('nav.accounting'),
            href: route('warehouse.accounting.index'),
            icon: Calculator,
        });
    }

    // Restaurant
    if (can('restaurant_menu.view')) {
        restaurant.push({
            title: t('nav.restaurantMenu'),
            href: route('warehouse.restaurant-menu.index'),
            icon: UtensilsCrossed,
        });
    }
    if (can('restaurant_orders.view')) {
        restaurant.push({
            title: t('nav.restaurantOrders'),
            href: route('warehouse.restaurant-orders.index'),
            icon: ClipboardList,
        });
    }

    // Administration
    if (can('users.view')) {
        administration.push({
            title: t('nav.admins'),
            href: usersIndex.url(),
            icon: Users,
        });
    }
    if (can('roles.view')) {
        administration.push({
            title: t('permissions.title'),
            href: '/warehouse/roles',
            icon: ShieldCheck,
        });
    }

    // AI & Chat
    const aiChat: NavItem[] = [];
    aiChat.push({
        title: t('nav.aiChat'),
        href: aiChatIndex.url(),
        icon: MessageSquare,
    });

    const groups: NavGroup[] = [];
    if (quickActions.length) {
        groups.push({
            label: t('navGroups.quickActions'),
            items: quickActions,
        });
    }
    if (inventory.length) {
        groups.push({ label: t('navGroups.inventory'), items: inventory });
    }
    if (management.length) {
        groups.push({ label: t('navGroups.management'), items: management });
    }
    if (restaurant.length) {
        groups.push({ label: t('navGroups.restaurant'), items: restaurant });
    }
    if (administration.length) {
        groups.push({
            label: t('navGroups.administration'),
            items: administration,
        });
    }
    if (aiChat.length) {
        groups.push({
            label: t('navGroups.aiChat'),
            items: aiChat,
        });
    }

    return groups;
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
            <NavMain :groups="mainNavGroups" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
