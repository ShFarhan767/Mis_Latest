<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";
import axios from "axios";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import Dialog from "primevue/dialog";
import Calendar from "primevue/calendar";
import DemoNotesDialog from "@/components/demo/DemoNotesDialog.vue";

const toast = useToast();

const loading = ref(false);
const customers = ref<any[]>([]);

const activeTab = ref<"All" | "Pending" | "Done" | "Cancelled">("All");
const searchQuery = ref("");
// Default month filter to the running month
const filterAssignedMonth = ref<Date | null>(new Date());

// Status update modal
const showStatusDialog = ref(false);
const statusCustomer = ref<any | null>(null);
const selectedDemoStatus = ref<"Pending" | "Done" | "Cancelled">("Pending");
const statusNote = ref("");

// Details modal (info/history)
const showDetailsDialog = ref(false);
const detailsCustomer = ref<any | null>(null);
const detailsTab = ref<"info" | "history">("info");

// Notes modal (chat)
const showNotesDialog = ref(false);
const notesCustomer = ref<any | null>(null);

// History timeline
const historyLoading = ref(false);
const historyData = ref<any[]>([]);
const assignedForDemoAt = ref<string | null>(null);
const assignedForDemoBy = ref<string | null>(null);
const demoAssignedAtMap = ref<Record<number, string | null>>({});
const demoAssignedByMap = ref<Record<number, string | null>>({});

const isHtml = (value: any) => typeof value === "string" && value.includes("<");

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

onMounted(async () => {
    await fetchDemoCustomers();
    window.addEventListener('open-demo-note-notification', handleDemoNotificationEvent as EventListener);
    applyDemoNotificationNavigation();
});

onBeforeUnmount(() => {
    window.removeEventListener('open-demo-note-notification', handleDemoNotificationEvent as EventListener);
});

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

const formatDateTime = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleString('en-GB', {
        day: 'numeric',       // 4
        month: 'long',        // March
        year: 'numeric',      // 2026
        hour: 'numeric',      // 2
        minute: '2-digit',    // 30
        hour12: true          // PM
    });
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
    return ["Done", "Cancelled"].includes(row.demo_status ?? "Pending") || !!row.demo_done_at;
};

const demoStatusPillClass = (status: string | null) => {
    if (status === "Done") return "bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200";
    if (status === "Cancelled") return "bg-rose-100 text-rose-700 ring-1 ring-rose-200";
    if (status === "Pending") return "bg-blue-100 text-blue-700 ring-1 ring-blue-200";
    return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
};

const demoStatusStripeClass = (status: string | null) => {
    if (status === "Done") return "from-emerald-500 to-emerald-300";
    if (status === "Cancelled") return "from-rose-500 to-rose-300";
    if (status === "Pending") return "from-blue-500 to-indigo-400";
    return "from-slate-500 to-slate-300";
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
        demo_status: c.demo_status ?? null,
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

const monthFilteredRows = computed(() => {
    if (!filterAssignedMonth.value) return tableRows.value;
    const month = new Date(filterAssignedMonth.value);

    return tableRows.value.filter((r: any) => {
        const assignedAt = getAssignedForDemoAt(r.id);
        if (!assignedAt) return false;
        const d = new Date(assignedAt);
        if (isNaN(d.getTime())) return false;
        return d.getFullYear() === month.getFullYear() && d.getMonth() === month.getMonth();
    });
});

const total = computed(() => monthFilteredRows.value.filter((r: any) => r.demo_status === null).length);
const pendingCount = computed(
    () => monthFilteredRows.value.filter((r: any) => r.demo_status === "Pending").length
);
const doneCount = computed(() => monthFilteredRows.value.filter((r: any) => r.demo_status === "Done").length);
const cancelledCount = computed(
    () => monthFilteredRows.value.filter((r: any) => r.demo_status === "Cancelled").length
);

const searchFilteredRows = computed(() => {
    const month = filterAssignedMonth.value ? new Date(filterAssignedMonth.value) : null;
    const q = searchQuery.value.trim().toLowerCase();
    const base = month
        ? tableRows.value.filter((r: any) => {
              const assignedAt = getAssignedForDemoAt(r.id);
              if (!assignedAt) return false;
              const d = new Date(assignedAt);
              if (isNaN(d.getTime())) return false;
              return d.getFullYear() === month.getFullYear() && d.getMonth() === month.getMonth();
          })
        : tableRows.value;

    if (!q) return base;

    return base.filter((r: any) => {
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
    if (activeTab.value === "All") return searchFilteredRows.value.filter((r: any) => r.demo_status === null);
    return searchFilteredRows.value.filter((r: any) => r.demo_status === activeTab.value);
});

const showFilteredCount = computed(() => {
    return !!(searchQuery.value.trim() || filterAssignedMonth.value);
});

const filteredCount = computed(() => filteredRows.value.length);

const openStatus = (row: any) => {
    if (isLocked(row)) {
        const status = row?.demo_status ?? "Assigned";
        toast.add({
            severity: "info",
            summary: "Locked",
            detail: `This demo is already marked ${status} and cannot be updated.`,
            life: 2500,
        });
        return;
    }
    statusCustomer.value = row;
    selectedDemoStatus.value = ["Done", "Cancelled"].includes(row.demo_status ?? "Pending")
        ? (row.demo_status ?? "Pending")
        : "Pending";
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

const openDetails = async (row: any, tab: "info" | "history" = "info") => {
    detailsCustomer.value = row;
    detailsTab.value = tab;
    showDetailsDialog.value = true;
    historyData.value = [];
    await fetchHistory(row.id);
};

const openNotes = (row: any) => {
    notesCustomer.value = row;
    showNotesDialog.value = true;
};

const getDemoNotesUnread = (customerId: number | null) => {
    if (!customerId) return 0;
    const customer = customers.value.find((item: any) => item.id === customerId);
    return Number(customer?.demo_notes_unread ?? 0);
};

const handleNotesMarkedRead = ({ customerId }: { customerId: number | null }) => {
    if (!customerId) return;
    const customer = customers.value.find((item: any) => item.id === customerId);
    if (customer) {
        customer.demo_notes_unread = 0;
    }
};

const handleNotesVisibilityChange = (visible: boolean) => {
    showNotesDialog.value = visible;
    if (!visible) {
        notesCustomer.value = null;
        void fetchDemoCustomers();
    }
};

const openNotesFromPayload = (customerId: number, demoTabKey?: string | null) => {
    const customer = customers.value.find((item: any) => item.id === customerId);
    if (!customer) return;

    if (demoTabKey === 'pending') {
        activeTab.value = 'Pending';
    } else if (demoTabKey === 'done') {
        activeTab.value = 'Done';
    } else if (demoTabKey === 'cancelled') {
        activeTab.value = 'Cancelled';
    } else {
        activeTab.value = customer.demo_status ?? 'All';
    }

    openNotes(customer);
};

const handleDemoNotificationEvent = (event: Event) => {
    const detail = (event as CustomEvent)?.detail ?? {};
    const customerId = Number(detail.customerId || 0);
    if (!customerId) return;
    openNotesFromPayload(customerId, typeof detail.demoTabKey === 'string' ? detail.demoTabKey : null);
};

const applyDemoNotificationNavigation = () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('openDemoNotes') !== '1') return;

    const customerId = Number(params.get('demoNoteCustomer') || 0);
    const demoTabKey = (params.get('demoNoteTab') || '').toLowerCase();
    if (!customerId) return;

    openNotesFromPayload(customerId, demoTabKey);

    params.delete('openDemoNotes');
    params.delete('demoNoteCustomer');
    params.delete('demoNoteTab');
    const nextQuery = params.toString();
    const nextUrl = `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ''}`;
    window.history.replaceState({}, '', nextUrl);
};

watch(
    () => showDetailsDialog.value,
    (v) => {
        if (!v) {
            detailsCustomer.value = null;
            detailsTab.value = "info";
            historyData.value = [];
            assignedForDemoAt.value = null;
            assignedForDemoBy.value = null;
        }
    }
);

const saveDemoStatus = async () => {
    if (!statusCustomer.value) return;

    if (["Done", "Cancelled"].includes(selectedDemoStatus.value) && !statusNote.value.trim()) {
        toast.add({
            severity: "warn",
            summary: "Note Required",
            detail:
                selectedDemoStatus.value === "Cancelled"
                    ? "Please write why this demo was cancelled."
                    : "Please write a note before marking demo as Done.",
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

const historyEntries = (oldData: any): Array<[string, any]> => {
    return Object.entries(oldData ?? {}).filter(([, value]) => {
        if (value === null || value === undefined || value === "") return false;
        if (Array.isArray(value) && value.length === 0) return false;
        if (typeof value === "object" && !Array.isArray(value) && Object.keys(value).length === 0) return false;
        return true;
    });
};

const getAssignedForDemoAt = (customerId: number) => demoAssignedAtMap.value[customerId] ?? null;
const getAssignedForDemoBy = (customerId: number) => demoAssignedByMap.value[customerId] ?? null;
</script>

<template>
    <div class="p-5 space-y-6">
        <Toast />

        <!-- Header -->
        <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm lg:block hidden">
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

                    <button
                        class="px-4 py-2 rounded-full text-sm font-semibold transition"
                        :class="activeTab === 'Cancelled' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100'"
                        @click="activeTab = 'Cancelled'"
                        type="button"
                    >
                        <i class="pi pi-times-circle mr-2" />
                        Cancelled <span class="ml-1 text-xs opacity-80">({{ cancelledCount }})</span>
                    </button>
                </div>

                <div class="lg:col-span-3">
                    <label class="text-sm font-medium mb-1 text-slate-700 block">
                        <i class="pi pi-calendar mr-2" />
                        Assigned Month
                    </label>
                    <Calendar
                        v-model="filterAssignedMonth"
                        view="month"
                        dateFormat="mm/yy"
                        showIcon
                        showButtonBar
                        class="w-full"
                        placeholder="Pick month"
                    />
                </div>

                <div class="lg:col-span-2">
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

        <div class="flex justify-center items-center gap-2">
            <span
                v-if="showFilteredCount"
                class="inline-block bg-indigo-600 text-white text-base font-semibold px-5 py-2 rounded-2xl shadow-sm"
            >
                Total Found - ({{ filteredCount }})
            </span>
        </div>

        <!-- Cards (2 in one line on lg) -->
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-900">
                    <i class="pi pi-users mr-2" />
                    Customers
                </h2>
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
                        <div class="absolute left-0 top-0 h-full w-1.5 bg-gradient-to-b" :class="demoStatusStripeClass(row.demo_status ?? null)" />

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

                                            <span class="px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap" :class="demoStatusPillClass(row.demo_status ?? null)">
                                                {{ row.demo_status ?? "Assigned" }}
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
                                            @click="openNotes(row)"
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
            </div>

            <!-- Info -->
            <div v-if="detailsTab === 'info'" class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-slate-900 font-semibold">
                            {{ detailsCustomer?.name || "-" }}
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full w-fit" :class="demoStatusPillClass(detailsCustomer?.demo_status ?? null)">
                            {{ detailsCustomer?.demo_status ?? "Assigned" }}
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

                <div class="max-h-[28rem] overflow-y-auto rounded-3xl border border-slate-200 bg-white p-3 sm:p-5">
                    <div v-if="historyLoading" class="text-sm text-slate-500 text-center py-10">Loading history...</div>

                    <div v-else-if="historyData.length === 0" class="text-sm text-slate-500 text-center py-10">
                        No history available.
                    </div>

                    <div v-else class="relative">
                        <div class="absolute left-1.5 top-2 bottom-0 w-1 rounded-full bg-gray-200"></div>

                        <template v-for="(item, idx) in historyData" :key="item.id">
                            <div class="group relative flex items-start gap-3 sm:gap-8">
                                <div class="z-10 flex flex-col items-center self-stretch">
                                    <div class="relative top-2 h-4 w-4 rounded-full shadow"
                                        :class="item.note === 'Customer created' ? 'bg-green-500' : 'bg-indigo-600'"></div>
                                    <div v-if="idx !== historyData.length - 1" class="w-1 flex-1 bg-gray-200"></div>
                                </div>

                                <div class="relative mb-5 flex-1 rounded-xl border-l-4 bg-white p-3 shadow sm:-top-1 sm:p-5"
                                    :class="item.note === 'Customer created' ? 'border-green-500' : 'border-indigo-600'">
                                    <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center gap-2 text-sm">
                                            <i class="pi pi-user text-indigo-600" />
                                            <span class="font-semibold text-indigo-700">{{ item.staff || "-" }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-indigo-600 break-words">
                                            Change Time: {{ formatDateTime(item.created_at) }}
                                        </span>
                                    </div>

                                    <div v-if="item.note" class="text-gray-800 mb-3">
                                        <strong>Note:</strong> <span class="ml-1">{{ item.note }}</span>
                                    </div>

                                    <div v-if="historyEntries(item.old_data).length"
                                        class="rounded-lg border border-gray-200 bg-gray-50 p-3 sm:p-4">
                                        <strong class="text-indigo-700"><i class="pi pi-pencil mr-2" />Changed Fields:</strong>
                                        <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-gray-700 sm:grid-cols-2 sm:gap-3">
                                            <div v-for="([key, value]) in historyEntries(item.old_data)" :key="key" class="flex flex-col gap-1 break-words sm:flex-row sm:gap-2">
                                                <span class="font-medium capitalize sm:min-w-28">
                                                    {{ String(key).replace(/_/g, ' ') }}:
                                                </span>
                                                <span v-if="isHtml(value)" v-html="value" class="prose prose-sm max-w-none min-w-0 break-words" />
                                                <span v-else class="min-w-0 break-words text-gray-700">{{ formatHistoryValue(value) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <template #footer>
                <button class="w-full rounded-2xl bg-slate-900 px-5 py-2 text-white transition hover:bg-slate-800 sm:w-auto" @click="showDetailsDialog = false">
                    Close
                </button>
            </template>
        </Dialog>

        <DemoNotesDialog
            :visible="showNotesDialog"
            :customerId="notesCustomer?.id ?? null"
            :customerName="notesCustomer?.name ?? ''"
            :unreadCount="getDemoNotesUnread(notesCustomer?.id ?? null)"
            @marked-read="handleNotesMarkedRead"
            @update:visible="handleNotesVisibilityChange"
        />

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
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="font-medium">
                        Note <span class="text-red-600" v-if="selectedDemoStatus === 'Done' || selectedDemoStatus === 'Cancelled'">*</span>
                    </label>
                    <textarea
                        v-model="statusNote"
                        rows="4"
                        class="w-full border rounded-2xl p-3 focus:ring-2 focus:ring-blue-400 focus:outline-none disabled:bg-slate-50"
                        :disabled="isLocked(statusCustomer)"
                        :placeholder="selectedDemoStatus === 'Cancelled' ? 'Write why this demo was cancelled...' : 'Write demo feedback / result...'"
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
