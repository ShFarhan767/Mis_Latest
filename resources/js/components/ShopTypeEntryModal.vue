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
const shopTypeName = ref('');
const status = ref('Running');
const editId = ref<number | null>(null);

// Status options
const statuses = [
    { label: 'Running', value: 'Running' },
    { label: 'Disabled', value: 'Disabled' }
];

// Shop types list
const shopTypes = ref<any[]>([]);

// Fetch shop types
const fetchShopTypes = async () => {
    try {
        const { data } = await axios.get('/api/shop-types');
        shopTypes.value = Array.isArray(data) ? data : [];
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch shop types', life: 3000 });
    }
};

// Close modal and clear form
const close = () => {
    visibleModel.value = false;
    clearForm();
};

const clearForm = () => {
    shopTypeName.value = '';
    status.value = 'Running';
    editId.value = null;
};

// Save or update
const saveOrUpdate = async () => {
    if (!shopTypeName.value.trim()) return;

    try {
        let savedData;
        if (editId.value) {
            await axios.put(`/api/shop-types/${editId.value}`, {
                name: shopTypeName.value,
                status: status.value
            });
            const index = shopTypes.value.findIndex(s => s.id === editId.value);
            if (index !== -1) {
                shopTypes.value[index].name = shopTypeName.value;
                shopTypes.value[index].status = status.value;
            }
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Shop type updated', life: 3000 });
        } else {
            const { data } = await axios.post('/api/shop-types', {
                name: shopTypeName.value,
                status: status.value
            });
            savedData = data;
            shopTypes.value.push(data);
            toast.add({ severity: 'success', summary: 'Created', detail: 'Shop type added', life: 3000 });
        }

        clearForm();
        visibleModel.value = false;
        if (savedData) emit('created', savedData);
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save', life: 3000 });
    }
};

// Edit shop type
const editShopType = (item: any) => {
    editId.value = item.id;
    shopTypeName.value = item.name;
    status.value = item.status;
};

// Delete shop type
const deleteShopType = (id: number) => {
    confirm.require({
        message: 'Are you sure you want to delete this shop type?',
        header: 'Confirm',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(`/api/shop-types/${id}`);
                shopTypes.value = shopTypes.value.filter(s => s.id !== id);
                if (editId.value === id) clearForm();
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Shop type deleted', life: 3000 });
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete', life: 3000 });
            }
        }
    });
};

// DataTable columns
const columns = [
    { key: 'sn', label: 'SN', align: 'center' },
    { key: 'name', label: 'Shop Type', align: 'center' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'center' }
];

// Map rows for DataTable
const tableRows = computed(() =>
    shopTypes.value.map((item, index) => ({
        sn: index + 1,
        id: item.id,
        name: item.name,
        status: item.status
    }))
);

// Fetch shop types whenever modal opens
watch(visibleModel, (val) => {
    if (val) fetchShopTypes();
});
</script>

<template>
    <Dialog v-model:visible="visibleModel" header="Shop Type Management" modal :style="{ width: '40rem' }">
        <Toast />
        <ConfirmDialog />

        <div class="space-y-6">
            <!-- Form -->
            <div class="space-y-4 w-80 mx-auto border-b pb-4">
                <div>
                    <label class="font-medium">Shop Type</label>
                    <InputText v-model="shopTypeName" class="w-full mt-1" placeholder="Enter Shop Type" />
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
            <DataTable title="Shop Types" :columns="columns" :rows="tableRows" :onEdit="editShopType"
                :onDelete="deleteShopType" :showSearch="true" />
        </div>
    </Dialog>
</template>
