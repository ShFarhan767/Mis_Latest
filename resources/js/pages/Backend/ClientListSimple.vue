<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Card from "primevue/card";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";

const toast = useToast();

const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Client List", href: "/clientListSimple" },
];

const loading = ref(false);
const clients = ref<any[]>([]);

const fetchClients = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/api/clients");
        clients.value = Array.isArray(data) ? data : data?.data || [];
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error?.response?.data?.message || "Failed to fetch clients.",
            life: 3000,
        });
        clients.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(fetchClients);

const formatBD = (iso: string | null | undefined) => {
    if (!iso) return "-";
    const d = new Date(iso);
    if (isNaN(d.getTime())) return "-";
    return d.toLocaleString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
        timeZone: "Asia/Dhaka",
    });
};

const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "name", label: "Client Name", align: "left" },
    { key: "company_name", label: "Company", align: "left" },
    { key: "project_name", label: "Project", align: "left" },
    { key: "business_type", label: "Business Type", align: "center" },
    { key: "number", label: "Client Number", align: "center" },
    { key: "operator_name", label: "Operator", align: "left" },
    { key: "oparetor_number", label: "Operator Number", align: "center" },
    { key: "area_name", label: "Area", align: "center" },
    { key: "address", label: "Address", align: "left" },
    { key: "country_name", label: "Country", align: "center" },
    { key: "referred_by_name", label: "Referred By", align: "left" },
    { key: "referred_by_number", label: "Ref. Number", align: "center" },
    { key: "status", label: "Status", align: "center" },
    { key: "created_at", label: "Created", align: "center" },
    { key: "updated_at", label: "Updated", align: "center" },
];

const tableRows = computed(() =>
    (clients.value || []).map((c: any, index: number) => ({
        sn: index + 1,
        id: c.id,
        name: c.name ?? "-",
        company_name: c.company_name ?? "-",
        project_name: c.project_name ?? "-",
        business_type: c.business_type ?? "-",
        number: c.number ?? "-",
        operator_name: c.operator_name ?? "-",
        oparetor_number: c.oparetor_number ?? "-",
        area_name: c.area_name ?? "-",
        address: c.address ?? "-",
        country_name: c.country_name ?? "-",
        referred_by_name: c.referred_by_name ?? "-",
        referred_by_number: c.referred_by_number ?? "-",
        status: c.status ?? "-",
        created_at: formatBD(c.created_at),
        updated_at: formatBD(c.updated_at),
    }))
);

const total = computed(() => tableRows.value.length);
const runningCount = computed(() => tableRows.value.filter((r: any) => r.status === "Running").length);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Client List" />
        <Toast />

        <div class="p-6 space-y-6">
            <Card>
                <template #content>
                    <div
                        class="rounded-2xl p-6 text-white shadow-lg bg-gradient-to-r from-fuchsia-600 via-purple-600 to-indigo-600">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 class="text-2xl font-semibold tracking-tight">Client List</h1>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="rounded-2xl bg-white shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4">
                    <DataTable title="Client Details" :columns="columns" :rows="tableRows" :showSearch="true">
                        <template #cell-status="{ row }">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="row.status === 'Running'
                                ? 'bg-green-100 text-green-700'
                                : row.status === 'Disable'
                                    ? 'bg-gray-200 text-gray-700'
                                    : row.status === 'Suspend'
                                        ? 'bg-yellow-100 text-yellow-700'
                                        : 'bg-blue-100 text-blue-700'">
                                {{ row.status }}
                            </span>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
