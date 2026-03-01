<script setup lang="ts">
import DataTable from "./DataTable.vue";
import Button from "primevue/button";

const props = defineProps<{
    rows: any[];
    columns: any[];
    staffStatusOptions: string[];
    userRole: string;
    userId: number;
}>();

const emit = defineEmits([
    "openHistory",
    "openNote",
    "openModal",
    "editCustomer",
]);

const canEdit = (row: any) => {
    if (props.userRole === "admin") return true;

    // staff can edit only assigned customer
    return row.assigned_staff_id === props.userId;
};
</script>

<template>
    <DataTable title="Customers" :columns="columns" :rows="rows" :showSearch="false"
        @openModal="$emit('openModal', $event)">
        <!-- Name -->
        <template #cell-name="{ row }">
            <div class="flex flex-col">
                <span class="font-semibold text-gray-800">{{ row.name }}</span>
                <span class="text-sm text-gray-500">({{ row.designation || '-' }})</span>
                <span class="text-sm text-gray-500">{{ row.staff_status || '-' }}</span>
            </div>
        </template>

        <!-- Numbers -->
        <template #cell-numbers="{ row }">
            <div class="flex flex-col text-sm">
                <span v-for="(n, i) in row.numbers_text.split(',')" :key="i">{{ n }}</span>
            </div>
        </template>

        <!-- Actions -->
        <template #cell-actions="{ row }">
            <div class="flex gap-2 justify-center">

                <!-- History -->
                <Button icon="pi pi-history" size="small" severity="secondary" @click="emit('openHistory', row)" />

                <!-- Add Note -->
                <Button icon="pi pi-plus" size="small" severity="info" @click="emit('openNote', row)" />

                <!-- EDIT (ROLE BASED) -->
                <Button v-if="canEdit(row)" icon="pi pi-pencil" size="small" severity="warning"
                    @click="emit('editCustomer', row)" />

                <!-- NO ACCESS LABEL -->
                <span v-else class="text-xs text-gray-400 italic">
                    No access
                </span>
            </div>
        </template>

        <!-- Staff Status -->
        <template #cell-staff_status="{ row }">
            <select v-model="row.staff_status" class="border rounded px-2 py-1"
                @change="$emit('updateStaffStatus', row.id, row.staff_status)">
                <option v-for="status in staffStatusOptions" :key="status" :value="status">
                    {{ status }}
                </option>
            </select>
        </template>
    </DataTable>
</template>
