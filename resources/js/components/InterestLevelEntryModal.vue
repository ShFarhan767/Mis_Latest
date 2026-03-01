<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import axios from 'axios';
import DataTable from '@/Components/DataTable.vue';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const toast = useToast();
const confirm = useConfirm();

const props = defineProps<{ visible: boolean }>();
const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void;
    (e: 'created', value: any): void;
}>();

// Modal v-model wrapper
const visibleModel = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

// Form fields
const levelName = ref('');
const status = ref('Running');
const editId = ref<number | null>(null);

// Status options
const statuses = [
    { label: 'Running', value: 'Running' },
    { label: 'Disabled', value: 'Disabled' }
];

// Interest levels list
const levels = ref<any[]>([]);

// Fetch all interest levels
const fetchLevels = async () => {
    try {
        const { data } = await axios.get('/api/interest-levels');
        levels.value = data;
    } catch (error) {
        console.error('Failed to fetch interest levels', error);
    }
};

// Close modal and clear form
const close = () => {
    visibleModel.value = false;
    clearForm();
};

const clearForm = () => {
    levelName.value = '';
    status.value = 'Running';
    editId.value = null;
};

// Save or update interest level
const saveOrUpdate = async () => {
    if (!levelName.value.trim()) return;

    try {
        let savedData;
        if (editId.value) {
            await axios.put(`/api/interest-levels/${editId.value}`, {
                level_name: levelName.value,
                status: status.value
            });
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Interest level updated', life: 3000 });
            // Update local levels array
            const index = levels.value.findIndex(l => l.id === editId.value);
            if (index !== -1) {
                levels.value[index].level_name = levelName.value;
                levels.value[index].status = status.value;
            }
        } else {
            const { data } = await axios.post('/api/interest-levels', {
                level_name: levelName.value,
                status: status.value
            });
            savedData = data;
            levels.value.push(data); // Add new level locally
            toast.add({ severity: 'success', summary: 'Created', detail: 'Interest level added', life: 3000 });
        }

        clearForm();
        visibleModel.value = false; // Close modal
        if (savedData) emit('created', savedData); // Notify parent for select update

    } catch (error) {
        console.error('Error saving interest level', error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Something went wrong', life: 3000 });
    }
};

// Edit interest level
const editLevel = (item: any) => {
    editId.value = item.id;
    levelName.value = item.level_name;
    status.value = item.status;
};

// Delete interest level
const deleteLevel = (id: number) => {
    confirm.require({
        message: 'Are you sure you want to delete this interest level?',
        header: 'Confirm',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(`/api/interest-levels/${id}`);
                // Remove from local array
                levels.value = levels.value.filter(l => l.id !== id);
                if (editId.value === id) clearForm();
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Interest level deleted', life: 3000 });
            } catch (error) {
                console.error('Error deleting interest level', error);
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete', life: 3000 });
            }
        }
    });
};

// DataTable columns
const columns = [
    { key: 'sn', label: 'SN', align: 'center' },
    { key: 'level_name', label: 'Interest Level', align: 'center' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'center' },
];

// Map rows for DataTable
const tableRows = computed(() =>
    levels.value.map((item, index) => ({
        sn: index + 1,
        id: item.id,
        level_name: item.level_name,
        status: item.status
    }))
);

// Fetch levels whenever modal opens
watch(visibleModel, (val) => {
    if (val) fetchLevels();
});
</script>

<template>
    <Dialog v-model:visible="visibleModel" header="Interest Level Management" modal :style="{ width: '40rem' }">
        <Toast />
        <ConfirmDialog />

        <div class="space-y-6">
            <!-- Form -->
            <div class="space-y-4 w-80 mx-auto border-b pb-4">
                <div>
                    <label class="font-medium">Interest Level</label>
                    <InputText v-model="levelName" class="w-full mt-1" placeholder="Enter Interest Level" />
                </div>

                <div>
                    <label class="font-medium">Status</label>
                    <Dropdown v-model="status" :options="statuses" optionLabel="label" optionValue="value"
                        class="w-full mt-1" />
                </div>

                <div class="flex justify-end mt-4 space-x-2">
                    <Button label="Cancel" class="p-button-text" @click="close" />
                    <Button :label="editId ? 'Update' : 'Save'" class="p-button-success" icon="pi pi-check"
                        @click="saveOrUpdate" />
                </div>
            </div>

            <!-- DataTable -->
            <DataTable title="Interest Levels" :columns="columns" :rows="tableRows" :onEdit="editLevel"
                :onDelete="deleteLevel" :showSearch="true" />
        </div>
    </Dialog>
</template>
