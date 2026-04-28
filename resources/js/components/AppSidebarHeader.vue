<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { Bell, ChevronDown } from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

// 🔥 Get user role from Inertia
const page = usePage();
const userRole = page.props.auth?.user?.role ?? null;
const currentPath = computed(() => {
    const rawUrl = typeof page.url === 'string' ? page.url : '';
    return rawUrl.split('?')[0] || window.location.pathname;
});

const emit = defineEmits(["search"]);
const searchText = ref("");
const isOpen = ref(false);
const showDemoNotifications = ref(false);
const demoNotificationItems = ref<any[]>([]);
const notificationsLoading = ref(false);
const notificationTimer = ref<ReturnType<typeof setInterval> | null>(null);

const wrapperRef = ref<HTMLElement | null>(null);
const notificationRef = ref<HTMLElement | null>(null);

const canSeeDemoNotifications = computed(() =>
    userRole === 'admin' || userRole === 'staff'
);

const totalDemoUnread = computed(() =>
    demoNotificationItems.value.reduce((sum: number, item: any) => sum + Number(item.unreadCount ?? 0), 0)
);

const getDemoTabKey = (customer: any) => {
    if ((customer?.demo_status ?? null) === 'Pending') return 'pending';
    if (customer?.staff_status === 'Demo Done' || customer?.demo_status === 'Done') return 'done';
    if (customer?.staff_status === 'Cancelled' || customer?.demo_status === 'Cancelled') return 'cancelled';
    return 'assigned';
};

const getDemoTabLabel = (tabKey: string) => {
    if (userRole === 'demo_presenter') {
        if (tabKey === 'pending') return 'Pending';
        if (tabKey === 'done') return 'Done';
        if (tabKey === 'cancelled') return 'Cancelled';
        return 'Assigned';
    }

    if (tabKey === 'pending') return 'Demo Pending';
    if (tabKey === 'done') return 'Demo Done';
    if (tabKey === 'cancelled') return 'Demo Cancelled';
    return 'Need To Show Demo';
};

const fetchDemoNotifications = async () => {
    if (!canSeeDemoNotifications.value) {
        demoNotificationItems.value = [];
        return;
    }

    notificationsLoading.value = true;
    try {
        const { data } = await axios.get('/api/customers');
        const customers = Array.isArray(data?.customers) ? data.customers : [];

        demoNotificationItems.value = customers
            .filter((customer: any) => Number(customer?.demo_notes_unread ?? 0) > 0)
            .map((customer: any) => {
                const tabKey = getDemoTabKey(customer);
                return {
                    id: customer.id,
                    name: customer.name || 'Customer',
                    unreadCount: Number(customer.demo_notes_unread ?? 0),
                    shop_type: customer.shop_type || 'N/A',
                    tabKey,
                    tabLabel: getDemoTabLabel(tabKey),
                };
            })
            .sort((left: any, right: any) => {
                if (right.unreadCount !== left.unreadCount) {
                    return right.unreadCount - left.unreadCount;
                }

                return right.id - left.id;
            });
    } catch {
        demoNotificationItems.value = [];
    } finally {
        notificationsLoading.value = false;
    }
};

const toggleDemoNotifications = async () => {
    showDemoNotifications.value = !showDemoNotifications.value;
    if (showDemoNotifications.value && demoNotificationItems.value.length === 0) {
        await fetchDemoNotifications();
    }
};

const openDemoNotification = (item: any) => {
    if (!item?.id) return;

    showDemoNotifications.value = false;

    if (currentPath.value === '/all-contacts/list') {
        window.dispatchEvent(new CustomEvent('open-demo-note-notification', {
            detail: {
                customerId: item.id,
                demoTabKey: item.tabKey,
            },
        }));
        return;
    }

    router.visit(`/all-contacts/list?openDemoNotes=1&demoNoteCustomer=${item.id}&demoNoteTab=${item.tabKey}`);
};

const resetSearch = () => {
    searchText.value = "";
    emit("search", "");
};

// Close search box if clicked outside and field is empty
const handleClickOutside = (e: MouseEvent) => {
    if (wrapperRef.value && !wrapperRef.value.contains(e.target as Node)) {
        if (searchText.value === "") {
            isOpen.value = false;
        }
    }

    if (notificationRef.value && !notificationRef.value.contains(e.target as Node)) {
        showDemoNotifications.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    void fetchDemoNotifications();
    notificationTimer.value = setInterval(() => {
        void fetchDemoNotifications();
    }, 10000);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
    if (notificationTimer.value) {
        clearInterval(notificationTimer.value);
        notificationTimer.value = null;
    }
});
</script>

<template>
    <header
        class="sticky top-0 z-30 flex min-h-16 shrink-0 items-center border-b border-slate-200 bg-white px-4 text-slate-900 shadow-none md:px-4 md:bg-white md:text-slate-900 md:shadow-none max-md:border-white/60 max-md:bg-gradient-to-r max-md:from-cyan-500 max-md:via-sky-500 max-md:to-emerald-500 max-md:text-white max-md:shadow-[0_10px_30px_-18px_rgba(14,165,233,0.85)]"
    >

        <!-- Left: Sidebar + Breadcrumbs -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-2 py-1 md:bg-transparent md:px-0 md:py-0 md:ring-0">
                <SidebarTrigger
                    class="-ml-1 h-10 w-10 bg-white text-sky-700 transition hover:bg-sky-50 md:h-8 md:w-8 md:bg-transparent md:text-slate-700 md:shadow-none md:hover:bg-slate-100"
                />
            </div>

            <div v-if="breadcrumbs && breadcrumbs.length > 0" class="hidden md:block">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>

        <!-- Right: Search + User -->
        <div class="ml-auto flex items-center gap-2 relative capitalize">

            <!-- 🔥 Search Component -->
            <!-- <div v-if="userRole !== 'employee'" ref="wrapperRef" class="relative">
                <transition name="fade">
                    <input v-if="isOpen" v-model="searchText" @input="emit('search', searchText)" type="text"
                        placeholder="Search clients..."
                        class="w-64 md:w-80 border rounded-full py-2 pl-10 pr-10 bg-white shadow-sm focus:outline-none" />
                </transition>

                <button @click="isOpen = true"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-800">
                    <Search class="w-5 h-5" />
                </button>

                <button v-if="searchText.length > 0" @click="resetSearch"
                    class="absolute right-70 top-1/2 -translate-y-1/2 text-gray-500 hover:text-black">
                    <X class="w-5 h-5" />
                </button>
            </div> -->

            <div v-if="canSeeDemoNotifications" ref="notificationRef" class="relative">
                <button
                    type="button"
                    class="relative inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-slate-700 transition hover:bg-slate-50 max-md:border-white/30 max-md:bg-white/15 max-md:text-white max-md:hover:bg-white/20"
                    @click.stop="toggleDemoNotifications"
                >
                    <Bell class="h-4 w-4" />
                    <span class="hidden text-sm font-medium md:inline">Notes</span>
                    <ChevronDown class="h-4 w-4" :class="showDemoNotifications ? 'rotate-180' : ''" />
                    <span
                        v-if="totalDemoUnread > 0"
                        class="absolute -right-2 -top-2 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white"
                    >
                        {{ totalDemoUnread }}
                    </span>
                </button>

                <div
                    v-if="showDemoNotifications"
                    class="absolute right-0 top-12 z-50 w-[22rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl max-md:fixed max-md:left-3 max-md:right-3 max-md:top-16 max-md:w-auto"
                >
                    <div class="border-b border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Demo Note Notifications</h3>
                            </div>
                            <span
                                v-if="totalDemoUnread > 0"
                                class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-semibold text-red-700"
                            >
                                {{ totalDemoUnread }} unread
                            </span>
                        </div>
                    </div>

                    <div class="max-h-[24rem] overflow-y-auto">
                        <div v-if="notificationsLoading" class="px-4 py-6 text-center text-sm text-slate-500">
                            Loading notifications...
                        </div>

                        <div v-else-if="demoNotificationItems.length === 0" class="px-4 py-6 text-center text-sm text-slate-500">
                            No unread demo-note messages.
                        </div>

                        <template v-else>
                            <button
                                v-for="item in demoNotificationItems"
                                :key="item.id"
                                type="button"
                                class="flex w-full items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition hover:bg-slate-50"
                                @click="openDemoNotification(item)"
                            >
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="truncate text-sm font-semibold text-slate-900">{{ item.name }}</span>
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                            {{ item.tabLabel }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500 font-semibold">Shop Type: {{ item.shop_type }}</p>
                                </div>

                                <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-red-600 px-2 py-1 text-[11px] font-semibold text-white">
                                    {{ item.unreadCount }}
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- User -->
            <NavUser />
        </div>

    </header>
</template>
