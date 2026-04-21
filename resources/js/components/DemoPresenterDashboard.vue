<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import Dialog from "primevue/dialog";

const toast = useToast();

const loading = ref(false);
const customers = ref<any[]>([]);

const activeTab = ref<"All" | "Pending" | "Done">("All");
const searchQuery = ref("");

// Status update modal
const showStatusDialog = ref(false);
const statusCustomer = ref<any | null>(null);
const selectedDemoStatus = ref<"Pending" | "Done">("Pending");
const statusNote = ref("");

// Details modal (includes tabs: info/history/notes)
const showDetailsDialog = ref(false);
const detailsCustomer = ref<any | null>(null);
const detailsTab = ref<"info" | "history" | "notes">("info");

// History timeline
const historyLoading = ref(false);
const historyData = ref<any[]>([]);
const assignedForDemoAt = ref<string | null>(null);
const assignedForDemoBy = ref<string | null>(null);
const demoAssignedAtMap = ref<Record<number, string | null>>({});
const demoAssignedByMap = ref<Record<number, string | null>>({});

// Demo notes (chat)
const notes = ref<any[]>([]);
const notesLoading = ref(false);
const sending = ref(false);
const message = ref("");
const currentUser = ref<any | null>(null);

const isHtml = (value: any) => typeof value === "string" && value.includes("<");

const fetchCurrentUser = async () => {
    if (currentUser.value) return;
    try {
        const { data } = await axios.get("/api/current-user");
        currentUser.value = data;
    } catch {
        currentUser.value = null;
    }
};

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
            service_type: Array.isArray(c.service_type)
                ? c.service_type
                : (() => {
                      try {
                          return JSON.parse(c.service_type || "[]");
                      } catch {
                          return [];
                      }
                  })(),
        }));

        void hydrateDemoAssignmentMeta(list);
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

const hydrateDemoAssignmentMeta = async (list: any[]) => {
    const results = await Promise.allSettled(
        list.map(async (customer: any) => {
            const { data } = await axios.get(`/api/customers/${customer.id}/history`);
            const historyList = Array.isArray(data) ? data : [];
            const assignEntry = historyList.find(
                (item: any) =>
                    item?.old_data &&
                    Object.prototype.hasOwnProperty.call(item.old_data, "demo_presenter_id")
            );

            return {
                customerId: customer.id,
                assignedAt: assignEntry?.created_at ?? null,
                assignedBy: assignEntry?.staff ?? null,
            };
        })
    );

    const assignedAt: Record<number, string | null> = {};
    const assignedBy: Record<number, string | null> = {};

    results.forEach((result) => {
        if (result.status !== "fulfilled") return;
        assignedAt[result.value.customerId] = result.value.assignedAt;
        assignedBy[result.value.customerId] = result.value.assignedBy;
    });

    demoAssignedAtMap.value = assignedAt;
    demoAssignedByMap.value = assignedBy;
};

const formatDate = (date: string | null) => {
    if (!date) return "-";
    const d = new Date(date);
    if (isNaN(d.getTime())) return "-";
    return new Intl.DateTimeFormat("en-GB", { day: "2-digit", month: "short", year: "numeric" }).format(d);
};

const formatDateTime = (date: string | null) => {
    if (!date) return "-";
    const d = new Date(date);
    if (isNaN(d.getTime())) return "-";
    return new Intl.DateTimeFormat("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(d);
};

const formatTime = (iso: string) => {
    const d = new Date(iso);
    if (isNaN(d.getTime())) return "";
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

const initials = (name: string) => {
    const clean = (name || "").trim();
    if (!clean) return "C";
    const parts = clean.split(/\s+/).slice(0, 2);
    return parts.map((p) => p[0]?.toUpperCase()).join("");
};

const isLocked = (row: any) => {
    if (!row) return false;
    return (row.demo_status ?? "Pending") === "Done" || !!row.demo_done_at;
};

const demoStatusPillClass = (status: string) => {
    if (status === "Done") return "bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200";
    return "bg-blue-100 text-blue-700 ring-1 ring-blue-200";
};

const demoStatusStripeClass = (status: string) => {
    if (status === "Done") return "from-emerald-500 to-emerald-300";
    return "from-blue-500 to-indigo-400";
};

const tableRows = computed(() =>
    customers.value.map((c: any, index: number) => ({
        sn: index + 1,
        id: c.id,
        name: c.name ?? "-",
        designation: c.designation ?? "-",
        numbers: c.numbers ?? "-",
        email: c.email ?? "-",
        locations: c.locations ?? "-",
        lead_source: c.lead_source ?? "-",
        shop_type: c.shop_type ?? "-",
        interest_level: c.interest_level ?? "-",
        service_type_text: (c.service_type || []).join(", ") || "-",
        next_follow_up_date: c.next_follow_up_date ?? null,
        last_contact_date: c.last_contact_date ?? null,
        staff_status: c.staff_status ?? "-",
        assigned_staff: c.assigned_staff ?? c.assignedStaff ?? null,
        demo_status: c.demo_status ?? "Pending",
        demo_done_at: c.demo_done_at ?? null,
        demo_notes_unread: c.demo_notes_unread ?? 0,
        demo_presenter_id: c.demo_presenter_id ?? null,

        // additional details (shown in modal)
        last_discuss_note: c.last_discuss_note ?? null,
        our_commitment: c.our_commitment ?? null,
        feature_need: c.feature_need ?? null,
        client_behaviour: c.client_behaviour ?? null,
        offer_connect: c.offer_connect ?? null,
    }))
);

const total = computed(() => tableRows.value.length);
const pendingCount = computed(
    () => tableRows.value.filter((r: any) => (r.demo_status ?? "Pending") === "Pending").length
);
const doneCount = computed(() => tableRows.value.filter((r: any) => (r.demo_status ?? "Pending") === "Done").length);

const searchFilteredRows = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return tableRows.value;

    return tableRows.value.filter((r: any) => {
        const haystack = [
            r.name,
            r.numbers,
            r.email,
            r.locations,
            r.lead_source,
            r.service_type_text,
            String(r.id ?? ""),
        ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();

        return haystack.includes(q);
    });
});

const filteredRows = computed(() => {
    if (activeTab.value === "All") return searchFilteredRows.value;
    return searchFilteredRows.value.filter((r: any) => (r.demo_status ?? "Pending") === activeTab.value);
});

const openStatus = (row: any) => {
    if (isLocked(row)) {
        toast.add({
            severity: "info",
            summary: "Locked",
            detail: "This demo is already marked Done and cannot be updated.",
            life: 2500,
        });
        return;
    }
    statusCustomer.value = row;
    selectedDemoStatus.value = (row.demo_status ?? "Pending") === "Done" ? "Done" : "Pending";
    statusNote.value = "";
    showStatusDialog.value = true;
};

const fetchHistory = async (customerId: number) => {
    historyLoading.value = true;
    assignedForDemoAt.value = null;
    assignedForDemoBy.value = null;
    try {
        const { data } = await axios.get(`/api/customers/${customerId}/history`);
        const list = Array.isArray(data) ? data : [];

        const normalized = list.map((item: any) => ({
            ...item,
            service_type: Array.isArray(item.service_type)
                ? item.service_type
                : (() => {
                      try {
                          return JSON.parse(item.service_type || "[]");
                      } catch {
                          return [];
                      }
                  })(),
            old_data: item.old_data ?? null,
        }));

        // Latest first, but keep created at bottom (same behavior as AllContactList)
        historyData.value = normalized.sort((a: any, b: any) => {
            if (a.note === "Customer created") return 1;
            if (b.note === "Customer created") return -1;
            return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        });

        // Try to detect when demo presenter was assigned (history entry where demo_presenter_id changed)
        const assignEntry = historyData.value.find(
            (h: any) => h?.old_data && Object.prototype.hasOwnProperty.call(h.old_data, "demo_presenter_id")
        );
        if (assignEntry?.created_at) {
            assignedForDemoAt.value = assignEntry.created_at;
            assignedForDemoBy.value = assignEntry.staff ?? null;
        }
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error?.response?.data?.message || "Failed to load customer history.",
            life: 3000,
        });
        historyData.value = [];
    } finally {
        historyLoading.value = false;
    }
};

const fetchNotes = async (customerId: number) => {
    notesLoading.value = true;
    try {
        const { data } = await axios.get(`/api/customers/${customerId}/demo-notes`);
        notes.value = Array.isArray(data?.notes) ? data.notes : [];
        await axios.put(`/api/customers/${customerId}/demo-notes/mark-read`);

        const idx = customers.value.findIndex((c: any) => c.id === customerId);
        if (idx !== -1) customers.value[idx].demo_notes_unread = 0;
        if (detailsCustomer.value?.id === customerId) detailsCustomer.value.demo_notes_unread = 0;
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error?.response?.data?.message || "Failed to load demo notes.",
            life: 3000,
        });
        notes.value = [];
    } finally {
        notesLoading.value = false;
    }
};

const isMine = (note: any) => currentUser.value?.id && note?.user_id === currentUser.value.id;

const send = async () => {
    if (!detailsCustomer.value?.id) return;
    const customerId = detailsCustomer.value.id;
    const text = message.value.trim();
    if (!text) return;

    sending.value = true;
    try {
        const { data } = await axios.post(`/api/customers/${customerId}/demo-notes`, { message: text });
        if (data?.note) notes.value.push(data.note);
        message.value = "";
        await axios.put(`/api/customers/${customerId}/demo-notes/mark-read`);
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error?.response?.data?.message || "Failed to send message.",
            life: 3000,
        });
    } finally {
        sending.value = false;
    }
};

const openDetails = async (row: any, tab: "info" | "history" | "notes" = "info") => {
    detailsCustomer.value = row;
    detailsTab.value = tab;
    showDetailsDialog.value = true;
    message.value = "";
    notes.value = [];
    historyData.value = [];
    await fetchCurrentUser();
    await Promise.all([fetchNotes(row.id), fetchHistory(row.id)]);
};

watch(
    () => showDetailsDialog.value,
    (v) => {
        if (!v) {
            detailsCustomer.value = null;
            detailsTab.value = "info";
            message.value = "";
            notes.value = [];
            historyData.value = [];
            assignedForDemoAt.value = null;
            assignedForDemoBy.value = null;
        }
    }
);

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

const formatHistoryValue = (value: any) => {
    if (value === null || value === undefined || value === "") return "-";

    if (Array.isArray(value)) return value.join(", ") || "-";

    // Try to detect date
    const date = new Date(value);

    if (!isNaN(date.getTime())) {
        return new Intl.DateTimeFormat("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "numeric",
            minute: "2-digit",
            hour12: true,
        }).format(date).replace(",", " ,");
    }

    return String(value);
};

const getAssignedForDemoAt = (customerId: number) => demoAssignedAtMap.value[customerId] ?? null;
const getAssignedForDemoBy = (customerId: number) => demoAssignedByMap.value[customerId] ?? null;
</script>

<template>
    <div class="p-5 space-y-6">
        <Toast />

        <!-- Header -->
        <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div
                class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-gradient-to-br from-indigo-200/60 to-sky-200/60 blur-3xl"
            />
            <div
                class="absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-gradient-to-br from-emerald-200/60 to-amber-200/60 blur-3xl"
            />

            <div class="relative p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="mt-3 text-2xl font-semibold text-slate-900">Assigned Customers</h1>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition disabled:opacity-60"
                        :disabled="loading"
                        @click="fetchDemoCustomers"
                    >
                        <i class="pi pi-refresh mr-2" />
                        {{ loading ? "Refreshing..." : "Refresh" }}
                    </button>
                </div>

            </div>
        </div>

        <!-- Controls -->
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm p-4">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">
                <div class="lg:col-span-7 flex flex-wrap gap-2">
                    <button
                        class="px-4 py-2 rounded-full text-sm font-semibold transition"
                        :class="activeTab === 'All' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        @click="activeTab = 'All'"
                        type="button"
                    >
                        <i class="pi pi-list mr-2" />
                        All <span class="ml-1 text-xs opacity-80">({{ total }})</span>
                    </button>

                    <button
                        class="px-4 py-2 rounded-full text-sm font-semibold transition"
                        :class="activeTab === 'Pending' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                        @click="activeTab = 'Pending'"
                        type="button"
                    >
                        <i class="pi pi-clock mr-2" />
                        Pending <span class="ml-1 text-xs opacity-80">({{ pendingCount }})</span>
                    </button>

                    <button
                        class="px-4 py-2 rounded-full text-sm font-semibold transition"
                        :class="activeTab === 'Done' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                        @click="activeTab = 'Done'"
                        type="button"
                    >
                        <i class="pi pi-check mr-2" />
                        Done <span class="ml-1 text-xs opacity-80">({{ doneCount }})</span>
                    </button>
                </div>

                <div class="lg:col-span-5">
                    <label class="text-sm font-medium mb-1 text-slate-700 block">
                        <i class="pi pi-search mr-2" />
                        Search
                    </label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search name, number, email, location..."
                        class="w-full h-[44px] px-4 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition"
                    />
                </div>
            </div>
        </div>

        <!-- Cards (2 in one line on lg) -->
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-900">
                    <i class="pi pi-users mr-2" />
                    Customers
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    Notes, full details, and timeline are inside View Details.
                </p>
            </div>

            <div class="p-4">
                <div v-if="loading" class="text-sm text-slate-500 text-center py-10">Loading...</div>

                <div v-else-if="filteredRows.length === 0" class="text-sm text-slate-500 text-center py-10">
                    No customers found.
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="row in filteredRows"
                        :key="row.id"
                        class="relative h-full overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 hover:shadow-md transition"
                    >
                        <div class="absolute left-0 top-0 h-full w-1.5 bg-gradient-to-b" :class="demoStatusStripeClass(row.demo_status ?? 'Pending')" />

                        <div class="pl-2">
                            <div class="flex flex-col gap-5 2xl:flex-row 2xl:items-start">
                                <div class="flex items-start gap-3 2xl:w-[20rem]">
                                    <div class="h-12 w-12 shrink-0 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 flex items-center justify-center text-white font-bold">
                                        {{ initials(row.name) }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <h3 class="font-semibold text-slate-900 truncate">{{ row.name }}</h3>
                                                <p class="mt-1 text-sm text-slate-500">{{ row.designation || "-" }}</p>
                                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5">
                                                        <i class="pi pi-calendar mr-1" />Next Follow Up: {{ formatDate(row.next_follow_up_date) }}
                                                    </span>
                                                    <span v-if="getAssignedForDemoAt(row.id)" class="rounded-full bg-amber-50 text-amber-700 px-2 py-0.5">
                                                        <i class="pi pi-send mr-1" />Assigned: {{ formatDateTime(getAssignedForDemoAt(row.id)) }}
                                                    </span>
                                                    <span v-if="row.demo_done_at" class="rounded-full bg-emerald-50 text-emerald-700 px-2 py-0.5">
                                                        <i class="pi pi-check-circle mr-1" />Done: {{ formatDateTime(row.demo_done_at) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <span class="px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap" :class="demoStatusPillClass(row.demo_status ?? 'Pending')">
                                                {{ row.demo_status ?? "Pending" }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 text-sm">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="text-[11px] font-semibold text-slate-500"><i class="pi pi-briefcase mr-2" />Service Type</div>
                                            <div class="mt-1 font-medium text-slate-800 break-words">{{ row.service_type_text || "-" }}</div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="text-[11px] font-semibold text-slate-500"><i class="pi pi-phone mr-2" />Numbers</div>
                                            <div class="mt-1 text-slate-800 break-words">{{ row.numbers || "-" }}</div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="text-[11px] font-semibold text-slate-500"><i class="pi pi-envelope mr-2" />Email</div>
                                            <div class="mt-1 text-slate-800 break-words">{{ row.email || "-" }}</div>
                                        </div>
                                        <!-- <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="text-[11px] font-semibold text-slate-500"><i class="pi pi-map-marker mr-2" />Location</div>
                                            <div class="mt-1 text-slate-800 break-words">{{ row.locations || "-" }}</div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="text-[11px] font-semibold text-slate-500"><i class="pi pi-link mr-2" />Lead Source</div>
                                            <div class="mt-1 text-slate-800 break-words">{{ row.lead_source || "-" }}</div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="text-[11px] font-semibold text-slate-500"><i class="pi pi-chart-line mr-2" />Interest</div>
                                            <div class="mt-1 text-slate-800 break-words">{{ row.interest_level || "-" }}</div>
                                        </div> -->
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="text-[11px] font-semibold text-slate-500"><i class="pi pi-shop mr-2" />Shop Type</div>
                                            <div class="mt-1 text-slate-800 break-words">{{ row.shop_type || "-" }}</div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="text-[11px] font-semibold text-slate-500"><i class="pi pi-user mr-2" />Assigned By Demo</div>
                                            <div class="mt-1 text-slate-800 break-words">{{ getAssignedForDemoBy(row.id) || "-" }}</div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="text-[11px] font-semibold text-slate-500"><i class="pi pi-clock mr-2" />Last Contact</div>
                                            <div class="mt-1 text-slate-800 break-words">{{ formatDate(row.last_contact_date) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="2xl:w-[17rem] shrink-0">
                                    <div class="grid grid-cols-2 gap-2 md:grid-cols-4 2xl:grid-cols-1">
                                        <button
                                            type="button"
                                            class="relative w-full inline-flex items-center justify-center rounded-2xl bg-purple-600 text-white px-4 py-2 text-sm font-semibold hover:bg-purple-700 transition"
                                            @click="openDetails(row, 'notes')"
                                        >
                                            <i class="pi pi-comments mr-2" />Notes
                                            <span
                                                v-if="row.demo_notes_unread > 0"
                                                class="absolute -top-2 -right-2 min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[10px] flex items-center justify-center"
                                            >
                                                {{ row.demo_notes_unread }}
                                            </span>
                                        </button>

                                        <button
                                            type="button"
                                            class="relative w-full inline-flex items-center justify-center rounded-2xl bg-blue-600 text-white px-4 py-2 text-sm font-semibold hover:bg-blue-700 transition"
                                            @click="openDetails(row, 'history')"
                                        >
                                            <i class="pi pi-history mr-2" />History
                                        </button>

                                        <button
                                            type="button"
                                            class="w-full inline-flex items-center justify-center rounded-2xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold hover:bg-slate-800 transition"
                                            @click="openDetails(row, 'info')"
                                        >
                                            <i class="pi pi-eye mr-2" />View Details
                                        </button>

                                        <button
                                            type="button"
                                            class="w-full inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-semibold transition disabled:opacity-60 disabled:cursor-not-allowed"
                                            :class="isLocked(row) ? 'bg-slate-100 text-slate-500' : 'bg-blue-600 text-white hover:bg-blue-700'"
                                            :disabled="isLocked(row)"
                                            @click="openStatus(row)"
                                        >
                                            <i class="pi pi-pencil mr-2" />{{ isLocked(row) ? "Locked" : "Update Status" }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <Dialog
            v-model:visible="showDetailsDialog"
            modal
            :style="{ width: '70rem', maxWidth: '96vw' }"
            :header="detailsCustomer?.name ? `Customer — ${detailsCustomer.name}` : 'Customer Details'"
        >
            <div class="flex flex-wrap gap-2 mb-4">
                <button
                    type="button"
                    class="px-4 py-2 rounded-full text-sm font-semibold transition"
                    :class="detailsTab === 'info' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    @click="detailsTab = 'info'"
                >
                    <i class="pi pi-info-circle mr-2" />Info
                </button>
                <button
                    type="button"
                    class="px-4 py-2 rounded-full text-sm font-semibold transition"
                    :class="detailsTab === 'history' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'"
                    @click="detailsTab = 'history'"
                >
                    <i class="pi pi-history mr-2" />History
                </button>
                <button
                    type="button"
                    class="px-4 py-2 rounded-full text-sm font-semibold transition"
                    :class="detailsTab === 'notes' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-700 hover:bg-purple-100'"
                    @click="detailsTab = 'notes'"
                >
                    <i class="pi pi-comments mr-2" />Notes
                    <span v-if="detailsCustomer?.demo_notes_unread > 0" class="ml-2 px-2 py-0.5 rounded-full text-xs bg-red-600 text-white">
                        {{ detailsCustomer.demo_notes_unread }}
                    </span>
                </button>
            </div>

            <!-- Info -->
            <div v-if="detailsTab === 'info'" class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-slate-900 font-semibold">
                            {{ detailsCustomer?.name || "-" }}
                            <span class="text-slate-500 text-sm font-medium ml-2">ID: {{ detailsCustomer?.id }}</span>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full w-fit" :class="demoStatusPillClass(detailsCustomer?.demo_status ?? 'Pending')">
                            {{ detailsCustomer?.demo_status ?? "Pending" }}
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="text-xs font-semibold text-slate-500"><i class="pi pi-briefcase mr-2" />Service Type</div>
                            <div class="mt-1 text-slate-800">{{ detailsCustomer?.service_type_text || "-" }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-500"><i class="pi pi-link mr-2" />Lead Source</div>
                            <div class="mt-1 text-slate-800">{{ detailsCustomer?.lead_source || "-" }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-500"><i class="pi pi-envelope mr-2" />Email</div>
                            <div class="mt-1 text-slate-800 break-words">{{ detailsCustomer?.email || "-" }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-500"><i class="pi pi-map-marker mr-2" />Locations</div>
                            <div class="mt-1 text-slate-800 break-words">{{ detailsCustomer?.locations || "-" }}</div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="text-xs font-semibold text-slate-500"><i class="pi pi-phone mr-2" />Numbers</div>
                            <div class="mt-1 text-slate-800 break-words">{{ detailsCustomer?.numbers || "-" }}</div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-white/60 bg-white/70 p-4">
                            <div class="text-xs font-semibold text-slate-500"><i class="pi pi-user mr-2" />Assigned By (Demo)</div>
                            <div class="mt-1 text-slate-900 font-semibold">{{ assignedForDemoBy || "-" }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/60 bg-white/70 p-4">
                            <div class="text-xs font-semibold text-slate-500"><i class="pi pi-calendar mr-2" />Assigned For Demo At</div>
                            <div class="mt-1 text-slate-900 font-semibold">{{ formatDateTime(assignedForDemoAt) || "-" }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/60 bg-white/70 p-4">
                            <div class="text-xs font-semibold text-slate-500"><i class="pi pi-check-circle mr-2" />Demo Done At</div>
                            <div class="mt-1 text-slate-900 font-semibold">{{ formatDateTime(detailsCustomer.demo_done_at) || "-" }}</div>
                        </div>
                    </div>
                </div>

                <!-- Important notes/fields -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-3xl border border-indigo-100 bg-indigo-50 p-4">
                        <div class="text-xs font-semibold text-indigo-700"><i class="pi pi-file mr-2" />Last Discuss Note</div>
                        <div class="mt-2 text-sm text-slate-800 whitespace-pre-line break-words max-h-72 overflow-auto">
                            <div v-if="isHtml(detailsCustomer?.last_discuss_note)" v-html="detailsCustomer?.last_discuss_note" class="prose prose-sm max-w-none" />
                            <div v-else>{{ detailsCustomer?.last_discuss_note || "-" }}</div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-amber-100 bg-amber-50 p-4">
                        <div class="text-xs font-semibold text-amber-700"><i class="pi pi-star mr-2" />Our Commitment</div>
                        <div class="mt-2 text-sm text-slate-800 whitespace-pre-line break-words max-h-72 overflow-auto">
                            <div v-if="isHtml(detailsCustomer?.our_commitment)" v-html="detailsCustomer?.our_commitment" class="prose prose-sm max-w-none" />
                            <div v-else>{{ detailsCustomer?.our_commitment || "-" }}</div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-emerald-100 bg-emerald-50 p-4">
                        <div class="text-xs font-semibold text-emerald-700"><i class="pi pi-cog mr-2" />Feature Need</div>
                        <div class="mt-2 text-sm text-slate-800 whitespace-pre-line break-words max-h-72 overflow-auto">
                            <div v-if="isHtml(detailsCustomer?.feature_need)" v-html="detailsCustomer?.feature_need" class="prose prose-sm max-w-none" />
                            <div v-else>{{ detailsCustomer?.feature_need || "-" }}</div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-4">
                        <div class="text-xs font-semibold text-slate-700"><i class="pi pi-comment mr-2" />Client Behaviour</div>
                        <div class="mt-2 text-sm text-slate-800 whitespace-pre-line break-words max-h-72 overflow-auto">
                            <div v-if="isHtml(detailsCustomer?.client_behaviour)" v-html="detailsCustomer?.client_behaviour" class="prose prose-sm max-w-none" />
                            <div v-else>{{ detailsCustomer?.client_behaviour || "-" }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History -->
            <div v-else-if="detailsTab === 'history'" class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-sm font-semibold text-slate-900"><i class="pi pi-history mr-2" />Customer History Timeline</div>
                    <p class="text-xs text-slate-500 mt-1">Shows what was changed and by whom.</p>
                </div>

                <div class="max-h-[28rem] overflow-y-auto rounded-3xl border border-slate-200 bg-white p-5">
                    <div v-if="historyLoading" class="text-sm text-slate-500 text-center py-10">Loading history...</div>

                    <div v-else-if="historyData.length === 0" class="text-sm text-slate-500 text-center py-10">
                        No history available.
                    </div>

                    <div v-else class="relative">
                        <div class="absolute left-1.5 top-2 bottom-0 w-1 bg-gray-200 rounded-full"></div>

                        <template v-for="(item, idx) in historyData" :key="item.id">
                            <div class="relative flex items-start gap-8 group">
                                <div class="flex flex-col items-center z-10">
                                    <div class="w-4 h-4 rounded-full shadow relative top-2"
                                        :class="item.note === 'Customer created' ? 'bg-green-500' : 'bg-indigo-600'"></div>
                                    <div v-if="idx !== historyData.length - 1" class="flex-1 w-1 bg-gray-200"></div>
                                </div>

                                <div class="flex-1 bg-white p-5 rounded-xl shadow border-l-4 relative -top-1 mb-5"
                                    :class="item.note === 'Customer created' ? 'border-green-500' : 'border-indigo-600'">
                                    <div class="flex justify-between items-center mb-3">
                                        <div class="flex items-center gap-2 text-sm">
                                            <i class="pi pi-user text-indigo-600" />
                                            <span class="font-semibold text-indigo-700">{{ item.staff || "-" }}</span>
                                        </div>
                                        <span class="text-indigo-600 text-sm font-medium">
                                            <i class="pi pi-clock mr-2" />{{ formatDateTime(item.created_at) }}
                                        </span>
                                    </div>

                                    <div v-if="item.note" class="text-gray-800 mb-3">
                                        <strong>Note:</strong> <span class="ml-1">{{ item.note }}</span>
                                    </div>

                                    <div v-if="item.old_data && Object.keys(item.old_data).length"
                                        class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <strong class="text-indigo-700"><i class="pi pi-pencil mr-2" />Changed Fields:</strong>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 text-sm text-gray-700">
                                            <div v-for="(value, key) in item.old_data" :key="key" class="flex gap-2">
                                                <span class="font-medium capitalize min-w-28">
                                                    {{ String(key).replace(/_/g, ' ') }}:
                                                </span>
                                                <span v-if="isHtml(value)" v-html="value" class="prose prose-sm max-w-none" />
                                                <span v-else class="text-gray-700">{{ formatHistoryValue(value) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div v-else class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-sm font-semibold text-slate-900"><i class="pi pi-comments mr-2" />Demo Notes</div>
                    <p class="text-xs text-slate-500 mt-1">Press Enter to send, Shift+Enter for new line.</p>
                </div>

                <div class="h-[22rem] overflow-y-auto rounded-3xl border border-slate-200 bg-white p-4">
                    <div v-if="notesLoading" class="text-sm text-slate-500 text-center py-10">Loading...</div>
                    <div v-else-if="notes.length === 0" class="text-sm text-slate-500 text-center py-10">
                        No messages yet.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="n in notes" :key="n.id" class="flex" :class="isMine(n) ? 'justify-end' : 'justify-start'">
                            <div
                                class="max-w-[75%] rounded-3xl px-4 py-3 shadow-sm border"
                                :class="isMine(n) ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-800 border-slate-200'"
                            >
                                <div class="text-xs opacity-90 mb-1 flex items-center justify-between gap-3">
                                    <span class="font-semibold">
                                        <i class="pi pi-user mr-2" />
                                        {{ isMine(n) ? 'You' : (n.user?.name || 'User') }}
                                        <span class="font-normal opacity-80" v-if="n.user?.role">({{ n.user.role }})</span>
                                    </span>
                                    <span class="opacity-80"><i class="pi pi-clock mr-2" />{{ formatTime(n.created_at) }}</span>
                                </div>
                                <div class="text-sm whitespace-pre-line">{{ n.message }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <textarea
                        v-model="message"
                        rows="2"
                        class="flex-1 border rounded-3xl p-3 focus:ring-2 focus:ring-purple-400 focus:outline-none"
                        placeholder="Write a message..."
                        @keydown.enter.exact.prevent="send"
                    />
                    <button
                        class="px-5 py-2 rounded-3xl bg-purple-600 text-white font-semibold hover:bg-purple-700 transition disabled:opacity-60"
                        :disabled="sending || !message.trim()"
                        type="button"
                        @click="send"
                    >
                        <i class="pi pi-send mr-2" />
                        {{ sending ? "Sending..." : "Send" }}
                    </button>
                </div>
            </div>

            <template #footer>
                <button class="px-5 py-2 bg-slate-900 text-white rounded-2xl hover:bg-slate-800 transition" @click="showDetailsDialog = false">
                    Close
                </button>
            </template>
        </Dialog>

        <!-- Update Status Modal -->
        <Dialog v-model:visible="showStatusDialog" header="Update Demo Status" modal :style="{ width: '32rem', maxWidth: '95vw' }">
            <div class="flex flex-col gap-4">
                <div class="text-sm text-slate-700">
                    <span class="font-semibold">Customer:</span>
                    <span class="ml-1">{{ statusCustomer?.name || "-" }}</span>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="font-medium">Demo Status</label>
                    <select v-model="selectedDemoStatus" class="border rounded-2xl px-3 py-2" :disabled="isLocked(statusCustomer)">
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
                        class="w-full border rounded-2xl p-3 focus:ring-2 focus:ring-blue-400 focus:outline-none disabled:bg-slate-50"
                        :disabled="isLocked(statusCustomer)"
                        placeholder="Write demo feedback / result..."
                    />
                </div>
            </div>

            <div class="text-right mt-6 flex gap-2 justify-end">
                <button class="px-5 py-2 bg-slate-200 text-slate-700 rounded-2xl hover:bg-slate-300" @click="showStatusDialog = false">
                    Cancel
                </button>
                <button
                    class="px-5 py-2 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed"
                    :disabled="isLocked(statusCustomer)"
                    @click="saveDemoStatus"
                >
                    Save
                </button>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
.prose :deep(p) {
    margin-bottom: 0.7rem;
}
.prose :deep(ul),
.prose :deep(ol) {
    margin-left: 1.2rem;
}
.prose :deep(li) {
    margin-bottom: 0.4rem;
}
</style>
