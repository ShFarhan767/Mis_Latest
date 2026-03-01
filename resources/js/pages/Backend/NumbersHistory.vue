<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import AppLayout from "@/Layouts/AppLayout.vue";

const toast = useToast();

// Tabs
const activeReportTab = ref("Numbers");
const reportTabs = ["Numbers", "Tasks", "SMS Send", "Auto SMS Send", "Today Add Number","Today Final Number"];

// Customers / Users
const users = ref<any[]>([]);

// Number history table
const numberHistory = ref<any[]>([]);

const numberColumns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "assign_staff", label: "Employee", align: "left" },
    { key: "customer_name", label: "Name", align: "left" },
    { key: "service_type", label: "Service Type", align: "center" },
    { key: "numbers", label: "Numbers", align: "center" },
    { key: "latest_update", label: "Latest Updated Data", align: "left" },
    { key: "last_update", label: "Old/Previous Data", align: "left" },
];

// Fetch all users (staff)
const fetchUsers = async () => {
    try {
        const { data } = await axios.get("/api/users");
        users.value = data;
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch users", life: 3000 });
    }
};

// Helper: get staff name
const getStaffName = (staffId: number | null) => {
    if (!staffId) return "-";
    const staff = users.value.find(u => u.id === staffId);
    return staff ? staff.name : "-";
};

// Fetch number history for all customers
const fetchNumberHistory = async () => {
    try {
        const { data } = await axios.get("/api/customers/all/history");

        numberHistory.value = data.map((item: any, idx: number) => {
            const assignStaffId = item.assigned_staff_id || null;

            // Numbers
            const numbers = (() => {
                if (Array.isArray(item.numbers)) {
                    // old format (array of objects)
                    return item.numbers.map((n: any) => `${n.full_number} (${n.type})`).join(", ");
                } else if (typeof item.numbers === 'string') {
                    // new format (already formatted string)
                    return item.numbers;
                } else {
                    return "-";
                }
            })();

            // ✅ Convert service_type JSON string → array
            const serviceType = (() => {
                try {
                    return Array.isArray(item.service_type)
                        ? item.service_type
                        : JSON.parse(item.service_type || "[]");
                } catch {
                    return [];
                }
            })();

            // Format history as cards
            const latestDataArr: string[] = [];
            const oldDataArr: string[] = [];

            if (Array.isArray(item.history)) {
                item.history.forEach((h: any) => {
                    if (h.old_data) {
                        try {
                            const oldObj = typeof h.old_data === "string" ? JSON.parse(h.old_data) : h.old_data;
                            oldDataArr.push(
                                `<div class="history-card">${Object.entries(oldObj)
                                    .map(([k, v]) => `<strong>${k.replace(/_/g, ' ')}:</strong> ${v}`)
                                    .join("<br>")}</div>`
                            );
                        } catch { }
                    }
                    if (h.note) {
                        latestDataArr.push(`<div class="history-card"><strong>Note:</strong> ${h.note}</div>`);
                    }
                });
            }

            return {
                sn: idx + 1,
                assign_staff: item.assign_staff,
                customer_name: item.customer_name || "-",
                service_type: serviceType, // now parsed array
                numbers,
                latest_update: latestDataArr.join("") || "-",
                last_update: oldDataArr.join("") || "-",
            };
        });
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch number history", life: 3000 });
    }
};

// Load data
onMounted(async () => {
    await fetchUsers();
    await fetchNumberHistory();
});
</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gray-50 min-h-screen">
            <Toast />

            <!-- Page Title -->
            <div class="mb-6 border-l-4 border-green-600 pl-5">
                <h1 class="text-3xl font-extrabold text-gray-900 leading-tight">Customer Numbers History</h1>
                <p class="mt-1 text-gray-600 text-sm">View all customers’ number history and updates.</p>
            </div>

            <!-- Tabs -->
            <div class="mb-4 flex gap-2 flex-wrap">
                <button v-for="tab in reportTabs" :key="tab" @click="activeReportTab = tab" :class="activeReportTab === tab
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-full text-sm font-medium transition">
                    {{ tab }}
                </button>
            </div>

            <!-- Tab content -->
            <div class="bg-white rounded-lg shadow-lg p-6 overflow-x-auto">
                <!-- Numbers Tab -->
                <DataTable v-if="activeReportTab === 'Numbers'" title="Number History" :columns="numberColumns"
                    :rows="numberHistory" :showSearch="true">

                    <!-- Service Type: one per line -->
                    <template #cell-service_type="{ row }">
                        <div>
                            <div v-for="(service, i) in row.service_type" :key="i">{{ service }}</div>
                        </div>
                    </template>

                    <!-- Latest Update -->
                    <template #cell-latest_update="{ row }">
                        <div class="history-container" v-html="row.latest_update"></div>
                    </template>

                    <!-- Old Update -->
                    <template #cell-last_update="{ row }">
                        <div class="history-container" v-html="row.last_update"></div>
                    </template>

                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Service type one per line */
.data-table td div {
    white-space: normal !important;
}

/* Card style for history */
.history-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 6px 10px;
    margin-bottom: 4px;
    background-color: #f9fafb;
}

/* Scrollable container if too many history items */
.history-container {
    max-height: 200px;
    overflow-y: auto;
    padding: 2px;
}
</style>