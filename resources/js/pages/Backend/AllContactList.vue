<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";
import DataTable from "@/Components/DataTable.vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import Dialog from "primevue/dialog";
import Calendar from 'primevue/calendar'; // PrimeVue Calendar
import Multiselect from "vue-multiselect";
import AppLayout from "@/Layouts/AppLayout.vue";
import DemoPresenterDashboard from "@/components/DemoPresenterDashboard.vue";
import DemoNotesDialog from "@/components/demo/DemoNotesDialog.vue";

const toast = useToast();
const customers = ref<any[]>([]);

const searchQuery = ref('');
const isSearching = computed(() => searchQuery.value.trim().length > 0);
const filterCreatedBy = ref<any | null>(null);
const filterCreatedDate = ref<Date | null>(null);
const filterCreatedMonth = ref<Date | null>(null);

// Admin-only: tab-specific filters
const filterAssignedUser = ref<any | null>(null);
const filterDemoPresenter = ref<any | null>(null);
const demoAssignedAtMap = ref<Record<number, string | null>>({});

// Modal states
const showModal = ref(false);
const modalTitle = ref("");
const modalContent = ref("");

const showHistoryModal = ref(false);
const showExtraNoteModal = ref(false);
const showPreviewModal = ref(false);

const previewTitle = ref("");
const previewContent = ref("");

const historyData = ref<any[]>([]);
const noteCustomerId = ref<number | null>(null);
const newNote = ref("");
const customerHistory = ref<any[]>([]);

const allServiceTypeOptions = ref<string[]>([]);
const selectedOldServiceTypes = ref<string[]>([]);

const editingNoteId = ref<number | null>(null);
const editingContent = ref("");

const openModal = (title: string, content: string) => {
    previewTitle.value = title;
    previewContent.value = content?.trim() || "";
    showPreviewModal.value = true;
};

const openHistoryModal = async (customer: any) => {
    try {
        const { data } = await axios.get(`/api/customers/${customer.id}/history`);

        // Normalize service_type
        historyData.value = data
            .map((item: any) => ({
                ...item,
                service_type: Array.isArray(item.service_type)
                    ? item.service_type
                    : (() => {
                        try { return JSON.parse(item.service_type || "[]"); }
                        catch { return []; }
                    })()
            }))
            // Sort: latest first, but keep 'Customer created' at bottom
            .sort((a, b) => {
                if (a.note === 'Customer created') return 1;      // created last
                if (b.note === 'Customer created') return -1;     // created last
                return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
            });

        modalTitle.value = `History of ${customer.name}`;
        showHistoryModal.value = true;
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch history', life: 3000 });
    }
};

const openNoteModal = async (customer: any) => {
    noteCustomerId.value = customer.id;
    newNote.value = "";
    await fetchLatestNotes();
    showExtraNoteModal.value = true;
};

const fetchLatestNotes = async () => {
    if (!noteCustomerId.value) return;

    const { data } = await axios.get(
        `/api/customers/${noteCustomerId.value}/notes`
    );

    customerHistory.value = data.slice(0, 2);
};

const saveNote = async () => {
    if (!noteCustomerId.value || !newNote.value.trim()) return;

    await axios.post(`/api/customers/${noteCustomerId.value}/add-note`, {
        note: newNote.value
    });

    toast.add({ severity: 'success', summary: 'Saved', detail: 'Note added', life: 2000 });
    newNote.value = "";
    showExtraNoteModal.value = false;
    fetchLatestNotes();
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

const allUsers = ref<any[]>([]);
const currentUser = ref<any | null>(null);
const users = ref<any[]>([]);
const demoPresenters = ref<any[]>([]);

// ====================
// FETCH LOGGED-IN STAFF
// ====================
const fetchUsers = async () => {
    try {
        const { data } = await axios.get('/api/users');

        // Keep only admin and staff role users
        allUsers.value = data.filter((u: any) => u.role === 'admin' || u.role === 'staff');
        // ✅ Keep only staff role users
        users.value = data.filter((user: any) => user.role === 'staff');

    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch users',
            life: 3000,
        });
    }
};

const fetchDemoPresenters = async () => {
    try {
        const { data } = await axios.get('/api/demo-presenters');
        const list = Array.isArray(data) ? data : data?.data || [];
        demoPresenters.value = list.map((presenter: any) => {
            const presenterNumber = presenter.mobile || presenter.phone || presenter.number || null;

            return {
                ...presenter,
                label: presenterNumber ? `${presenter.name} (${presenterNumber})` : presenter.name,
            };
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch demo presenters',
            life: 3000,
        });
        demoPresenters.value = [];
    }
};

const fetchCurrentUser = async () => {
    try {
        const { data } = await axios.get('/api/current-user'); // returns single user
        currentUser.value = data;
    } catch (err) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch current user',
            life: 3000,
        });
    }
};

// Fetch customers
const fetchNewCustomers = async () => {
    try {
        const { data } = await axios.get("/api/customers");

        let list = data.customers
            .map((c: any) => ({
                ...c,

                service_type: (() => {
                    try {
                        return Array.isArray(c.service_type)
                            ? c.service_type
                            : JSON.parse(c.service_type || "[]");
                    } catch {
                        return [];
                    }
                })(),

                numbers_raw: c.numbers,
                numbers:
                    c.numbers
                        ?.map((n: any) => `${n.full_number} (${n.type})`)
                        .join(", ") ?? "-",

                assigned_users: c.assigned_staff ? [c.assigned_staff] : [],
                demo_notes_unread: c.demo_notes_unread ?? 0,
            }));

        customers.value = list;
        void hydrateDemoAssignedTimes(list);
    } catch {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch customers",
            life: 3000,
        });
    }
};

const hydrateDemoAssignedTimes = async (list: any[]) => {
    const demoRows = list.filter((customer: any) =>
        customer.staff_status === "Need To Show Demo" || customer.staff_status === "Demo Done"
    );

    if (!demoRows.length) {
        demoAssignedAtMap.value = {};
        return;
    }

    const results = await Promise.allSettled(
        demoRows.map(async (customer: any) => {
            const { data } = await axios.get(`/api/customers/${customer.id}/history`);
            const historyList = Array.isArray(data) ? data : [];
            const assignedEntry = historyList.find(
                (item: any) =>
                    item?.old_data &&
                    Object.prototype.hasOwnProperty.call(item.old_data, "demo_presenter_id")
            );

            return {
                id: customer.id,
                assignedAt: assignedEntry?.created_at ?? null,
            };
        })
    );

    const assignedMap: Record<number, string | null> = {};
    results.forEach((result) => {
        if (result.status !== "fulfilled") return;
        assignedMap[result.value.id] = result.value.assignedAt;
    });

    demoAssignedAtMap.value = assignedMap;
};

// Assign staff modal
const showAssignStaffModal = ref(false);
const assigningCustomer = ref<any | null>(null);
const selectedStaff = ref<any | null>(null);

const openAssignStaffModal = (row: any) => {
    assigningCustomer.value = row;
    selectedStaff.value = row.assigned_users?.[0] || null;
    showAssignStaffModal.value = true;
};

const assignSingle = async () => {
    if (!assigningCustomer.value || !selectedStaff.value) return;

    await axios.post("/api/customers/assign", {
        customer_ids: [assigningCustomer.value.id],
        staff_id: selectedStaff.value.id,
    });

    toast.add({
        severity: "success",
        summary: "Assigned",
        detail: `${assigningCustomer.value.name} assigned successfully`,
        life: 2000,
    });

    showAssignStaffModal.value = false;
    assigningCustomer.value = null;
    selectedStaff.value = null;

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

// Watch multiselect changes
watch(selectedOldServiceTypes, (val) => {
    // Remove items that were unselected
    serviceTypes.value = serviceTypes.value.filter(s => val.includes(s));

    // Add back any newly selected items
    val.forEach(s => {
        if (!serviceTypes.value.includes(s)) {
            serviceTypes.value.push(s);
        }
    });
});

// Add service type from input
const addServiceType = async () => {
    const value = newServiceType.value.trim();
    if (!value) return;

    // 1️⃣ Save to DB if not already in options
    if (!allServiceTypeOptions.value.includes(value)) {
        try {
            const { data } = await axios.post('/api/service-types', {
                service_type_name: value, // use the correct field name
                status: 'Running',         // required field (adjust if needed)
            });

            // Add to multiselect options
            allServiceTypeOptions.value.push(data.service_type_name);

        } catch (error: any) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response?.data?.message || 'Failed to add service type',
                life: 3000
            });
            return;
        }
    }

    // 2️⃣ Add to the current customer's service type list
    if (!serviceTypes.value.includes(value)) {
        serviceTypes.value.push(value);
        selectedOldServiceTypes.value.push(value); // auto-select in multiselect
    }

    // Clear input
    newServiceType.value = '';
};

// Save service types
const saveServiceTypes = async () => {
    if (!editingServiceCustomer.value) return;

    try {
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
        if (customer) customer.service_type = [...serviceTypes.value];

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

const fetchServiceTypes = async () => {
    const { data } = await axios.get('/api/service-types/names');
    allServiceTypeOptions.value = data;
};

// Modal state for Next Follow-Up update
const showFollowUpModal = ref(false);
const editingFollowUpCustomer = ref<any | null>(null);
const newFollowUpDate = ref<Date | null>(null);

// Open modal for specific row
const openFollowUpModal = (customer: any) => {
    editingFollowUpCustomer.value = customer;
    newFollowUpDate.value = customer.next_follow_up_date ? new Date(customer.next_follow_up_date) : null;
    showFollowUpModal.value = true;
};

const updateFollowUpDate = async () => {
    if (!editingFollowUpCustomer.value || !newFollowUpDate.value) {
        toast.add({
            severity: 'warn',
            summary: 'Warning',
            detail: 'Please select a date',
            life: 3000
        });
        return;
    }

    const customer = editingFollowUpCustomer.value;

    const formatDateLocal = (date: Date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    try {
        const payload = {
            name: customer.name,
            created_by: customer.created_by,
            numbers: customer.numbers_raw,

            // ✅ FIXED DATE
            next_follow_up_date: formatDateLocal(newFollowUpDate.value),
        };

        await axios.put(`/api/customers/${customer.id}`, payload);

        toast.add({
            severity: 'success',
            summary: 'Updated',
            detail: 'Next Follow-Up Date updated',
            life: 3000
        });

        const index = customers.value.findIndex(c => c.id === customer.id);
        if (index !== -1) {
            customers.value[index].next_follow_up_date =
                payload.next_follow_up_date;
        }

        showFollowUpModal.value = false;
        editingFollowUpCustomer.value = null;
        newFollowUpDate.value = null;

    } catch (error: any) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Update failed',
            life: 5000
        });
    }
};

// Staff Status Modal
const showStaffStatusModal = ref(false);
const editingStaffCustomer = ref<any | null>(null);
const selectedStaffStatus = ref("");
const selectedDemoPresenter = ref<any | null>(null);

const showDemoNotes = ref(false);
const demoNotesCustomer = ref<any | null>(null);

// Open modal
const openStaffStatusModal = (customer: any) => {
    editingStaffCustomer.value = customer;
    selectedStaffStatus.value = customer.staff_status || "";
    selectedDemoPresenter.value =
        customer.demo_presenter ||
        demoPresenters.value.find((p: any) => p.id === customer.demo_presenter_id) ||
        null;
    showStaffStatusModal.value = true;
};

const openDemoNotes = (customer: any) => {
    demoNotesCustomer.value = customer;
    showDemoNotes.value = true;
};

watch(selectedStaffStatus, (val) => {
    if (val !== "Need To Show Demo") {
        selectedDemoPresenter.value = null;
    }
});

// Save
const saveStaffStatus = async () => {
    if (!editingStaffCustomer.value) return;

    if (selectedStaffStatus.value === "Need To Show Demo" && !selectedDemoPresenter.value) {
        toast.add({
            severity: "warn",
            summary: "Missing Demo Presenter",
            detail: "Please assign a demo presenter for 'Need To Show Demo'.",
            life: 3000,
        });
        return;
    }

    await updateStaffStatus(
        editingStaffCustomer.value.id,
        selectedStaffStatus.value,
        selectedStaffStatus.value === "Need To Show Demo" ? selectedDemoPresenter.value?.id : null
    );

    showStaffStatusModal.value = false;
    editingStaffCustomer.value = null;
    selectedDemoPresenter.value = null;
};

// ====================
// UPDATE STAFF STATUS
// ====================
const staffStatusOptions = [
    "Interested",
    "New",
    "Serious Interested",
    "Call For Demo",
    "Need To Show Demo",
    "Demo Done",
    "Need Direct Meeting",
    "Future",
    "Unwanted",
    "Final Pending Client",
    "Success Client",
    "Cancelled",
];

const updateStaffStatus = async (customerId: number, status: string, demoPresenterId: number | null = null) => {
    try {
        await axios.put(`/api/customers/${customerId}/staff-status`, {
            staff_status: status,
            demo_presenter_id: demoPresenterId,
        });

        toast.add({
            severity: "success",
            summary: "Updated",
            detail: "Staff status updated successfully",
            life: 2000,
        });

        // 🚫 REMOVE FROM UI IF CANCELLED
        if (status === "Cancelled") {
            customers.value = customers.value.filter(c => c.id !== customerId);
            return;
        }

        // Otherwise update status normally
        const customer = customers.value.find(c => c.id === customerId);
        if (customer) customer.staff_status = status;

        if (customer) {
            customer.staff_status = status;
            activeTab.value = status; // 👈 jump to Future tab automatically
            customer.demo_presenter_id = demoPresenterId;
            customer.demo_presenter = demoPresenters.value.find((p: any) => p.id === demoPresenterId) || null;
        }

    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response?.data?.message || "Failed to update staff status",
            life: 3000,
        });
    }
};


const activeTab = ref<string>("All Customer");

const showAssignedUserFilter = computed(() =>
    currentUser.value?.role === 'admin'
);

const showDemoPresenterFilter = computed(() =>
    (currentUser.value?.role === 'admin' || currentUser.value?.role === 'staff') &&
    (activeTab.value === 'Need To Show Demo' || activeTab.value === 'Demo Done' || activeTab.value === 'Cancelled')
);

watch(activeTab, (tab) => {
    if (tab !== 'Need To Show Demo' && tab !== 'Demo Done' && tab !== 'Cancelled') filterDemoPresenter.value = null;
});

const statusTabs = computed(() => {
    const commonTabs = [
        "All Customer",
        "All Assign",
        "Today Customers",
        "Interested",
        "Serious Interested",
        "Call For Demo",
        "Need To Show Demo",
        "Demo Done",
        "Cancelled",
        "Need Direct Meeting",
        "Future",
        "Unwanted",
        "Final Pending Client",
        "Success Client",
    ];

    // ✅ Admin sees "New"
    if (currentUser.value?.role === "admin") {
        return [
            "All Customer",
            "All Assign",
            "New",
            "Today Customers",
            "Interested",
            "Serious Interested",
            "Call For Demo",
            "Need To Show Demo",
            "Demo Done",
            "Cancelled",
            "Need Direct Meeting",
            "Future",
            "Unwanted",
            "Final Pending Client",
            "Success Client",
        ];
    }

    // ❌ Staff does NOT see "New"
    return commonTabs;
});

const isNewStatus = (c: any) => !c.staff_status || c.staff_status === "New";

const allAssignedCustomers = computed(() =>
    customers.value.filter(c => c.assigned_staff_id !== null)
);

const statusCount = (status: string) => {
    let baseList = customers.value;

    // 🔒 apply staff visibility rule to count also
    if (currentUser.value?.role === 'staff') {
        const today = new Date();
        const isToday = (dateStr: string) => {
            const d = new Date(dateStr);
            return d.getFullYear() === today.getFullYear() &&
                d.getMonth() === today.getMonth() &&
                d.getDate() === today.getDate();
        };

        baseList = baseList.filter(c => {
            if (c.assigned_staff_id === currentUser.value.id) return true;

            if (
                status === "Today Customers" &&
                c.created_by === currentUser.value.id &&
                c.created_at &&
                isToday(c.created_at)
            ) return true;

            return false;
        });
    }

    if (status === "All Customer") return baseList.length;

    if (status === "All Assign") {
        return baseList.filter(c => c.assigned_staff_id !== null).length;
    }

    if (status === "Today Customers") {
        const today = new Date();
        return baseList.filter(c => {
            const d = new Date(c.created_at);
            return d.toDateString() === today.toDateString();
        }).length;
    }

    if (status === "New") {
        return baseList.filter(c => !c.staff_status || c.staff_status === "New").length;
    }

    return baseList.filter(c => c.staff_status === status).length;
};

const intOrNull = (value: any) => {
    if (value === null || value === undefined || value === '') return null;
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
};

const filteredCustomers = computed(() => {
    let list = customers.value;

    // Staff can only see customers they are assigned to
    if (currentUser.value?.role === 'staff') {
        const today = new Date();

        const isToday = (dateStr: string) => {
            const d = new Date(dateStr);
            return (
                d.getFullYear() === today.getFullYear() &&
                d.getMonth() === today.getMonth() &&
                d.getDate() === today.getDate()
            );
        };

        list = list.filter(c => {
            // ✅ Assigned → always visible
            if (c.assigned_staff_id === currentUser.value.id) return true;

            // ✅ Not assigned BUT created today → visible ONLY in Today Customers tab
            if (
                activeTab.value === "Today Customers" &&
                c.created_by === currentUser.value.id &&
                c.created_at &&
                isToday(c.created_at)
            ) {
                return true;
            }

            // ❌ Otherwise hidden
            return false;
        });
    }

    // --- Search Query ---
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(c =>
            c.name?.toLowerCase().includes(q) ||
            c.numbers?.toLowerCase().includes(q) ||
            c.assigned_staff?.name?.toLowerCase().includes(q) ||
            c.assigned_users?.some((staff: any) => staff?.name?.toLowerCase().includes(q)) ||
            c.demo_presenter?.name?.toLowerCase().includes(q)
        );
    }

    // --- Created By Filter (for admins) ---
    if (filterCreatedBy.value) {
        list = list.filter(c => c.created_by === filterCreatedBy.value.id);
    }

    // --- Created Date Filter ---
    if (filterCreatedDate.value) {
        const selected = new Date(filterCreatedDate.value);
        list = list.filter(c => {
            if (!c.created_at) return false;
            const created = new Date(c.created_at);
            return (
                created.getFullYear() === selected.getFullYear() &&
                created.getMonth() === selected.getMonth() &&
                created.getDate() === selected.getDate()
            );
        });
    }

    if (filterCreatedMonth.value) {
        const selectedMonth = new Date(filterCreatedMonth.value);
        list = list.filter(c => {
            if (!c.created_at) return false;
            const created = new Date(c.created_at);
            return (
                created.getFullYear() === selectedMonth.getFullYear() &&
                created.getMonth() === selectedMonth.getMonth()
            );
        });
    }

    // --- Status Tabs ---
    const today = new Date();
    const isToday = (dateStr: string) => {
        const d = new Date(dateStr);
        return d.getFullYear() === today.getFullYear() &&
            d.getMonth() === today.getMonth() &&
            d.getDate() === today.getDate();
    };

    if (activeTab.value === "All Assign") {
        list = list.filter(c => c.assigned_staff_id !== null);
    } else if (activeTab.value === "New") {
        list = list.filter(c => !c.staff_status || c.staff_status === "New");
    } else if (activeTab.value === "Today Customers") {
        list = list.filter(c => c.created_at && isToday(c.created_at));
    } else if (activeTab.value !== "All Customer") {
        list = list.filter(c => c.staff_status === activeTab.value);
    }

    // --- Admin: tab-specific filters ---
    if (currentUser.value?.role === 'admin' && filterAssignedUser.value) {
        list = list.filter(c => (intOrNull(c.assigned_staff_id) === intOrNull(filterAssignedUser.value?.id)));
    }

    if (
        (currentUser.value?.role === 'admin' || currentUser.value?.role === 'staff') &&
        (activeTab.value === 'Need To Show Demo' || activeTab.value === 'Demo Done' || activeTab.value === 'Cancelled') &&
        filterDemoPresenter.value
    ) {
        list = list.filter(c => (intOrNull(c.demo_presenter_id) === intOrNull(filterDemoPresenter.value?.id)));
    }

    return list;
});

const showFilteredCount = computed(() => {
    // Show badge only if any filter is applied or search query exists
    return (
        searchQuery.value.trim() ||
        filterCreatedBy.value ||
        filterCreatedDate.value ||
        filterCreatedMonth.value ||
        filterAssignedUser.value ||
        filterDemoPresenter.value
    );
});

const filteredCount = computed(() => filteredCustomers.value.length);

const formattedUsers = computed(() =>
    users.value.map(u => ({
        ...u,
        label: u.mobile ? `${u.name} (${u.mobile})` : u.name, // Name + mobile
        value: u.id
    }))
);

const getDemoPresenterName = (row: any) => {
    if (!row) return "-";

    const presenter =
        row.demo_presenter ||
        demoPresenters.value.find((p: any) => p.id === row.demo_presenter_id);

    if (!presenter?.name) return "-";

    const presenterNumber = presenter.mobile || presenter.phone || presenter.number || null;

    return presenterNumber ? `${presenter.name} (${presenterNumber})` : presenter.name;
};

const searchResults = computed(() => {
    if (!searchQuery.value.trim()) return customers.value;

    const q = searchQuery.value.toLowerCase();

    return customers.value.filter(c =>
        c.name?.toLowerCase().includes(q) ||
        c.numbers?.toLowerCase().includes(q) ||
        c.assigned_staff?.name?.toLowerCase().includes(q) ||
        c.assigned_users?.some((staff: any) => staff?.name?.toLowerCase().includes(q)) ||
        c.demo_presenter?.name?.toLowerCase().includes(q)
    );
});

watch(searchResults, (list) => {
    if (!searchQuery.value.trim()) return;

    const statuses = [...new Set(list.map(c => c.staff_status).filter(Boolean))];

    if (statuses.length === 1) {
        activeTab.value = statuses[0];
    }
});

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

const historyEntries = (oldData: any): Array<[string, any]> => {
    return Object.entries(oldData ?? {}).filter(([, value]) => {
        if (value === null || value === undefined || value === "") return false;
        if (Array.isArray(value) && value.length === 0) return false;
        if (typeof value === "object" && !Array.isArray(value) && Object.keys(value).length === 0) return false;
        return true;
    });
};

// Table columns
const columns = [
    { key: "sn", label: "SN", align: "center" },
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

const tableRows = computed(() =>
    filteredCustomers.value.map((c, index) => ({ sn: index + 1, ...c }))
);

onMounted(async () => {
    await fetchUsers();
    await fetchDemoPresenters();
    await fetchCurrentUser();
    await fetchServiceTypes(); // 👈 add this
    fetchNewCustomers();
});

const getStaffName = (staffId: number | null, fallback = '-') => {
    if (!staffId) return fallback;
    const staff = allUsers.value.find(u => u.id === staffId);
    if (staff?.name) return staff.name;
    const presenter = demoPresenters.value.find((p: any) => p.id === staffId);
    return presenter?.name || fallback;
};

const getStaffMobile = (staffId: number | null, fallback = '-') => {
    if (!staffId) return fallback;
    const staff = allUsers.value.find(u => u.id === staffId);
    if (staff?.mobile) return staff.mobile;
    const presenter = demoPresenters.value.find((p: any) => p.id === staffId);
    return presenter?.mobile || presenter?.phone || presenter?.number || fallback;
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

const getDemoAssignedAt = (customerId: number) => demoAssignedAtMap.value[customerId] ?? null;
</script>

<template>
    <AppLayout>
        <DemoPresenterDashboard v-if="currentUser?.role === 'demo_presenter'" />

        <div v-else class="p-6 bg-gray-50 min-h-screen">
            <Toast />

            <div class="mb-6 border-l-4 border-green-600 pl-5">
                <h1 class="text-3xl font-extrabold text-gray-900 leading-tight">
                    All Contacts Report
                </h1>
                <p class="mt-1 text-gray-600 text-sm">
                    Showing all customers lists.
                </p>
            </div>

            <div class="flex justify-between items-center mb-4">
                <!-- Left (empty for future filters) -->
                <div></div>

                <!-- Search Box -->
                <div class="relative w-96">
                    <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>

                    <input v-model="searchQuery" type="text" placeholder="Search by name or phone number..." class="w-full pl-11 pr-10 py-3 rounded-xl
                   border border-gray-300
                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                   shadow-sm transition" />

                    <!-- Reset Button -->
                    <button v-if="isSearching" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2
                   text-gray-400 hover:text-red-500 transition">
                        <i class="pi pi-times"></i>
                    </button>
                </div>
            </div>

            <!-- STATUS TABS -->
            <div class="mb-4 flex flex-wrap gap-2">
                <button v-for="status in statusTabs" :key="status" @click="activeTab = status"
                    class="px-4 py-2 rounded-full text-sm font-medium transition" :class="activeTab === status
                        ? 'bg-blue-600 text-white shadow'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    {{ status === 'Cancelled' ? 'Demo Cancelled' : status }}
                    <span class="ml-1 text-xs opacity-80">
                        (
                        {{ statusCount(status) }}
                        )
                    </span>
                </button>
            </div>

            <!-- Search Option Inputs -->
            <div class="bg-white rounded-xl shadow-md p-5 mb-6 border border-gray-100">
                <div class="flex flex-wrap gap-3 items-end">

                    <!-- Created By Filter -->
                    <!-- <div class="flex flex-col w-full md:w-70" v-if="currentUser?.role === 'admin'">
                        <label class="text-sm font-semibold text-gray-600 mb-1">
                            <i class="pi pi-user mr-1 text-blue-500"></i>
                            Created By
                        </label>
                        <Multiselect v-model="filterCreatedBy" :options="formattedUsers" label="label" track-by="value"
                            placeholder="Select staff" class="rounded-lg" />
                    </div> -->

                    <!-- Assigned User Filter (Admin: All Assign tab) -->
                    <div class="flex flex-col w-full md:w-60" v-if="showAssignedUserFilter">
                        <label class="text-sm font-semibold text-gray-600 mb-1">
                            <i class="pi pi-users mr-1 text-indigo-500"></i>
                            Assigned User
                        </label>
                        <Multiselect
                            v-model="filterAssignedUser"
                            :options="formattedUsers"
                            label="label"
                            track-by="id"
                            placeholder="Select assigned staff"
                            :searchable="true"
                            :close-on-select="true"
                            :allow-empty="true"
                            class="rounded-lg"
                        />
                    </div>

                    <!-- Demo Presenter Filter (Admin: Need To Show Demo + Demo Done tabs) -->
                    <div class="flex flex-col w-full md:w-60" v-if="showDemoPresenterFilter">
                        <label class="text-sm font-semibold text-gray-600 mb-1">
                            <i class="pi pi-user-edit mr-1 text-purple-600"></i>
                            Demo Presenter
                        </label>
                        <Multiselect
                            v-model="filterDemoPresenter"
                            :options="demoPresenters"
                            label="label"
                            track-by="id"
                            placeholder="Select demo presenter"
                            :searchable="true"
                            :close-on-select="true"
                            :allow-empty="true"
                            class="rounded-lg"
                        />
                    </div>

                    <!-- Created Date Filter (PrimeVue Calendar) -->
                    <div class="flex flex-col w-full md:w-40">
                        <label class="text-sm font-semibold text-gray-600 mb-1">
                            <i class="pi pi-calendar mr-1 text-purple-500"></i>
                            Created Date
                        </label>

                        <Calendar v-model="filterCreatedDate" dateFormat="yy-mm-dd" showIcon showButtonBar
                            class="w-full" placeholder="Pick a date" />
                    </div>

                    <div class="flex flex-col w-full md:w-34">
                        <label class="text-sm font-semibold text-gray-600 mb-1">
                            <i class="pi pi-calendar-clock mr-1 text-indigo-500"></i>
                            Created Month
                        </label>
                        <Calendar
                            v-model="filterCreatedMonth"
                            view="month"
                            dateFormat="mm/yy"
                            showIcon
                            showButtonBar
                            class="w-full"
                            placeholder="Pick month"
                        />
                    </div>

                    <!-- Clear Button -->
                    <div class="flex items-end">
                        <button @click="filterCreatedBy = null; filterCreatedDate = null; filterCreatedMonth = null; filterAssignedUser = null; filterDemoPresenter = null" class="
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
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex justify-center items-center gap-2">
                <!-- Filtered Count Badge -->
                <span v-if="showFilteredCount"
                    class="inline-block bg-blue-600 text-white text-base font-semibold px-5 py-2 rounded-lg shadow mb-1">
                    Total Found - ({{ filteredCount }})
                </span>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-2 overflow-x-auto">
                <DataTable title="All Customers Lists" :columns="columns" :rows="tableRows" :showSearch="true"
                    @openModal="openModal">

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

                                    <!-- Demo Notes (chat) -->
                                    <button v-if="row.staff_status === 'Need To Show Demo' || row.staff_status === 'Demo Done'"
                                        class="relative w-7 h-7 flex items-center justify-center
                                        rounded-md bg-purple-50 text-purple-700
                                        hover:bg-purple-100 hover:text-purple-800
                                        transition"
                                        title="Demo Notes"
                                        @click="openDemoNotes(row)"
                                    >
                                        <i class="pi pi-comments text-xs"></i>
                                        <span
                                            v-if="row.demo_notes_unread > 0"
                                            class="absolute -top-2 -right-2 min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[10px] flex items-center justify-center"
                                        >
                                            {{ row.demo_notes_unread }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Designation -->
                            <span class="text-sm text-gray-500 font-semibold">
                                {{ row.designation || '-' }}
                            </span>

                            <!-- Assigned Staff -->
                            <div v-for="staff in row.assigned_users" :key="staff.id" class="flex items-center gap-2 z">
                                <span class="text-xs text-blue-600 font-semibold">
                                    Assigned: {{ staff.name || 'Not Assigned' }}
                                </span>

                                <!-- Pencil Button -->
                                <button class="text-gray-400 hover:text-blue-600 transition"
                                    title="Change Assigned Staff" @click="openAssignStaffModal(row)">
                                    <i class="pi pi-pencil text-xs"></i>
                                </button>
                            </div>

                            <!-- Staff Status -->
                            <span class="text-xs font-semibold text-gray-500">
                                {{ row.staff_status || '-' }}
                            </span>

                            <span v-if="row.staff_status === 'Need To Show Demo' && (row.demo_presenter || row.demo_presenter_id)"
                                class="text-xs font-semibold text-purple-600">
                                Demo Presenter:
                                {{ getDemoPresenterName(row) }}
                            </span>

                            <span v-if="row.staff_status === 'Need To Show Demo' && getDemoAssignedAt(row.id)"
                                class="text-xs font-semibold text-indigo-600">
                                Demo Assigned:
                                {{ formatDateTime(getDemoAssignedAt(row.id)!) }}
                            </span>

                            <span v-if="row.staff_status === 'Demo Done' && row.demo_done_at"
                                class="text-xs text-emerald-600">
                                Demo Done:
                                {{ formatDateTime(row.demo_done_at) }}
                            </span>
                        </div>
                    </template>

                    <!-- Created By + Created Date -->
                    <template #cell-created_info="{ row }">
                        <div class="flex flex-col items-center text-sm">
                            <span class="font-medium text-gray-800">
                                {{ getStaffName(row.created_by) }} ({{ getStaffMobile(row.created_by) }})
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
                        <div class="flex items-center justify-center gap-2">
                            <span class="text-sm text-gray-600">
                                {{
                                    row.next_follow_up_date
                                        ? new Intl.DateTimeFormat('en-GB', {
                                            day: '2-digit', month: 'short', year: 'numeric'
                                        }).format(new Date(row.next_follow_up_date))
                                        : '-'
                                }}
                            </span>
                            <button @click="openFollowUpModal(row)" class="text-blue-600 hover:text-blue-800">
                                <i class="pi pi-plus"></i>
                            </button>
                        </div>
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

                    <!-- History & Add Note Buttons -->
                    <template #cell-actions="{ row }">
                        <div class="flex gap-2">
                            <button
                                class="flex items-center gap-1 px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                                @click="openHistoryModal(row)">
                                <i class="pi pi-history"></i> History
                            </button>

                            <button
                                class="flex items-center gap-1 px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                                @click="openNoteModal(row)">
                                <i class="pi pi-plus"></i> Note
                            </button>
                        </div>
                    </template>

                </DataTable>
            </div>

            <!-- Assign Staff Modal -->
            <Dialog v-model:visible="showAssignStaffModal" modal header="Change Assigned Staff"
                :style="{ width: '30rem', height: '18rem' }">
                <div class="flex flex-col gap-4 mt-3">
                    <label class="font-medium text-gray-700">
                        Select Staff
                    </label>

                    <Multiselect v-model="selectedStaff" :options="formattedUsers" label="label" track-by="id"
                        placeholder="Select staff" :searchable="true" :close-on-select="true" :allow-empty="false"
                        class="w-full h-auto" />
                </div>

                <div class="flex justify-end gap-2 mt-10">
                    <button class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                        @click="showAssignStaffModal = false">
                        Cancel
                    </button>

                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                        :disabled="!selectedStaff" @click="assignSingle">
                        Save
                    </button>
                </div>
            </Dialog>

            <!-- Update Next Follow-Up Date Modal -->
            <Dialog v-model:visible="showFollowUpModal" modal header="Update Next Follow-Up Date"
                :style="{ width: '30rem' }">
                <div class="flex flex-col gap-4">
                    <label class="font-medium text-gray-700">Select New Date:</label>
                    <Calendar v-model="newFollowUpDate" date-format="yy-mm-dd" :show-icon="true" class="w-full" />
                </div>

                <div class="text-right mt-6 flex gap-2 justify-end">
                    <button
                        class="px-5 py-2 bg-gray-300 text-gray-700 font-medium rounded-md shadow hover:bg-gray-400 transition"
                        @click="showFollowUpModal = false">
                        Cancel
                    </button>

                    <button
                        class="px-5 py-2 bg-blue-600 text-white font-medium rounded-md shadow hover:bg-blue-700 transition"
                        @click="updateFollowUpDate">
                        Save
                    </button>
                </div>
            </Dialog>

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

                    <div v-if="selectedStaffStatus === 'Need To Show Demo'" class="flex flex-col gap-2">
                        <label class="font-medium">
                            Assign Demo Presenter <span class="text-red-600">*</span>
                        </label>
                        <Multiselect
                            v-model="selectedDemoPresenter"
                            :options="demoPresenters"
                            label="label"
                            track-by="id"
                            placeholder="Select demo presenter"
                            :searchable="true"
                            :close-on-select="true"
                            :allow-empty="false"
                            class="w-full"
                        />
                        <p class="text-xs text-gray-500">
                            This is required for <span class="font-medium">Need To Show Demo</span>.
                        </p>
                    </div>
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

            <Dialog v-model:visible="showHistoryModal" modal :header="modalTitle"
                :style="{ width: '60rem', maxHeight: '80vh' }" class="overflow-hidden rounded-xl shadow-2xl">
                <div class="overflow-y-auto px-6 py-5 relative">

                    <!-- Timeline Container -->
                    <div v-if="historyData.length" class="relative">

                        <!-- Vertical line connecting all dots -->
                        <div class="absolute left-1.5 top-2 bottom-0 w-1 bg-gray-200 rounded-full"></div>

                        <template v-for="(item, idx) in historyData" :key="item.id">
                            <div class="relative flex items-start gap-8 group">

                                <!-- Timeline Dot -->
                                <div class="flex flex-col items-center z-10">
                                    <div class="w-4 h-4 rounded-full shadow relative top-2"
                                        :class="item.note === 'Customer created' ? 'bg-green-500' : 'bg-indigo-600'">
                                    </div>
                                    <!-- Connecting line -->
                                    <div v-if="idx !== historyData.length - 1" class="flex-1 w-1 bg-gray-200"></div>
                                </div>

                                <!-- History Card -->
                                <div class="flex-1 bg-white p-5 rounded-xl shadow border-l-4 relative -top-1 mb-5"
                                    :class="item.note === 'Customer created' ? 'border-green-500' : 'border-indigo-600'">

                                    <!-- Created Customer Card -->
                                    <div v-if="item.note === 'Customer created' || item.customer_name">
                                        <div class="flex justify-between items-center mb-3">
                                            <span class="text-green-600 font-bold">Customer Created</span>
                                            <span class="text-green-600 text-sm font-medium">
                                                {{ formatDateTime(item.created_at) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4 text-gray-700 text-sm">
                                            <div><strong>Name:</strong> {{ item.customer_name || '-' }}</div>
                                            <div>
                                                <strong>Service Type:</strong>
                                                {{ item.service_type?.length ? item.service_type.join(', ') : '-' }}
                                            </div>
                                            <div><strong>Email:</strong> {{ item.email || '-' }}</div>
                                            <div><strong>Phone:</strong> {{ item.phone || '-' }}</div>
                                        </div>
                                    </div>

                                    <!-- Staff History Card -->
                                    <div v-else>
                                        <!-- Header: Staff + Date -->
                                        <div class="flex justify-between items-center mb-3">
                                            <div class="flex items-center gap-3">
                                                <i class="pi pi-user text-indigo-600"></i>
                                                <span class="text-indigo-600 font-semibold text-sm">
                                                    {{ getStaffName(item.staff_id) || '-' }}
                                                </span>
                                            </div>
                                            <span class="text-indigo-600 text-sm font-medium">
                                               Change Time: {{ formatDateTime(item.created_at) }}
                                            </span>
                                        </div>

                                        <!-- Note -->
                                        <div v-if="item.note && item.note.trim()" class="mb-3 text-gray-800">
                                            <strong>Note: </strong>
                                            <span class="mt-1"> {{ item.note }}</span>
                                        </div>

                                        <!-- Changed Fields -->
                                        <div v-if="historyEntries(item.old_data).length"
                                            class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                            <strong class="text-indigo-700">Changed Fields:</strong>
                                            <div class="grid grid-cols-2 gap-3 mt-2 text-sm text-gray-700">
                                                <div v-for="([key, value]) in historyEntries(item.old_data)" :key="key"
                                                    class="flex gap-2">
                                                    <span class="font-medium capitalize">{{ key.replace(/_/g, ' ')
                                                    }}:</span>
                                                    <span v-if="typeof value === 'string' && value.includes('<')"
                                                        v-html="formatHistoryValue(key, value)">
                                                    </span>
                                                    <span v-else>{{ formatHistoryValue(key, value) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </template>

                    </div>

                    <!-- No History -->
                    <div v-else class="text-center text-gray-400 py-16 text-lg">
                        No history available
                    </div>

                </div>

                <!-- Footer -->
                <div class="text-right mt-6">
                    <button
                        class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg shadow hover:bg-indigo-700 transition"
                        @click="showHistoryModal = false">
                        Close
                    </button>
                </div>
            </Dialog>

            <!-- Add Note Modal -->
            <Dialog v-model:visible="showExtraNoteModal" header="Customer Notes &       History" :style="{ width: '50rem' }">
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

            <DemoNotesDialog
                :visible="showDemoNotes"
                :customerId="demoNotesCustomer?.id ?? null"
                :customerName="demoNotesCustomer?.name ?? ''"
                @update:visible="(v:boolean) => {
                    showDemoNotes = v;
                    if (!v) {
                        const id = demoNotesCustomer?.id;
                        if (id) {
                            const c = customers.find((x:any) => x.id === id);
                            if (c) c.demo_notes_unread = 0;
                        }
                        demoNotesCustomer = null;
                        fetchNewCustomers();
                    }
                }"
            />

        </div>
    </AppLayout>
</template>

<style scoped>
/* Improve list spacing and readability inside modal */
.multiselect {
    min-height: 42px;
}

:deep(.multiselect-assist) {
    display: none !important;
}

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
