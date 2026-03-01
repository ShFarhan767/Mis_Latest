<script setup lang="ts">
import { ref, computed, watch } from "vue";
import DataTable from '@/Components/DataTable.vue';
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import axios from "axios";

const toast = useToast();
const confirm = useConfirm();

// Props & emits
const props = defineProps<{ visible: boolean }>();
const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void;
    (e: 'created', value: any): void;
}>();

const visibleModel = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

// Form fields
const name = ref('');
const status = ref('Running');
const editId = ref<number | null>(null);

// Status options
const statuses = [
    { label: 'Running', value: 'Running' },
    { label: 'Disabled', value: 'Disabled' }
];

// Lead sources array
const leadSources = ref<any[]>([]);

// Fetch lead sources
const fetchLeadSources = async () => {
    try {
        const { data } = await axios.get("/api/lead-sources");
        leadSources.value = data;
    } catch (error) {
        console.error(error);
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch lead sources", life: 2500 });
    }
};

// Close modal
const close = () => {
    visibleModel.value = false;
    clearForm();
};

// Clear form
const clearForm = () => {
    name.value = '';
    status.value = 'Running';
    editId.value = null;
};

// Save or update lead source
const saveLeadSource = async () => {
    if (!name.value.trim()) return;

    try {
        if (editId.value !== null) {
            // Update
            await axios.put(`/api/lead-sources/${editId.value}`, { name: name.value, status: status.value });
            const index = leadSources.value.findIndex(l => l.id === editId.value);
            if (index !== -1) {
                leadSources.value[index] = { ...leadSources.value[index], name: name.value, status: status.value };
            }
            toast.add({ severity: "success", summary: "Updated", detail: "Lead source updated", life: 2500 });
        } else {
            // Create
            const { data } = await axios.post("/api/lead-sources", { name: name.value, status: status.value });
            leadSources.value.push(data);
            toast.add({ severity: "success", summary: "Created", detail: "Lead source added", life: 2500 });
            emit("created", data);
        }

        clearForm();
        visibleModel.value = false;
    } catch (error) {
        console.error(error);
        toast.add({ severity: "error", summary: "Error", detail: "Failed to save lead source", life: 2500 });
    }
};

// Edit lead source
const editLeadSource = (item: any) => {
    editId.value = item.id;
    name.value = item.name;
    status.value = item.status;
    visibleModel.value = true;
};

// Delete lead source
const deleteLeadSource = (id: number) => {
    confirm.require({
        message: "Are you sure you want to delete?",
        header: "Confirm",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: async () => {
            try {
                await axios.delete(`/api/lead-sources/${id}`);
                leadSources.value = leadSources.value.filter(l => l.id !== id);
                toast.add({ severity: "success", summary: "Deleted", detail: "Lead source deleted", life: 2500 });
            } catch (error) {
                console.error(error);
                toast.add({ severity: "error", summary: "Error", detail: "Failed to delete lead source", life: 2500 });
            }
        },
    });
};

// DataTable columns
const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "name", label: "Lead Source", align: "center" },
    { key: "status", label: "Status", align: "center" },
    { key: "actions", label: "Actions", align: "center" },
];

// Table rows
const tableRows = computed(() =>
    leadSources.value.map((item, index) => ({
        sn: index + 1,
        id: item.id,
        name: item.name,
        status: item.status,
    }))
);

// Fetch lead sources when modal opens
watch(visibleModel, (val) => {
    if (val) fetchLeadSources();
});
</script>

<template>
    <Toast />
    <ConfirmDialog />

    <!-- Modal -->
    <Dialog v-model:visible="visibleModel" modal header="Lead Source Management" :style="{ width: '40rem' }">
        <div class="space-y-4 w-80 mx-auto border-b pb-4">
            <div>
                <label class="font-semibold block mb-1">Name</label>
                <InputText v-model="name" class="w-full" placeholder="Enter name" />
            </div>

            <div>
                <label class="font-semibold block mb-1">Status</label>
                <Dropdown v-model="status" :options="statuses" optionLabel="label" optionValue="value" class="w-full" />
            </div>

            <div class="flex justify-end mt-4 space-x-2">
                <Button label="Cancel" class="p-button-text" @click="close" />
                <Button label="Save" icon="pi pi-save" class="p-button-success" @click="saveLeadSource" />
            </div>
        </div>

        <!-- Custom DataTable -->
        <DataTable 
            title="Lead Sources" 
            :columns="columns" 
            :rows="tableRows" 
            :onEdit="editLeadSource"
            :onDelete="deleteLeadSource" 
            :showSearch="true" 
        />

    </Dialog>

</template>
