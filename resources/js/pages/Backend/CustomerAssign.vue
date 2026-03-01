<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import DataTable from "@/Components/DataTable.vue";
import Multiselect from "vue-multiselect";
import axios from "axios";

const toast = useToast();

// ============================
// BREADCRUMB
// ============================
const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Customer Assignment", href: "/customer-assign" },
];

// ============================
// STATE
// ============================
const customers = ref<any[]>([]);
const staffList = ref<any[]>([]);
const selectedCustomers = ref<number[]>([]);
const selectedStaff = ref<any>(null);
const selectAll = ref(false);
const loading = ref(false);

// ============================
// FETCH DATA
// ============================
const fetchCustomers = async () => {
    try {
        const { data } = await axios.get("/api/customers");
        customers.value = data.customers;
    } catch {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch customers",
            life: 3000,
        });
    }
};

const fetchStaff = async () => {
    try {
        const { data } = await axios.get("/api/staff");
        staffList.value = data;
    } catch {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch staff list",
            life: 3000,
        });
    }
};

onMounted(() => {
    fetchCustomers();
    fetchStaff();
});

// ============================
// ASSIGN CUSTOMERS
// ============================
const assignCustomers = async () => {
    if (!selectedCustomers.value.length) {
        toast.add({
            severity: "warn",
            summary: "Warning",
            detail: "Please select at least one customer",
            life: 3000,
        });
        return;
    }

    if (!selectedStaff.value) {
        toast.add({
            severity: "warn",
            summary: "Warning",
            detail: "Please select a staff",
            life: 3000,
        });
        return;
    }

    loading.value = true;

    try {
        await axios.post("/api/customers/assign", {
            customer_ids: selectedCustomers.value,
            staff_id: selectedStaff.value.id,
        });

        toast.add({
            severity: "success",
            summary: "Assigned",
            detail: "Customer(s) assigned successfully",
            life: 3000,
        });

        selectedCustomers.value = [];
        selectAll.value = false;
        selectedStaff.value = null;
        await fetchCustomers();
    } catch {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to assign customers",
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
};


// ============================
// HANDLE SELECT ALL
// ============================
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedCustomers.value = customers.value.map(c => c.id);
    } else {
        selectedCustomers.value = [];
    }
};

// Update selectAll if user manually selects/unselects rows
watch(selectedCustomers, (val) => {
    if (val.length === customers.value.length && customers.value.length > 0) {
        selectAll.value = true;
    } else {
        selectAll.value = false;
    }
});

// ============================
// TABLE CONFIG
// ============================
const columns = [
    { key: "select", label: "", align: "center" },
    { key: "sn", label: "SN", align: "center" },
    { key: "name", label: "Customer Name", align: "center" },
    { key: "shop_type", label: "Shop Type", align: "center" },
    { key: "status", label: "Status", align: "center" },
    { key: "staff_status", label: "Staff Status", align: "center" },
    { key: "assigned_staff", label: "Assigned Staff", align: "center" },
];

const tableRows = computed(() =>
    customers.value.map((c, index) => ({
        sn: index + 1,
        id: c.id,
        name: c.name,
        shop_type: c.shop_type || "—",
        status: c.status,
        staff_status: c.staff_status,
        assigned_staff: c.assigned_staff?.name || "Unassigned",
    }))
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Customer Assignment" />
        <Toast />

        <div class="p-6 space-y-8">

            <!-- ASSIGNMENT CARD -->
            <Card>
                <template #title>
                    <h2 class="text-xl font-semibold">Assign Customers to Staff</h2>
                </template>

                <template #content>
                    <div class="flex flex-col md:flex-row gap-4 items-center bg-gray-50 p-6 rounded-xl">

                        <!-- STAFF SELECT -->
                        <div class="w-full md:w-1/3">
                            <label class="font-semibold block mb-2 text-gray-700">Select Staff</label>
                            <Multiselect v-model="selectedStaff" :options="staffList" label="name" track-by="id"
                                placeholder="Choose staff" :searchable="true" :allow-empty="true" />
                        </div>

                        <!-- ASSIGN BUTTON -->
                        <div class="mt-6 md:mt-8">
                            <Button label="Assign Selected" icon="pi pi-user-plus" class="p-button-success px-6"
                                :loading="loading" @click="assignCustomers" />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- CUSTOMER TABLE -->
            <DataTable title="Customer List" :columns="columns" :rows="tableRows" :showSearch="true">

                <!-- SELECT ALL CHECKBOX IN HEADER -->
                <template #header-select>
                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4" />
                </template>

                <!-- ROW CHECKBOX -->
                <template #cell-select="{ row }">
                    <input type="checkbox" :value="row.id" v-model="selectedCustomers" class="w-4 h-4" />
                </template>

                <!-- STATUS BADGE -->
                <template #cell-status="{ row }">
                    <span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-700">
                        {{ row.status }}
                    </span>
                </template>

                <!-- STAFF STATUS BADGE -->
                <template #cell-staff_status="{ row }">
                    <span class="px-2 py-1 rounded text-xs bg-purple-100 text-purple-700">
                        {{ row.staff_status }}
                    </span>
                </template>

                <!-- ASSIGNED STAFF -->
                <template #cell-assigned_staff="{ row }">
                    <span :class="row.assigned_staff === 'Unassigned'
                        ? 'text-red-500 font-semibold'
                        : 'text-green-600 font-semibold'">
                        {{ row.assigned_staff }}
                    </span>
                </template>

            </DataTable>
        </div>
    </AppLayout>
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";
</style>
