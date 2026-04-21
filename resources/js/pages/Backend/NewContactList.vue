<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import Dialog from "primevue/dialog";
import Calendar from "primevue/calendar";
import Multiselect from "vue-multiselect";
import AppLayout from "@/Layouts/AppLayout.vue";

const toast = useToast();
const customers = ref<any[]>([]);
const user = ref<any>(null); // logged-in staff

const filterCreatedBy = ref<any>(null);   // staff object
const filterCreatedDate = ref<Date | null>(null);

const staffList = ref<any[]>([]);
const selectedCustomers = ref<number[]>([]);
const allServiceTypeOptions = ref<string[]>([]);
const selectedOldServiceTypes = ref<string[]>([]);
const selectAll = ref(false);
const bulkStaff = ref<any>(null);
const assigning = ref(false);

// Modal states
const showHistoryModal = ref(false);
const modalTitle = ref("");
const modalContent = ref("");

const showPreviewModal = ref(false);
const previewTitle = ref("");
const previewContent = ref("");

const openModal = (title: string, content: string) => {
    previewTitle.value = title;
    previewContent.value = content?.trim() || "";
    showPreviewModal.value = true;
};

// Modal state
const showExtraNoteModal = ref(false);
const noteCustomerId = ref<number | null>(null);
const newNote = ref("");
const customerHistory = ref<any[]>([]);

const editingNoteId = ref<number | null>(null);
const editingContent = ref("");


// Open Note Modal
const openNoteModal = async (customer: any) => {
    noteCustomerId.value = customer.id;
    newNote.value = "";

    await fetchLatestNotes();

    showExtraNoteModal.value = true;
};

const fetchServiceTypes = async () => {
    const { data } = await axios.get('/api/service-types/names');
    allServiceTypeOptions.value = data;
};

const fetchLatestNotes = async () => {
    if (!noteCustomerId.value) return;

    try {
        const { data } = await axios.get(
            `/api/customers/${noteCustomerId.value}/notes`
        );

        // always keep last 2 only
        customerHistory.value = data.slice(0, 2);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to refresh notes',
            life: 3000
        });
    }
};

// Save Note
const saveNote = async () => {
    if (!noteCustomerId.value || !newNote.value.trim()) return;

    try {
        await axios.post(`/api/customers/${noteCustomerId.value}/add-note`, {
            note: newNote.value
        });

        toast.add({ severity: 'success', summary: 'Success', detail: 'Note saved', life: 2000 });

        // Add the new note locally to display instantly
        customerHistory.value.unshift({
            note: newNote.value,
            created_at: new Date(),
            staff: { name: user.value?.name || 'Staff' }
        });

        newNote.value = ''; // clear textarea
        showExtraNoteModal.value = false;
        await fetchLatestNotes();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save note', life: 3000 });
    }
};

const startEdit = (note: any) => {
    editingNoteId.value = note.id;
    editingContent.value = note.note;
};

const updateNote = async () => {
    if (!editingNoteId.value || !editingContent.value.trim()) return;

    try {
        await axios.put(`/api/customer-history/${editingNoteId.value}/update-note`, {
            note: editingContent.value
        });

        const note = customerHistory.value.find(n => n.id === editingNoteId.value);
        if (note) note.note = editingContent.value;

        editingNoteId.value = null;
        editingContent.value = "";
        showExtraNoteModal.value = false;

        toast.add({
            severity: 'success',
            summary: 'Updated',
            detail: 'Note updated successfully',
            life: 2000
        });
    } catch {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update note',
            life: 3000
        });
    }
};

const historyData = ref<any[]>([]);
const currentCustomer = ref<any | null>(null);

const openHistoryModal = async (customer: any) => {
    currentCustomer.value = customer;

    try {
        const { data } = await axios.get(`/api/customers/${customer.id}/history`);
        modalTitle.value = `History of ${customer.name}`;

        // create "created" entry
        const createdEntry = {
            created_at: customer.created_at,
            staff_id: customer.created_by,
            note: 'Customer record created',
            old_data: {}
        };

        // merge & sort DESC (latest first)
        historyData.value = [...data, createdEntry].sort(
            (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
        );

        showHistoryModal.value = true;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch history',
            life: 3000
        });
    }
};

const users = ref<any[]>([]);

// ====================
// FETCH LOGGED-IN STAFF
// ====================
const fetchUsers = async () => {
    try {
        const { data } = await axios.get('/api/users');
        users.value = data; // assuming this returns an array of all staff
        // ✅ ONLY staff can be assigned customers
        staffList.value = data.filter((u: any) => u.role === 'staff');
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch users',
            life: 3000,
        });
    }
};

// ====================
// FETCH NEW CUSTOMERS
// ====================
const fetchNewCustomers = async () => {
    try {
        const { data } = await axios.get("/api/customers");

        let filtered = data.customers
            // ✅ only NEW customers
            .filter((c: any) => c.status === "New")

            // ✅ ONLY UNASSIGNED customers
            .filter((c: any) => c.assigned_staff_id === null)

            .map((c: any) => ({
                ...c,

                // convert JSON string → array
                service_type: (() => {
                    try {
                        return Array.isArray(c.service_type)
                            ? c.service_type
                            : JSON.parse(c.service_type || "[]");
                    } catch {
                        return [];
                    }
                })(),

                numbers:
                    c.numbers
                        ?.map((n: any) => `${n.full_number} (${n.type})`)
                        .join(", ") ?? "-",

                assigned_users: c.assigned_staff ? [c.assigned_staff] : [],
            }));

        customers.value = filtered;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch customers",
            life: 3000,
        });
    }
};

const assignBulkCustomers = async () => {
    if (!selectedCustomers.value.length) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Select customers first", life: 3000 });
        return;
    }

    if (!bulkStaff.value) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Select staff", life: 3000 });
        return;
    }

    assigning.value = true;

    try {
        await axios.post("/api/customers/assign", {
            customer_ids: selectedCustomers.value,
            staff_id: bulkStaff.value.id,
        });

        toast.add({
            severity: "success",
            summary: "Assigned",
            detail: "Customers assigned successfully",
            life: 3000,
        });

        selectedCustomers.value = [];
        selectAll.value = false;
        bulkStaff.value = null;
        fetchNewCustomers();
    } finally {
        assigning.value = false;
    }
};

const assignSingle = async (row: any) => {
    if (!row._assignStaff) return;

    await axios.post("/api/customers/assign", {
        customer_ids: [row.id],
        staff_id: row._assignStaff.id,
    });

    toast.add({
        severity: "success",
        summary: "Assigned",
        detail: `${row.name} assigned successfully`,
        life: 2000,
    });

    fetchNewCustomers();
};

// Modal state for service type
const showServiceTypeModal = ref(false);
const editingServiceCustomer = ref<any | null>(null);
const serviceTypes = ref<string[]>([]);
const newServiceType = ref("");

// Open modal
const openServiceTypeModal = (customer: any) => {
    editingServiceCustomer.value = customer;

    serviceTypes.value = Array.isArray(customer.service_type)
        ? [...customer.service_type]
        : [];

    // preload multiselect
    selectedOldServiceTypes.value = [...serviceTypes.value];

    newServiceType.value = "";
    showServiceTypeModal.value = true;
};

watch(selectedOldServiceTypes, (val) => {
    serviceTypes.value = [...new Set([...val, ...serviceTypes.value])];
});

// Add service type
const addServiceType = () => {
    const value = newServiceType.value.trim();
    if (!value) return;

    if (!serviceTypes.value.includes(value)) {
        serviceTypes.value.push(value);
        selectedOldServiceTypes.value.push(value); // auto-select it
    }

    newServiceType.value = "";
};

// Save service types
const saveServiceTypes = async () => {
    if (!editingServiceCustomer.value) return;

    try {
        // ✅ Send service_type as an array
        await axios.put(`/api/customers/${editingServiceCustomer.value.id}/service-type`, {
            service_type: serviceTypes.value
        });

        toast.add({
            severity: "success",
            summary: "Updated",
            detail: "Service type updated successfully",
            life: 3000
        });

        // Update local table
        const customer = customers.value.find(c => c.id === editingServiceCustomer.value?.id);
        if (customer) customer.service_type = [...serviceTypes.value]; // keep as array

        showServiceTypeModal.value = false;
        editingServiceCustomer.value = null;

    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response?.data?.message || "Failed to update service type",
            life: 3000
        });
    }
};

// Staff Status Modal
const showStaffStatusModal = ref(false);
const editingStaffCustomer = ref<any | null>(null);
const selectedStaffStatus = ref("");

// Open modal
const openStaffStatusModal = (customer: any) => {
    editingStaffCustomer.value = customer;
    selectedStaffStatus.value = customer.staff_status || "";
    showStaffStatusModal.value = true;
};

// Save
const saveStaffStatus = async () => {
    if (!editingStaffCustomer.value) return;

    await updateStaffStatus(editingStaffCustomer.value.id, selectedStaffStatus.value);

    showStaffStatusModal.value = false;
};

// ====================
// UPDATE STAFF STATUS
// ====================
const staffStatusOptions = [
    "Interested",
    "Serious Interested",
    "Call For Demo",
    "Need To Show Demo",
    "Need Direct Meeting",
    "Future",
    "Unwanted",
    "Final Client",
    "New",
];

const updateStaffStatus = async (customerId: number, status: string) => {
    try {
        await axios.put(`/api/customers/${customerId}/staff-status`, {
            staff_status: status,
        });

        toast.add({
            severity: "success",
            summary: "Updated",
            detail: "Staff status updated successfully",
            life: 2000,
        });

        // Update local table without refetching all
        const customer = customers.value.find((c) => c.id === customerId);
        if (customer) customer.staff_status = status;
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response?.data?.message || "Failed to update staff status",
            life: 3000,
        });
    }
};

const formatDate = (date: string | null) => {
    if (!date) return '-';

    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));
};

const formatHistoryValue = (key: string, value: any) => {
    if (!value) return '-';

    // Date fields
    if (
        key === 'last_contact_date' ||
        key === 'next_follow_up_date'
    ) {
        return formatDate(value);
    }

    // HTML content (like client_behaviour)
    if (typeof value === 'string' && value.includes('<')) {
        return value;
    }

    return value;
};

// ====================
// TABLE CONFIG
// ====================
const columns = [
    { key: "select", label: "", align: "center" },
    { key: "name", label: "Name", align: "left" },
    { key: "created_info", label: "Created By", align: "center" },
    { key: "service_type", label: "Service Type", align: "center" },
    { key: "next_follow_up_date", label: "Next Follow Up", align: "center" },
    { key: "staff_status", label: "Staff Status", align: "center", type: "select" },
    { key: "numbers", label: "Numbers", align: "center" },
    { key: "email", label: "Email", align: "left" },
    { key: "shop_type", label: "Shop Type", align: "center" },
    { key: "locations", label: "Locations", align: "center" },
    { key: "lead_source", label: "Lead Source", align: "center" },
    { key: "interest_level", label: "Interest", align: "center" },

    { key: "feature_need", label: "Feature Need", type: "modal" },
    { key: "our_commitment", label: "Our Commitment", type: "modal" },
    { key: "client_behaviour", label: "Client Behaviour", type: "modal" },

    { key: "last_discuss_note", label: "Last Discuss Note", type: "modal" },
    { key: "offer_connect", label: "Offer Connect", align: "center" },
    { key: "last_contact_date", label: "Last Contact", align: "center" },
    // NEW actions column
];

const filteredCustomers = computed(() => {
    return customers.value.filter(c => {
        const matchStaff = filterCreatedBy.value
            ? c.created_by === filterCreatedBy.value.id
            : true;

        const matchDate = filterCreatedDate.value
            ? new Date(c.created_at).toLocaleDateString('en-CA') ===
            filterCreatedDate.value.toLocaleDateString('en-CA')
            : true;

        return matchStaff && matchDate;
    });
});

const formattedUsers = computed(() =>
    users.value.map(u => ({
        ...u,
        label: u.mobile ? `${u.name} (${u.mobile})` : u.name, // Name + mobile
        value: u.id
    }))
);

const tableRows = computed(() =>
    filteredCustomers.value.map((c, index) => ({ sn: index + 1, ...c }))
);

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedCustomers.value = customers.value.map(c => c.id);
    } else {
        selectedCustomers.value = [];
    }
};

watch(selectedCustomers, (val) => {
    selectAll.value = val.length === customers.value.length && customers.value.length > 0;
});

// ====================
// MOUNT DATA
// ====================
onMounted(async () => {
    await fetchUsers();
    await fetchServiceTypes(); // 👈 add this
    fetchNewCustomers();
});

const getStaffName = (staffId: number | null, fallback = 'Staff') => {
    if (!staffId) return fallback;
    const staff = users.value.find(u => u.id === staffId);
    return staff ? staff.name : fallback;
};

</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gray-50 min-h-screen">
            <Toast />

            <div class="">
                <!-- ASSIGNMENT CARD -->
                <Card>
                    <template #title>
                        <h2 class="text-xl font-semibold">Assign Customers to Staff</h2>
                    </template>

                    <template #content>
                        <div class="flex flex-col md:flex-row gap-4 items-center bg-gray-50 p-6 rounded-xl">
                            <div class="w-full md:w-1/3">
                                <label class="font-semibold block mb-2 text-gray-700">Select Staff</label>
                                <Multiselect v-model="bulkStaff" :options="staffList" label="name" track-by="id"
                                    placeholder="Assign selected to staff" class="w-64" />
                            </div>

                            <!-- ASSIGN BUTTON -->
                            <div class="mt-6 md:mt-8">
                                <Button label="Assign Selected" icon="pi pi-user-plus" class="p-button-success px-6"
                                    :loading="loading" @click="assignBulkCustomers" :disabled="assigning" />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <div class="my-6 border-l-4 border-blue-600 pl-5">
                <h1 class="text-3xl font-extrabold text-gray-900 leading-tight">
                    New Contacts Report
                </h1>
                <p class="mt-1 text-gray-600 text-sm">
                    Showing all customers with
                    <span class="text-blue-600 font-semibold">New</span> status.
                </p>
            </div>

            <!-- Search Option Inputs -->
            <div class="bg-white rounded-xl shadow-md p-5 mb-6 border border-gray-100">
                <div class="flex flex-wrap gap-6 items-end">

                    <!-- Created By Filter -->
                    <div class="flex flex-col w-full md:w-64">
                        <label class="text-sm font-semibold text-gray-600 mb-1">
                            <i class="pi pi-user mr-1 text-blue-500"></i>
                            Created By
                        </label>
                        <Multiselect v-model="filterCreatedBy" :options="formattedUsers" label="label" track-by="value"
                            placeholder="Select staff" class="rounded-lg" />
                    </div>

                    <!-- Created Date Filter (PrimeVue Calendar) -->
                    <div class="flex flex-col w-full md:w-64">
                        <label class="text-sm font-semibold text-gray-600 mb-1">
                            <i class="pi pi-calendar mr-1 text-purple-500"></i>
                            Created Date
                        </label>

                        <Calendar v-model="filterCreatedDate" dateFormat="yy-mm-dd" showIcon showButtonBar
                            class="w-full" placeholder="Pick a date" />
                    </div>

                    <!-- Clear Button -->
                    <div class="flex items-end">
                        <button @click="filterCreatedBy = null; filterCreatedDate = null" class="
                        h-[42px]
                        px-6
                        rounded-lg
                        bg-gradient-to-r from-gray-200 to-gray-300
                        text-gray-700 font-medium
                        hover:from-gray-300 hover:to-gray-400
                        transition
                        flex items-center gap-2
                    ">
                            <i class="pi pi-filter-slash"></i>
                            Clear Filters
                        </button>
                    </div>

                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 overflow-x-auto">
                <DataTable title="New Customers" :columns="columns" :rows="tableRows" :showSearch="true"
                    @openModal="openModal">

                    <template #header-select>
                        <div class="flex justify-center items-center gap-2">
                            <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4" />

                            <span class="text-sm font-medium">
                                SN
                            </span>
                        </div>
                    </template>

                    <template #cell-select="{ row }">
                        <div class="flex items-center gap-3 justify-center">
                            <!-- Checkbox -->
                            <input type="checkbox" :value="row.id" v-model="selectedCustomers"
                                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />

                            <!-- Serial Number -->
                            <span class="text-sm font-medium text-gray-700">
                                {{ row.sn }}
                            </span>
                        </div>
                    </template>

                    <!-- Name + Designation -->
                    <template #cell-name="{ row }">
                        <div class="flex flex-col gap-1">
                            <!-- Name + Action Buttons -->
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-gray-800 truncate">
                                    {{ row.name }}
                                </span>

                                <div class="flex items-center gap-1">
                                    <!-- History -->
                                    <button class="w-7 h-7 flex items-center justify-center
                                        rounded-md bg-gray-100 text-gray-600
                                        hover:bg-gray-200 hover:text-gray-800
                                        transition" title="View History" @click="openHistoryModal(row)">
                                        <i class="pi pi-history text-xs"></i>
                                    </button>

                                    <!-- Add Note -->
                                    <button class="w-7 h-7 flex items-center justify-center
                                        rounded-md bg-blue-50 text-blue-600
                                        hover:bg-blue-100 hover:text-blue-700
                                        transition" title="Add Note" @click="openNoteModal(row)">
                                        <i class="pi pi-pencil text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Designation -->
                            <span class="text-sm text-gray-500">
                                {{ row.designation || '-' }}
                            </span>

                            <!-- Assigned Staff -->
                            <div v-for="staff in row.assigned_users" :key="staff.id">
                                <span class="text-xs text-blue-600 font-medium">
                                    Assigned: {{ staff.name || 'Not Assigned' }}
                                </span>
                            </div>

                            <!-- Staff Status -->
                            <span class="text-xs text-gray-500">
                                {{ row.staff_status || '-' }}
                            </span>
                        </div>
                    </template>

                    <!-- Created By + Created Date -->
                    <template #cell-created_info="{ row }">
                        <div class="flex flex-col items-center text-sm">
                            <span class="font-medium text-gray-800">
                                {{ getStaffName(row.created_by) }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ formatDate(row.created_at) }}
                            </span>
                        </div>
                    </template>

                    <template #cell-service_type="{ row }">
                        <div class="flex items-center justify-center gap-2">
                            <div class="flex flex-col text-sm text-gray-700">
                                <span v-for="(type, idx) in row.service_type" :key="idx">
                                    {{ type }}
                                </span>
                            </div>

                            <button @click="openServiceTypeModal(row)" class="text-blue-600 hover:text-blue-800">
                                <i class="pi pi-plus"></i>
                            </button>
                        </div>
                    </template>

                    <template #cell-staff_status="{ row }">
                        <div class="flex items-center justify-center gap-2">
                            <span>{{ row.staff_status || '-' }}</span>
                            <button @click="openStaffStatusModal(row)" class="text-blue-600 hover:text-blue-800">
                                <i class="pi pi-pencil"></i>
                            </button>
                        </div>
                    </template>

                    <!-- Numbers -->
                    <template #cell-numbers="{ row }">
                        <div class="flex flex-col text-sm text-gray-700">
                            <span v-for="(num, idx) in row.numbers.split(',')" :key="idx">{{ num }}</span>
                        </div>
                    </template>

                    <!-- Next Follow-up Date -->
                    <template #cell-next_follow_up_date="{ row }">
                        <span class="text-sm text-gray-600">
                            {{
                                row.next_follow_up_date
                                    ? new Intl.DateTimeFormat('en-GB', {
                                        day: '2-digit', month: 'short', year: 'numeric'
                                    }).format(new Date(row.next_follow_up_date))
                                    : '-'
                            }}
                        </span>
                    </template>

                    <!-- Last Contact Date -->
                    <template #cell-last_contact_date="{ row }">
                        <span class="text-sm text-gray-600">
                            {{
                                row.last_contact_date
                                    ? new Intl.DateTimeFormat('en-GB', {
                                        day: '2-digit', month: 'short', year: 'numeric'
                                    }).format(new Date(row.last_contact_date))
                                    : '-'
                            }}
                        </span>
                    </template>

                </DataTable>
            </div>

            <!-- ServiceType Modal -->
            <Dialog v-model:visible="showServiceTypeModal" header="Edit Service Types" modal
                :style="{ width: '36rem' }">
                <div class="flex flex-col gap-6">

                    <!-- OLD SERVICE TYPES -->
                    <div class="flex flex-col gap-2">
                        <label class="font-semibold text-gray-700">Select Old Service Types</label>
                        <Multiselect v-model="selectedOldServiceTypes" :options="allServiceTypeOptions"
                            placeholder="Select old service types" :multiple="true" :close-on-select="false"
                            :clear-on-select="false" class="w-full" />
                    </div>

                    <!-- NEW SERVICE TYPE INPUT -->
                    <div class="flex flex-col gap-2">
                        <label class="font-semibold text-gray-700">Add New Service Type</label>
                        <div class="flex gap-2">
                            <input v-model="newServiceType" placeholder="Enter service type"
                                class="flex-1 border rounded px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none" />
                            <button @click="addServiceType"
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                                Add
                            </button>
                        </div>
                    </div>

                    <!-- ADDED SERVICE TYPES LIST -->
                    <div class="flex flex-col gap-2">
                        <label class="font-semibold text-gray-700">Added Service Types</label>
                        <div v-if="serviceTypes.length" class="flex flex-col gap-2 max-h-64 overflow-y-auto">
                            <div v-for="(s, idx) in serviceTypes" :key="idx"
                                class="flex justify-between items-center bg-gray-100 px-3 py-2 rounded shadow-sm hover:bg-gray-200 transition">
                                <span class="text-gray-800">{{ s }}</span>
                                <button @click="serviceTypes.splice(idx, 1)" class="text-red-500 hover:text-red-700">
                                    <i class="pi pi-times"></i>
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-gray-400 italic">No service types added yet</div>
                    </div>

                    <!-- DIALOG FOOTER -->
                    <div class="flex justify-end gap-3 mt-4">
                        <button class="px-5 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition"
                            @click="showServiceTypeModal = false">
                            Cancel
                        </button>
                        <button class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                            @click="saveServiceTypes">
                            Save
                        </button>
                    </div>
                </div>
            </Dialog>

            <!-- Staff Status Update Modal -->
            <Dialog v-model:visible="showStaffStatusModal" header="Update Staff Status" modal
                :style="{ width: '30rem' }">
                <div class="flex flex-col gap-4">
                    <label class="font-medium">Select Status:</label>
                    <select v-model="selectedStaffStatus" class="border rounded px-3 py-2">
                        <option v-for="status in staffStatusOptions" :key="status" :value="status">
                            {{ status }}
                        </option>
                    </select>
                </div>

                <div class="text-right mt-6 flex gap-2 justify-end">
                    <button class="px-5 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                        @click="showStaffStatusModal = false">
                        Cancel
                    </button>
                    <button class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" @click="saveStaffStatus">
                        Save
                    </button>
                </div>
            </Dialog>

            <!-- PREVIEW MODAL -->
            <Dialog v-model:visible="showPreviewModal" modal :style="{ width: '40rem' }">

                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    {{ previewTitle }}
                </h2>

                <!-- Glass Card Wrapper -->
                <div v-if="previewContent && previewContent.trim()" class="
            relative overflow-hidden
            rounded-lg prose prose-gray
            bg-white/30 backdrop-blur-xl
            border-l-4 border-blue-500
            shadow-lg shadow-black/10
        ">
                    <!-- Glow Layer -->
                    <span
                        class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/40 via-transparent to-transparent">
                    </span>

                    <!-- HTML Content -->
                    <div v-html="previewContent"
                        class="relative p-6 text-gray-800 leading-relaxed whitespace-pre-line max-w-none">
                    </div>
                </div>

                <!-- No Data Message -->
                <div v-else class="
            flex flex-col items-center justify-center
            p-8 rounded-lg
            bg-gray-50 border border-dashed
            text-gray-500
        ">
                    <i class="pi pi-info-circle text-3xl mb-3 text-gray-400"></i>
                    <p class="text-lg font-medium">
                        There is no data available
                    </p>
                    <p class="text-sm mt-1">
                        No information has been added yet.
                    </p>
                </div>

                <!-- Footer -->
                <div class="text-right mt-6">
                    <button class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
                        @click="showPreviewModal = false">
                        Close
                    </button>
                </div>

            </Dialog>

            <!--HISTORY  MODAL -->
            <Dialog v-model:visible="showHistoryModal" modal :header="modalTitle" :style="{ width: '50rem' }">

                <div class="max-h-96 overflow-y-auto space-y-4">
                    <div v-if="historyData.length" class="space-y-4">
                        <div v-for="(item, idx) in historyData" :key="idx" class="relative pl-8">
                            <!-- Timeline Dot -->
                            <div class="absolute left-0 top-1.5 w-3 h-3 bg-blue-600 rounded-full"></div>
                            <!-- Timeline Line -->
                            <div v-if="idx !== historyData.length - 1"
                                class="absolute left-1.5 top-6 w-0.5 h-full bg-gray-300">
                            </div>

                            <!-- History Card -->
                            <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm text-gray-500">
                                        {{ new Date(item.created_at).toLocaleString('en-GB', {
                                            day: 'numeric',
                                            month: 'short',
                                            year: 'numeric',
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        }) }}
                                    </span>

                                    <span class="text-sm font-semibold text-blue-600">
                                        {{ getStaffName(item.staff_id) }}
                                    </span>
                                </div>

                                <div v-if="item.note && item.note.trim()"
                                    class="text-gray-800 whitespace-pre-line mb-2">
                                    <strong>Note:</strong> {{ item.note }}
                                </div>

                                <div v-if="item.old_data && Object.keys(item.old_data).length"
                                    class="bg-gray-50 p-2 rounded border-l-4 border-gray-300 text-sm text-gray-600">
                                    <strong>Changed Fields:</strong>
                                    <ul class="list-disc list-inside mt-1">
                                        <li v-for="(value, key) in item.old_data" :key="key" class="flex gap-2">
                                            <span class="font-medium capitalize">
                                                {{ key.replace(/_/g, ' ') }}:
                                            </span>

                                            <!-- HTML content -->
                                            <span v-if="typeof value === 'string' && value.includes('<')"
                                                v-html="formatHistoryValue(key, value)"
                                                class="prose prose-sm max-w-none"></span>

                                            <!-- Normal text / formatted date -->
                                            <span v-else class="text-gray-700">
                                                {{ formatHistoryValue(key, value) }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center text-gray-500 py-8">
                        No history available
                    </div>
                </div>

                <div class="text-right mt-6">
                    <button
                        class="px-5 py-2 bg-blue-600 text-white font-medium rounded-md shadow hover:bg-blue-700 transition"
                        @click="showHistoryModal = false">
                        Close
                    </button>
                </div>
            </Dialog>

            <!-- Add Note Modal -->
            <Dialog v-model:visible="showExtraNoteModal" header="Customer Notes & History" :style="{ width: '50rem' }">
                <div class="mb-4">
                    <textarea v-model="newNote" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-400"
                        rows="4" placeholder="Write a new note here..."></textarea>
                </div>

                <div class="mb-4">
                    <h3 class="font-semibold text-gray-800 text-lg mb-2 border-b pb-1">Previous Notes</h3>
                    <div v-if="customerHistory.length" class="space-y-3">

                        <div v-for="(h, idx) in customerHistory" :key="h.id" class="relative p-4 rounded-xl
                            bg-white/40 backdrop-blur-md
                            border border-white/50
                            shadow-sm hover:shadow-md transition">

                            <!-- Header -->
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-gray-500">
                                    {{ new Date(h.created_at).toLocaleString() }}
                                </span>

                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-blue-600">
                                        {{ h.staff?.name || 'Staff' }}
                                    </span>

                                    <!-- Edit button (only last 2 → already limited) -->
                                    <button class="text-gray-500 hover:text-blue-600" @click="startEdit(h)">
                                        <i class="pi pi-pencil"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- VIEW MODE -->
                            <div v-if="editingNoteId !== h.id" class="text-gray-800 whitespace-pre-line">
                                {{ h.note }}
                            </div>

                            <!-- EDIT MODE -->
                            <div v-else>
                                <textarea v-model="editingContent" rows="3"
                                    class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400"></textarea>

                                <div class="flex justify-end gap-2 mt-2">
                                    <button class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                                        @click="updateNote">
                                        Save
                                    </button>
                                    <button class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400"
                                        @click="editingNoteId = null">
                                        Cancel
                                    </button>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div v-else class="text-center text-gray-500 py-6">
                        No notes available
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button @click="saveNote"
                        class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition">Save</button>
                    <button @click="showExtraNoteModal = false"
                        class="bg-gray-300 px-5 py-2 rounded-lg hover:bg-gray-400 transition">Close</button>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";
</style>
