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

import DataTable from "@/Components/DataTable.vue";

const toast = useToast();
const confirm = useConfirm();

const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Header Logo Entry", href: "/header-logo" },
];

const form = ref({
    image: null as File | null,
    previewUrl: "" as string,
});

const entries = ref([] as any[]);
const editingId = ref<number | null>(null);

const fetchEntries = async () => {
    try {
        const { data } = await axios.get("/api/header-logo");
        entries.value = data;
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch logos.", life: 3000 });
    }
};

onMounted(() => {
    fetchEntries();
});

const handleFileChange = (event) => {
    const target = event.target;
    if (target.files && target.files[0]) {
        form.value.image = target.files[0];
        form.value.previewUrl = URL.createObjectURL(target.files[0]);
        console.log('File selected:', target.files[0]); // ✅ Check this
    }
};

const editEntry = (entry: any) => {
    editingId.value = entry.id;
    form.value.previewUrl = entry.image_url; // Assuming backend returns the full image URL
    window.scrollTo({ top: 0, behavior: "smooth" });
};

const deleteEntry = (id: number) => {
    confirm.require({
        message: "Are you sure you want to delete this logo?",
        header: "Confirm Deletion",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: async () => {
            try {
                await axios.delete(`/api/header-logo/${id}`);
                toast.add({
                    severity: "success",
                    summary: "Deleted",
                    detail: "Logo deleted successfully!",
                    life: 3000,
                });
                await fetchEntries();
            } catch (error) {
                toast.add({
                    severity: "error",
                    summary: "Error",
                    detail: "Failed to delete logo.",
                    life: 3000,
                });
            }
        },
    });
};

const submitForm = async () => {
    if (!form.value.image && !editingId.value) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Please select an image.", life: 3000 });
        return;
    }

    const formData = new FormData();
    if (form.value.image) formData.append("image", form.value.image);

    try {
        if (editingId.value) {
            await axios.post(`/api/header-logo/${editingId.value}?_method=PUT`, formData);
            toast.add({ severity: "success", summary: "Updated", detail: "Header logo updated successfully!", life: 3000 });
            editingId.value = null;
        } else {
            await axios.post("/api/header-logo", formData);
            toast.add({ severity: "success", summary: "Created", detail: "Header logo added successfully!", life: 3000 });
        }

        form.value.image = null;
        form.value.previewUrl = "";
        await fetchEntries();
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to save header logo.", life: 3000 });
    }
};

// DataTable config
const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "image", label: "Logo Preview", align: "center" },
    { key: "actions", label: "Actions", align: "center" },
];

const tableRows = computed(() =>
    entries.value.map((entry, index) => ({
        sn: index + 1,
        id: entry.id,
        image: entry.image,
    }))
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Header Logo Entry" />
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-8">
            <!-- Form Section -->
            <Card>
                <template #title>
                    <h2 class="text-xl font-semibold">
                        {{ editingId ? "Edit Header Logo" : "Add New Header Logo" }}
                    </h2>
                </template>

                <template #content>
                    <div class="flex justify-center items-center py-10"
                        style="background-image: url('/images/form_bg/form_bg.jpg'); background-size: cover; background-position: center;">
                        <form @submit.prevent="submitForm"
                            class="space-y-4 bg-white dark:bg-gray-900 p-6 rounded-xl shadow-lg w-1/2">
                            <!-- Image Upload -->
                            <div>
                                <label class="font-semibold block mb-2 text-black">Upload Logo</label>
                                <input type="file" accept="image/*" @change="handleFileChange"
                                    class="w-full border rounded-lg p-2 cursor-pointer" />
                            </div>

                            <!-- Image Preview -->
                            <div v-if="form.previewUrl" class="flex justify-start mt-4">
                                <img :src="form.previewUrl" alt="Preview"
                                    class="w-48 h-48 object-contain border rounded-lg shadow-md" />
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
            <DataTable title="Header Logo List" :columns="columns" :rows="tableRows" :onEdit="editEntry"
                :onDelete="deleteEntry" :showSearch="true">
                <template #cell-image="{ row }">
                    <img :src="row.image" alt="Logo" class="w-16 h-16 object-contain mx-auto" />
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
