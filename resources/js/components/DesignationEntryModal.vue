<script setup lang="ts">
import { ref, computed, watch } from "vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import Button from "primevue/button";
import axios from "axios";
import DataTable from "@/Components/DataTable.vue"; // your custom DataTable
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

const toast = useToast();
const confirm = useConfirm();

const props = defineProps({
    visible: Boolean
});
const emits = defineEmits(["update:visible"]);

// Modal v-model wrapper
const visibleModel = computed({
    get: () => props.visible,
    set: (value) => emits("update:visible", value),
});

// Form fields
const designationName = ref("");
const status = ref("Running");
const editId = ref<number | null>(null);

const statuses = [
    { label: "Running", value: "Running" },
    { label: "Disabled", value: "Disabled" }
];

// Designations list
const designations = ref<any[]>([]);

// Fetch all designations
const fetchDesignations = async () => {
    try {
        const { data } = await axios.get("/api/designations");
        designations.value = data;
    } catch (error) {
        console.error("Failed to fetch designations", error);
    }
};

// Close modal
const close = () => {
    visibleModel.value = false;
    clearForm();
};

// Clear form
const clearForm = () => {
    designationName.value = "";
    status.value = "Running";
    editId.value = null;
};

// Save or update designation
const saveOrUpdate = async () => {
    if (!designationName.value.trim()) return;

    try {
        let newData;
        if (editId.value) {
            const res = await axios.put(`/api/designations/${editId.value}`, {
                designation_name: designationName.value,
                status: status.value
            });
            newData = res.data;
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Designation updated', life: 3000 });
        } else {
            const res = await axios.post("/api/designations", {
                designation_name: designationName.value,
                status: status.value
            });
            newData = res.data;
            toast.add({ severity: 'success', summary: 'Created', detail: 'Designation created', life: 3000 });

            // 🔹 Emit created event with new entry
            emits("created", newData);

            // 🔹 Close modal automatically
            visibleModel.value = false;
        }

        await fetchDesignations();
        clearForm();
    } catch (error) {
        console.error("Error saving designation", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'Something went wrong', life: 3000 });
    }
};

// Edit designation
const editDesignation = (item: any) => {
    editId.value = item.id;
    designationName.value = item.designation_name;
    status.value = item.status;
};

// Delete designation using ConfirmDialog
const deleteDesignation = (id: number) => {
    confirm.require({
        message: 'Are you sure you want to delete this designation?',
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(`/api/designations/${id}`);
                await fetchDesignations();
                if (editId.value === id) clearForm();
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Designation deleted', life: 3000 });
            } catch (error) {
                console.error("Error deleting designation", error);
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete designation', life: 3000 });
            }
        }
    });
};

// Table columns for DataTable
const columns = [
    { key: 'sn', label: 'SN', align: 'center' },
    { key: 'designation_name', label: 'Designation Name', align: 'center' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'center' },
];

// Map rows for DataTable
const tableRows = computed(() =>
    designations.value.map((item, index) => ({
        sn: index + 1,
        id: item.id,
        designation_name: item.designation_name,
        status: item.status,
    }))
);

// Fetch designations whenever modal opens
watch(visibleModel, (val) => {
    if (val) fetchDesignations();
});
</script>

<template>
    <Dialog v-model:visible="visibleModel" header="Designation Management" modal :style="{ width: '40rem' }">
        <Toast />
        <ConfirmDialog />

        <div class="space-y-6">
            <!-- Form -->
            <div class="space-y-4 w-80 mx-auto border-b pb-4">
                <div>
                    <label class="font-medium">Designation Name</label>
                    <InputText v-model="designationName" class="w-full mt-1" placeholder="Enter designation" />
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
            <DataTable title="Designation List" :columns="columns" :rows="tableRows" :onEdit="editDesignation"
                :onDelete="deleteDesignation" :showSearch="true" />
        </div>
    </Dialog>
</template>
