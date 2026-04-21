<script setup lang="ts">
import { ref, computed } from 'vue'
import { CircleUserRound } from 'lucide-vue-next'
import type { User, Employee } from '@/types'

interface Props {
    user?: User
    employee?: Employee
    showEmail?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
})

const person = computed(() => props.user ?? props.employee)

const isOpen = ref(false)

const toggleDropdown = () => {
    isOpen.value = !isOpen.value
}
</script>

<template>
    <div class="relative" v-if="person">
        <!-- USER ICON BUTTON -->
         <div class="flex items-center gap-2">
            <span class="font-medium text-gray-700 dark:text-gray-200">
                {{ person.name }}
            </span>
            <button @click="toggleDropdown"
                class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 shadow-md transition cursor-pointer">
                <CircleUserRound class="w-6 h-6 text-gray-700 dark:text-gray-200" />
            </button>
         </div>

        <!-- DROPDOWN CONTENT -->
        <div v-if="isOpen"
            class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 z-50 border border-gray-100 dark:border-gray-700">
            <p class="font-semibold text-gray-800 dark:text-gray-100">
                {{ person.name }}
            </p>

            <hr class="my-3 border-gray-200 dark:border-gray-700" />

            <slot />
            <!-- You can put Logout, Profile, Settings here -->
        </div>
    </div>

    <div v-else>
        <span class="text-sm text-muted-foreground">No user logged in</span>
    </div>
</template>
