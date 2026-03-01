<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import axios from "axios";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import Multiselect from "vue-multiselect";

const toast = useToast();
const confirm = useConfirm();

// Breadcrumb
const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Client Management", href: "/client-management" },
];

// Clients
const clients = ref<any[]>([]);
const searchQuery = ref(""); // For search

// All status values
const statusTabs = ref<string[]>([
    "All",
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
]);

const activeStatus = ref("All");

const countries = ref<any[]>([]);
const areas = ref<any[]>([]);
const selectedCountry = ref(null);
const selectedArea = ref(null);

const selectedReferredBy = ref(null);
const referredByOptions = ref([]);

// Fetch clients
const fetchClients = async () => {
    try {
        const { data } = await axios.get("/api/clients");
        clients.value = data.map(c => ({ ...c, status: c.status || "Running" }));

        const uniqueCountries = Array.from(new Set(data.map(c => c.country_name).filter(Boolean)));
        countries.value = uniqueCountries.map(c => ({ label: c, value: c }));

        // Extract referred_by options
        const uniqueReferredBy = Array.from(
            new Set(data.map(c => c.referred_by_name).filter(Boolean))
        );

        referredByOptions.value = uniqueReferredBy.map(r => ({
            name: r
        }));
    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch clients", life: 3000 });
    }
};

// Fetch areas based on selected country
const fetchAreas = () => {
    if (!selectedCountry.value) {
        areas.value = [];
        selectedArea.value = null;
        return;
    }

    const countryName = selectedCountry.value.value; // ✅ extract real string

    const filteredAreas = clients.value
        .filter(c => c.country_name === countryName)
        .map(c => c.area_name)
        .filter(Boolean);

    const uniqueAreas = Array.from(new Set(filteredAreas));
    areas.value = uniqueAreas.map(a => ({ label: a, value: a }));
    selectedArea.value = null;
};

// Reset filters
const resetFilters = () => {
    selectedCountry.value = null;
    selectedReferredBy.value = null;
    selectedArea.value = null;
    searchQuery.value = "";
    areas.value = [];
};

// Filtered clients
const filteredClients = computed(() => {
    let filtered = clients.value;

    // ✅ STATUS FILTER (TAB)
    if (activeStatus.value !== "All") {
        filtered = filtered.filter(c => c.status === activeStatus.value);
    }

    // SEARCH FILTER
    if (searchQuery.value.trim() !== "") {
        const q = searchQuery.value.toLowerCase();
        filtered = filtered.filter(c =>
            (c.company_name ?? '').toLowerCase().includes(q) ||
            (c.name ?? '').toLowerCase().includes(q) ||
            (c.number ?? '').toLowerCase().includes(q) ||
            (c.operator_name ?? '').toLowerCase().includes(q) ||
            (c.oparetor_number ?? '').toLowerCase().includes(q) ||
            (c.area_name ?? '').toLowerCase().includes(q) ||
            (c.project_name ?? '').toLowerCase().includes(q)
        );
    }

    // COUNTRY FILTER
    if (selectedCountry.value) {
        const countryName = selectedCountry.value.value;
        filtered = filtered.filter(c => c.country_name === countryName);
    }

    // AREA FILTER
    if (selectedArea.value) {
        const areaName = selectedArea.value.value;
        filtered = filtered.filter(c => c.area_name === areaName);
    }

    // REFERRED BY FILTER
    if (selectedReferredBy.value) {
        filtered = filtered.filter(c =>
            c.referred_by_name === selectedReferredBy.value.name
        );
    }

    return filtered;
});

const statusCounts = computed(() => {
    const counts: Record<string, number> = {};

    // init all statuses with 0
    statusTabs.value.forEach(status => {
        counts[status] = 0;
    });

    // ✅ COUNT FROM FULL CLIENT LIST
    clients.value.forEach(c => {
        const status = c.status || "Running";

        if (counts[status] !== undefined) {
            counts[status]++;
        }

        counts["All"]++;
    });

    return counts;
});

// Tasks
const tasks = ref<any[]>([]);

// Fetch tasks
const fetchTasks = async () => {
    try {
        const { data } = await axios.get("/api/tasks");
        tasks.value = data;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch tasks.",
            life: 3000,
        });
    }
};

// Notes
const notes = ref<any[]>([]); // fetched from API
const showNoteModal = ref(false);
const noteModalTitle = ref("");
const selectedClientNotes = ref<any[]>([]);
const selectedClientId = ref<number | null>(null);
const newNoteContent = ref("");

// Fetch notes
const fetchNotes = async () => {
    try {
        const { data } = await axios.get("/api/notes"); // your notes API
        notes.value = data;
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch notes.", life: 3000 });
    }
};

// Get last note date
const getLastNoteDate = (clientId: number) => {
    const clientNotes = notes.value.filter(n => n.client_id === clientId);
    if (!clientNotes.length) return "-";
    const lastNote = clientNotes.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())[0];
    return new Date(lastNote.created_at).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
};

// Add new note
const addNote = async (clientId: number | null) => {
    if (!clientId) return toast.add({ severity: "error", summary: "Error", detail: "Client not selected", life: 3000 });
    if (!newNoteContent.value.trim()) return;

    try {
        await axios.post("/api/notes", { client_id: clientId, content: newNoteContent.value });
        toast.add({ severity: "success", summary: "Success", detail: "Note added successfully!", life: 3000 });
        newNoteContent.value = "";
        await fetchNotes();
        viewNotes(clientId);
        // Scroll to last note
        nextTick(() => {
            const container = document.querySelector('#note-container');
            container?.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
        });
    } catch (error: any) {
        const msg = error.response?.data?.message || "Failed to add note.";
        toast.add({ severity: "error", summary: "Error", detail: msg, life: 3000 });
    }
};

const editingNoteId = ref<number | null>(null);
const editingNoteContent = ref("");

const startEditNote = (note: any) => {
    editingNoteId.value = note.id;
    editingNoteContent.value = note.content;
};

const updateNote = async () => {
    if (!editingNoteId.value) return;

    try {
        await axios.put(`/api/notes/${editingNoteId.value}`, {
            content: editingNoteContent.value
        });

        toast.add({
            severity: "success",
            summary: "Updated",
            detail: "Note updated successfully",
            life: 3000
        });

        editingNoteId.value = null;
        editingNoteContent.value = "";

        await fetchNotes();
        viewNotes(selectedClientId.value!);

    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to update note",
            life: 3000
        });
    }
};

// View notes / timeline
const viewNotes = (clientId: number) => {
    selectedClientId.value = clientId; // store the client id
    selectedClientNotes.value = notes.value
        .filter(n => n.client_id === clientId)
        .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
    noteModalTitle.value = "Client Notes";
    showNoteModal.value = true;
};

const showTimelineModal = ref(false);
const timelineTab = ref<'all' | 'notes' | 'new_tasks' | 'assigned_tasks' | 'reissue_tasks' | 'completed_tasks' | 'status'>('all');
const clientTimeline = ref<any[]>([]);

const filteredTimeline = computed(() => {
    if (timelineTab.value === 'all') {
        return [...clientTimeline.value].sort(
            (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
        );
    }

    if (timelineTab.value === 'notes') {
        return clientTimeline.value.filter(i => i.type === 'note');
    }

    if (timelineTab.value === 'new_tasks') {
        return clientTimeline.value.filter(i => i.type === 'task_new');
    }

    if (timelineTab.value === 'assigned_tasks') {
        return clientTimeline.value.filter(i => i.type === 'task_assigned');
    }

    if (timelineTab.value === 'reissue_tasks') {
        return clientTimeline.value.filter(i => i.type === 'task_reissue');
    }

    if (timelineTab.value === 'completed_tasks') {
        return clientTimeline.value.filter(i => i.type === 'task_complete');
    }

    if (timelineTab.value === 'status') {
        return clientTimeline.value.filter(i => i.type === 'status');
    }

    return [];
});

const openTimelineModal = async (clientId: number) => {
    selectedClientId.value = clientId;

    // Fetch timeline
    try {
        const { data } = await axios.get(`/api/clients/${clientId}/timeline`);
        clientTimeline.value = data;
        timelineTab.value = 'all';
        showTimelineModal.value = true;
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch timeline', life: 3000 });
    }
};

onMounted(() => {
    const loadData = async () => {
        await fetchClients();
        await fetchTasks();
        await fetchNotes();
    };
    loadData();
});

const showStatusReasonModal = ref(false);
const pendingStatusClientId = ref<number | null>(null);
const pendingStatusValue = ref("");
const statusChangeReason = ref("");

// Change client status
const changeStatus = (id: number, status: string) => {
    pendingStatusClientId.value = id;
    pendingStatusValue.value = status;
    statusChangeReason.value = "";
    showStatusReasonModal.value = true;
};

const confirmStatusChange = async () => {
    if (!statusChangeReason.value.trim()) {
        toast.add({
            severity: "warn",
            summary: "Required",
            detail: "Please enter status change reason",
            life: 3000
        });
        return;
    }

    try {
        await axios.patch(`/api/clients/${pendingStatusClientId.value}/status`, {
            status: pendingStatusValue.value,
            reason: statusChangeReason.value
        });

        toast.add({
            severity: "success",
            summary: "Updated",
            detail: "Status updated successfully",
            life: 3000
        });

        showStatusReasonModal.value = false;
        await fetchClients();

    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to update status",
            life: 3000
        });
    }
};

const getLastTaskDates = (clientId: number) => {
    const clientTasks = tasks.value.filter(t => t.shop_id === clientId);
    const lastTask = clientTasks.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())[0];

    const completedTasks = clientTasks.filter(t => t.status === "Complete");
    const lastCompleted = completedTasks.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())[0];

    return {
        lastTask: lastTask ? new Date(lastTask.created_at).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" }) : "-",
        lastCompletedTask: lastCompleted ? new Date(lastCompleted.created_at).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" }) : "-"
    };
};

// Operator History
const showOperatorHistoryModal = ref(false);
const operatorHistory = ref<any[]>([]);
const selectedOperatorClient = ref<any>(null);

// Open operator history modal
const openOperatorHistory = async (client: any) => {
    selectedOperatorClient.value = client;
    try {
        const { data } = await axios.get(`/api/clients/${client.id}/operator-history`);
        operatorHistory.value = data;
        showOperatorHistoryModal.value = true;
    } catch (e) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to load operator history",
            life: 3000
        });
    }
};

const operatorCreatedTime = computed(() => {
    if (!operatorHistory.value.length) return null;
    return operatorHistory.value[0].created_at;
});

const filteredOperatorHistory = computed(() => {
    return operatorHistory.value.filter(item =>
        item.old_operator_name && item.old_operator_number
    );
});

function formatDate(dateString) {
    const options = {
        month: 'short',   // "Feb"
        year: 'numeric',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    };
    return new Date(dateString).toLocaleString('en-US', options);
}

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'Running': return 'bg-green-100 text-green-800';
        case 'Not Using': return 'bg-red-100 text-red-800';
        case 'Closed': return 'bg-gray-100 text-gray-700';
        case 'Software Not Urgent': return 'bg-yellow-100 text-yellow-800';
        case 'Disappointed': return 'bg-pink-100 text-pink-800';
        case 'Not Happy': return 'bg-purple-100 text-purple-800';
        case 'Happy': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-700';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Client List" />
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div
                class="flex justify-between items-center mb-6 bg-white/90 backdrop-blur-md shadow-md px-6 py-4 rounded-2xl border border-gray-200">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i
                        class="pi pi-list-check bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent"></i>
                    Client List
                </h1>

                <div class="flex gap-4 items-center mb-2">
                    <!-- Country Dropdown -->
                    <Dropdown v-model="selectedCountry" :options="countries" optionLabel="label"
                        placeholder="Select Country" @change="fetchAreas" class="w-55" />

                    <!-- Area Dropdown -->
                    <Dropdown v-if="areas.length" v-model="selectedArea" :options="areas" optionLabel="label"
                        placeholder="Select Area" class="w-40" />

                    <div class="min-w-50 max-w-auto">
                        <Multiselect v-model="selectedReferredBy" :options="referredByOptions" label="name" track-by="name"
                            placeholder="Referred By" :searchable="true" :allow-empty="true" />
                    </div>

                    <div class="relative w-55">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                            <i class="pi pi-search"></i>
                        </span>
                        <input type="text" v-model="searchQuery" placeholder="Search client..."
                            class="border rounded-lg p-2 pl-10 shadow-sm w-full focus:ring-2 focus:ring-indigo-300" />
                    </div>

                    <Button label="Reset" class="ml-2" @click="resetFilters" />
                </div>

                <!-- Search Input with Icon -->
            </div>

            <!-- Status Tabs -->
            <div class="flex gap-2 mb-4 flex-wrap">
                <button v-for="status in statusTabs" :key="status"
                    class="px-4 py-2 rounded-full text-sm transition-all duration-200 cursor-pointer" :class="activeStatus === status
                        ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold shadow-lg'
                        : 'bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 hover:text-indigo-600'"
                    @click="activeStatus = status">

                    {{ status }}
                    <!-- Count -->
                    <span class="ml-2 bg-gray-200 text-gray-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                        {{ statusCounts[status] || 0 }}
                    </span>
                </button>
            </div>

            <!-- Client Table -->
            <div class="bg-white rounded-2xl shadow-lg overflow-auto border border-gray-100" style="max-height: 600px;">
                <DataTable :value="filteredClients" paginator :rows="8" :rowsPerPageOptions="[2, 4, 8, 10]"
                    responsiveLayout="scroll" class="text-sm" tableStyle="min-width: 110rem" stripedRows>
                    <!-- Serial Number -->
                    <Column header="SN">
                        <template #body="{ index }">
                            <div>
                                <div> <span class="font-bold">{{ index + 1 }}</span></div>
                            </div>
                        </template>
                    </Column>

                    <!-- Company + Business Type -->
                    <Column header="Organization">
                        <template #body="{ data }">
                            <div>
                                <div>
                                    <span class="font-bold text-base">
                                        {{ data.company_name }}
                                    </span> <br>
                                    {{ data.business_type }}
                                </div>

                                <!-- Timeline button -->
                                <div class="mt-1">
                                    <button
                                        class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-3 py-2 rounded-md text-xs flex items-center gap-2 cursor-pointer hover:shadow-md transition"
                                        @click="openTimelineModal(data.id)"><i class="pi pi-history text-xs"></i>
                                        Timeline</button>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <!-- Name + Number -->
                    <Column header="Status">
                        <template #body="{ data }">
                            <div>
                                <div> <!-- Status Badge -->
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-600"
                                        :class="getStatusBadgeClass(data.status)">
                                        {{ data.status }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Column>


                    <!-- Last Note Date + Note Button -->
                    <Column header="Last Note Date" style="width: 180px;">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1">
                                <span>{{ getLastNoteDate(data.id) }}</span>
                                <button
                                    class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg py-2 mt-1 text-xs"
                                    @click="viewNotes(data.id)"> <i class="pi pi-pencil"></i> Add Note</button>
                            </div>
                        </template>
                    </Column>


                    <!-- Name + Number -->
                    <Column header="Client Name">
                        <template #body="{ data }">
                            <div>
                                <div> <span class="font-bold">{{ data.name }}</span> <br> {{ data.number }}</div>
                            </div>
                        </template>
                    </Column>

                    <!-- Operator Name + Number -->
                    <Column header="Operator Name">
                        <template #body="{ data }">
                            <div>
                                <div>
                                    <span class="font-bold">{{ data.operator_name }}</span> <br>
                                    {{ data.oparetor_number }}
                                </div>

                                <!-- History Button -->
                                <button
                                    class="mt-1 text-xs px-3 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow hover:shadow-lg transition flex items-center gap-1"
                                    @click="openOperatorHistory(data)">
                                    <i class="pi pi-history text-xs"></i> History
                                </button>
                            </div>
                        </template>
                    </Column>

                    <!-- Project Name -->
                    <Column field="project_name" header="Project Name" />

                    <!-- Area + Address -->
                    <Column header="Country / Area " style="width: 200px; text-align: start;">
                        <template #body="{ data }">
                            <div><span class="font-bold">{{ data.country_name }}</span> <br> <span class="font-bold">{{
                                data.area_name }}</span> <br> {{ data.address }}</div>
                        </template>
                    </Column>

                    <!-- Referred By -->
                    <Column header="Referred By">
                        <template #body="{ data }">
                            <div>
                                <div>
                                    <span class="font-bold">{{ data.referred_by_name }}</span> <br>
                                    {{ data.referred_by_number }}
                                </div>

                                <!-- Show 'Client' if the referrer is in clients list -->
                                <div v-if="clients.find(c => c.name === data.referred_by_name)">
                                    <small class="text-green-500 font-bold text-sm">Client</small>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <!-- Last Task Date -->
                    <Column header="Last Task Date">
                        <template #body="{ data }">
                            <div>
                                <div>Entry: <span class="font-bold">{{ getLastTaskDates(data.id).lastTask }}</span>
                                </div>
                                <div>Completed: <span class="font-bold">{{ getLastTaskDates(data.id).lastCompletedTask
                                        }}</span></div>
                            </div>
                        </template>
                    </Column>


                    <!-- Status -->
                    <Column header="Status">
                        <template #body="{ data }">
                            <Dropdown :options="statusTabs.slice(1)" v-model="data.status"
                                :placeholder="data.status || 'Select Status'"
                                @change="changeStatus(data.id, data.status)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- Note Modal -->
        <Dialog v-model:visible="showNoteModal" :header="noteModalTitle" modal
            :style="{ width: '60vw', maxWidth: '500px' }" :closable="true">
            <div class="space-y-4 max-h-[70vh] overflow-auto px-3 py-2">

                <!-- Add New Note -->
                <div class="flex gap-2 mb-4">
                    <input v-model="newNoteContent" type="text" placeholder="Add new note..."
                        class="border p-2 rounded w-full" />
                    <Button label="Add" icon="pi pi-plus" class="p-button-sm p-button-success"
                        @click="addNote(selectedClientId)" />
                </div>

                <!-- Timeline Notes -->
                <div v-for="note in selectedClientNotes" :key="note.id"
                    class="bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-all duration-300">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-gray-500 text-sm">{{ new Date(note.created_at).toLocaleString('en-GB')
                            }}</span>
                    </div>
                    <div class="flex justify-between items-start gap-2">
                        <div v-if="editingNoteId !== note.id" class="text-gray-700">
                            {{ note.content }}
                        </div>

                        <input v-else v-model="editingNoteContent" class="border p-2 rounded w-full" />

                        <div class="flex gap-1">
                            <Button v-if="editingNoteId !== note.id" icon="pi pi-pencil"
                                class="p-button-text p-button-sm" @click="startEditNote(note)" />

                            <Button v-else icon="pi pi-check" class="p-button-text p-button-sm p-button-success"
                                @click="updateNote" />
                        </div>
                    </div>
                </div>

            </div>
        </Dialog>

        <!-- Timeline History Modal -->
        <Dialog v-model:visible="showTimelineModal" header="Client Timeline" modal
            :style="{ width: '60vw', maxWidth: '800px' }">

            <!-- Tabs with counts -->
            <div class="flex gap-2 mb-4 flex-wrap">

                <button @click="timelineTab = 'all'" :class="timelineTab === 'all'
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg'
                    : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-full text-sm">
                    All ({{ clientTimeline.length }})
                </button>

                <button @click="timelineTab = 'notes'" :class="timelineTab === 'notes'
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg'
                    : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-full text-sm">
                    Notes ({{clientTimeline.filter(i => i.type === 'note').length}})
                </button>

                <button @click="timelineTab = 'new_tasks'" :class="timelineTab === 'new_tasks'
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg'
                    : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-full text-sm">
                    New Tasks ({{clientTimeline.filter(i => i.type === 'task_new').length}})
                </button>

                <button @click="timelineTab = 'assigned_tasks'" :class="timelineTab === 'assigned_tasks'
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg'
                    : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-full text-sm">
                    Assigned ({{clientTimeline.filter(i => i.type === 'task_assigned').length}})
                </button>

                <button @click="timelineTab = 'reissue_tasks'" :class="timelineTab === 'reissue_tasks'
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg'
                    : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-full text-sm">
                    Reissue ({{clientTimeline.filter(i => i.type === 'task_reissue').length}})
                </button>

                <button @click="timelineTab = 'completed_tasks'" :class="timelineTab === 'completed_tasks'
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg'
                    : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-full text-sm">
                    Completed ({{clientTimeline.filter(i => i.type === 'task_complete').length}})
                </button>

                <button @click="timelineTab = 'status'" :class="timelineTab === 'status'
                    ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg'
                    : 'bg-gray-200 text-gray-700'" class="px-4 py-2 rounded-full text-sm">
                    Status ({{clientTimeline.filter(i => i.type === 'status').length}})
                </button>

            </div>

            <!-- Timeline Content -->
            <div class="max-h-[60vh] overflow-auto">
                <div class="relative border-l-2 border-gray-200 ml-4">

                    <div v-for="item in filteredTimeline" :key="item.id" class="mb-6 relative pl-6">

                        <!-- DOT COLOR -->
                        <span class="absolute -left-3 top-0 w-6 h-6 rounded-full border-2 border-white" :class="{
                            'bg-gradient-to-r from-blue-500 to-indigo-600': item.type === 'client_created',
                            'bg-gradient-to-r from-indigo-500 to-gray-600': item.type === 'note',
                            'bg-gradient-to-r from-purple-400 to-cyan-500': item.type === 'task_new',
                            'bg-gradient-to-r from-yellow-400 to-orange-500': item.type === 'task_pending',
                            'bg-gradient-to-r from-green-500 to-emerald-600': item.type === 'task_complete',
                            'bg-gradient-to-r from-orange-500 to-red-600': item.type === 'status'
                        }"></span>

                        <!-- TIME -->
                        <div class="text-gray-500 text-xs">
                            {{ new Date(item.created_at).toLocaleString('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true
                            }) }}
                        </div>

                        <!-- CONTENT -->
                        <!-- CLIENT CREATED DESIGN -->
                        <div v-if="item.type === 'client_created'"
                            class="relative p-4 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg overflow-hidden mt-1">

                            <!-- Glow circle -->
                            <div class="absolute -top-6 -right-6 w-24 h-24 bg-white/20 rounded-full blur-2xl"></div>

                            <!-- Title -->
                            <div class="flex items-center gap-2 font-bold text-sm mb-1">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Client Created
                            </div>

                            <!-- Subtitle -->
                            <div class="text-xs opacity-90 mb-2">
                                New client profile was created in the system
                            </div>

                            <!-- Time badge -->
                            <div class="inline-block bg-white/20 px-3 py-1 rounded-full text-xs font-semibold">
                                {{ new Date(item.created_at).toLocaleString('en-GB', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: 'numeric',
                                    minute: '2-digit',
                                    hour12: true
                                }) }}
                            </div>
                        </div>


                        <!-- NORMAL CONTENT (all others) -->
                        <div v-else class="p-3 rounded-lg mt-1 shadow-sm" :class="{
                            'bg-blue-50': item.type === 'note',
                            'bg-purple-50': item.type === 'task_new',
                            'bg-yellow-50': item.type === 'task_pending',
                            'bg-green-50': item.type === 'task_complete',
                            'bg-red-50': item.type === 'status'
                        }">
                            <!-- TITLE -->
                            <div class="font-semibold mb-1">
                                <span v-if="item.type === 'note'">Note</span>
                                <span v-if="item.type === 'task_new'">New Task</span>
                                <span v-if="item.type === 'task_pending'">Task Pending</span>
                                <span v-if="item.type === 'task_complete'">Task Completed</span>
                                <span v-if="item.type === 'status'">Status</span>
                            </div>


                            <div v-if="item.type === 'task_new'">
                                <div class="font-semibold">{{ item.title }}</div>

                                <div class="text-sm text-gray-700" v-html="item.details"></div>

                                <!-- Image -->
                                <div v-if="item.image_path" class="mt-2">
                                    <img :src="`${item.image_path}`" alt="Task Image"
                                        class="w-full sm:w-1/2 md:w-1/3 h-32 object-cover rounded-lg border border-gray-200 shadow-sm" />
                                </div>

                                <div class="text-xs text-gray-500 mt-1">
                                    Start Date: {{ new Date(item.start_date).toLocaleDateString('en-GB') }}
                                </div>

                                <span class="inline-block mt-1 text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">
                                    Newly Added Task
                                </span>
                            </div>


                            <div v-if="item.type === 'task_assigned' || item.type === 'task_reissue'">
                                <div class="font-semibold">{{ item.title }}</div>
                                <div class="text-sm text-gray-700" v-html="item.details"></div>

                                !-- Image -->
                                <div v-if="item.image_path" class="mt-2">
                                    <img :src="`${item.image_path}`" alt="Task Image"
                                        class="w-full sm:w-1/2 md:w-1/3 h-32 object-cover rounded-lg border border-gray-200 shadow-sm" />
                                </div>

                                <div class="text-xs text-gray-500 mt-1">
                                    Start Date: {{ new Date(item.start_date).toLocaleDateString('en-GB') }}
                                </div>
                                <span class="inline-block mt-1 text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">
                                    {{ item.type === 'task_assigned' ? 'Assigned Task' : 'Reissued Task' }}
                                </span>
                            </div>

                            <div v-if="item.type === 'task_complete'">
                                <div class="font-semibold">{{ item.title }}</div>

                                <div class="text-sm text-gray-700" v-html="item.details"></div>

                                !-- Image -->
                                <div v-if="item.image_path" class="mt-2">
                                    <img :src="`${item.image_path}`" alt="Task Image"
                                        class="w-full sm:w-1/2 md:w-1/3 h-32 object-cover rounded-lg border border-gray-200 shadow-sm" />
                                </div>

                                <div class="text-xs text-gray-500 mt-1">
                                    Start Date: {{ new Date(item.start_date).toLocaleDateString('en-GB') }}
                                </div>

                                <div v-if="item.complete_note"
                                    class="mt-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                    <div v-if="item.complete_note?.length"
                                        class="mt-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded flex justify-between">
                                        ✔ Complete Note: {{ item.complete_note[0].note }}

                                        <span>
                                            ({{ new Date(item.complete_note[0].submitted_at).toLocaleDateString('en-GB',
                                                {
                                                    day: '2-digit',
                                                    month: 'short',
                                                    year: 'numeric',
                                                    hour: 'numeric',
                                                    minute: '2-digit',
                                                    hour12: true
                                                }) }})
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- DESCRIPTION -->
                            <div v-if="item.type !== 'status'">
                                {{ item.description }}
                            </div>

                            <!-- STATUS BADGES -->
                            <div v-if="item.type === 'status'" class="mt-2">
                                <!-- Card container -->
                                <div
                                    class="p-4 bg-white shadow-md rounded-xl border-l-4 border-indigo-500 hover:shadow-lg transition-shadow duration-300">

                                    <!-- Status transition -->
                                    <div class="flex items-center gap-3 mb-2">
                                        <!-- Old Status -->
                                        <span
                                            class="px-4 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200 shadow-sm">
                                            Old Status : {{ item.old_status }}
                                        </span>

                                        <!-- Arrow -->
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7">
                                            </path>
                                        </svg>

                                        <!-- New Status -->
                                        <span
                                            class="px-4 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md">
                                            Current Status : {{ item.new_status }}
                                        </span>
                                    </div>

                                    <!-- Reason box -->
                                    <div
                                        class="text-sm text-gray-600 italic bg-gray-50 px-3 py-2 rounded-md border border-gray-100 shadow-inner">
                                        <span class="font-semibold text-gray-700">Reason:</span> {{ item.reason }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </Dialog>

        <!-- Operator History Modal -->
        <Dialog v-model:visible="showOperatorHistoryModal" modal :style="{ width: '48vw', maxWidth: '650px' }"
            :closable="true" class="operator-history-modal">

            <!-- Custom Header -->
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white flex items-center justify-center shadow-lg">
                            <i class="pi pi-users text-lg"></i>
                        </div>
                        <div>
                            <div class="text-lg font-bold">Operator History</div>
                            <div class="text-xs text-gray-500">
                                {{ selectedOperatorClient?.company_name }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Client Info Card -->
            <div
                class="mb-5 p-4 rounded-xl bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 border border-indigo-100 shadow-sm">
                <div class="flex items-center gap-3">
                    <div
                        class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-lg">
                        {{ selectedOperatorClient?.operator_name?.charAt(0) }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-800">
                            {{ selectedOperatorClient?.operator_name }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ selectedOperatorClient?.oparetor_number }}
                        </div>

                        <div>
                            Created Date & Time: <span class="font-bold">{{ formatDate(operatorCreatedTime) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredOperatorHistory.length === 0"
                class="flex flex-col items-center justify-center py-14 text-gray-500">
                <i class="pi pi-inbox text-5xl mb-3 opacity-40"></i>
                <div class="text-sm">No operator change history found</div>
            </div>

            <!-- Timeline -->
            <div v-else class="relative pl-8 max-h-[55vh] overflow-auto pr-2">

                <!-- Vertical Line -->
                <div
                    class="absolute left-4 top-0 bottom-0 w-[2px] bg-gradient-to-b from-indigo-300 via-purple-300 to-pink-300">
                </div>

                <!-- OPERATOR UPDATE HISTORY -->
                <div v-for="item in filteredOperatorHistory" :key="item.id" class="mb-8 relative">
                    <span
                        class="absolute -left-7 top-0 w-6 h-6 rounded-full bg-white border-4 border-indigo-500 shadow-md">
                    </span>

                    <div class="text-sm font-bold text-gray-500 mt-3 ml-2">
                        {{ formatDate(item.created_at) }}
                    </div>

                    <div class="ml-4 bg-white rounded-2xl p-4 shadow-md hover:shadow-lg transition">

                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">
                            Operator Changed
                        </div>

                        <div class="flex items-center justify-between gap-3">

                            <!-- Old -->
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-9 h-9 rounded-full bg-red-100 text-red-700 flex items-center justify-center font-bold">
                                    {{ item.old_operator_name.charAt(0) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-700">
                                        {{ item.old_operator_name }}
                                    </div>
                                    <div class="text-xs font-bold text-gray-500">
                                        {{ item.old_operator_number }}
                                    </div>
                                </div>
                            </div>

                            <i class="pi pi-arrow-right text-indigo-500 text-lg"></i>

                            <!-- New -->
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-9 h-9 rounded-full bg-green-100 text-green-700 flex items-center justify-center font-bold">
                                    {{ item.new_operator_name.charAt(0) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-700">
                                        {{ item.new_operator_name }}
                                    </div>
                                    <div class="text-xs font-bold text-gray-500">
                                        {{ item.new_operator_number }}
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </Dialog>


        <Dialog v-model:visible="showStatusReasonModal" header="Status Change Reason" modal :style="{ width: '400px' }">

            <div class="space-y-3">
                <label class="font-semibold text-gray-700">
                    Why are you changing status?
                </label>

                <textarea v-model="statusChangeReason" rows="3" class="w-full border rounded-lg p-2"
                    placeholder="Enter reason...">
        </textarea>

                <div class="flex justify-end gap-2 mt-3">
                    <Button label="Cancel" class="p-button-text" @click="showStatusReasonModal = false" />

                    <Button label="Confirm" icon="pi pi-check" class="p-button-success" @click="confirmStatusChange" />
                </div>
            </div>
        </Dialog>

    </AppLayout>
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";
/* Hover effect */
.p-datatable .p-datatable-tbody>tr:hover {
    background-color: rgba(255, 229, 229, 0.3);
    transition: background-color 0.3s;
}

.operator-history-modal .p-dialog-header {
    background: linear-gradient(to right, #eef2ff, #f5f3ff, #fdf2f8);
    border-bottom: 1px solid #e5e7eb;
}

.operator-history-modal .p-dialog-content {
    background: #fafafa;
}
</style>
