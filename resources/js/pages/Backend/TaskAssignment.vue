<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import InputNumber from "primevue/inputnumber";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import Calendar from "primevue/calendar";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import DataTable from "@/Components/DataTable.vue";
import Multiselect from "vue-multiselect";
import axios from "axios";

const toast = useToast();
const confirm = useConfirm();

const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Task Assignment", href: "/task-assignment" },
];

const statusOptions = ["New", "Assigned", "Pending", "Cancelled", "Reissue"];

const form = ref({
    employee: null,
    task: null,
    status: "New",
    reissue_comment: "",
    committed_hours: null,
    start_date: null,
    end_date: null,
});

const employees = ref<any[]>([]);
const tasks = ref<any[]>([]);
const entries = ref<any[]>([]);
const editingId = ref<number | null>(null);

const fetchData = async () => {
    try {
        const [userRes, taskRes, entryRes] = await Promise.all([
            axios.get("/api/users"),
            axios.get("/api/tasks"),
            axios.get("/api/task-assignments"),
        ]);

        employees.value = userRes.data.filter((u: any) => u.role === "employee");
        tasks.value = taskRes.data;
        entries.value = entryRes.data;
    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch data", life: 3000 });
    }
};

onMounted(fetchData);

const formatDate = (date: Date | null) =>
    date
        ? `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, "0")}-${date
            .getDate()
            .toString()
            .padStart(2, "0")}`
        : null;

const submitForm = async () => {
    if (!form.value.employee || !form.value.task) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Select employee and task", life: 3000 });
        return;
    }

    const payload = {
        employee_id: form.value.employee.id,
        task_id: form.value.task.id,
        status: form.value.status,
        reissue_comment: form.value.status === "Reissue" ? form.value.reissue_comment : null,
        committed_hours: form.value.committed_hours,
        start_date: formatDate(form.value.start_date),
        end_date: formatDate(form.value.end_date),
    };

    try {
        if (editingId.value) {
            await axios.put(`/api/task-assignments/${editingId.value}`, payload);
            toast.add({ severity: "success", summary: "Updated", detail: "Assignment updated!", life: 3000 });
            editingId.value = null;
        } else {
            await axios.post("/api/task-assignments", payload);
            toast.add({ severity: "success", summary: "Created", detail: "Assignment created!", life: 3000 });
        }

        resetForm();
        await fetchData();
    } catch {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to save assignment", life: 3000 });
    }
};

const resetForm = () => {
    form.value = {
        employee: null,
        task: null,
        status: "New",
        reissue_comment: "",
        committed_hours: "",
        start_date: null,
        end_date: null,
    };
};

// ✅ Fixed version
// ✅ Corrected editEntry function
const editEntry = async (entry) => {
    editingId.value = entry.id;
    console.log("Editing entry:", entry);

    // Wait until employees and tasks are loaded
    if (employees.value.length === 0 || tasks.value.length === 0) {
        await fetchData();
    }

    // ✅ Match by name (since your entry only has employee_name and task_title)
    form.value.employee = employees.value.find(
        (e) => e.name === entry.employee_name
    ) || null;

    form.value.task = tasks.value.find(
        (t) => t.title === entry.task_title
    ) || null;

    form.value.status = statusOptions.includes(entry.status)
        ? entry.status
        : "New";

    form.value.reissue_comment = entry.reissue_comment || "";
    form.value.committed_hours = entry.committed_hours || "";
    form.value.start_date = entry.start_date ? new Date(entry.start_date) : null;
    form.value.end_date = entry.end_date ? new Date(entry.end_date) : null;

    window.scrollTo({ top: 0, behavior: "smooth" });
};

const deleteEntry = (id: number) => {
    confirm.require({
        message: "Are you sure you want to delete this assignment?",
        header: "Confirm Deletion",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: async () => {
            await axios.delete(`/api/task-assignments/${id}`);
            toast.add({ severity: "success", summary: "Deleted", detail: "Assignment deleted", life: 3000 });
            await fetchData();
        },
    });
};

const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "employee_name", label: "Employee", align: "center" },
    { key: "task_title", label: "Task Title", align: "center" },
    { key: "status", label: "Status", align: "center" },
    { key: "start_date", label: "Start Date", align: "center" },
    { key: "end_date", label: "End Date", align: "center" },
    { key: "committed_hours", label: "Commited Hour", align: "center" },
    { key: "actions", label: "Actions", align: "center" },
];

const tableRows = computed(() =>
    entries.value.map((entry, index) => ({
        sn: index + 1,
        id: entry.id,
        employee_name: entry.employee?.name || "—",
        task_title: entry.task?.title || "—",
        status: entry.status,
        start_date: entry.start_date ? new Date(entry.start_date).toLocaleDateString() : "—",
        end_date: entry.end_date ? new Date(entry.end_date).toLocaleDateString() : "—",
        committed_hours: entry.committed_hours || "—",
    }))
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Task Assignment" />
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-8">
            <Card>
                <template #title>
                    <h2 class="text-xl font-semibold">
                        {{ editingId ? "Edit Assignment" : "Add New Assignment" }}
                    </h2>
                </template>

                <template #content>
                    <div class="flex justify-center items-center py-10 bg-gray-50 rounded-lg"
                        style="background-image: url('/images/form_bg/form_bg.jpg'); background-size: cover; background-position: center;">
                        <form @submit.prevent="submitForm"
                            class="space-y-4 bg-white p-6 rounded-xl shadow-lg w-full max-w-2xl">
                            <!-- Employee -->
                            <div>
                                <label class="font-semibold block mb-2">Employee</label>
                                <Multiselect v-model="form.employee" :options="employees" label="name" track-by="id"
                                    placeholder="Select employee" />
                            </div>

                            <!-- Task -->
                            <div>
                                <label class="font-semibold block mb-2">Task</label>
                                <Multiselect v-model="form.task" :options="tasks" label="title" track-by="id"
                                    placeholder="Select task" />
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="font-semibold block mb-2">Status</label>
                                <Multiselect v-model="form.status" :options="statusOptions" placeholder="Select status"
                                    :allow-empty="false" />
                            </div>

                            <!-- Committed Hours -->
                            <div>
                                <label class="font-semibold block mb-2">Committed Hours</label>
                                <InputNumber v-model="form.committed_hours" :min="1" showButtons mode="decimal"
                                    class="w-full" placeholder="Enter committed hours" />
                            </div>

                            <!-- Start Date -->
                            <div>
                                <label class="font-semibold block mb-2">Start Date</label>
                                <Calendar v-model="form.start_date" dateFormat="yy-mm-dd" showIcon />
                            </div>

                            <!-- End Date -->
                            <div>
                                <label class="font-semibold block mb-2">End Date (Optional)</label>
                                <Calendar v-model="form.end_date" dateFormat="yy-mm-dd" showIcon />
                            </div>

                            <!-- Reissue comment -->
                            <div v-if="form.status === 'Reissue'">
                                <label class="font-semibold block mb-2">Reissue Comment</label>
                                <textarea v-model="form.reissue_comment" class="w-full border rounded p-2"></textarea>
                            </div>

                            <div class="flex justify-center mt-6">
                                <Button type="submit" :label="editingId ? 'Update Assignment' : 'Save Assignment'"
                                    icon="pi pi-save" class="p-button-success w-1/2" />
                            </div>
                        </form>
                    </div>
                </template>
            </Card>

            <DataTable title="Assignments List" :columns="columns" :rows="tableRows" :onEdit="editEntry"
                :onDelete="deleteEntry" :showSearch="true" />
        </div>
    </AppLayout>
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";

.p-inputtext,
.p-datepicker {
    width: 100% !important;
}
</style>
