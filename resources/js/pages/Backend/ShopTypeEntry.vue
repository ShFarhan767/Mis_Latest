<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import axios from "axios";
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

const toast = useToast();
const confirm = useConfirm();

// Breadcrumbs
const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Shop Type Management", href: "/shop-type-management" },
];

// Status options
const statusOptions = ["Running", "Disabled"];

// Form
const form = ref({
    name: "",
    status: "Running",
});

// Table data
const entries = ref<any[]>([]);
const editingId = ref<number | null>(null);

// Fetch all shop types
const fetchEntries = async () => {
    try {
        const { data } = await axios.get("/api/shop-types");
        entries.value = data;
    } catch (error) {
        console.error("Failed to fetch shop types:", error);
    }
};

// On component mount
onMounted(() => {
    fetchEntries();
});

// Submit form
const submitForm = async () => {
    if (!form.value.name) {
        toast.add({
            severity: "warn",
            summary: "Warning",
            detail: "Shop Type name is required",
            life: 3000,
        });
        return;
    }

    try {
        if (editingId.value) {
            await axios.put(`/api/shop-types/${editingId.value}`, form.value);
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Shop Type updated successfully!",
                life: 3000,
            });
        } else {
            await axios.post("/api/shop-types", form.value);
            toast.add({
                severity: "success",
                summary: "Created",
                detail: "Shop Type added successfully!",
                life: 3000,
            });
        }

        form.value = { name: "", status: "Running" };
        editingId.value = null;
        fetchEntries();
    } catch (error) {
        console.error("Submit error:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Something went wrong!",
            life: 3000,
        });
    }
};

// Edit entry
const editEntry = (entry: any) => {
    editingId.value = entry.id;
    form.value = {
        name: entry.name,
        status: entry.status,
    };
    window.scrollTo({ top: 0, behavior: "smooth" });
};

// Delete entry
const deleteEntry = (id: number) => {
    confirm.require({
        message: "Delete this shop type?",
        header: "Confirm",
        acceptClass: "p-button-danger",
        accept: async () => {
            try {
                await axios.delete(`/api/shop-types/${id}`);
                fetchEntries();
                toast.add({
                    severity: "success",
                    summary: "Deleted",
                    detail: "Shop Type deleted!",
                    life: 3000,
                });
            } catch (error) {
                console.error("Delete error:", error);
                toast.add({
                    severity: "error",
                    summary: "Error",
                    detail: "Failed to delete!",
                    life: 3000,
                });
            }
        },
    });
};

// Table columns
const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "name", label: "Shop Type Name", align: "center" },
    { key: "status", label: "Status", align: "center" },
    { key: "actions", label: "Actions", align: "center" },
];

// Map table rows
const tableRows = computed(() =>
    entries.value.map((entry, index) => ({
        sn: index + 1,
        id: entry.id,
        name: entry.name,
        status: entry.status,
    }))
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Shop Type Management" />
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-8">
            <Card>
                <template #title>
                    <h2 class="text-xl font-semibold text-gray-800">
                        {{ editingId ? "Edit Shop Type" : "Add New Shop Type" }}
                    </h2>
                </template>

                <template #content>
                    <div
                        class="flex justify-center items-center py-10 bg-gray-50 rounded-lg"
                        style="background-image: url('/images/form_bg/form_bg.jpg');
                        background-size: cover;
                        background-position: center;"
                    >
                        <form
                            @submit.prevent="submitForm"
                            class="space-y-4 bg-white p-6 rounded-xl shadow-lg w-full max-w-xl"
                        >
                            <div>
                                <label class="font-semibold block mb-2 text-gray-700">Shop Type Name</label>
                                <InputText
                                    v-model="form.name"
                                    class="w-full"
                                    placeholder="Enter shop type name"
                                />
                            </div>

                            <div>
                                <label class="font-semibold block mb-2 text-gray-700">Status</label>
                                <Multiselect
                                    v-model="form.status"
                                    :options="statusOptions"
                                    placeholder="Select Status"
                                    :close-on-select="true"
                                    :searchable="true"
                                />
                            </div>

                            <div class="flex justify-center mt-6">
                                <Button
                                    type="submit"
                                    :label="editingId ? 'Update Shop Type' : 'Save Shop Type'"
                                    icon="pi pi-save"
                                    class="p-button-success w-1/2"
                                />
                            </div>
                        </form>
                    </div>
                </template>
            </Card>

            <!-- Datatable -->
            <DataTable
                title="Shop Type List"
                :columns="columns"
                :rows="tableRows"
                :onEdit="editEntry"
                :onDelete="deleteEntry"
                :showSearch="true"
            />
        </div>
    </AppLayout>
</template>

<style scoped>
@import "vue-multiselect/dist/vue-multiselect.css";

.p-multiselect {
    width: 100%;
}
</style>
