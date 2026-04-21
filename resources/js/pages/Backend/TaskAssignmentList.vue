<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import axios from "axios";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Calendar from "primevue/calendar";
import Card from "primevue/card";
import ProgressSpinner from "primevue/progressspinner";
import Multiselect from "vue-multiselect"; // ✅ import vue-multiselect

const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Task Assignment List", href: "/task-assignment" },
];

const taskAssignments = ref([]);
const filteredTasks = ref([]);
const loading = ref(true);
const showDialog = ref(false);
const selectedTask = ref<any>(null);

// Filters
const selectedEmployees = ref([]); // ✅ multiselect value
const allUsers = ref([]); // ✅ list of all users
const startDate = ref<Date | null>(null);
const endDate = ref<Date | null>(null);
const selectedStatus = ref<string[]>([]); // ✅ fix: add this!

// ✅ Fetch all users for multiselect
async function fetchUsers() {
    try {
        const response = await axios.get("/api/users");
        allUsers.value = response.data.map((user: any) => ({
            id: user.id,
            name: user.name,
        }));
    } catch (error) {
        console.error("Error fetching users:", error);
    }
}

onMounted(async () => {
    await Promise.all([fetchTasks(), fetchUsers()]);
});

async function fetchTasks() {
    loading.value = true;
    try {
        const response = await axios.get("/api/task-assignments");
        taskAssignments.value = response.data;
        filteredTasks.value = response.data;
    } catch (error) {
        console.error("Error fetching task assignments:", error);
    } finally {
        loading.value = false;
    }
}

// ✅ Filter logic
function applyFilters() {
    filteredTasks.value = taskAssignments.value.filter((task) => {
        const selectedIds = selectedEmployees.value.map((e: any) => e.id);

        // ✅ Employee filter
        const employeeMatch =
            selectedIds.length === 0 ||
            selectedIds.includes(task.employee?.id) ||
            selectedIds.includes(task.assigner?.id);

        // ✅ Convert all to "YYYY-MM-DD" strings
        const taskStart = task.start_date;
        const taskEnd = task.end_date;

        const start = startDate.value
            ? new Date(startDate.value).toISOString().split("T")[0]
            : null;
        const end = endDate.value
            ? new Date(endDate.value).toISOString().split("T")[0]
            : null;

        // ✅ Date filter
        let dateMatch = true;
        if (start && taskEnd && taskEnd < start) dateMatch = false; // task ends before filter start
        if (end && taskStart && taskStart > end) dateMatch = false; // task starts after filter end

        // ✅ Status filter
        const statusMatch =
            selectedStatus.value.length === 0 ||
            selectedStatus.value.includes(task.status);

        return employeeMatch && dateMatch && statusMatch;
    });
}

// ✅ Reset filters
function clearFilters() {
    selectedEmployees.value = [];
    startDate.value = null;
    endDate.value = null;
    selectedStatus.value = [];
    filteredTasks.value = [...taskAssignments.value];
}

function openDialog(task: any) {
    selectedTask.value = task;
    showDialog.value = true;
}

const parsedCompleteNotes = computed(() => {
    const completeNoteData = selectedTask.value?.task?.complete_note;
    if (!completeNoteData) return [];

    try {
        const notes = JSON.parse(completeNoteData);

        // Optional: sort by submitted_at if needed
        return notes.sort((a, b) => new Date(a.submitted_at) - new Date(b.submitted_at));
    } catch (error) {
        console.error("Failed to parse complete_note:", error);
        return [];
    }
});

const parsedReissueComments = computed(() => {
    const reissueData = selectedTask.value?.task?.reissue_comment;
    if (!reissueData) return [];

    try {
        const comments = JSON.parse(reissueData);

        // Optional: sort by submitted_at if needed
        return comments.sort((a, b) => new Date(a.submitted_at) - new Date(b.submitted_at));
    } catch (error) {
        console.error("Failed to parse reissue_comment:", error);
        return [];
    }
});

const formatBDDateTime = (dateStr: string | null) => {
    if (!dateStr) return '-';

    const date = new Date(dateStr); // JS automatically parses UTC 'Z' if present

    // Convert to BD timezone using toLocaleString
    const options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZone: 'Asia/Dhaka', // BD timezone
    };

    return date.toLocaleString('en-GB', options);
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';

    const date = new Date(dateStr); // JS automatically parses UTC 'Z' if present

    // Convert to BD timezone using toLocaleString
    const options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        timeZone: 'Asia/Dhaka', // BD timezone
    };

    return date.toLocaleString('en-GB', options);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Task Assignment List" />

        <div class="p-8 min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 relative">
            <!-- HEADER -->
            <div
                class="flex justify-between items-center mb-6 bg-white/80 backdrop-blur-sm shadow-md px-6 py-4 rounded-2xl border border-gray-200">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="pi pi-list-check text-red-500"></i> Task Assignment List
                </h1>
            </div>

            <!-- Filter Card -->
            <div class="mb-6 p-6 bg-white rounded-xl shadow-md border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-1 items-end">
                    <!-- Employee -->
                    <div>
                        <label class="block text-gray-700 text-sm mb-2">Search by Employee</label>
                        <Multiselect v-model="selectedEmployees" :options="allUsers" label="name" track-by="id"
                            placeholder="Select employees" multiple class="w-full" />
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-gray-700 text-sm mb-2">Filter by Status</label>
                        <Multiselect v-model="selectedStatus"
                            :options="['Pending', 'Working', 'Assigned', 'Reissue  ', 'Complete', 'Cancelled']"
                            placeholder="Select status" multiple class="w-full" />
                    </div>
                    <!-- Buttons -->
                    <div class="flex gap-3">
                        <Button icon="pi pi-search" class="bg-red-500 hover:bg-red-600 text-white w-full"
                            @click="applyFilters" />
                        <Button icon="pi pi-refresh" class="p-button-secondary w-full" @click="clearFilters" />
                    </div>
                </div>
            </div>
            <!-- FILTERS -->


            <!-- DATA TABLE -->
            <div class="bg-white/90 backdrop-blur-md shadow-lg border border-gray-100 rounded-2xl overflow-hidden">
                <div v-if="loading" class="flex justify-center items-center h-40">
                    <ProgressSpinner />
                </div>

                <div v-else>
                    <DataTable :value="filteredTasks" paginator :rows="8" :rowsPerPageOptions="[2, 4, 10]"
                        class="text-sm" tableStyle="min-width: 60rem" stripedRows>
                        <Column field="id" header="SN" style="width: 60px" />
                        <Column header="Task">
                            <template #body="{ data }">
                                <div>
                                    <span class="font-semibold text-gray-800">{{ data.task?.title }}</span>
                                    <p class="text-xs text-gray-500">{{ data.task?.shop_name }}</p>
                                </div>
                            </template>
                        </Column>

                        <Column header="Assign Employee">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-user text-red-500"></i>
                                    <span>{{ data.employee?.name || "—" }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Committed Hours">
                            <template #body="{ data }">
                                <div class="w-full text-center">
                                    {{ data.committed_hours ? data.committed_hours + ' Hours' : '—' }}
                                </div>
                            </template>
                        </Column>
                        <Column field="start_date" header="Start Date" />
                        <Column field="end_date" header="End Date" />

                        <Column header="Status">
                            <template #body="{ data }">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="{
                                    'bg-yellow-100 text-yellow-800': data.status === 'Reissue',
                                    'bg-indigo-100 text-indigo-800': data.status === 'Assigned',
                                    'bg-blue-100 text-blue-800': data.status === 'Working',
                                    'bg-green-100 text-green-800': data.status === 'Complete',
                                    'bg-red-100 text-red-800': data.status === 'Cancelled'
                                }">
                                    {{ data.status }}
                                </span>
                            </template>
                        </Column>

                        <Column header="Action" style="text-align:center">
                            <template #body="{ data }">
                                <Button icon="pi pi-eye" rounded text @click="openDialog(data)" :class="{
                                    'text-blue-600 hover:text-blue-800': data.status === 'Working',
                                    'text-indigo-600 hover:text-indigo-800': data.status === 'Assigned',
                                    'text-green-600 hover:text-green-800': data.status === 'Complete',
                                    'text-yellow-600 hover:text-yellow-800': data.status === 'Reissue',
                                    'text-red-600 hover:text-red-800': data.status === 'Cancelled'
                                }" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>

            <!-- DIALOG -->
            <Dialog v-model:visible="showDialog" modal
                :style="{ width: '45vw', borderRadius: '20px', overflow: 'hidden' }">
                <template #header>
                    <div class="flex items-center gap-3 w-full text-white py-4 px-6 rounded-t-2xl" :class="{
                        'bg-gradient-to-r from-blue-600 to-blue-400': selectedTask?.status === 'Working',
                        'bg-gradient-to-r from-green-600 to-green-400': selectedTask?.status === 'Complete',
                        'bg-gradient-to-r from-yellow-600 to-yellow-500': selectedTask?.status === 'Reissue',
                        'bg-gradient-to-r from-indigo-600 to-indigo-400': selectedTask?.status === 'Assigned',
                        'bg-gradient-to-r from-red-600 to-red-400': selectedTask?.status === 'Cancelled'
                    }">
                        <i class="pi pi-briefcase text-2xl"></i>
                        <h2 class="text-xl font-semibold tracking-wide">
                            {{ selectedTask?.task?.title || "Task Details" }}
                        </h2>
                    </div>
                </template>

                <div class="p-6 space-y-5" :class="{
                    'bg-gradient-to-br from-blue-50 to-white': selectedTask.status === 'Working',
                    'bg-gradient-to-br from-green-50 to-white': selectedTask.status === 'Complete',
                    'bg-gradient-to-br from-yellow-50 to-white': selectedTask.status === 'Reissue',
                    'bg-gradient-to-br from-indigo-50 to-white': selectedTask.status === 'Assigned',
                    'bg-gradient-to-br from-red-50 to-white': selectedTask.status === 'Cancelled'
                }">

                    <!-- 🌟 Status Info -->
                    <div class="flex justify-between items-center bg-white shadow-md rounded-xl px-6 py-4 border-l-8"
                        :class="{
                            'border-blue-400': selectedTask.status === 'Working',
                            'border-green-400': selectedTask.status === 'Complete',
                            'border-yellow-400': selectedTask.status === 'Reissue',
                            'border-indigo-400': selectedTask.status === 'Assigned',
                            'border-red-400': selectedTask.status === 'Cancelled'
                        }">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-tag text-lg" :class="{
                                'text-blue-500': selectedTask.status === 'Working',
                                'text-green-500': selectedTask.status === 'Complete',
                                'text-yellow-500': selectedTask.status === 'Reissue',
                                'text-indigo-500': selectedTask.status === 'Assigned',
                                'text-red-500': selectedTask.status === 'Cancelled'
                            }"></i>
                            <p class="text-gray-700 font-medium">
                                Status:
                                <span class="px-3 py-1 rounded-full text-sm font-semibold" :class="{
                                    'bg-blue-100 text-blue-700': selectedTask.status === 'Working',
                                    'bg-green-100 text-green-700': selectedTask.status === 'Complete',
                                    'bg-yellow-100 text-yellow-700': selectedTask.status === 'Reissue',
                                    'bg-indigo-100 text-indigo-700': selectedTask.status === 'Assigned',
                                    'bg-red-100 text-red-700': selectedTask.status === 'Cancelled'
                                }">
                                    {{ selectedTask.status }}
                                </span>
                            </p>
                        </div>
                        <p class="text-sm text-gray-500 italic">
                            Task ID: #{{ selectedTask.id }}
                        </p>
                    </div>

                    <!-- 🏪 Task Info -->
                    <div class="grid grid-cols-2 gap-6 text-sm">
                        <div
                            class="bg-white rounded-xl p-5 shadow-sm hover:shadow-lg transition-all border border-gray-100">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="pi pi-shop" :class="{
                                    'text-blue-500': selectedTask?.status === 'Working',
                                    'text-green-500': selectedTask?.status === 'Complete',
                                    'text-yellow-500': selectedTask?.status === 'Reissue',
                                    'text-indigo-500': selectedTask?.status === 'Assigned',
                                    'text-red-500': selectedTask?.status === 'Cancelled'
                                }"></i>
                                <h3 class="font-semibold text-gray-700">Shop Info</h3>
                            </div>
                            <p><strong>Shop Name:</strong> {{ selectedTask?.task?.shop_name || "—" }}</p>
                            <div class="mt-1 text-gray-700">
                                <strong>Details:</strong>
                                <div v-html="selectedTask?.task?.details || '—'"></div>
                            </div>
                        </div>

                        <!-- ⏰ Timeline -->
                        <div
                            class="bg-white rounded-xl p-5 shadow-sm hover:shadow-lg transition-all border border-gray-100">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="pi pi-calendar" :class="{
                                    'text-blue-500': selectedTask.status === 'Working',
                                    'text-green-500': selectedTask.status === 'Complete',
                                    'text-yellow-500': selectedTask.status === 'Reissue',
                                    'text-indigo-500': selectedTask.status === 'Assigned',
                                    'text-red-500': selectedTask.status === 'Cancelled'
                                }"></i>
                                <h3 class="font-semibold text-gray-700">Task Timeline</h3>
                            </div>
                            <p>
                                <i class="pi pi-calendar-times text-gray-500 mr-2"></i>
                                <strong>Start:</strong> {{ formatDate(selectedTask.start_date) }}
                            </p>
                            <p>
                                <i class="pi pi-calendar-times text-gray-500 mr-2"></i>
                                <strong>End:</strong> {{ formatDate(selectedTask.end_date) || "—" }}
                            </p>
                            <p>
                                <i class="pi pi-clock text-gray-500 mr-2"></i>
                                <strong>Hours : </strong> {{ selectedTask.committed_hours ? selectedTask.committed_hours
                                    + ' Hours'
                                    : "—" }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-xl p-5 shadow hover:shadow-lg" :class="{
                        'bg-blue-50 border border-blue-200': selectedTask.status === 'Working',
                        'bg-green-50 border border-green-200': selectedTask.status === 'Complete',
                        'bg-yellow-50 border border-yellow-200': selectedTask.status === 'Reissue',
                        'bg-red-50 border border-red-200': selectedTask.status === 'Cancelled',
                        'bg-indigo-50 border border-indigo-200': selectedTask.status === 'Assigned'
                    }">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="pi pi-user" :class="{
                                'text-blue-500': selectedTask.status === 'Working',
                                'text-green-500': selectedTask.status === 'Complete',
                                'text-yellow-500': selectedTask.status === 'Reissue',
                                'text-indigo-500': selectedTask.status === 'Assigned',
                                'text-red-500': selectedTask.status === 'Cancelled'
                            }"></i>
                            <h3 class="font-semibold text-gray-700 text-lg">Assigned Employee</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm text-gray-700">
                            <p><strong>Name:</strong> {{ selectedTask.employee?.name || "—" }}</p>
                            <p><strong>Email:</strong> {{ selectedTask.employee?.email || "—" }}</p>
                            <p><strong>Designation:</strong> {{ selectedTask.employee?.designation || "—" }}</p>
                            <p><strong>Employee Code:</strong> {{ selectedTask.employee?.code || "—" }}</p>
                        </div>
                    </div>

                    <!-- Complete Note -->
                    <div v-if="parsedCompleteNotes.length"
                        class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg mt-4 shadow-sm">
                        <h3 class="text-green-600 font-semibold mb-2">Complete Notes History</h3>
                        <ul class="flex flex-col gap-3">
                            <li v-for="(n, index) in parsedCompleteNotes" :key="index"
                                class="bg-white border-green-100 border p-3 rounded-lg shadow hover:shadow-md transition flex flex-col sm:flex-row sm:justify-between sm:items-center">
                                <p class="text-gray-700 flex-1">{{ n.note }}</p>
                                <span class="text-gray-500 text-sm mt-1 sm:mt-0">
                                    {{ formatBDDateTime(n.submitted_at) }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <!-- Reissue Comments (Optional) -->
                    <div v-if="parsedReissueComments.length"
                        class="bg-gray-50 border-l-4 border-yellow-500 p-4 rounded-lg mt-4 shadow-sm">
                        <h3 class="text-yellow-800 font-semibold mb-2">Reissue Comments History</h3>
                        <ul class="flex flex-col gap-3">
                            <li v-for="(n, index) in parsedReissueComments" :key="index"
                                class="bg-white p-3 rounded-lg shadow hover:shadow-md transition flex flex-col sm:flex-row sm:justify-between sm:items-center">
                                <p class="text-gray-700 flex-1">{{ n.comment }}</p>
                                <span class="text-gray-500 text-sm mt-1 sm:mt-0">
                                    {{ formatBDDateTime(n.submitted_at) }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-end gap-3 px-4 pb-3">
                        <Button label="Close" icon="pi pi-times" text @click="showDialog = false" />
                    </div>
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
