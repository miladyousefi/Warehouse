<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarInput,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { type NavGroup } from '@/types';

const props = defineProps<{
    groups: NavGroup[];
}>();

const { isCurrentUrl } = useCurrentUrl();
const { isMobile, setOpenMobile, state } = useSidebar();

const query = ref('');

const filteredGroups = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return null;

    return (
        props.groups
            .map((group) => ({
                ...group,
                items: group.items.filter((item) =>
                    item.title.toLowerCase().includes(q),
                ),
            }))
            // keep groups with matches only
            .filter((group) => group.items.length > 0)
    );
});

function onNavigate() {
    if (isMobile.value) setOpenMobile(false);
}
</script>

<template>
    <div class="px-2">
        <SidebarInput
            v-if="state !== 'collapsed' || isMobile"
            v-model="query"
            :placeholder="$t('common.search')"
            type="search"
            autocomplete="off"
            :aria-label="$t('common.search')"
            class="mb-2 rounded-none border-x-0 border-t-0 border-b border-sidebar-border/70 bg-transparent px-0 shadow-none focus-visible:border-sidebar-ring focus-visible:ring-0 focus-visible:ring-offset-0"
        />
    </div>

    <template
        v-for="(group, index) in filteredGroups ?? groups"
        :key="group.label"
    >
        <SidebarGroup class="px-2 py-0">
            <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in group.items" :key="item.title">
                    <SidebarMenuButton
                        as-child
                        :is-active="isCurrentUrl(item.href)"
                        :tooltip="item.title"
                    >
                        <Link
                            :href="item.href"
                            class="[&>svg]:shrink-0"
                            :aria-current="
                                isCurrentUrl(item.href) ? 'page' : undefined
                            "
                            @click="onNavigate"
                        >
                            <component
                                :is="item.icon"
                                :class="item.iconClass"
                            />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>

        <SidebarSeparator
            v-if="index !== (filteredGroups ?? groups).length - 1"
            class="mx-3 my-2 group-data-[collapsible=icon]:opacity-0"
        />
    </template>
</template>
