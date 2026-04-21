<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import Multiselect from 'vue-multiselect';
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
const selectedCountry = ref<any>(null);
const areaName = ref('');
const status = ref('Running');
const editId = ref<number | null>(null);

// Options
const statuses = [
    { label: 'Running', value: 'Running' },
    { label: 'Disabled', value: 'Disabled' }
];

const currentUser = ref<any | null>(null);

const fetchCurrentUser = async () => {
    try {
        const { data } = await axios.get('/api/current-user');
        currentUser.value = data;
    } catch { }
};

onMounted(() => fetchCurrentUser());

const countryOptions = ref<any[]>([]);
const areaList = ref<any[]>([]);

// Fetch countries
const fetchCountries = async () => {
    try {
        const { data } = await axios.get('/api/countries');
        countryOptions.value = data.map((c: any) => ({
            id: c.id,
            name: c.country_name,
            status: c.status
        }));
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch countries', life: 3000 });
    }
};

// Fetch areas
const fetchAreas = async () => {
    try {
        const { data } = await axios.get('/api/areas');
        areaList.value = data.map((a: any) => ({
            id: a.id,
            name: a.area_name,
            country: a.country_name,
            status: a.status,
            created_by: a.created_by
        }));
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch areas', life: 3000 });
    }
};

// Clear form
const clearForm = () => {
    selectedCountry.value = null;
    areaName.value = '';
    status.value = 'Running';
    editId.value = null;
};

// Close modal
const close = () => {
    visibleModel.value = false;
    clearForm();
};

// Save or update area
const saveOrUpdate = async () => {
    if (!selectedCountry.value || !areaName.value.trim()) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'Please select country and enter area name', life: 3000 });
        return;
    }

    try {
        if (editId.value) {
            await axios.put(`/api/areas/${editId.value}`, {
                area_name: areaName.value,
                country_name: selectedCountry.value.name,
                status: status.value
            });
            const index = areaList.value.findIndex(a => a.id === editId.value);
            if (index !== -1) {
                areaList.value[index].name = areaName.value;
                areaList.value[index].country = selectedCountry.value.name;
                areaList.value[index].status = status.value;
            }
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Area updated', life: 3000 });
        } else {
            const { data } = await axios.post('/api/areas', {
                area_name: areaName.value,
                country_name: selectedCountry.value.name,
                status: status.value
            });
            areaList.value.push({
                id: data.id,
                name: data.area_name,
                country: data.country_name,
                status: data.status
            });

            // 🔥 notify parent
            emit('created', {
                area_name: data.area_name,
                country_name: data.country_name
            });

            toast.add({ severity: 'success', summary: 'Created', detail: 'Area added', life: 3000 });
        }

        clearForm();
        fetchAreas();
        visibleModel.value = false;
    } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save area', life: 3000 });
    }
};

// Edit area
const editArea = (item: any) => {
    editId.value = item.id;
    areaName.value = item.name;
    status.value = item.status;

    const country = countryOptions.value.find(c => c.name === item.country);
    selectedCountry.value = country || null;
    visibleModel.value = true;
};

// Delete area
const deleteArea = (id: number) => {
    confirm.require({
        message: 'Are you sure you want to delete this area?',
        header: 'Confirm',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(`/api/areas/${id}`);
                areaList.value = areaList.value.filter(a => a.id !== id);
                if (editId.value === id) clearForm();
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Area deleted', life: 3000 });
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete area', life: 3000 });
            }
        }
    });
};

// DataTable columns
const columns = [
    { key: 'sn', label: 'SN', align: 'center' },
    { key: 'country', label: 'Country', align: 'center' },
    { key: 'name', label: 'Area Name', align: 'center' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'center' }
];

// Map rows for DataTable
const tableRows = computed(() =>
    areaList.value.map((item, index) => ({
        sn: index + 1,
        id: item.id,
        country: item.country,
        name: item.name,
        status: item.status
    }))
);

// Fetch on modal open
watch(visibleModel, (val) => {
    if (val) {
        fetchCountries();
        fetchAreas();
        clearForm();
    }
});
</script>

<template>
    <Dialog v-model:visible="visibleModel" header="Area Management" modal :style="{ width: '40rem' }">
        <Toast />
        <ConfirmDialog />

        <div class="space-y-6">
            <!-- Form -->
            <div class="space-y-4 w-80 mx-auto border-b pb-4">
                <div>
                    <Multiselect v-model="selectedCountry" :options="countryOptions" label="name" track-by="name"
                        placeholder="Select Country" />
                </div>

                <div>
                    <label class="font-medium">Area Name</label>
                    <InputText v-model="areaName" class="w-full mt-1" placeholder="Enter Area Name" />
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
            <DataTable :columns="columns" :rows="tableRows" :onEdit="editArea" :onDelete="deleteArea"
                :rowDisabled="row => currentUser?.role === 'staff' && row.created_by !== currentUser?.id" />
        </div>
    </Dialog>
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";
</style>
