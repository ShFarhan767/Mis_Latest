<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
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
    { title: "Logo Upload", href: "/logo-entry" },
];

const form = ref({
    title: "",
    logo: null,
    favicon: null,
});

const imagePreview = ref<string | null>(null);
const faviconPreview = ref<string | null>(null);
const isSubmitting = ref(false);
const editingId = ref<number | null>(null);
const entries = ref<any[]>([]);

/* ================= FETCH ================= */
const fetchLogos = async () => {
    const { data } = await axios.get("/api/logos");
    entries.value = data;
};

onMounted(fetchLogos);

// Favicon Preview
const onFaviconChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    form.value.favicon = file;

    const reader = new FileReader();
    reader.onload = e => faviconPreview.value = e.target?.result as string;
    reader.readAsDataURL(file);
};

/* ================= IMAGE PREVIEW ================= */
const onImageChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    form.value.logo = file;

    const reader = new FileReader();
    reader.onload = e => imagePreview.value = e.target?.result as string;
    reader.readAsDataURL(file);
};

/* ================= SUBMIT ================= */
const submitForm = async () => {
    isSubmitting.value = true;
    const formData = new FormData();
    formData.append("title", form.value.title);
    if (form.value.logo) formData.append("logo", form.value.logo);
    if (form.value.favicon) formData.append("favicon", form.value.favicon);

    try {
        if (editingId.value) {
            formData.append("_method", "PUT");
            await axios.post(`/api/logos/${editingId.value}`, formData);
            toast.add({ severity: "success", summary: "Updated", detail: "Logo updated", life: 3000 });
        } else {
            await axios.post("/api/logos", formData);
            toast.add({ severity: "success", summary: "Created", detail: "Logo uploaded", life: 3000 });
        }

        resetForm();
        fetchLogos();
    } catch {
        toast.add({ severity: "error", summary: "Error", detail: "Failed", life: 3000 });
    } finally {
        isSubmitting.value = false;
    }
};

const resetForm = () => {
    form.value = { title: "", logo: null, favicon: null };
    imagePreview.value = null;
    faviconPreview.value = null;
    editingId.value = null;
};

/* ================= DELETE ================= */
const deleteEntry = (id: number) => {
    confirm.require({
        message: "Delete this logo?",
        accept: async () => {
            await axios.delete(`/api/logos/${id}`);
            fetchLogos();
            toast.add({ severity: "success", summary: "Deleted" });
        }
    });
};

/* ================= EDIT ================= */
const editEntry = (row: any) => {
    editingId.value = row.id;

    form.value.title = row.title;

    // set previews from DB paths
    imagePreview.value = row.logo_path ? "/" + row.logo_path : null;
    faviconPreview.value = row.favicon_path ? "/" + row.favicon_path : null;

    // clear file inputs (important)
    form.value.logo = null;
    form.value.favicon = null;

    window.scrollTo({ top: 0, behavior: "smooth" });
};

/* ================= TABLE ================= */
const columns = [
    { key: "sn", label: "SN" },
    { key: "title", label: "Title" },
    { key: "logo", label: "Logo" },
    { key: "favicon", label: "Favicon" },
    { key: "actions", label: "Actions" },
];

const tableRows = computed(() =>
    entries.value.map((e, i) => ({
        sn: i + 1,
        id: e.id,
        title: e.title,
        logo_path: e.logo_path,
        favicon_path: e.favicon_path,
    }))
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Toast />
        <ConfirmDialog />

        <Card>
            <template #title>{{ editingId ? "Edit Logo" : "Upload Logo" }}</template>
            <template #content>
                <div class="flex justify-center items-center py-10 bg-gray-50 rounded-lg"
                    style="background-image: url('/images/form_bg/form_bg.jpg'); background-size: cover; background-position: center;">
                    <form @submit.prevent="submitForm"
                        class="space-y-4 bg-white p-6 rounded-xl shadow-lg w-full max-w-lg">

                        <div class="mb-5">
                            <label for="logo-title" class="mb-2 block">Logo Title</label>
                            <InputText v-model="form.title" placeholder="Logo Title" class="w-full" />
                        </div>

                        <div>
                            <label for="logo-file" class="mb-2 block">Logo File</label>
                            <input type="file" id="logo-file" accept="image/*" @change="onImageChange" class="w-full rounded-md border p-2 border-gray-300" />
                            <img v-if="imagePreview" :src="imagePreview" class="h-25 mx-auto my-2 bg-contain" />
                        </div>

                        <div>
                            <!-- Favicon -->
                            <label for="favicon-file" class="mb-2 block">Favicon File</label>
                            <input type="file" id="favicon-file" accept="image/*" @change="onFaviconChange" class="w-full rounded-md border p-2 border-gray-300" />
                            <img v-if="faviconPreview" :src="faviconPreview" class="h-12 my-2"/>
                        </div>

                        <Button type="submit" :loading="isSubmitting" :label="editingId ? 'Updating...' : 'Submit'"
                            class="w-full" />
                    </form>
                </div>
            </template>
        </Card>

        <DataTable :columns="columns" :rows="tableRows" :onEdit="editEntry" :onDelete="deleteEntry">
            <template #cell-logo="{ row }">
                <img :src="`/${row.logo_path}`" class="h-16 mx-auto" />
            </template>

            <template #cell-favicon="{ row }">
                <img :src="`/${row.favicon_path}`" class="h-8 mx-auto"/>
            </template>
        </DataTable>
    </AppLayout>
</template>
