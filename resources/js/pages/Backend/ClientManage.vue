<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Multiselect from "vue-multiselect";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import DataTable from "@/Components/DataTable.vue";
import axios from "axios";

const toast = useToast();
const confirm = useConfirm();

// Breadcrumb
const breadcrumbItems = [
    { title: "Home", href: "/" },
    { title: "Client Management", href: "/client-management" },
];

// Status options
const statusOptions = [
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
];

// Business type options
const businessTypeOptions = ref<any[]>([]);

// Form
const form = ref({
    name: "",
    company_name: "",
    operator_name: "",
    number: "",
    oparetor_number: "",
    address: "",
    country: null as any,
    area: null as any,
    project_name: "",
    referred_by_name: "",
    referred_by_number: "",
    business_type: null as any,
    status: "Running",
});

const entries = ref<any[]>([]);
const editingId = ref<number | null>(null);

// Modal for adding business type
const showBusinessTypeModal = ref(false);
const newBusinessType = ref({ name: "", status: "Running" });

const hasReferral = ref(false);

// Fetch clients
const fetchEntries = async () => {
    try {
        const { data } = await axios.get("/api/clients");
        entries.value = Array.isArray(data) ? data : data?.data || [];
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch clients.", life: 3000 });
    }
};

// Fetch business types
const fetchBusinessTypes = async () => {
    try {
        const { data } = await axios.get("/api/business-types");
        businessTypeOptions.value = Array.isArray(data) ? data : data?.data || [];
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch business types.", life: 3000 });
    }
};

const runningBusinessTypes = computed(() => businessTypeOptions.value.filter((bt) => bt.status === "Running"));

// Country options
const countryOptions = ref<any[]>([]);

// Country modal
const showCountryModal = ref(false);
const newCountry = ref({ name: "", status: "Running" });
const editingCountryId = ref<number | null>(null);

const fetchCountries = async () => {
    try {
        const { data } = await axios.get("/api/countries");
        const raw = Array.isArray(data) ? data : data?.data || [];
        countryOptions.value = raw.map((c: any) => ({
            id: c.id,
            name: c.country_name,
            status: c.status,
        }));
    } catch {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch countries" });
    }
};

const runningCountries = computed(() => countryOptions.value.filter((c) => c.status === "Running"));

// Area options
const areaOptions = ref<any[]>([]);

// Area modal
const showAreaModal = ref(false);
const newArea = ref({ name: "", status: "Running", country: null as any });
const editingAreaId = ref<number | null>(null);

const fetchAreas = async () => {
    try {
        const { data } = await axios.get("/api/areas");
        const raw = Array.isArray(data) ? data : data?.data || [];
        areaOptions.value = raw.map((a: any) => ({
            id: a.id,
            country_name: a.country_name,
            name: a.area_name,
            status: a.status,
        }));
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to fetch areas.", life: 3000 });
    }
};

const runningAreas = computed(() => areaOptions.value.filter((a) => a.status === "Running"));

const filteredAreasByCountry = computed(() => {
    if (!form.value.country) return [];
    return runningAreas.value.filter((a) => a.country_name === form.value.country.name);
});

onMounted(() => {
    fetchEntries();
    fetchBusinessTypes();
    fetchCountries();
    fetchAreas();
});

// Submit client form
const submitForm = async () => {
    try {
        // REQUIRED (add more if you want)
        if (
            !form.value.company_name ||
            !form.value.business_type ||
            !form.value.name ||
            !form.value.number ||
            !form.value.operator_name ||
            !form.value.oparetor_number ||
            !form.value.project_name ||
            !form.value.country ||
            !form.value.area ||
            !form.value.address ||
            !form.value.status
        ) {
            toast.add({ severity: "warn", summary: "Warning", detail: "Please fill all required fields (*).", life: 3000 });
            return;
        }

        const payload: any = { ...form.value };

        if (payload.area?.name) payload.area_name = payload.area.name;
        delete payload.area;

        if (payload.country?.name) payload.country_name = payload.country.name;
        delete payload.country;

        if (payload.business_type?.name) payload.business_type = payload.business_type.name;

        if (editingId.value) {
            await axios.put(`/api/clients/${editingId.value}`, payload);
            toast.add({ severity: "success", summary: "Updated", detail: "Client updated successfully!", life: 3000 });
            editingId.value = null;
        } else {
            await axios.post("/api/clients", payload);
            toast.add({ severity: "success", summary: "Created", detail: "Client added successfully!", life: 3000 });
        }

        form.value = {
            name: "",
            company_name: "",
            operator_name: "",
            number: "",
            oparetor_number: "",
            address: "",
            country: null,
            area: null,
            project_name: "",
            referred_by_name: "",
            referred_by_number: "",
            business_type: null,
            status: "Running",
        };

        hasReferral.value = false;
        await fetchEntries();
    } catch (error) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed to save client.", life: 3000 });
    }
};

// Edit client
const editEntry = (entry: any) => {
    editingId.value = entry.id;

    const selectedBusinessType =
        businessTypeOptions.value.find((bt) => bt.name === entry.business_type) || null;

    const selectedArea = areaOptions.value.find((a) => a.name === entry.area_name) || null;

    const selectedCountry = countryOptions.value.find((c) => c.name === entry.country_name) || null;

    form.value = {
        name: entry.name ?? "",
        company_name: entry.company_name ?? "",
        operator_name: entry.operator_name ?? "",
        number: entry.number ?? "",
        oparetor_number: entry.oparetor_number ?? "",
        address: entry.address ?? "",
        country: selectedCountry,
        area: selectedArea,
        project_name: entry.project_name ?? "",
        referred_by_name: entry.referred_by_name ?? "",
        referred_by_number: entry.referred_by_number ?? "",
        business_type: selectedBusinessType,
        status: entry.status ?? "Running",
    };

    hasReferral.value = !!(entry.referred_by_name || entry.referred_by_number);
    window.scrollTo({ top: 0, behavior: "smooth" });
};

// Delete client
const deleteEntry = (id: number) => {
    confirm.require({
        message: "Are you sure you want to delete this client?",
        header: "Confirm Deletion",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: async () => {
            try {
                await axios.delete(`/api/clients/${id}`);
                toast.add({ severity: "success", summary: "Deleted", detail: "Client deleted successfully!", life: 3000 });
                await fetchEntries();
            } catch {
                toast.add({ severity: "error", summary: "Error", detail: "Failed to delete client.", life: 3000 });
            }
        },
    });
};

// Table columns
const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "company_name", label: "Company", align: "center" },
    { key: "business_type", label: "Business Type", align: "center" },
    { key: "name", label: "Client Name", align: "center" },
    { key: "number", label: "Client Number", align: "center" },
    { key: "operator_name", label: "Operator Name", align: "center" },
    { key: "oparetor_number", label: "Operator Number", align: "center" },
    { key: "project_name", label: "Project Name", align: "center" },
    { key: "country_name", label: "Country", align: "center" },
    { key: "area_name", label: "Area", align: "center" },
    { key: "address", label: "Address", align: "center" },
    { key: "referred_by_name", label: "Referred By Name", align: "center" },
    { key: "referred_by_number", label: "Referred By Number", align: "center" },
    { key: "status", label: "Status", align: "center" },
    { key: "actions", label: "Actions", align: "center" },
];

const tableRows = computed(() =>
    (entries.value || []).map((entry: any, index: number) => ({
        sn: index + 1,
        id: entry.id,
        company_name: entry.company_name ?? "-",
        business_type: entry.business_type ?? "-",
        name: entry.name ?? "-",
        number: entry.number ?? "-",
        operator_name: entry.operator_name ?? "-",
        oparetor_number: entry.oparetor_number ?? "-",
        project_name: entry.project_name ?? "-",
        country_name: entry.country_name ?? "-",
        area_name: entry.area_name ?? "-",
        address: entry.address ?? "-",
        referred_by_name: entry.referred_by_name || "-",
        referred_by_number: entry.referred_by_number || "-",
        status: entry.status ?? "-",
    }))
);

const editingBusinessTypeId = ref<number | null>(null);

const submitBusinessType = async () => {
    if (!newBusinessType.value.name) return;

    try {
        if (editingBusinessTypeId.value) {
            await axios.put(`/api/business-types/${editingBusinessTypeId.value}`, newBusinessType.value);
        } else {
            await axios.post("/api/business-types", newBusinessType.value);
        }

        newBusinessType.value = { name: "", status: "Running" };
        editingBusinessTypeId.value = null;
        await fetchBusinessTypes();
    } catch (e) {
        toast.add({ severity: "error", summary: "Error", detail: "Failed", life: 3000 });
    }
};

const editBusinessType = (bt: any) => {
    newBusinessType.value = { name: bt.name, status: bt.status };
    editingBusinessTypeId.value = bt.id;
};

const deleteBusinessType = async (id: number) => {
    if (!confirm("Delete this business type?")) return;
    await axios.delete(`/api/business-types/${id}`);
    await fetchBusinessTypes();
};

const submitCountry = async () => {
    if (!newCountry.value.name) return;

    const payload = {
        country_name: newCountry.value.name,
        status: newCountry.value.status,
    };

    if (editingCountryId.value) {
        await axios.put(`/api/countries/${editingCountryId.value}`, payload);
    } else {
        await axios.post("/api/countries", payload);
    }

    newCountry.value = { name: "", status: "Running" };
    editingCountryId.value = null;
    await fetchCountries();
};

const editCountry = (c: any) => {
    newCountry.value = { name: c.name, status: c.status };
    editingCountryId.value = c.id;
};

const deleteCountry = async (id: number) => {
    if (!confirm("Delete this country?")) return;
    await axios.delete(`/api/countries/${id}`);
    await fetchCountries();
};

const submitArea = async () => {
    if (!newArea.value.name || !newArea.value.country) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Please select a country and area name.", life: 3000 });
        return;
    }

    const payload = {
        area_name: newArea.value.name,
        status: newArea.value.status,
        country_name: newArea.value.country.name,
    };

    try {
        if (editingAreaId.value) {
            await axios.put(`/api/areas/${editingAreaId.value}`, payload);
        } else {
            await axios.post("/api/areas", payload);
        }

        newArea.value = { name: "", status: "Running", country: null };
        editingAreaId.value = null;
        await fetchAreas();

        toast.add({ severity: "success", summary: "Success", detail: "Area saved successfully!", life: 3000 });
    } catch (error: any) {
        toast.add({ severity: "error", summary: "Error", detail: error.response?.data?.message || "Failed", life: 3000 });
    }
};

const editArea = (area: any) => {
    const selectedCountry = countryOptions.value.find((c) => c.name === area.country_name) || null;
    newArea.value = { name: area.name, status: area.status, country: selectedCountry };
    editingAreaId.value = area.id;
    showAreaModal.value = true;
};

const deleteArea = async (id: number) => {
    if (!confirm("Delete this area?")) return;
    await axios.delete(`/api/areas/${id}`);
    await fetchAreas();
};

const referrerOptions = ref<any[]>([]);
const selectedReferrer = ref<any>(null);

const fetchReferrers = async (query = "") => {
    const { data } = await axios.get("/api/clients/search?query=" + query);
    referrerOptions.value = Array.isArray(data) ? data : data?.data || [];
};

const onReferrerSelect = (val: any) => {
    if (val?.company_name) {
        form.value.referred_by_name = val.company_name;
        form.value.referred_by_number = val.number ?? "";
    }
};

const addNewReferrer = (newTag: string) => {
    const obj = { company_name: newTag, number: "" };
    referrerOptions.value.push(obj);
    selectedReferrer.value = obj;

    form.value.referred_by_name = obj.company_name;
    form.value.referred_by_number = "";
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Client Management" />
        <Toast />
        <ConfirmDialog />

        <div class="p-6 space-y-8">
            <Card>
                <template #title>
                    <h2 class="text-xl font-semibold text-gray-800">
                        {{ editingId ? "Edit Client" : "Add New Client" }}
                    </h2>
                </template>

                <template #content>
                    <div class="flex justify-center items-center py-10 bg-gray-50 rounded-lg"
                        style="background-image: url('/images/form_bg/form_bg.jpg'); background-size: cover; background-position: center;">
                        <form @submit.prevent="submitForm"
                            class="space-y-4 bg-white p-6 rounded-xl shadow-lg w-full max-w-2xl">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="mt-5 font-medium">
                                        Company Name <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.company_name" class="w-full mt-3" />
                                </div>

                                <div>
                                    <div class="flex justify-between items-center">
                                        <label class="font-medium">
                                            Business Type <span class="text-red-600">*</span>
                                        </label>
                                        <Button icon="pi pi-plus" class="p-button-sm"
                                            @click="showBusinessTypeModal = true" />
                                    </div>
                                    <Multiselect v-model="form.business_type" :options="runningBusinessTypes"
                                        label="name" track-by="id" placeholder="Select Business Type" />
                                </div>

                                <div>
                                    <label class="font-medium">
                                        Client Name <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.name" class="w-full" />
                                </div>

                                <div>
                                    <label class="font-medium">
                                        Client Number <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.number" class="w-full" />
                                </div>

                                <div>
                                    <label class="font-medium">
                                        Operator Name <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.operator_name" class="w-full" />
                                </div>

                                <div>
                                    <label class="font-medium">
                                        Operator Number <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.oparetor_number" class="w-full" />
                                </div>

                                <div>
                                    <label class="font-medium">
                                        Project Name <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.project_name" class="w-full mt-3" />
                                </div>

                                <div>
                                    <div class="flex justify-between items-center">
                                        <label class="font-medium">
                                            Country <span class="text-red-600">*</span>
                                        </label>
                                        <Button icon="pi pi-plus" class="p-button-sm"
                                            @click="showCountryModal = true" />
                                    </div>
                                    <Multiselect v-model="form.country" :options="runningCountries" label="name"
                                        track-by="id" placeholder="Select Country" />
                                </div>

                                <div>
                                    <div class="flex justify-between items-center">
                                        <label class="font-medium">
                                            Area <span class="text-red-600">*</span>
                                        </label>
                                        <Button icon="pi pi-plus" class="p-button-sm" @click="showAreaModal = true" />
                                    </div>
                                    <Multiselect v-model="form.area" :options="filteredAreasByCountry" label="name"
                                        track-by="id" placeholder="Select Area" />
                                </div>

                                <div>
                                    <label class="font-medium">
                                        Address <span class="text-red-600">*</span>
                                    </label>
                                    <InputText v-model="form.address" class="w-full mt-3" />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="font-medium">
                                        Status <span class="text-red-600">*</span>
                                    </label>
                                    <Multiselect v-model="form.status" :options="statusOptions" />
                                </div>

                                <div class="md:col-span-2">
                                    <div class="flex items-center gap-2 mb-2">
                                        <input type="checkbox" v-model="hasReferral" id="refCheck" />
                                        <label for="refCheck" class="font-medium">Referred By</label>
                                    </div>

                                    <div v-if="hasReferral" class="grid grid-cols-2 gap-4">
                                        <div class="w-full mt-2">
                                            <label class="font-medium">Referred By</label>
                                            <Multiselect v-model="selectedReferrer" :options="referrerOptions"
                                                label="company_name" track-by="company_name"
                                                placeholder="Search or type referrer name" :searchable="true"
                                                :taggable="true" @search-change="fetchReferrers"
                                                @select="onReferrerSelect" @tag="addNewReferrer" class="w-full" />
                                        </div>

                                        <div class="mt-2">
                                            <label class="font-medium">Referrer Number</label>
                                            <InputText v-model="form.referred_by_number" class="w-full" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-center mt-6">
                                <Button type="submit" :label="editingId ? 'Update Client' : 'Save Client'"
                                    icon="pi pi-save" class="p-button-success w-1/2" />
                            </div>
                        </form>
                    </div>
                </template>
            </Card>

            <!-- BUSINESS TYPE MODAL -->
            <Dialog v-model:visible="showBusinessTypeModal" header="Business Types" modal style="width:600px">
                <div class="flex justify-center">
                    <div class="grid grid-cols-1 gap-3 mb-4 w-full max-w-sm">
                        <label class="text-sm font-medium">
                            Business Type Name <span class="text-red-600">*</span>
                        </label>
                        <InputText v-model="newBusinessType.name" placeholder="Business Type Name" class="w-full" />

                        <label class="text-sm font-medium">
                            Status <span class="text-red-600">*</span>
                        </label>
                        <Multiselect v-model="newBusinessType.status" :options="['Running', 'Disabled']"
                            class="w-full" />

                        <Button label="Save" icon="pi pi-check" class="p-button-success w-full"
                            @click="submitBusinessType" />
                    </div>
                </div>

                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2">SN</th>
                            <th class="border p-2">Name</th>
                            <th class="border p-2">Status</th>
                            <th class="border p-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(bt, i) in businessTypeOptions" :key="bt.id">
                            <td class="border p-2">{{ i + 1 }}</td>
                            <td class="border p-2">{{ bt.name }}</td>
                            <td class="border p-2">{{ bt.status }}</td>
                            <td class="border p-2 space-x-2">
                                <Button icon="pi pi-pencil" class="p-button-sm" @click="editBusinessType(bt)" />
                                <Button icon="pi pi-trash" class="p-button-sm p-button-danger"
                                    @click="deleteBusinessType(bt.id)" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Dialog>

            <!-- COUNTRY MODAL -->
            <Dialog v-model:visible="showCountryModal" header="Countries" modal style="width:600px">
                <div class="flex justify-center">
                    <div class="grid grid-cols-1 gap-3 mb-4 w-full max-w-sm">
                        <label class="text-sm font-medium">
                            Country Name <span class="text-red-600">*</span>
                        </label>
                        <InputText v-model="newCountry.name" placeholder="Country Name" />

                        <label class="text-sm font-medium">
                            Status <span class="text-red-600">*</span>
                        </label>
                        <Multiselect v-model="newCountry.status" :options="['Running', 'Disabled']" />

                        <Button label="Save" icon="pi pi-check" class="p-button-success" @click="submitCountry" />
                    </div>
                </div>

                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2">SN</th>
                            <th class="border p-2">Name</th>
                            <th class="border p-2">Status</th>
                            <th class="border p-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(c, i) in countryOptions" :key="c.id">
                            <td class="border p-2">{{ i + 1 }}</td>
                            <td class="border p-2">{{ c.name }}</td>
                            <td class="border p-2">{{ c.status }}</td>
                            <td class="border p-2 space-x-2">
                                <Button icon="pi pi-pencil" class="p-button-sm" @click="editCountry(c)" />
                                <Button icon="pi pi-trash" class="p-button-sm p-button-danger"
                                    @click="deleteCountry(c.id)" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Dialog>

            <!-- AREA MODAL -->
            <Dialog v-model:visible="showAreaModal" header="Areas" modal style="width:600px">
                <div class="grid grid-cols-1 gap-3 mb-4 w-full max-w-sm mx-auto">
                    <label class="text-sm font-medium">
                        Country <span class="text-red-600">*</span>
                    </label>
                    <Multiselect v-model="newArea.country" :options="runningCountries" label="name" track-by="name"
                        placeholder="Select Country" />

                    <label class="text-sm font-medium">
                        Area Name <span class="text-red-600">*</span>
                    </label>
                    <InputText v-model="newArea.name" placeholder="Area Name" class="w-full" />

                    <label class="text-sm font-medium">
                        Status <span class="text-red-600">*</span>
                    </label>
                    <Multiselect v-model="newArea.status" :options="['Running', 'Disabled']" class="w-full" />

                    <Button label="Save" icon="pi pi-check" class="p-button-success w-full" @click="submitArea" />
                </div>

                <table class="w-full border text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2">SN</th>
                            <th class="border p-2">Country Name</th>
                            <th class="border p-2">Name</th>
                            <th class="border p-2">Status</th>
                            <th class="border p-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(a, i) in areaOptions" :key="a.id">
                            <td class="border p-2">{{ i + 1 }}</td>
                            <td class="border p-2">{{ a.country_name }}</td>
                            <td class="border p-2">{{ a.name }}</td>
                            <td class="border p-2">{{ a.status }}</td>
                            <td class="border p-2 space-x-2">
                                <Button icon="pi pi-pencil" class="p-button-sm" @click="editArea(a)" />
                                <Button icon="pi pi-trash" class="p-button-sm p-button-danger"
                                    @click="deleteArea(a.id)" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Dialog>

            <DataTable title="Client List" :columns="columns" :rows="tableRows" :onEdit="editEntry"
                :onDelete="deleteEntry" :showSearch="true" />
        </div>
    </AppLayout>
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";
</style>
