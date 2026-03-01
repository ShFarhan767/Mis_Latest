<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
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
    { title: "Area Management", href: "/area-management" },
];

const statusOptions = ["Running", "Disabled"];

const form = ref({
    area_name: "",
    status: "Running",
});

const entries = ref<any[]>([]);
const editingId = ref<number | null>(null);

const fetchEntries = async () => {
    const { data } = await axios.get("/api/areas");
    entries.value = data;
};

onMounted(() => {
    fetchEntries();
});

const submitForm = async () => {
    if (!form.value.area_name) {
        toast.add({
            severity: "warn",
            summary: "Warning",
            detail: "Area name is required",
            life: 3000,
        });
        return;
    }

    if (editingId.value) {
        await axios.put(`/api/areas/${editingId.value}`, form.value);
        toast.add({ severity: "success", summary: "Updated", detail: "Area updated successfully!", life: 3000 });
    } else {
        await axios.post("/api/areas", form.value);
        toast.add({ severity: "success", summary: "Created", detail: "Area added successfully!", life: 3000 });
    }

    form.value = { area_name: "", status: "Running" };
    editingId.value = null;
    fetchEntries();
};

const deleteEntry = (id: number) => {
    confirm.require({
        message: "Delete this area?",
        header: "Confirm",
        acceptClass: "p-button-danger",
        accept: async () => {
            await axios.delete(`/api/areas/${id}`);
            fetchEntries();
            toast.add({ severity: "success", summary: "Deleted", detail: "Area deleted!", life: 3000 });
        },
    });
};

const editEntry = (entry: any) => {
    editingId.value = entry.id;
    form.value = {
        area_name: entry.area_name,
        status: entry.status,
    };
    window.scrollTo({ top: 0, behavior: "smooth" });
};

const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "area_name", label: "Area Name", align: "center" },
    { key: "status", label: "Status", align: "center" },
    { key: "actions", label: "Actions", align: "center" },
];

const tableRows = computed(() =>
    entries.value.map((entry, index) => ({
        sn: index + 1,
        id: entry.id,
        area_name: entry.area_name,
        status: entry.status,
    }))
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Area Management" />
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-8">
            <Card>
                <template #title>
                    <h2 class="text-xl font-semibold text-gray-800">
                        {{ editingId ? "Edit Area" : "Add New Area" }}
                    </h2>
                </template>

                <template #content>
                    <div class="flex justify-center items-center py-10 bg-gray-50 rounded-lg"
                        style="background-image: url('/images/form_bg/form_bg.jpg'); background-size: cover; background-position: center;">
                        <form @submit.prevent="submitForm"
                            class="space-y-4 bg-white p-6 rounded-xl shadow-lg w-full max-w-xl">

                            <div>
                                <label class="font-semibold block mb-2 text-gray-700">Area Name</label>
                                <InputText v-model="form.area_name" class="w-full" placeholder="Enter area name" />
                            </div>

                            <div>
                                <label class="font-semibold block mb-2 text-gray-700">Status</label>
                                <Multiselect v-model="form.status" :options="statusOptions" placeholder="Select Status"
                                    :close-on-select="true" :searchable="true" />
                            </div>

                            <div class="flex justify-center mt-6">
                                <Button type="submit" :label="editingId ? 'Update Area' : 'Save Area'"
                                    icon="pi pi-save" class="p-button-success w-1/2" />
                            </div>
                        </form>
                    </div>
                </template>
            </Card>

            <DataTable title="Area List" :columns="columns" :rows="tableRows" :onEdit="editEntry"
                :onDelete="deleteEntry" :showSearch="true" />
        </div>
    </AppLayout>
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";
</style>