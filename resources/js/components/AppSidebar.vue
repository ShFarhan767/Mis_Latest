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

const { props } = usePage();
const user = computed(() => props.auth?.user || null);

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
                        item.children = item.children.filter(child => canAccess(child.roles));
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
    <Sidebar collapsible="icon" variant="inset" v-if="user.role !== 'employee'">
        <!-- Header -->
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                        <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <!-- Content -->
        <SidebarContent>
            <SidebarMenu>
                <template v-for="group in filteredSidebarItems" :key="group.section">
                    <h2 class="px-4 pt-4 pb-2 text-gray-500 uppercase text-xs font-semibold">
                        {{ group.section }}
                    </h2>

                    <template v-for="item in group.items" :key="item.title">
                        <!-- Has submenu -->
                        <SidebarMenuItem v-if="item.children && item.children.length > 0">
                            <SidebarMenuButton @click="openGroups[item.title] = !openGroups[item.title]"
                                class="flex items-center">
                                <component :is="item.icon" v-if="item.icon" class="w-5 h-5" />
                                <span class="ml-2">{{ item.title }}</span>
                                <ChevronDown class="ml-auto w-4 h-4 transition-transform duration-200"
                                    :class="{ 'rotate-180': openGroups[item.title] }" />
                            </SidebarMenuButton>

                            <SidebarMenuSub v-if="openGroups[item.title]">
                                <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                                    <SidebarMenuSubButton as-child>
                                        <Link :href="child.href">{{ child.title }}</Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </SidebarMenuItem>

                        <!-- Regular item -->
                        <SidebarMenuItem v-else>
                            <SidebarMenuButton as-child>
                                <Link :href="item.href">
                                <component :is="item.icon" v-if="item.icon" class="w-5 h-5" />
                                <span class="ml-2">{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </template>
                </template>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter />
    </Sidebar>

    <slot />
</template>
