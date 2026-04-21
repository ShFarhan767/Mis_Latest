<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import Dialog from "primevue/dialog";
import DemoNotesDialog from "@/components/demo/DemoNotesDialog.vue";

const toast = useToast();

const loading = ref(false);
const customers = ref<any[]>([]);
const activeTab = ref<"All" | "Pending" | "Done">("All");

const showStatusDialog = ref(false);
const statusCustomer = ref<any | null>(null);
const selectedDemoStatus = ref<"Pending" | "Done">("Pending");
const statusNote = ref("");

const showNotes = ref(false);
const notesCustomer = ref<any | null>(null);

const fetchDemoCustomers = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/api/customers");
        const list = Array.isArray(data?.customers) ? data.customers : [];

        customers.value = list.map((c: any) => ({
            ...c,
            numbers:
                c.numbers
                    ?.map((n: any) => `${n.full_number || n.number} (${n.type || "call"})`)
                    .join(", ") ?? "-",
            service_type: Array.isArray(c.service_type) ? c.service_type : (() => {
                try { return JSON.parse(c.service_type || "[]"); } catch { return []; }
            })(),
        }));
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error?.response?.data?.message || "Failed to load demo customers.",
            life: 3000,
        });
        customers.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(fetchDemoCustomers);

const formatDate = (date: string | null) => {
    if (!date) return "-";
    const d = new Date(date);
    if (isNaN(d.getTime())) return "-";
    return new Intl.DateTimeFormat("en-GB", { day: "2-digit", month: "short", year: "numeric" }).format(d);
};

const tableRows = computed(() =>
    customers.value.map((c: any, index: number) => ({
        sn: index + 1,
        id: c.id,
        name: c.name ?? "-",
        numbers: c.numbers ?? "-",
        email: c.email ?? "-",
        locations: c.locations ?? "-",
        lead_source: c.lead_source ?? "-",
        service_type_text: (c.service_type || []).join(", ") || "-",
        next_follow_up_date: formatDate(c.next_follow_up_date),
        staff_status: c.staff_status ?? "-",
        demo_status: c.demo_status ?? "Pending",
        demo_done_at: c.demo_done_at ?? null,
        demo_notes_unread: c.demo_notes_unread ?? 0,
    }))
);

const total = computed(() => tableRows.value.length);

const filteredRows = computed(() => {
    if (activeTab.value === "All") return tableRows.value;
    return tableRows.value.filter((r: any) => (r.demo_status ?? "Pending") === activeTab.value);
});

const openStatus = (row: any) => {
    statusCustomer.value = row;
    selectedDemoStatus.value = (row.demo_status ?? "Pending") === "Done" ? "Done" : "Pending";
    statusNote.value = "";
    showStatusDialog.value = true;
};

const openChat = (row: any) => {
    notesCustomer.value = row;
    showNotes.value = true;
};

const saveDemoStatus = async () => {
    if (!statusCustomer.value) return;

    if (selectedDemoStatus.value === "Done" && !statusNote.value.trim()) {
        toast.add({
            severity: "warn",
            summary: "Note Required",
            detail: "Please write a note before marking demo as Done.",
            life: 3000,
        });
        return;
    }

    try {
        await axios.put(`/api/customers/${statusCustomer.value.id}/demo-status`, {
            demo_status: selectedDemoStatus.value,
            note: statusNote.value.trim() || null,
        });

        toast.add({
            severity: "success",
            summary: "Updated",
            detail: "Demo status updated successfully.",
            life: 2000,
        });

        showStatusDialog.value = false;
        statusCustomer.value = null;
        statusNote.value = "";

        await fetchDemoCustomers();
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error?.response?.data?.message || "Failed to update demo status.",
            life: 3000,
        });
    }
};
</script>

<template>
    <div class="p-6 bg-gray-50 min-h-screen">
        <Toast />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Demo Presenter Dashboard</h1>
                <p class="text-sm text-gray-500">
                    Customers assigned for demo (<span class="font-medium text-gray-800">{{ total }}</span>)
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 transition disabled:opacity-60"
                :disabled="loading"
                @click="fetchDemoCustomers"
            >
                {{ loading ? "Refreshing..." : "Refresh" }}
            </button>
        </div>

        <div class="mb-6 flex flex-wrap gap-2">
            <button
                class="px-4 py-2 rounded-full text-sm font-medium transition"
                :class="activeTab === 'All' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                @click="activeTab = 'All'"
                type="button"
            >
                All ({{ total }})
            </button>
            <button
                class="px-4 py-2 rounded-full text-sm font-medium transition"
                :class="activeTab === 'Pending' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                @click="activeTab = 'Pending'"
                type="button"
            >
                Pending ({{ tableRows.filter((r:any)=> (r.demo_status ?? 'Pending') === 'Pending').length }})
            </button>
            <button
                class="px-4 py-2 rounded-full text-sm font-medium transition"
                :class="activeTab === 'Done' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                @click="activeTab = 'Done'"
                type="button"
            >
                Done ({{ tableRows.filter((r:any)=> (r.demo_status ?? 'Pending') === 'Done').length }})
            </button>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Need To Show Demo</h2>
                <p class="text-sm text-gray-500">Contact details to arrange/perform demos</p>
            </div>

            <div class="p-4">
                <div v-if="loading" class="text-sm text-gray-500 text-center py-10">Loading...</div>

                <div v-else-if="filteredRows.length === 0" class="text-sm text-gray-500 text-center py-10">
                    No customers found.
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div
                        v-for="row in filteredRows"
                        :key="row.id"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md transition"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-900 truncate">{{ row.name }}</h3>
                                <p class="text-xs text-gray-500 mt-1">ID: {{ row.id }}</p>
                            </div>

                            <span
                                class="px-3 py-1 text-xs font-semibold rounded-full"
                                :class="(row.demo_status ?? 'Pending') === 'Done'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-blue-100 text-blue-700'"
                            >
                                {{ row.demo_status ?? 'Pending' }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-2 text-sm text-gray-700">
                            <div>
                                <span class="text-gray-500">Numbers:</span>
                                <div class="mt-1 text-gray-800 break-words">{{ row.numbers }}</div>
                            </div>

                            <div>
                                <span class="text-gray-500">Locations:</span>
                                <div class="mt-1 text-gray-800 break-words">{{ row.locations }}</div>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Next Follow Up:</span>
                                <span class="text-gray-800 font-medium">{{ row.next_follow_up_date }}</span>
                            </div>
                        </div>

                        <div class="mt-5 flex gap-2">
                            <button
                                class="relative flex-1 px-3 py-2 rounded-xl bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800 transition"
                                type="button"
                                @click="openChat(row)"
                            >
                                Notes
                                <span
                                    v-if="row.demo_notes_unread > 0"
                                    class="absolute -top-2 -right-2 min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[10px] flex items-center justify-center"
                                >
                                    {{ row.demo_notes_unread }}
                                </span>
                            </button>
                            <button
                                class="flex-1 px-3 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition"
                                type="button"
                                @click="openStatus(row)"
                            >
                                Update Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showStatusDialog" header="Update Demo Status" modal :style="{ width: '32rem' }">
            <div class="flex flex-col gap-4">
                <div class="text-sm text-gray-700">
                    <span class="font-semibold">Customer:</span>
                    <span class="ml-1">{{ statusCustomer?.name || "-" }}</span>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="font-medium">Demo Status</label>
                    <select v-model="selectedDemoStatus" class="border rounded-lg px-3 py-2">
                        <option value="Pending">Pending</option>
                        <option value="Done">Done</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="font-medium">
                        Note <span class="text-red-600" v-if="selectedDemoStatus === 'Done'">*</span>
                    </label>
                    <textarea
                        v-model="statusNote"
                        rows="4"
                        class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                        placeholder="Write demo feedback / result..."
                    />
                </div>
            </div>

            <div class="text-right mt-6 flex gap-2 justify-end">
                <button class="px-5 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400" @click="showStatusDialog = false">
                    Cancel
                </button>
                <button class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" @click="saveDemoStatus">
                    Save
                </button>
            </div>
        </Dialog>

        <DemoNotesDialog
            :visible="showNotes"
            :customerId="notesCustomer?.id ?? null"
            :customerName="notesCustomer?.name ?? ''"
            @update:visible="(v:boolean) => { showNotes = v; if (!v) { notesCustomer = null; fetchDemoCustomers(); } }"
        />
    </div>
</template>
