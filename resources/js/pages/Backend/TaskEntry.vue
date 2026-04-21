<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Editor from 'primevue/editor';
import InputText from 'primevue/inputtext';
import Calendar from "primevue/calendar";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import DataTable from "@/Components/DataTable.vue";
import Multiselect from "vue-multiselect";
import axios from "axios";

const toast = useToast();
const confirm = useConfirm();

const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Task Entry", href: "/task-entry" },
];

const page = usePage();
const user = page.props.authUser;
const isSubmitting = ref(false);

// Detect if staff
const isStaff = user?.role === "staff";
const isFutureTask = ref(false);

const statusOptions = isStaff ? ["Staff"] : ["New"];

// ============================
// FORM DATA
// ============================
const form = ref({
    shop_id: null,
    title: "",       // ✅ Added
    details: "",
    image: null,
    start_date: null, // ✅ Added
    status: "New", // bind with multiselect
});

if (isStaff) {
    form.value.status = "Staff";
}

const selectedShop = ref<any>(null);
const clientOptions = ref<any[]>([]);
const imagePreview = ref<string | null>(null);
const isSearching = ref(false);

const entries = ref<any[]>([]);
const editingId = ref<number | null>(null);

// ============================
// SHOP SEARCH (with debounce)
// ============================
let clientSearchTimer: ReturnType<typeof setTimeout> | null = null;

const onClientSearch = (query: string) => {
    if (query.length < 3) {
        clientOptions.value = [];
        return;
    }

    if (clientSearchTimer) clearTimeout(clientSearchTimer);

    clientSearchTimer = setTimeout(async () => {
        isSearching.value = true;
        try {
            const { data } = await axios.get(`/api/clients/search?query=${query}`);
            clientOptions.value = data;
        } catch (error) {
            console.error("Client search failed:", error);
        } finally {
            isSearching.value = false;
        }
    }, 500);
};

// ============================
// FETCH TASK ENTRIES
// ============================
const fetchEntries = async () => {
    try {
        const { data } = await axios.get("/api/tasks");
        entries.value = data;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch tasks.",
            life: 3000,
        });
    }
};

onMounted(() => {
    fetchEntries();
});

// ============================
// IMAGE PREVIEW
// ============================
const onImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];
    form.value.image = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
};

// Format date safely for API
const formatDate = (date) => {
    if (!date) return null;

    // If it's already a Date object, use it directly
    const d = date instanceof Date ? date : new Date(date);

    if (isNaN(d.getTime())) return null; // invalid date check

    const year = d.getFullYear();
    const month = (d.getMonth() + 1).toString().padStart(2, "0");
    const day = d.getDate().toString().padStart(2, "0");

    return `${year}-${month}-${day}`;
};

// ============================
// SUBMIT FORM (CREATE / UPDATE)
// ============================
const submitForm = async () => {
    if (!selectedShop.value && !isFutureTask.value) {
        toast.add({
            severity: "warn",
            summary: "Warning",
            detail: "Please select a shop.",
            life: 3000,
        });
        return;
    }

    isSubmitting.value = true; // 🔥 start loading

    if (isStaff) {
        form.value.status = "Staff";
    }

    if (isFutureTask.value) {
        form.value.status = "Future";
    }

    const formData = new FormData();
    if (selectedShop.value) {
        formData.append("shop_id", selectedShop.value.id);
        formData.append("shop_name", selectedShop.value.company_name);
    }

    formData.append("title", form.value.title); // ✅ Added
    formData.append("details", form.value.details);
    formData.append("start_date", formatDate(form.value.start_date) || ""); // ✅ Added
    formData.append("status", form.value.status);
    if (form.value.image) formData.append("image", form.value.image);

    try {
        if (editingId.value) {
            formData.append("_method", "PUT");
            await axios.post(`/api/tasks/${editingId.value}`, formData);
            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Task updated successfully!",
                life: 3000,
            });
            editingId.value = null;
        } else {
            await axios.post("/api/tasks", formData);
            toast.add({
                severity: "success",
                summary: "Created",
                detail: "Task created successfully!",
                life: 3000,
            });
        }

        form.value = { shop_id: null, title: "", details: "", image: null, start_date: null, status: "New" };
        selectedShop.value = null;
        imagePreview.value = null;
        await fetchEntries();
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to save task.",
            life: 3000,
        });
    } finally {
        isSubmitting.value = false; // 🔥 end loading
    }
};

// ============================
// DELETE TASK
// ============================
const deleteEntry = (id: number) => {
    confirm.require({
        message: "Are you sure you want to delete this task?",
        header: "Confirm Deletion",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: async () => {
            try {
                await axios.delete(`/api/tasks/${id}`);
                toast.add({
                    severity: "success",
                    summary: "Deleted",
                    detail: "Task deleted successfully!",
                    life: 3000,
                });
                await fetchEntries();
            } catch {
                toast.add({
                    severity: "error",
                    summary: "Error",
                    detail: "Failed to delete task.",
                    life: 3000,
                });
            }
        },
    });
};

// ============================
// EDIT TASK
// ============================
const editEntry = (entry: any) => {
    editingId.value = entry.id;

    // Only set selectedShop if shop exists
    if (entry.shop_id) {
        selectedShop.value = {
            id: entry.shop_id,
            company_name: entry.shop_name
        };
        clientOptions.value = [selectedShop.value];
    } else {
        selectedShop.value = null;
    }

    form.value.title = entry.title;
    form.value.details = entry.details;
    form.value.start_date = entry.start_date;
    form.value.status = entry.status || "New";
    isFutureTask.value = entry.status === "Future";

    imagePreview.value = entry.image_path ? `/${entry.image_path}` : null;
    form.value.image = null;

    window.scrollTo({ top: 0, behavior: "smooth" });
};

// ============================
// TABLE CONFIG
// ============================
const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "shop_name", label: "Shop Name", align: "center" },
    { key: "title", label: "Task Title", align: "center" },
    { key: "details", label: "Details", align: "center" },
    { key: "image", label: "Image", align: "center" },
    { key: "start_date", label: "Start Date", align: "center" },
    { key: "status", label: "Status", align: "center" },
    { key: "actions", label: "Actions", align: "center" },
];

const tableRows = computed(() =>
    entries.value
        .filter(entry => !["Cancelled", "Approved"].includes(entry.status)) // ✅ filter out unwanted statuses
        .map((entry, index) => ({
            sn: index + 1,
            id: entry.id,
            shop_id: entry.shop_id,
            shop_name: entry.shop_name || "—",
            title: entry.title || "—",
            details: entry.details || "—",
            image_path: entry.image_path || null,
            start_date: entry.start_date || "—",
            status: entry.status
        }))
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Task Entry" />
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-8">
            <!-- Form Section -->
            <Card>
                <template #title>
                    <h2 class="text-xl font-semibold">
                        {{ editingId ? "Edit Task" : "Add New Task" }}
                    </h2>
                </template>

                <template #content>
                    <div class="flex justify-center items-center py-10 bg-gray-50 rounded-lg"
                        style="background-image: url('/images/form_bg/form_bg.jpg'); background-size: cover; background-position: center;">
                        <form @submit.prevent="submitForm"
                            class="space-y-4 bg-white p-6 rounded-xl shadow-lg w-full max-w-2xl">

                            <!-- Future Task Checkbox -->
                            <div class="flex items-center gap-2">
                                <input type="checkbox" v-model="isFutureTask" id="futureTask" />
                                <label for="futureTask" class="font-semibold text-gray-700">
                                    Mark as Future Task
                                </label>
                            </div>

                            <!-- Shop multiselect -->
                            <div>
                                <label class="font-semibold block mb-2 text-gray-700">Shop</label>
                                <Multiselect v-model="selectedShop" :options="clientOptions" :searchable="true"
                                    :loading="isSearching" :clear-on-select="true" track-by="id" label="company_name"
                                    placeholder="Type at least 3 characters to search shop"
                                    @search-change="onClientSearch" />
                            </div>

                            <!-- Tilte -->
                            <div>
                                <label class="font-semibold block mb-2 text-gray-700">Task Title</label>
                                <InputText type="text" v-model="form.title" class="w-full" />
                            </div>


                            <!-- Details Editor -->
                            <div>
                                <label class="font-semibold block mb-2 text-gray-700">Details</label>
                                <Editor v-model="form.details" :style="{ height: '200px' }" />
                            </div>

                            <!-- Image input + preview -->
                            <div class="mt-26">
                                <label class="font-semibold block mb-2 text-gray-700">Image (optional)</label>
                                <input type="file" accept="image/*" @change="onImageChange"
                                    class="border w-full p-2 text-black rounded" />
                                <div v-if="imagePreview" class="mt-3">
                                    <img :src="imagePreview" class="max-w-full max-h-64 rounded-lg shadow" />
                                </div>
                            </div>

                            <!-- Project Start Date -->
                            <div>
                                <label class="font-semibold block mb-2 text-gray-700">Project Start Date</label>
                                <Calendar v-model="form.start_date" dateFormat="yy-mm-dd" showIcon
                                    placeholder="Select start date" class="w-full" />
                            </div>

                            <!-- Status Multiselect -->
                            <div>
                                <label class="font-semibold block mb-2 text-gray-700">Status</label>
                                <Multiselect v-model="form.status" :options="statusOptions" placeholder="Select Status"
                                    :close-on-select="true" :clear-on-select="true" :allow-empty="false"
                                    :searchable="true" :disabled="isStaff || isFutureTask" />
                            </div>

                            <!-- Submit -->
                            <div class="flex justify-center mt-6">
                                <Button
                                    type="submit"
                                    :label="editingId ? 'Updating Task...' : 'Submit Task'"
                                    icon="pi pi-save"
                                    class="p-button-success w-1/2"
                                    :loading="isSubmitting"
                                    :disabled="isSubmitting"
                                />
                            </div>
                        </form>
                    </div>
                </template>
            </Card>

            <!-- List Section -->
            <DataTable title="Task List" :columns="columns" :rows="tableRows" :onEdit="editEntry"
                :onDelete="deleteEntry" :showSearch="true">
                <template #cell-image="{ row }">
                    <img v-if="row.image_path" :src="`/${row.image_path}`" alt="Task image"
                        class="h-20 w-20 rounded object-cover border mx-auto" />
                    <span v-else class="text-gray-400 italic">No image</span>
                </template>

                <!-- ✅ Add this for rendering HTML safely -->
                <template #cell-details="{ row }">
                    <div class="prose max-w-none" v-html="row.details"></div>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";
</style>
