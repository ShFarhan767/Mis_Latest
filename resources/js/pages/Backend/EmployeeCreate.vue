<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import axios from "axios";
import Multiselect from "vue-multiselect";
import DataTable from "@/Components/DataTable.vue";
import DesignationEntryModal from "@/components/DesignationEntryModal.vue"

const props = defineProps({
    userRole: String,
    userId: Number
});

const toast = useToast();
const confirm = useConfirm();

const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Employee Entry", href: "/employee-entry" },
];

// ============================
// FORM DATA
// ============================
const form = ref({
    name: "",
    mobile: "",
    email: "",
    designation: null as any, // can be string or array
    password: "",
    status: "Running",
});

const statusOptions = [
    "Running",
    "Disable",
    "Suspend"
];

const entries = ref<any[]>([]);
const editingId = ref<number | null>(null);
const editingStatusId = ref<number | null>(null);

// ============================
// FETCH EMPLOYEES
// ============================
const fetchEntries = async () => {
    try {
        const { data } = await axios.get("/api/employees");
        entries.value = data;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch employees.",
            life: 3000,
        });
    }
};

const designations = ref<any[]>([]); // store API fetched designations
const showDesignationModal = ref(false);

const handleDesignationCreated = (newData: any) => {
    const option = { label: newData.designation_name, value: newData.id };
    designations.value.push(option);
    form.value.designation = option;
};

// ============================
// FETCH DESIGNATIONS
// ============================
const fetchDesignations = async () => {
    try {
        const { data } = await axios.get("/api/designations");
        designations.value = data
            .filter((d: any) => d.status !== "Disabled")
            .map((d: any) => ({
                label: d.designation_name,
                value: d.id,
            }));
    } catch (err) {
        console.error(err);
    }
};

// Fetch both on mounted
onMounted(() => {
    fetchEntries();
    fetchDesignations();
});

// ============================
// EDIT EMPLOYEE
// ============================
const editEntry = (entry: any) => {
    editingId.value = entry.id;

    // Map staff designation to full object for multiselect
    const selectedDesignation = designations.value.find(
        (d: any) => d.label === entry.designation || d.value === entry.designation_id
    ) || null;

    form.value = {
        name: entry.name,
        mobile: entry.mobile,
        email: entry.email,
        designation: selectedDesignation, // ✅ full object
        password: "",
        status: entry.status,
    };

    window.scrollTo({ top: 0, behavior: "smooth" });
};

const updateStatus = async (id: number, newStatus: string) => {
    try {
        await axios.post(`/api/employees/${id}?_method=PUT`, { status: newStatus });
        toast.add({ severity: "success", summary: "Updated", detail: "Status updated successfully!", life: 3000 });
        await fetchEntries(); // refresh table
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to update status.", life: 3000 });
    }
};

// ============================
// DELETE EMPLOYEE
// ============================
const deleteEntry = (id: number) => {
    confirm.require({
        message: "Are you sure you want to delete this employee?",
        header: "Confirm Deletion",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: async () => {
            try {
                await axios.delete(`/api/employees/${id}`);
                toast.add({
                    severity: "success",
                    summary: "Deleted",
                    detail: "Employee deleted successfully!",
                    life: 3000,
                });
                await fetchEntries();
            } catch (error) {
                toast.add({
                    severity: "error",
                    summary: "Error",
                    detail: "Failed to delete employee.",
                    life: 3000,
                });
            }
        },
    });
};

// ============================
// SUBMIT FORM (CREATE / UPDATE)
// ============================
const submitForm = async () => {
    // Validation
    if (!form.value.name || !form.value.mobile || (!editingId.value && !form.value.password)) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Name, Mobile, and Password are required.", life: 3000 });
        return;
    }

    // Prepare payload
    const payload = {
        ...form.value,
        designation: form.value.designation?.label || "", // send only label
    };

    if (editingId.value && !payload.password) delete payload.password;

    try {
        if (editingId.value) {
            await axios.post(`/api/employees/${editingId.value}?_method=PUT`, payload);
            toast.add({ severity: "success", summary: "Updated", detail: "Employee updated successfully!", life: 3000 });
            editingId.value = null;
        } else {
            await axios.post("/api/employees", payload);
            toast.add({ severity: "success", summary: "Created", detail: "Employee created successfully!", life: 3000 });
        }

        // Reset form
        form.value = { name: "", mobile: "", email: "", designation: null, password: "", status: "Running" };
        await fetchEntries();
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to save employee.", life: 3000 });
    }
};

// ============================
// TABLE CONFIG
// ============================
const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "name", label: "Name", align: "center" },
    { key: "mobile", label: "Mobile", align: "center" },
    { key: "email", label: "E-Mail", align: "center" },
    { key: "plain_password", label: "Password", align: "center" },
    { key: "designation", label: "Designation", align: "center" },
    { key: "status", label: "Status", align: "center" },
    { key: "actions", label: "Actions", align: "center" },
];

const tableRows = computed(() =>
    entries.value.map((entry, index) => ({
        sn: index + 1,
        id: entry.id,
        name: entry.name,
        mobile: entry.mobile,
        email: entry.email,
        plain_password: entry.plain_password,
        designation: entry.designation,
        status: entry.status,
    }))
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Employee Entry" />
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-8">
            <!-- Form Section -->
            <Card>
                <template #title>
                    <h2 class="text-xl font-semibold">
                        {{ editingId ? "Edit Employee" : "Add New Employee" }}
                    </h2>
                </template>

                <template #content>
                    <div class="flex justify-center items-center py-10"
                        style="background-image: url('/images/form_bg/form_bg.jpg'); background-size: cover; background-position: center;">
                        <form @submit.prevent="submitForm"
                            class="space-y-4 bg-white dark:bg-gray-900 p-6 rounded-xl shadow-lg w-1/2">
                            <!-- Name -->
                            <div>
                                <label class="font-semibold block mb-2 text-black">Name</label>
                                <input v-model="form.name" type="text" placeholder="Enter full name"
                                    class="w-full border rounded-lg p-2" />
                            </div>

                            <!-- Mobile -->
                            <div>
                                <label class="font-semibold block mb-2 text-black">Mobile</label>
                                <input v-model="form.mobile" type="text" placeholder="Enter mobile number"
                                    class="w-full border rounded-lg p-2" />
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="font-semibold block mb-2 text-black">Email</label>
                                <input v-model="form.email" type="email" placeholder="Enter email address"
                                    class="w-full border rounded-lg p-2" />
                            </div>

                            <!-- Designation -->
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-medium">Designation</label>
                                    <Button v-if="props.userRole === 'admin'" icon="pi pi-plus"
                                        class="p-button-rounded p-button-sm p-button-success"
                                        @click="showDesignationModal = true" />
                                </div>
                                <Multiselect v-model="form.designation" :options="designations" :multiple="false"
                                    :searchable="true" placeholder="Select Designation" label="label" track-by="value"
                                    class="w-full" />
                            </div>

                            <!-- Password -->
                            <div v-if="!editingId">
                                <label class="font-semibold block mb-2 text-black">Password</label>
                                <input v-model="form.password" type="password" placeholder="Enter password"
                                    class="w-full border rounded-lg p-2" />
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="font-semibold block mb-2 text-black">Status</label>

                                <Multiselect v-model="form.status" :options="statusOptions" :searchable="true" />
                                <!-- <select v-model="form.status" class="w-full border rounded-lg p-2">
                                    <option value="running">Running</option>
                                    <option value="suspend">Suspend</option>
                                    <option value="disable">Disable</option>
                                </select> -->
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-center mt-4">
                                <Button type="submit" :label="editingId ? 'Update' : 'Save'" icon="pi pi-send"
                                    class="p-button-success w-1/2" />
                            </div>
                        </form>
                    </div>
                </template>
            </Card>

            <!-- List Section -->
            <DataTable title="Employee List" :columns="columns" :rows="tableRows" :onEdit="editEntry"
                :onDelete="deleteEntry" :showSearch="true">
                <template #cell-status="{ row }">
                    <select v-model="row.status" @change="updateStatus(row.id, row.status)"
                        class="border rounded-lg p-1">
                        <option value="Running">Running</option>
                        <option value="Suspend">Suspend</option>
                        <option value="Disable">Disable</option>
                    </select>
                </template>
            </DataTable>
        </div>
    </AppLayout>

    <DesignationEntryModal v-model:visible="showDesignationModal" @created="handleDesignationCreated" />
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";
</style>
