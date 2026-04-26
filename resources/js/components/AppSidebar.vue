<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubItem,
    SidebarMenuSubButton,
    SidebarFooter,
} from '@/components/ui/sidebar';
import {
    LayoutGrid,
    SlidersHorizontal,
    ImageUpscale,
    UsersRound,
    ChevronDown,
    MapPinCheckInside,
    UserRoundCog,
    Computer,
    TextSearch,
    ContactRound,
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const currentPath = computed(() => page.url || '');

const openGroups = ref<Record<string, boolean>>({});

// ✅ Menu configuration — now supports multiple roles per item
const sidebarItems = [
    {
        items: [
            { title: "Dashboard", href: "/dashboard", icon: LayoutGrid },
        ],
    },
    {
        section: "Admin Setting",
        items: [
            // Only admin
            { title: "Desk Staff Create", href: "/employeeCreate", icon: Computer, roles: ['admin'] },
            { title: "Sales Staff Create", href: "/staffCreate", icon: UserRoundCog, roles: ['admin'] },
            { title: "Demo Presenter Create", href: "/presenterCreate", icon: ContactRound, roles: ['admin'] },
            { title: "Area Create", href: "/areaCreate", icon: MapPinCheckInside, roles: ['admin'] },
            {
                title: "Client Management",
                icon: UsersRound,
                roles: ['admin'],
                children: [
                    { title: "Client Create", href: "/clientManagement", icon: SlidersHorizontal, roles: ['admin'] },
                    { title: "Client List", href: "/clientList", icon: SlidersHorizontal, roles: ['admin'] },
                ],
            },
            {
                title: "Task Section",
                icon: TextSearch,
                roles: ['admin', 'staff'],
                children: [
                    { title: "Task Entry", href: "/task-entry", icon: SlidersHorizontal, roles: ['admin', 'staff'] },
                    { title: "Task Assignment", href: "/task-assignment", icon: SlidersHorizontal, roles: ['admin'] },
                    { title: "All Task List", href: "/task-list", icon: SlidersHorizontal, roles: ['admin'] },
                ],
            },
        ],
    },
    {
        section: "Employee",
        items: [
            { title: "Employee Tasks", href: "/employeeTasks", icon: SlidersHorizontal, roles: ['employee'] },
        ],
    },
    {
        section: "Contact Management",
        items: [
            { title: "Contact Entry", href: "/contact-entry", icon: SlidersHorizontal, roles: ['admin', 'staff'] },
            {
                title: "Contact Details",
                icon: ContactRound,
                roles: ['admin', 'staff'],
                children: [
                    { title: "New Contacts", href: "/new-contacts/list", icon: SlidersHorizontal, roles: ['admin'] },
                    { title: "Cancel Contact List", href: "/cancel-contacts/list", icon: SlidersHorizontal, roles: ['admin'] },
                    { title: "All Contact Lists", href: "/all-contacts/list", icon: SlidersHorizontal, roles: ['admin', 'staff', 'demo_presenter'] },
                    { title: "Numbers History", href: "/numbers-histories/list", icon: SlidersHorizontal, roles: ['admin'] },
                ],
            },
        ],
    },
    {
        section: "Reports Management",
        items: [
            {
                title: "Report Details",
                icon: ContactRound,
                roles: ['admin'],
                children: [
                    { title: "Desk Staff List", href: "/employeeList", icon: SlidersHorizontal, roles: ['admin'] },
                    { title: "Sales Staff List", href: "/staffList", icon: SlidersHorizontal, roles: ['admin'] },
                    { title: "Demo Presenter List", href: "/demoPresenterList", icon: SlidersHorizontal, roles: ['admin'] },
                    { title: "Client List (Simple)", href: "/clientListSimple", icon: SlidersHorizontal, roles: ['admin'] },
                    { title: "Area List", href: "/areaList", icon: SlidersHorizontal, roles: ['admin'] },
                ],
            },
        ],
    },
    {
        section: "Website Configuration",
        items: [
            { title: "Site Branding", href: "/logo-upload", icon: ImageUpscale, roles: ['admin'] },
        ],
    },
];

// ✅ Function to check if user has access
function canAccess(roles) {
    if (!roles || roles.length === 0) return true; // open to all if no restriction
    return roles.includes(user.value?.role);
}

function isActiveLink(href?: string) {
    if (!href) return false;
    return currentPath.value === href || currentPath.value.startsWith(`${href}/`);
}

function isGroupActive(item) {
    if (item.href) return isActiveLink(item.href);
    return item.children?.some(child => isActiveLink(child.href));
}

// ✅ Filter items based on current user role
const filteredSidebarItems = computed(() => {
    if (!user.value) return [];

    return sidebarItems
        .map(group => {
            // Check if user can access the section
            if (group.roles && !canAccess(group.roles)) return null;

            // Filter items within the section
            const visibleItems = group.items
                ?.filter(item => canAccess(item.roles))
                .map(item => {
                    if (item.children) {
                        return {
                            ...item,
                            children: item.children.filter(child => canAccess(child.roles)),
                        };
                    }
                    return item;
                });

            if (!visibleItems || visibleItems.length === 0) return null;
            return { ...group, items: visibleItems };
        })
        .filter(Boolean);
});
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        v-if="user.role !== 'employee'"
        class="border-r border-slate-200 bg-white text-slate-800"
    >
        <!-- Header -->
        <SidebarHeader class="border-b border-slate-100 px-4 py-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="h-auto rounded-2xl px-3 py-3 transition hover:bg-slate-50"
                    >
                        <Link href="/dashboard" class="flex items-center gap-3">
                        <div class="flex h-10 w-full items-center justify-center rounded-xl text-white">
                            <AppLogo />
                        </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <!-- Content -->
        <SidebarContent class="px-3 py-4">
            <SidebarMenu class="space-y-5">
                <template v-for="group in filteredSidebarItems" :key="group.section">
                    <div>
                        <h2 class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400 group-data-[collapsible=icon]:hidden">
                            {{ group.section }}
                        </h2>

                        <template v-for="item in group.items" :key="item.title">
                            <!-- Has submenu -->
                            <SidebarMenuItem v-if="item.children && item.children.length > 0">
                                <SidebarMenuButton
                                    @click="openGroups[item.title] = !openGroups[item.title]"
                                    class="mb-1 flex h-11 items-center rounded-xl px-3 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                    :class="isGroupActive(item) ? 'bg-slate-900 text-white hover:bg-slate-900 hover:text-white' : ''"
                                >
                                    <component :is="item.icon" v-if="item.icon" class="h-5 w-5 shrink-0" />
                                    <span class="ml-3 truncate font-medium group-data-[collapsible=icon]:hidden">{{ item.title }}</span>
                                    <ChevronDown
                                        class="ml-auto h-4 w-4 transition-transform duration-200 group-data-[collapsible=icon]:hidden"
                                        :class="{ 'rotate-180': openGroups[item.title] || isGroupActive(item) }"
                                    />
                                </SidebarMenuButton>

                                <SidebarMenuSub
                                    v-if="openGroups[item.title] || isGroupActive(item)"
                                    class="ml-5 mt-1 space-y-1 border-l border-slate-200 pl-3 group-data-[collapsible=icon]:hidden"
                                >
                                    <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                                        <SidebarMenuSubButton
                                            as-child
                                            class="h-9 rounded-lg px-3 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                            :class="isActiveLink(child.href) ? 'bg-slate-100 font-semibold text-slate-900' : ''"
                                        >
                                            <Link :href="child.href">{{ child.title }}</Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </SidebarMenuItem>

                            <!-- Regular item -->
                            <SidebarMenuItem v-else>
                                <SidebarMenuButton
                                    as-child
                                    class="mb-1 h-11 rounded-xl px-3 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                    :class="isActiveLink(item.href) ? 'bg-slate-900 text-white hover:bg-slate-900 hover:text-white' : ''"
                                >
                                    <Link :href="item.href" class="flex items-center">
                                    <component :is="item.icon" v-if="item.icon" class="h-5 w-5 shrink-0" />
                                    <span class="ml-3 truncate font-medium group-data-[collapsible=icon]:hidden">{{ item.title }}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </template>
                    </div>
                </template>
            </SidebarMenu>
        </SidebarContent>

    </Sidebar>

    <slot />
</template>
