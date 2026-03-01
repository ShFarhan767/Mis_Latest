<script setup lang="ts">
import { ref, watch, computed } from "vue";
import Card from "primevue/card";
import Button from "primevue/button";

interface Column {
    key: string;
    label: string;
    align?: "left" | "center" | "right";
    type?: string; // 👈 added for modal support
}

const props = defineProps<{
    title: string;
    columns: Column[];
    rows?: any[];
    onEdit?: (row: any) => void;
    onDelete?: (id: any) => void;
    showSearch?: boolean;
}>();

const emit = defineEmits(["openModal"]); // 👈 added event emitter

const safeRows = computed(() => props.rows || []);

const searchQuery = ref("");

const filteredRows = computed(() => {
    if (!searchQuery.value) return safeRows.value;
    return safeRows.value.filter((row) =>
        Object.values(row)
            .join(" ")
            .toLowerCase()
            .includes(searchQuery.value.toLowerCase())
    );
});
</script>

<template>
    <Card>
        <template #title>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ title }}</h2>
                <input v-if="props.showSearch" v-model="searchQuery" type="text" placeholder="Search..."
                    class="border rounded px-2 py-1 text-sm" />
            </div>
        </template>

        <template #content>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th v-for="col in columns" :key="col.key" :class="[
                                'px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider',
                                col.align === 'center' ? 'text-center' :
                                    col.align === 'right' ? 'text-right' : 'text-left'
                            ]">

                                <!-- Use header slot if exists -->
                                <slot :name="`header-${col.key}`">
                                    {{ col.label }}
                                </slot>

                            </th>
                        </tr>
                    </thead>

                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(row, index) in filteredRows" :key="row.id ?? index"
                            class="hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200">
                            <td v-for="col in columns" :key="col.key" :class="[
                                'px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300',
                                col.align === 'center'
                                    ? 'text-center'
                                    : col.align === 'right'
                                        ? 'text-right'
                                        : 'text-left'
                            ]">
                                <!-- 🔥 Modal Button Support -->
                                <template v-if="col.type === 'modal'">
                                    <button
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200"
                                        @click="emit('openModal', col.label, row[col.key])">
                                        View
                                    </button>
                                </template>

                                <!-- ✔ Slot support -->
                                <template v-else-if="$slots[`cell-${col.key}`]">
                                    <slot :name="`cell-${col.key}`" :row="row" />
                                </template>

                                <!-- ✔ Default cell -->
                                <template v-else-if="col.key === 'actions'">
                                    <Button v-if="onEdit" icon="pi pi-pencil"
                                        class="p-button-rounded p-button-success mr-2" @click="onEdit(row)" />
                                    <Button v-if="onDelete" icon="pi pi-trash" class="p-button-rounded p-button-danger"
                                        @click="onDelete(row.id)" />
                                </template>

                                <template v-else>
                                    {{ row[col.key] }}
                                </template>
                            </td>
                        </tr>

                        <tr v-if="filteredRows.length === 0">
                            <td :colspan="columns.length"
                                class="px-6 py-4 text-center text-gray-400 dark:text-gray-500">
                                No data found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </Card>
</template>
