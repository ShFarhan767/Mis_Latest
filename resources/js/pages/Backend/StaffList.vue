<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";

const toast = useToast();

const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Staff List", href: "/staffList" },
];

const loading = ref(false);
const entries = ref<any[]>([]);

const fetchStaff = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/api/staff");
        entries.value = Array.isArray(data) ? data : data?.data || [];
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error?.response?.data?.message || "Failed to fetch staff.",
            life: 3000,
        });
        entries.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(fetchStaff);

const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "name", label: "Name", align: "left" },
    { key: "mobile", label: "Mobile", align: "center" },
    { key: "email", label: "E-Mail", align: "left" },
    { key: "designation", label: "Designation", align: "center" },
    { key: "status", label: "Status", align: "center" },
];

const tableRows = computed(() =>
    (entries.value || []).map((s: any, index: number) => ({
        sn: index + 1,
        id: s.id,
        name: s.name ?? "-",
        mobile: s.mobile ?? s.phone ?? "-",
        email: s.email ?? "-",
        designation: s.designation ?? s.designation_name ?? "-",
        status: s.status ?? "-",
    }))
);

const total = computed(() => tableRows.value.length);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Staff List" />
        <Toast />

        <div class="p-6 space-y-6">
            <!-- Simple header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Staff List</h1>
                </div>

                <button type="button"
                    class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 transition disabled:opacity-60"
                    :disabled="loading" @click="fetchStaff">
                    {{ loading ? "Refreshing..." : "Refresh" }}
                </button>
            </div>

            <!-- Table container -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">

                <div class="p-4">
                    <DataTable title="Staff" :columns="columns" :rows="tableRows" :showSearch="true">
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
