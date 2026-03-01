<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import Dialog from "primevue/dialog";
import AppLayout from "@/Layouts/AppLayout.vue";

const toast = useToast();
const customers = ref<any[]>([]);

// Modal states
const showModal = ref(false);
const modalTitle = ref("");
const modalContent = ref("");

const openModal = (title: string, content: string) => {
    modalTitle.value = title;
    modalContent.value = content || "<i>No data available.</i>";
    showModal.value = true;
};

// Fetch customers
const fetchCancelCustomers = async () => {
    try {
        const { data } = await axios.get("/api/customers");

        customers.value = data.customers
            // ✅ FIXED HERE
            .filter((c: any) => c.staff_status === "Cancelled")
            .map((c: any) => ({
                ...c,
                numbers: c.numbers
                    ?.map((n: any) => `${n.full_number} (${n.type})`)
                    .join(", ") ?? "-",
            }));
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch customers",
            life: 3000,
        });
    }
};

// Table columns
const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "name", label: "Customer Name", align: "left" },
    { key: "designation", label: "Designation", align: "center" },
    { key: "numbers", label: "Contact Numbers", align: "center" },
    { key: "email", label: "Email", align: "left" },
    { key: "shop_type", label: "Shop Type", align: "center" },
    { key: "locations", label: "Locations", align: "center" },
    { key: "lead_source", label: "Lead Source", align: "center" },
    { key: "interest_level", label: "Interest Level", align: "center" },
    { key: "service_type", label: "Service Type", align: "center" },

    // Modal-enabled fields
    { key: "feature_need", label: "Feature Need", type: "modal" },
    { key: "our_commitment", label: "Our Commitment", type: "modal" },
    { key: "client_behaviour", label: "Client Behaviour", type: "modal" },
    { key: "last_discuss_note", label: "Last Discuss Note", type: "modal" },

    { key: "offer_connect", label: "Offer Connect", align: "center" },
    { key: "staff_status", label: "Staff Status", align: "center" },
    { key: "last_contact_date", label: "Last Contact", align: "center" },
    { key: "next_follow_up_date", label: "Next Follow Up", align: "center" },
];

const tableRows = computed(() =>
    customers.value.map((c, index) => ({ sn: index + 1, ...c }))
);

onMounted(() => {
    fetchCancelCustomers();
});
</script>

<template>
    <AppLayout>
        <div class="p-6 bg-gray-50 min-h-screen">
            <Toast />

            <div class="mb-6 border-l-4 border-red-600 pl-5">
                <h1 class="text-3xl font-extrabold text-gray-900 leading-tight">
                    Cancel Contacts Report
                </h1>
                <p class="mt-1 text-gray-600 text-sm">
                    Showing all customers with 
                    <span class="text-red-600 font-semibold">Cancel</span> status.
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 overflow-x-auto">
                <DataTable title="Cancel Customers" :columns="columns" :rows="tableRows" :showSearch="true"
                    @openModal="openModal" />
            </div>

            <!-- IMPROVED MODAL -->
            <Dialog v-model:visible="showModal" modal header="" :style="{ width: '40rem' }">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">
                    {{ modalTitle }}
                </h2>

                <div class="p-4 rounded-lg border bg-gray-50 text-gray-700 shadow-inner leading-relaxed"
                    v-html="modalContent"></div>

                <div class="text-right mt-6">
                    <button
                        class="px-5 py-2 bg-blue-600 text-white font-medium rounded-md shadow hover:bg-blue-700 transition"
                        @click="showModal = false">
                        Close
                    </button>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Improve list spacing and readability inside modal */
.prose p {
    margin-bottom: 0.7rem;
}

.prose ul,
.prose ol {
    margin-left: 1.2rem;
}

.prose li {
    margin-bottom: 0.4rem;
}
</style>
