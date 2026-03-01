<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import DataTable from '@/Components/DataTable.vue';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from 'axios';

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
const offerName = ref('');
const status = ref('Running');
const editId = ref<number | null>(null);

// Status options
const statuses = [
    { label: 'Running', value: 'Running' },
    { label: 'Disabled', value: 'Disabled' }
];

// Offer connects list
const offers = ref<any[]>([]);

// Fetch offers
const fetchOffers = async () => {
    try {
        const { data } = await axios.get('/api/offer-connects');
        offers.value = Array.isArray(data) ? data : [];
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch offers', life: 3000 });
    }
};

// Close modal and clear form
const close = () => {
    visibleModel.value = false;
    clearForm();
};

const clearForm = () => {
    offerName.value = '';
    status.value = 'Running';
    editId.value = null;
};

// Save or update offer
const saveOrUpdate = async () => {
    if (!offerName.value.trim()) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'Offer name cannot be empty', life: 3000 });
        return;
    }

    try {
        let savedData;
        if (editId.value) {
            await axios.put(`/api/offer-connects/${editId.value}`, {
                name: offerName.value,
                status: status.value
            });
            const index = offers.value.findIndex(o => o.id === editId.value);
            if (index !== -1) {
                offers.value[index].name = offerName.value;
                offers.value[index].status = status.value;
            }
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Offer updated', life: 3000 });
        } else {
            const { data } = await axios.post('/api/offer-connects', {
                name: offerName.value,
                status: status.value
            });
            savedData = data;
            offers.value.push(data);
            toast.add({ severity: 'success', summary: 'Created', detail: 'Offer added', life: 3000 });
        }

        clearForm();
        visibleModel.value = false;
        if (savedData) emit('created', savedData);
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save offer', life: 3000 });
    }
};

// Edit offer
const editOffer = (item: any) => {
    editId.value = item.id;
    offerName.value = item.name;
    status.value = item.status;
};

// Delete offer
const deleteOffer = (id: number) => {
    confirm.require({
        message: 'Are you sure you want to delete this offer?',
        header: 'Confirm',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(`/api/offer-connects/${id}`);
                offers.value = offers.value.filter(o => o.id !== id);
                if (editId.value === id) clearForm();
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Offer deleted', life: 3000 });
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete offer', life: 3000 });
            }
        }
    });
};

// DataTable columns
const columns = [
    { key: 'sn', label: 'SN', align: 'center' },
    { key: 'name', label: 'Offer Name', align: 'center' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'center' }
];

// Map rows for DataTable
const tableRows = computed(() =>
    offers.value.map((item, index) => ({
        sn: index + 1,
        id: item.id,
        name: item.name,
        status: item.status
    }))
);

// Fetch offers whenever modal opens
watch(visibleModel, (val) => {
    if (val) fetchOffers();
});
</script>

<template>
    <Dialog v-model:visible="visibleModel" header="Offer Connect Management" modal :style="{ width: '40rem' }">
        <Toast />
        <ConfirmDialog />

        <div class="space-y-6">
            <!-- Form -->
            <div class="space-y-4 w-80 mx-auto border-b pb-4">
                <div>
                    <label class="font-medium">Offer Name</label>
                    <InputText v-model="offerName" class="w-full mt-1" placeholder="Enter Offer Name" />
                </div>

                <div>
                    <label class="font-medium">Status</label>
                    <Dropdown v-model="status" :options="statuses" optionLabel="label" optionValue="value" class="w-full mt-1" />
                </div>

                <div class="flex justify-end mt-4 space-x-2">
                    <Button label="Cancel" class="p-button-text" @click="close" />
                    <Button :label="editId ? 'Update' : 'Save'" class="p-button-success" icon="pi pi-check" @click="saveOrUpdate" />
                </div>
            </div>

            <!-- DataTable -->
            <DataTable title="Offer Connects" :columns="columns" :rows="tableRows" :onEdit="editOffer" :onDelete="deleteOffer" :showSearch="true" />
        </div>
    </Dialog>
</template>
