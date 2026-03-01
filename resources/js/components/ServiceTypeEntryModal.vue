<script setup lang="ts">
import { ref, computed, watch } from "vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

const toast = useToast();
const confirm = useConfirm();

// Props & emits for reusability
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

// Form state
const serviceTypeName = ref("");
const status = ref("Running");
const editId = ref<number | null>(null);

// Status options
const statuses = [
    { label: "Running", value: "Running" },
    { label: "Disabled", value: "Disabled" },
];

// Data list
const serviceTypes = ref<any[]>([]);

// Fetch service types
const fetchServiceTypes = async () => {
    try {
        const { data } = await axios.get("/api/service-types");
        serviceTypes.value = Array.isArray(data) ? data : [];
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch service types', life: 3000 });
    }
};

// Close modal and clear form
const close = () => {
    visibleModel.value = false;
    clearForm();
};

const clearForm = () => {
    serviceTypeName.value = '';
    status.value = 'Running';
    editId.value = null;
};

// Save or update
const saveOrUpdate = async () => {
    if (!serviceTypeName.value.trim()) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'Service Type name is required', life: 3000 });
        return;
    }

    try {
        let savedData;
        if (editId.value) {
            // UPDATE
            await axios.put(`/api/service-types/${editId.value}`, {
                service_type_name: serviceTypeName.value,
                status: status.value
            });

            const index = serviceTypes.value.findIndex(s => s.id === editId.value);
            if (index !== -1) {
                serviceTypes.value[index].service_type_name = serviceTypeName.value;
                serviceTypes.value[index].status = status.value;
            }

            toast.add({ severity: 'success', summary: 'Updated', detail: 'Service type updated', life: 3000 });
        } else {
            // CREATE
            const { data } = await axios.post("/api/service-types", {
                service_type_name: serviceTypeName.value,
                status: status.value
            });
            savedData = data;
            serviceTypes.value.push(data);

            toast.add({ severity: 'success', summary: 'Created', detail: 'Service type added', life: 3000 });
        }

        clearForm();
        visibleModel.value = false;
        if (savedData) emit('created', savedData);
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save service type', life: 3000 });
    }
};

// Edit service type
const editServiceType = (item: any) => {
    editId.value = item.id;
    serviceTypeName.value = item.service_type_name;
    status.value = item.status;
};

// Delete service type
const deleteServiceType = (id: number) => {
    confirm.require({
        message: 'Are you sure you want to delete this service type?',
        header: 'Confirm',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(`/api/service-types/${id}`);
                serviceTypes.value = serviceTypes.value.filter(s => s.id !== id);
                if (editId.value === id) clearForm();
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Service type deleted', life: 3000 });
            } catch {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete', life: 3000 });
            }
        }
    });
};

// DataTable columns
const columns = [
    { key: 'sn', label: 'SN', align: 'center' },
    { key: 'service_type_name', label: 'Service Type', align: 'center' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'center' }
];

// Map rows for DataTable
const tableRows = computed(() =>
    serviceTypes.value.map((item, index) => ({
        sn: index + 1,
        id: item.id,
        service_type_name: item.service_type_name,
        status: item.status
    }))
);

// Fetch whenever modal opens
watch(visibleModel, (val) => {
    if (val) fetchServiceTypes();
});
</script>

<template>
    <Dialog v-model:visible="visibleModel" header="Service Type Management" modal :style="{ width: '40rem' }">
        <Toast />
        <ConfirmDialog />

        <div class="space-y-6">
            <!-- Form -->
            <div class="space-y-4 w-80 mx-auto border-b pb-4">
                <div>
                    <label class="font-medium">Service Type Name</label>
                    <InputText v-model="serviceTypeName" class="w-full mt-1" placeholder="Enter service type name" />
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
            <DataTable title="Service Types" :columns="columns" :rows="tableRows" :onEdit="editServiceType"
                :onDelete="deleteServiceType" :showSearch="true" />
        </div>
    </Dialog>
</template>
