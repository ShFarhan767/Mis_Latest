<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Menu } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
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
console.log("User Role:", userRole);

const emit = defineEmits(["search"]);
const searchText = ref("");
const isOpen = ref(false);

const wrapperRef = ref<HTMLElement | null>(null);

const resetSearch = () => {
    searchText.value = "";
    emit("search", "");
};

// Close search box if clicked outside and field is empty
const handleClickOutside = (e: MouseEvent) => {
    if (!wrapperRef.value) return;

    if (!wrapperRef.value.contains(e.target as Node)) {
        if (searchText.value === "") {
            isOpen.value = false;
        }
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
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

            <!-- User -->
            <NavUser />
        </div>

    </header>
</template>
