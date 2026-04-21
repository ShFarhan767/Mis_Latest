<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Multiselect from "vue-multiselect";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import DataTable from "@/Components/DataTable.vue";
import axios from "axios";

const toast = useToast();
const confirm = useConfirm();

const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Client Management", href: "/client-management" },
];

const statusOptions = [
    "Running",
    "Not Using",
    "Closed",
    "Software Not Urgent",
    "Disappointed",
    "No Operator",
    "Another software choose",
    "Business Closed",
    "Not Happy",
    "Happy",
];

// ============================
// FORM DATA
// ============================
const form = ref({
    name: "", // Client Name
    company_name: "", // Company Name
    operator_name: "", // Operator Name
    number: "", // Client Number
    oparetor_number: "", // Operator Number
    address: "", // Address
    project_name: "", // Project/Area
    status: "Running", // Status
});

const entries = ref<any[]>([]);
const editingId = ref<number | null>(null);

// ============================
// FETCH CLIENTS
// ============================
const fetchEntries = async () => {
    try {
        const { data } = await axios.get("/api/clients");
        entries.value = Array.isArray(data) ? data : data?.data || [];
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch clients.",
            life: 3000,
        });
    }
};

onMounted(fetchEntries);

// ============================
// SUBMIT FORM (CREATE / UPDATE)
// ============================
const submitForm = async () => {
    try {
        // all mandatory
        if (
            !form.value.name ||
            !form.value.company_name ||
            !form.value.operator_name ||
            !form.value.number ||
            !form.value.oparetor_number ||
            !form.value.address ||
            !form.value.project_name ||
            !form.value.status
        ) {
            toast.add({
                severity: "warn",
                summary: "Warning",
                detail: "Please fill all required fields (*).",
                life: 3000,
            });
            return;
        }

        if (editingId.value) {
            await axios.put(`/api/clients/${editingId.value}`, form.value);
            toast.add({ severity: "success", summary: "Updated", detail: "Client updated successfully!", life: 3000 });
            editingId.value = null;
        } else {
            await axios.post("/api/clients", form.value);
            toast.add({ severity: "success", summary: "Created", detail: "Client added successfully!", life: 3000 });
        }

        form.value = {
            name: "",
            company_name: "",
            operator_name: "",
            number: "",
            oparetor_number: "",
            address: "",
            project_name: "",
            status: "Running",
        };

        await fetchEntries();
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to save client.",
            life: 3000,
        });
    }
};

// ============================
// DELETE CLIENT
// ============================
const deleteEntry = (id: number) => {
    confirm.require({
        message: "Are you sure you want to delete this client?",
        header: "Confirm Deletion",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: async () => {
            try {
                await axios.delete(`/api/clients/${id}`);
                toast.add({ severity: "success", summary: "Deleted", detail: "Client deleted successfully!", life: 3000 });
                await fetchEntries();
            } catch {
                toast.add({ severity: "error", summary: "Error", detail: "Failed to delete client.", life: 3000 });
            }
        },
    });
};

// ============================
// EDIT CLIENT
// ============================
const editEntry = (entry: any) => {
    editingId.value = entry.id;
    form.value = {
        name: entry.name ?? "",
        company_name: entry.company_name ?? "",
        operator_name: entry.operator_name ?? "",
        number: entry.number ?? "",
        oparetor_number: entry.oparetor_number ?? "",
        address: entry.address ?? "",
        project_name: entry.project_name ?? "",
        status: entry.status ?? "Running",
    };
    window.scrollTo({ top: 0, behavior: "smooth" });
};

// ============================
// TABLE CONFIG
// ============================
const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "name", label: "Client Name", align: "center" },
    { key: "number", label: "Number", align: "center" },
    { key: "oparetor_number", label: "Operator Number", align: "center" },
    { key: "project_name", label: "Project Name", align: "center" },
    { key: "status", label: "Status", align: "center" },
    { key: "actions", label: "Actions", align: "center" },
];

const tableRows = computed(() =>
    (entries.value || []).map((entry: any, index: number) => ({
        sn: index + 1,
        id: entry.id,
        name: entry.name ?? "-",
        number: entry.number ?? "-",
        oparetor_number: entry.oparetor_number ?? "-",
        project_name: entry.project_name ?? "-",
        status: entry.status ?? "-",
    }))
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Client Management" />
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-8">
            <Card>
                <template #title>
                    <h2 class="text-xl font-semibold text-gray-800">
                        {{ editingId ? "Edit Client" : "Add New Client" }}
                    </h2>
                </template>

                <template #content>
                    <div class="flex justify-center items-center py-10 bg-gray-50 rounded-lg"
                        style="background-image: url('/images/form_bg/form_bg.jpg'); background-size: cover; background-position: center;">
                        <form @submit.prevent="submitForm"
                            class="space-y-4 bg-white p-6 rounded-xl shadow-lg w-full max-w-2xl">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="font-semibold block mb-2 text-gray-700">
                                        Client Name <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.name" class="w-full" placeholder="Enter client name" />
                                </div>

                                <div>
                                    <label class="font-semibold block mb-2 text-gray-700">
                                        Company Name <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.company_name" class="w-full"
                                        placeholder="Enter company name" />
                                </div>

                                <div>
                                    <label class="font-semibold block mb-2 text-gray-700">
                                        Operator Name <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.operator_name" class="w-full"
                                        placeholder="Enter operator name" />
                                </div>

                                <div>
                                    <label class="font-semibold block mb-2 text-gray-700">
                                        Client Number <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.number" class="w-full" placeholder="Enter phone number" />
                                </div>

                                <div>
                                    <label class="font-semibold block mb-2 text-gray-700">
                                        Operator Number <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.oparetor_number" class="w-full"
                                        placeholder="Enter operator phone number" />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="font-semibold block mb-2 text-gray-700">
                                        Address <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.address" class="w-full" placeholder="Enter address" />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="font-semibold block mb-2 text-gray-700">
                                        Project / Area <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.project_name" class="w-full"
                                        placeholder="Enter project or area" />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="font-semibold block mb-2 text-gray-700">
                                        Status <span class="text-red-600">*</span>
                                    </label>
                                    <Multiselect v-model="form.status" :options="statusOptions"
                                        placeholder="Select Status" :close-on-select="true" :clear-on-select="false"
                                        :allow-empty="false" :searchable="true" />
                                </div>
                            </div>

                            <div class="flex justify-center mt-6">
                                <Button type="submit" :label="editingId ? 'Update Client' : 'Save Client'"
                                    icon="pi pi-save" class="p-button-success w-1/2" />
                            </div>
                        </form>
                    </div>
                </template>
            </Card>

            <DataTable title="Client List" :columns="columns" :rows="tableRows" :onEdit="editEntry"
                :onDelete="deleteEntry" :showSearch="true" />
        </div>
    </AppLayout>
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";
</style>
