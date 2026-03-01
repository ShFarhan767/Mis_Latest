<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import axios from "axios";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Calendar from "primevue/calendar";
import Card from "primevue/card";
import ProgressSpinner from "primevue/progressspinner";

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
const searchName = ref("");
const startDate = ref(null);
const endDate = ref(null);

onMounted(async () => {
    await fetchTasks();
});

const fetchTasks = async () => {
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
};

// Filter logic
const applyFilters = () => {
    let filtered = taskAssignments.value;
    const search = searchName.value.toLowerCase();

    if (search) {
        filtered = filtered.filter(
            (task) =>
                task.employee?.name?.toLowerCase().includes(search) ||
                task.assigner?.name?.toLowerCase().includes(search)
        );
    }

    if (startDate.value && endDate.value) {
        const start = new Date(startDate.value);
        const end = new Date(endDate.value);
        filtered = filtered.filter((task) => {
            const taskDate = new Date(task.start_date);
            return taskDate >= start && taskDate <= end;
        });
    }

    filteredTasks.value = filtered;
};

const clearFilters = () => {
    searchName.value = "";
    startDate.value = null;
    endDate.value = null;
    filteredTasks.value = taskAssignments.value;
};

const openDialog = (task) => {
    selectedTask.value = task;
    showDialog.value = true;
};
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
                <Button label="Add New Task" icon="pi pi-plus"
                    class="bg-red-500 hover:bg-red-600 text-white font-medium px-4 py-2 rounded-lg shadow" />
            </div>

            <!-- FILTERS -->
            <Card
                class="mb-6 p-6 shadow-lg border-0 bg-white/80 backdrop-blur-sm rounded-2xl transition-all hover:shadow-xl">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="pi pi-filter text-red-500"></i> Filter Tasks
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-5 items-end">
                    <div>
                        <label class="block text-gray-700 text-sm mb-2">Search by Employee</label>
                        <InputText v-model="searchName" placeholder="Enter employee or assigner name" class="w-full" />
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm mb-2">Start Date</label>
                        <Calendar v-model="startDate" dateFormat="yy-mm-dd" showIcon class="w-full" />
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm mb-2">End Date</label>
                        <Calendar v-model="endDate" dateFormat="yy-mm-dd" showIcon class="w-full" />
                    </div>
                    <div class="flex gap-3">
                        <Button label="Search" icon="pi pi-search" class="bg-red-500 hover:bg-red-600 text-white w-full"
                            @click="applyFilters" />
                        <Button label="Clear" icon="pi pi-refresh" class="p-button-secondary w-full"
                            @click="clearFilters" />
                    </div>
                </div>
            </Card>

            <!-- DATA TABLE -->
            <div class="bg-white/90 backdrop-blur-md shadow-lg border border-gray-100 rounded-2xl overflow-hidden">
                <div v-if="loading" class="flex justify-center items-center h-40">
                    <ProgressSpinner />
                </div>

                <div v-else>
                    <DataTable :value="filteredTasks" paginator :rows="8" :rowsPerPageOptions="[5, 10, 20]"
                        class="text-sm" tableStyle="min-width: 60rem" stripedRows>
                        <Column field="id" header="#" style="width: 60px" />
                        <Column header="Task">
                            <template #body="{ data }">
                                <div>
                                    <span class="font-semibold text-gray-800">{{ data.task?.title }}</span>
                                    <p class="text-xs text-gray-500">{{ data.task?.shop_name }}</p>
                                </div>
                            </template>
                        </Column>

                        <Column header="Employee">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-user text-red-500"></i>
                                    <span>{{ data.employee?.name || "—" }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="start_date" header="Start Date" />
                        <Column field="end_date" header="End Date" />

                        <Column header="Status">
                            <template #body="{ data }">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="{
                                    'bg-yellow-100 text-yellow-800': data.status === 'Pending',
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
                                    'text-green-600 hover:text-green-800': data.status === 'Complete',
                                    'text-yellow-600 hover:text-yellow-800': data.status === 'Pending',
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
                    <div class="flex items-center gap-3 w-full text-white py-4 px-6" :class="{
                        'bg-gradient-to-r from-blue-600 to-blue-400': selectedTask?.status === 'Working',
                        'bg-gradient-to-r from-green-600 to-green-400': selectedTask?.status === 'Complete',
                        'bg-gradient-to-r from-yellow-600 to-yellow-500': selectedTask?.status === 'Pending',
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
                    'bg-gradient-to-br from-yellow-50 to-white': selectedTask.status === 'Pending',
                    'bg-gradient-to-br from-red-50 to-white': selectedTask.status === 'Cancelled'
                }">
                    <div class="p-5 rounded-xl shadow-md bg-white border-l-8" :class="{
                        'border-blue-400': selectedTask.status === 'Working',
                        'border-green-400': selectedTask.status === 'Complete',
                        'border-yellow-400': selectedTask.status === 'Pending',
                        'border-red-400': selectedTask.status === 'Cancelled'
                    }">
                        <p class="text-gray-700 font-medium">
                            <i class="pi pi-info-circle text-gray-500 mr-2"></i>
                            <strong>Status:</strong>
                            <span class="ml-2 px-3 py-1 rounded-full text-xs font-semibold" :class="{
                                'bg-blue-100 text-blue-700': selectedTask.status === 'Working',
                                'bg-green-100 text-green-700': selectedTask.status === 'Complete',
                                'bg-yellow-100 text-yellow-700': selectedTask.status === 'Pending',
                                'bg-red-100 text-red-700': selectedTask.status === 'Cancelled'
                            }">
                                {{ selectedTask.status }}
                            </span>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="bg-white p-5 rounded-xl shadow-sm">
                            <h3 class="font-semibold text-gray-700 mb-2">Shop Info</h3>
                            <p><strong>Shop:</strong> {{ selectedTask.task?.shop_name || "—" }}</p>
                            <p><strong>Details:</strong> {{ selectedTask.task?.details || "—" }}</p>
                        </div>
                        <div class="bg-white p-5 rounded-xl shadow-sm">
                            <h3 class="font-semibold text-gray-700 mb-2">Timeline</h3>
                            <p><strong>Start:</strong> {{ selectedTask.start_date }}</p>
                            <p><strong>End:</strong> {{ selectedTask.end_date }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl p-5 shadow-inner" :class="{
                        'bg-blue-50 border border-blue-200': selectedTask.status === 'Working',
                        'bg-green-50 border border-green-200': selectedTask.status === 'Complete',
                        'bg-yellow-50 border border-yellow-200': selectedTask.status === 'Pending',
                        'bg-red-50 border border-red-200': selectedTask.status === 'Cancelled'
                    }">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="pi pi-user" :class="{
                                'text-blue-500': selectedTask.status === 'Working',
                                'text-green-500': selectedTask.status === 'Complete',
                                'text-yellow-500': selectedTask.status === 'Pending',
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
