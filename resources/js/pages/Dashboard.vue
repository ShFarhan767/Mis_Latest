<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import DataTable from "@/Components/DataTable.vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import Calendar from "primevue/calendar";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import InputText from 'primevue/inputtext';
import Textarea from "primevue/textarea";
import Editor from "primevue/editor";
import Multiselect from "vue-multiselect";
import DemoPresenterDashboard from "@/components/DemoPresenterDashboard.vue";

const breadcrumbItems = [
    { title: "Dashboard", href: "/" },
];

const props = defineProps({
    userRole: String,
    userId: Number,
});

const toast = useToast();
const selectedStatus = ref<{ [key: string]: string | null }>({});
const allTasks = ref<any[]>([]);
const originalTasks = ref<any[]>([]); // ← backup
const activeTab = ref("");

onMounted(() => {
    activeTab.value = props.userRole === "staff" ? "All" : "All";
});

//Dialogs
const showDetails = ref(false);
const showReissueDialog = ref(false);
const showCancelDialog = ref(false);
const showApprovedDialog = ref(false);
const selectedTask = ref<any>(null);
const reissueComment = ref("");
const cancelComment = ref("");
const approvedComment = ref("");

// Dialogs for Assign
const showAssignDialog = ref(false);
const selectedNewTask = ref(null);
const assignedEmployee = ref(null);
const committedHours = ref<number | null>(null);
const startDate = ref<Date | null>(null); // ✅ make reactive
const endDate = ref<Date | null>(null); // ✅ make reactive

// Countdown
const runningTimers = ref<{ [taskId: number]: number }>({});
let timerInterval: any = null;

// Employees list
const employees = ref([]);
const loadingEmployees = ref(false);
const employeeOptions = computed(() =>
    Array.isArray(employees.value)
        ? employees.value.filter((e: any) => e.role === "employee" && e.status === "Running")
        : []
);

// 🔹 Refs for filters
const shopOptions = ref<any[]>([]);
const selectedEmployee = ref(null);
const selectedShop = ref(null);
const selectedStatusFilter = ref(null);

const statusOptions = [
    { label: "Running", value: "Working" },
    { label: "Pending", value: "Assigned" },
    { label: "New", value: "New" },
    { label: "Reissue", value: "Reissue" },
    { label: "Staff Status", value: "Staff" },
];

// 🔹 Reset both filters
const resetAllFilters = () => {
  searchQuery.value = "";
  selectedEmployee.value = null;
  selectedShop.value = null;
  selectedStatusFilter.value = null;
};

// Edit Task Dialog
const showEditDialog = ref(false);
const editForm = ref({
    title: "",
    details: "",
    shop_name: "",
    shop_id: null,
    start_date: null,
    image: null,
    status: "",
});

// const editSelectedShop = ref(null);
const editImagePreview = ref(null);
const editingId = ref(null);

const editEntry = (task) => {
    const entry = task.task;

    editingId.value = entry.id;
    showEditDialog.value = true;

    // Fill form fields
    editForm.value.title = entry.title;
    editForm.value.details = entry.details;
    editForm.value.start_date = entry.start_date;
    editForm.value.status = entry.status;
    // Image preview
    editImagePreview.value = entry.image_url || (entry.image_path ? `/${entry.image_path}` : null);
};

const handleEditImage = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    editForm.value.image = file;

    const reader = new FileReader();
    reader.onload = (ev) => {
        editImagePreview.value = ev.target.result;
    };
    reader.readAsDataURL(file);
};

const submitEdit = async () => {
    const fd = new FormData();

    fd.append("title", editForm.value.title);
    fd.append("details", editForm.value.details);
    fd.append("start_date", formatDate(editForm.value.start_date) || "");
    fd.append("status", editForm.value.status || "New");

    if (editForm.value.image) fd.append("image", editForm.value.image);

    fd.append("_method", "PUT");

    await axios.post(`/api/tasks/${editingId.value}`, fd);

    showEditDialog.value = false;
    fetchNewTasks();
};

// 🔹 Fetch unique shop list (once tasks are loaded)
const extractUniqueShops = (tasks: any[]) => {
    const seen = new Set<string>();
    return tasks
        .filter(t => {
            const name = t.task?.shop_name ?? t.shop_name ?? "N/A"; // fallback to top-level field
            if (!name || seen.has(name)) return false;
            seen.add(name);
            return true;
        })
        .map(t => ({
            id: t.task?.id ?? t.id,
            shop_name: t.task?.shop_name ?? t.shop_name ?? "N/A",
            owner_name: t.task?.client_name ?? t.client_name ?? null
        }));
};

// After you fetch tasks, call:
onMounted(async () => {
    await fetchTasks();

    if (props.userRole === "staff") {
        await fetchStaffTasks();
    }

    shopOptions.value = extractUniqueShops(allTasks.value); // populate dropdown
});

// Complete Task Dialog
const showCompleteDialog = ref(false);
const completeNote = ref("");

// Fetch all tasks
const fetchTasks = async () => {
    console.log("fetchTasks triggered");  // 👈 see if this runs for staff
    try {
        const res = await axios.get("/api/task-assignments");

        allTasks.value = res.data
            .filter((t: any) =>
                ["Pending", "Working", "Complete", "Assigned", "Cancelled", "New", "Reissue", "Approved", "Staff"].includes(t.status)
            )
            .map((t: any) => {
                const sessions = t.work_sessions || t.sessions || [];
                const activeSession = sessions.find((s: any) => s.status === "active" || !s.stop_time);
                const workedMinutes = sessions.reduce(
                    (sum: number, s: any) => sum + (s.duration_minutes ?? 0),
                    0
                );

                // Initialize running timer for active session
                if (activeSession && activeSession.start_time) {
                    const start = new Date(activeSession.start_time + "Z").getTime();
                    const now = Date.now();
                    runningTimers.value[t.id] = Math.floor((now - start) / 1000); // seconds elapsed
                }

                return {
                    ...t,
                    sessions,
                    isWorkingSession: !!activeSession,
                    worked_minutes: workedMinutes,
                };
            });
        originalTasks.value = [...allTasks.value];  // ← store full list

        startRunningTimer(); // Start interval after tasks loaded
    } catch (err) {
        console.error("Error fetching tasks:", err);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch tasks",
            life: 3000,
        });
    }
};

const searchKey = ref("");
const searchResults = ref([]);
const showDashboard = ref(true); // 👈 controls dashboard visibility

const handleClientSearch = async (text: string) => {
    searchKey.value = text.trim();

    if (!searchKey.value) {
        showDashboard.value = true;
        searchResults.value = [];
        return;
    }

    showDashboard.value = false;
    try {
        const res = await axios.get(`/api/clients/search?query=${encodeURIComponent(searchKey.value)}`);

        // Ensure results is always an array
        let results = Array.isArray(res.data) ? res.data : res.data.data || [];

        // 🔥 STAFF: only tasks created by him
        if (props.userRole === "staff") {
            results = results.map(client => ({
                ...client,
                tasks: client.tasks.filter(t => t.created_by === props.userId)
            }));
        }

        // 🔥 EMPLOYEE: only tasks assigned to him
        if (props.userRole === "employee") {
            results = results.map(client => ({
                ...client,
                tasks: client.tasks.filter(t => t.employee?.id === props.userId)
            }));
        }

        searchResults.value = results;

    } catch (error) {
        console.error(error);
        searchResults.value = [];
    }
};

const searchedTasks = computed(() => {
    if (!searchKey.value.trim()) return [];

    const key = searchKey.value.toLowerCase().trim();

    let sourceTasks = [...allTasks.value];

    // STAFF
    if (props.userRole === "staff") {
        sourceTasks = sourceTasks.filter(t => t.created_by === props.userId);
    }

    // EMPLOYEE
    if (props.userRole === "employee") {
        sourceTasks = sourceTasks.filter(t => t.employee?.id === props.userId);
    }

    // ADMIN = sees everything

    return sourceTasks.filter((t: any) => {
        const task = t.task || t;

        return (
            task.title?.toLowerCase().includes(key) ||
            task.details?.toLowerCase().includes(key) ||
            task.shop_name?.toLowerCase().includes(key) ||
            task.client_name?.toLowerCase().includes(key) ||
            task.phone?.toLowerCase().includes(key)
        );
    });
});

const startRunningTimer = () => {
    if (timerInterval) return;
    timerInterval = setInterval(() => {
        Object.keys(runningTimers.value).forEach((taskId) => {
            runningTimers.value[taskId] += 1; // increment seconds
        });
    }, 1000);
};

// Stop global timer when component unmounts
onUnmounted(() => clearInterval(timerInterval));

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short', // Mar, Apr, etc.
        year: 'numeric'
    });
}

// Format seconds → "1h 42m 5s" or "2m 5s"
const formatTime = (seconds: number) => {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    if (h > 0) return `${h}h ${m}m ${s}s`;
    if (m > 0) return `${m}m ${s}s`;
    return `${s}s`;
};

// Format total worked minutes → "2 min" or "1 h 42 m"
const formatDuration = (minutes: number) => {
    if (!minutes || minutes <= 0) return '0 min';

    const totalSeconds = Math.round(minutes * 60);
    const hours = Math.floor(totalSeconds / 3600);
    const minutesPart = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (hours > 0) return `${hours}h ${minutesPart}m ${seconds}s`;
    if (minutesPart > 0) return `${minutesPart}m ${seconds}s`;
    return `${seconds}s`;
};

const fetchNewTasks = async () => {
    try {
        const res = await axios.get("/api/tasks");

        // Filter tasks with status "New" or "Future"
        const newOrFuture = res.data.filter((t: any) => ["New", "Future", "Staff"].includes(t.status));

        // Remove old New and Future tasks from allTasks
        allTasks.value = allTasks.value.filter((t: any) => !["New", "Future", "Staff"].includes(t.status));

        // Add fresh New and Future tasks
        allTasks.value.push(
            ...newOrFuture.map((t: any) => ({
                id: t.id,
                staff_decision: t.staff_decision,   // ✅ ADD THIS
                decline_note: t.decline_note,
                approve_note: t.approve_note,
                declined_trash_note: t.declined_trash_note,
                created_at: t.created_at,
                created_by: t.created_by,
                task: {
                    id: t.id,
                    title: t.title,
                    details: t.details,
                    image_url: t.image_url,
                    shop_name: t.shop_name ?? "N/A",
                    start_date: t.start_date,
                    status: t.status,
                },
                start_time: t.start_time ?? null,
                status: t.status,
                committed_hours: t.committed_hours ?? null,
                employee: t.employee ?? null,
            }))
        );
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch tasks",
            life: 3000,
        });
    }
};

const staffMainTasks = ref([]);

const fetchStaffTasks = async () => {
    try {
        const res = await axios.get("/api/tasks");

        // Include Future here!
        const allowed = [
            "Staff", "Future", "Cancelled", "Complete", "Approved", "Reissue",
            "Assigned", "Pending", "Working"
        ];

        const staffTasks = res.data.filter((t) => allowed.includes(t.status));

        const formatted = staffTasks.map((t) => {
            // Check all assignments for this task
            const assignments = t.task_assignments || [];

            // Flatten all work sessions from all assignments
            const allWorkSessions = assignments.flatMap(a => a.work_sessions || []);

            // Check if any work session is active
            const activeSession = allWorkSessions.find(ws => ws.status === "active") || null;

            return {
                id: t.id,
                status: t.status,
                staff_decision: t.staff_decision,   // ✅ ADD THIS
                decline_note: t.decline_note,
                approve_note: t.approve_note,
                declined_trash_note: t.declined_trash_note,
                created_at: t.created_at,
                created_by: t.created_by,
                task: {
                    id: t.id,
                    title: t.title,
                    details: t.details,
                    shop_name: t.shop_name ?? t.task?.shop_name ?? "N/A",
                    image_url: t.image_url,
                    start_date: t.start_date,
                    status: t.status,
                    reissue_comment: t.reissue_comment,
                    complete_note: t.complete_note,
                    cancelled_note: t.cancelled_note,
                    approved_note: t.approved_note,

                    // Assignments + Work Sessions
                    task_assignments: assignments,
                    work_sessions: allWorkSessions,
                    active_session: activeSession,

                    // Optionally, use the first assignment for start/end date
                    assignment_start_date: assignments[0]?.start_date ?? null,
                    assignment_end_date: assignments[0]?.end_date ?? null,
                },

                committed_hours: assignments[0]?.committed_hours || 0,
                worked_minutes: assignments[0]?.worked_minutes || 0,
            };
        });

        staffMainTasks.value = formatted;

        // ❌ FIX DUPLICATES (REMOVE OLD STAFF TASKS)
        allTasks.value = allTasks.value.filter(t => !allowed.includes(t.status));

        // Add fresh staff tasks
        allTasks.value.push(...formatted);

    } catch (error) {
        console.error("Failed to fetch staff tasks", error);
    }
};

onMounted(() => {
    if (props.userRole === "staff") {
        fetchStaffTasks();
    } else {
        fetchNewTasks();
    }
});

const handleTabClick = (key) => {
    activeTab.value = key;

    if (props.userRole === "staff") {
        fetchStaffTasks();
        // Staff user → load staff tasks
    } else {
        // Normal user → load new tasks when clicking "New"
        if (key === 'New' || key === "Future" || key === "Staff") {
            fetchNewTasks();
        }
    }
};

// Computed filters
const runningTasks = computed(() => allTasks.value.filter((t) => t.status === "Working"));
const pendingTasks = computed(() =>
    allTasks.value.filter((t) => t.status === "Pending" || t.status === "Assigned")
);
const cancelledTasks = computed(() => allTasks.value.filter((t) => t.status === "Cancelled"));
const completeTasks = computed(() =>
    allTasks.value.filter((t) => t.status === "Complete" || t.status === "Approved")
);
const newTasks = computed(() => allTasks.value.filter((t) => t.status === "New"));
const reissueTasks = computed(() => allTasks.value.filter(t => t.status === "Reissue"));

const futureTasks = computed(() =>
    allTasks.value.filter(t => t.status === "Future")
);

const allStaffTasks = computed(() =>
    allTasks.value.filter((t) =>
        ["Assigned", "Pending", "Working", "Staff"].includes(t.status)
    )
);

const staffDeclinedTasks = computed(() =>
    allTasks.value.filter(t =>
        t.staff_decision === "Declined" || t.staff_decision === "Declined Trash"
    )
);

const StaffTasks = computed(() =>
    allTasks.value.filter(t =>
        t.status === "Staff" && // Only Staff status tasks
        (t.staff_decision === "Pending" || t.staff_decision === "Approved")
    )
);

const todayCompleteTasks = computed(() => {
    const today = new Date().toISOString().split("T")[0]; // YYYY-MM-DD
    return allTasks.value.filter((t) =>
        (t.status === "Complete" || t.status === "Approved") &&
        t.updated_at?.split("T")[0] === today
    );
});


// Change status function
const changeStatus = async (
    task: any,
    newStatus: string,
    reissueComment?: string,
    completeNote?: string,
    cancelComment?: string,
    approvedComment?: string
) => {
    try {
        const taskId = task.task_id || task.id || task.task?.id;

        if (!taskId) {
            console.error("Task ID missing", task);
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Task ID not found",
                life: 3000,
            });
            return;
        }

        const payload: any = {
            status: newStatus   // ✅ ALWAYS send status
        };

        // Only send complete_note if Complete
        if (newStatus === "Complete" && completeNote) {
            payload.complete_note = completeNote;
            payload.completed_at = new Date().toISOString().slice(0, 19).replace("T", " ");
            payload.status = "Complete"; // only change status on complete
        }

        if (newStatus === "Assigned") {
            payload.assigned_at = new Date().toISOString().slice(0, 19).replace("T", " ");
            payload.status = "Assigned";
        }

        // Only send reissue_comment if Reissue
        if (newStatus === "Reissue" && reissueComment) {
            payload.reissue_comment = reissueComment;
            payload.status = "Reissue" // keep existing status

            // include assigned_at explicitly
            payload.assigned_at = new Date().toISOString().slice(0, 19).replace("T", " ");
        }

        // Only send cancelled_note if Reissue
        if (newStatus === "Cancelled" && cancelComment) {
            payload.cancelled_note = cancelComment;
            payload.status = "Cancelled" // keep existing status
        }

        // Only send approved_note if Approved
        if (newStatus === "Approved" && approvedComment) {
            payload.approved_note = approvedComment;
            payload.status = "Approved"; // also update task status
        }

        // Only add start_time if starting work
        if (newStatus === "Working") {
            payload.start_time = new Date().toISOString().slice(0, 19).replace("T", " ");
            payload.status = "Working";
        }

        await axios.put(`/api/task-assignments/task/${taskId}`, payload);

        toast.add({
            severity: "success",
            summary: "Task Updated",
            detail: newStatus === "Reissue" ? "Reissue note submitted" : `Task changed to ${newStatus}`,
            life: 3000,
        });

        fetchTasks(); // refresh
    } catch (error: any) {
        console.error("Status change failed:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response?.data?.message || "Failed to update task",
            life: 3000,
        });
    }
};

// 🔹 Combined filter
const filteredTasks = (tasks, status?) => {
    let filtered = [...tasks];

    // 🔹 Filter by status if provided
    if (status) {
        const statusMap = {
            'Complete': ['Complete'],
            'Staff': ['Staff', 'Assigned', 'Pending', 'Working'],
            'Running': ['Working'],
        };
        const allowedStatuses = statusMap[status] || [status];
        filtered = filtered.filter(task => allowedStatuses.includes(task.status));
    }

    // 🔹 Employee filter
    if (selectedEmployee.value) {
        filtered = filtered.filter((t) => {
            const empId = t.employee?.id ?? t.task?.employee?.id;
            return empId === selectedEmployee.value.id;
        });
    }

    // 🔹 Shop filter
    if (selectedShop.value) {
        filtered = filtered.filter((t) => {
            const shopName = t.task?.shop_name ?? t.shop_name ?? '';
            return shopName === selectedShop.value.shop_name;
        });
    }

    return filtered;
};

const adminAllFilteredTasks = computed(() => {
    let tasks = [...allTasks.value];

    if (activeTab.value === "All" && props.userRole === "admin") {
        tasks = tasks.filter(t => {
            // Include normal tasks
            const normalStatuses = ["Working", "Pending", "Assigned", "New", "Reissue"];

            if (normalStatuses.includes(t.status)) return true;

            // Include Staff tasks only if Pending or Approved
            if (t.status === "Staff" && (t.staff_decision === "Pending" || t.staff_decision === "Approved")) return true;

            // Exclude everything else
            return false;
        });
    }

    // 🔹 TEXT SEARCH (searchKey)
    if (searchKey.value.trim()) {
        const key = searchKey.value.toLowerCase().trim();
        tasks = tasks.filter((t: any) => {
            const task = t.task || t;
            return (
                task.title?.toLowerCase().includes(key) ||
                task.details?.toLowerCase().includes(key) ||
                task.shop_name?.toLowerCase().includes(key) ||
                task.client_name?.toLowerCase().includes(key) ||
                task.phone?.toLowerCase().includes(key)
            );
        });
    }

    // 🔹 EMPLOYEE FILTER
    if (selectedEmployee.value) {
        tasks = tasks.filter(t => {
            const empId = t.employee?.id ?? t.task?.employee?.id;
            return empId === selectedEmployee.value.id;
        });
    }

    // 🔹 SHOP FILTER
    if (selectedShop.value) {
        tasks = tasks.filter(t => {
            const shopName = t.task?.shop_name ?? t.shop_name ?? '';
            return shopName === selectedShop.value.shop_name;
        });
    }

    // 🔹 STATUS FILTER
    if (selectedStatusFilter.value) {
        tasks = tasks.filter(t => t.status === selectedStatusFilter.value.value);
    }

    return tasks;
});

// 🔹 Compute shops based on current tab tasks
const shopOptionsByTab = computed(() => {
    // Get tasks for active tab
    let tasksForTab = [];

    switch (activeTab.value) {
        case 'Running':
            tasksForTab = runningTasks.value;
            break;
        case 'Pending':
            tasksForTab = pendingTasks.value;
            break;
        case 'Cancelled':
            tasksForTab = cancelledTasks.value;
            break;
        case 'Complete':
            tasksForTab = completeTasks.value;
            break;
        case 'TodayComplete':
            tasksForTab = todayCompleteTasks.value;
            break;
        case 'New':
            tasksForTab = newTasks.value;
            break;
        case 'Reissue':
            tasksForTab = reissueTasks.value;
            break;
        case 'Future':
            tasksForTab = futureTasks.value;
            break;
        case 'Staff':
            tasksForTab = StaffTasks.value;
            break;
        case 'Declined Trash':
            tasksForTab = staffDeclinedTasks.value;
            break;
        default:
            tasksForTab = allTasks.value;
            break;
    }

    // 🔹 Extract unique shops
    const seen = new Set();
    return tasksForTab
        .map(t => {
            const shopName = t.task?.shop_name ?? t.shop_name ?? null;
            const id = t.task?.id ?? t.id;
            return shopName ? { id, shop_name: shopName } : null;
        })
        .filter(s => s && !seen.has(s.shop_name) && seen.add(s.shop_name));
});


const nestedTab = ref('Complete'); // default nested tab

const filteredTasksByStatus = (status: string) => {
    return filteredTasks(completeTasks.value).filter(task => task.status === status);
};

// Dialog actions
const openDetails = (task: any) => {
    selectedTask.value = task;
    showDetails.value = true;
};

const openReissue = (task: any) => {
    selectedTask.value = task;
    reissueComment.value = "";
    showReissueDialog.value = true;
};

const openCancel = (task: any) => {
    selectedTask.value = task;
    cancelComment.value = "";
    showCancelDialog.value = true;
};

const openApproved = (task: any) => {
    selectedTask.value = task;
    approvedComment.value = "";
    showApprovedDialog.value = true;
};

const submitReissue = async () => {
    if (!reissueComment.value.trim()) {
        toast.add({
            severity: "warn",
            summary: "Missing comment",
            detail: "Please add a reissue comment",
            life: 2500,
        });
        return;
    }
    await changeStatus(selectedTask.value, "Reissue", reissueComment.value);
    showReissueDialog.value = false;
    fetchTasks();
};

// Cancelled Task
const submitCancelled = async () => {
    if (!cancelComment.value.trim()) {
        toast.add({
            severity: "warn",
            summary: "Missing comment",
            detail: "Please add a cancel comment",
            life: 2500,
        });
        return;
    }

    await changeStatus(selectedTask.value, "Cancelled", undefined, undefined, cancelComment.value);
    showCancelDialog.value = false;
    fetchTasks();
};

// Approved Task
const submitApproved = async () => {
    if (!approvedComment.value.trim()) {
        toast.add({
            severity: "warn",
            summary: "Missing comment",
            detail: "Please add an approved comment",
            life: 2500,
        });
        return;
    }

    // Correctly pass approvedComment as 6th argument
    await changeStatus(selectedTask.value, "Approved", undefined, undefined, undefined, approvedComment.value);

    showApprovedDialog.value = false;
    fetchTasks();
};

const users = ref<any[]>([]);

// 🔹 Fetch all employees from API
const fetchEmployees = async () => {
    loadingEmployees.value = true;
    try {
        const res = await axios.get("/api/users");
        employees.value = Array.isArray(res.data) ? res.data : res.data?.data || [];
        users.value = employees.value.filter((u: any) => u.role === "staff");
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to load employees",
            life: 3000,
        });
        employees.value = [];
    } finally {
        loadingEmployees.value = false;
    }
};

// 🔹 Open Assign Dialog
const openAssign = (task: any) => {
    selectedNewTask.value = task;
    assignedEmployee.value = null;
    committedHours.value = null;
    startDate.value = null; // default to today
    endDate.value = null;
    showAssignDialog.value = true;

    // Fetch employees when opening dialog
    if (employees.value.length === 0) fetchEmployees();
};

// const formatDate = (date: string | Date | null) => {
//     if (!date) return null;

//     // Convert string to Date if necessary
//     const d = date instanceof Date ? date : new Date(date);

//     if (isNaN(d.getTime())) return null; // invalid date check

//     const year = d.getFullYear();
//     const month = (d.getMonth() + 1).toString().padStart(2, '0');
//     const day = d.getDate().toString().padStart(2, '0');

//     return `${year}-${month}-${day}`;
// };

const formatDateBD = (date) => {
    if (!date) return null;

    const d = new Date(date);

    return d.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

const toMysqlDate = (v: string | Date) => {
  const d = typeof v === 'string' ? new Date(v) : v
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}


const submitAssign = async () => {
    if (!assignedEmployee.value || !committedHours.value || !startDate.value || !endDate.value) {
        toast.add({
            severity: 'warn',
            summary: 'Missing Fields',
            detail: 'Please fill in all fields before assigning the task',
            life: 2500,
        });
        return;
    }

    try {
        const res = await axios.post('/api/task-assignments', {
            task_id: selectedNewTask.value.id,
            employee_id: assignedEmployee.value.id,
            committed_hours: committedHours.value,
            start_date: toMysqlDate(startDate.value),
            end_date: toMysqlDate(endDate.value),
            status: 'Assigned',
        });

        toast.add({
            severity: 'success',
            summary: 'Task Assigned',
            detail: 'Task successfully assigned to the employee',
            life: 3000,
        });

        // REMOVE FROM STAFF LIST
        staffMainTasks.value = staffMainTasks.value.filter(
            (t: any) => t.id !== selectedNewTask.value.id
        );

        // CLOSE DIALOG & RESET
        showAssignDialog.value = false;
        selectedNewTask.value = null;
        assignedEmployee.value = null;
        committedHours.value = null;
        startDate.value = null;
        endDate.value = null;

        // REFRESH OTHER TASKS
        fetchTasks();
        fetchNewTasks();

    } catch (err: any) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: err.response?.data?.message || 'Failed to assign task',
            life: 3000,
        });
    }
};


// If you want employees to be fetched immediately:
onMounted(fetchEmployees);

const openCompleteDialog = (task: any) => {
    selectedTask.value = task;
    completeNote.value = ""; // reset previous note
    showCompleteDialog.value = true;
};

const submitCompleteTask = async () => {
    if (!completeNote.value.trim()) {
        toast.add({
            severity: "warn",
            summary: "Missing Note",
            detail: "Please enter a work complete note",
            life: 2500,
        });
        return;
    }

    // ✅ Pass completeNote explicitly
    await changeStatus(selectedTask.value, "Complete", undefined, completeNote.value);

    showCompleteDialog.value = false;
    fetchTasks();
};

const startWork = async (task: any) => {
    try {
        const res = await axios.post(`/api/task-assignments/${task.task.id}/start-work`);

        task.status = "Working";
        task.sessions = res.data.assignment.work_sessions || res.data.assignment.sessions || [];
        task.isWorkingSession = true;
        task.worked_minutes = task.sessions.reduce((sum: number, s: any) => sum + (s.duration_minutes ?? 0), 0);

        // ✅ Start live timer
        const activeSession = task.sessions.find((s: any) => s.status === "active");
        if (activeSession?.start_time) {
            const start = new Date(activeSession.start_time + "Z").getTime();
            runningTimers.value[task.id] = Math.floor((Date.now() - start) / 1000);
            startRunningTimer();
        }

        toast.add({ severity: "success", summary: "Work Started", detail: "Timer started.", life: 5000 });
    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: err.response?.data?.error || err.message, life: 5000 });
    }
};

const stopWork = async (task: any) => {
    try {
        const res = await axios.post(`/api/task-assignments/${task.task.id}/stop-work`);

        task.sessions = task.sessions.map((s: any) =>
            s.id === res.data.session.id ? res.data.session : s
        );
        task.isWorkingSession = false;
        task.worked_minutes = task.sessions.reduce((sum: number, s: any) => sum + (s.duration_minutes ?? 0), 0);

        // ✅ Stop timer
        delete runningTimers.value[task.id];

        toast.add({ severity: "success", summary: "Work Stopped", detail: "Work stopped successfully", life: 5000 });
    } catch (err) {
        toast.add({ severity: "error", summary: "Error", detail: err.response?.data?.error || err.message, life: 5000 });
    }
};

const workHistory = ref<any[]>([]);
const showWorkHistoryDialog = ref(false);
const workHistoryMessage = ref<string>("");

const openWorkHistory = async (task: any) => {
    workHistoryMessage.value = "";

    const existingSessions = Array.isArray(task.sessions)
        ? task.sessions
        : Array.isArray(task.work_sessions)
            ? task.work_sessions
            : Array.isArray(task.task?.work_sessions)
                ? task.task.work_sessions
                : Array.isArray(task.task?.sessions)
                    ? task.task.sessions
                    : [];

    if (existingSessions.length > 0) {
        workHistory.value = existingSessions;
        showWorkHistoryDialog.value = true;
        return;
    }

    // NOTE: API expects TaskAssignment id, not Task id
    const assignmentId =
        task.task_assignment_id ??
        task.assignment_id ??
        task.task_assignment?.id ??
        task.task?.task_assignments?.[0]?.id ??
        task.task_assignments?.[0]?.id ??
        task.id ??
        null;

    if (!assignmentId) {
        workHistory.value = [];
        workHistoryMessage.value = "No working history.";
        showWorkHistoryDialog.value = true;
        return;
    }

    try {
        const res = await axios.get(`/api/task-assignments/${assignmentId}/work-history`);
        const data = (res as any).data?.data ?? (res as any).data;
        workHistory.value = Array.isArray(data) ? data : [];
        if (workHistory.value.length === 0) workHistoryMessage.value = "No working history.";
        showWorkHistoryDialog.value = true;
    } catch (err: any) {
        const status = err?.response?.status;

        // If there is no TaskAssignment or history, show empty dialog instead of an error toast
        if (status === 404) {
            workHistory.value = [];
            workHistoryMessage.value = "No working history.";
            showWorkHistoryDialog.value = true;
            return;
        }

        toast.add({
            severity: "error",
            summary: "Error",
            detail: err.response?.data?.error || err.message,
            life: 3000,
        });
    }
};

// ✅ Computed property to format dates and durations
const formattedWorkHistory = computed(() =>
    workHistory.value.map((s: any) => {
        const start = s.start_time ? new Date(s.start_time + "Z") : null;
        const stop = s.stop_time ? new Date(s.stop_time + "Z") : null;

        const formatDate = (date: Date | null) => {
            if (!date || isNaN(date.getTime())) return "Ongoing"; // safeguard
            return date.toLocaleString("en-GB", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
                hour12: true,
                timeZone: "Asia/Dhaka",
            });
        };

        let formattedDuration = "N/A";
        if (s.duration_minutes != null) {
            const total = Math.round(s.duration_minutes);
            const hours = Math.floor(total / 60);
            const minutes = total % 60;
            formattedDuration = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
        }

        return {
            ...s,
            formatted_start: formatDate(start),
            formatted_stop: formatDate(stop),
            formatted_duration: formattedDuration,
        };
    })
);

// 🔹 Month filter
const selectedMonth = ref<Date | null>(null);

// Computed for Approved Tasks filtered by month
const filteredApprovedTasks = computed(() => {
    const tasks = filteredTasksByStatus("Approved");

    if (!selectedMonth.value) return tasks;

    const month = selectedMonth.value.getMonth();
    const year = selectedMonth.value.getFullYear();

    return tasks.filter((task) => {
        const dateStr =
            task.updated_at ||
            task.start_date ||        // admin main date
            task.end_date ||
            task.task?.start_date ||
            task.task?.end_date ||
            task.task?.assignment_start_date || // staff
            task.task?.assignment_end_date;    // staff

        if (!dateStr) return false;

        const date = new Date(dateStr); // do NOT append ' UTC'
        if (isNaN(date.getTime())) return false;

        return date.getMonth() === month && date.getFullYear() === year;
    });
});

const tabClasses: Record<string, string> = {
    blue: "bg-blue-100 border-b-4 border-blue-400 text-blue-700",
    yellow: "bg-yellow-100 border-b-4 border-yellow-400 text-yellow-700",
    red: "bg-red-100 border-b-4 border-red-400 text-red-700",
    green: "bg-green-100 border-b-4 border-green-400 text-green-700",
    teal: "bg-teal-100 border-b-4 border-teal-400 text-teal-700",
    purple: "bg-purple-100 border-b-4 border-purple-400 text-purple-700",
    pink: "bg-pink-100 border-b-4 border-pink-400 text-pink-700",
};

// Update tabs array
const tabs = computed(() => {
    if (props.userRole === 'staff') {
        // Staff only sees All, Cancelled, Complete
        return [
            { key: "All", label: "All Tasks", color: "blue" },
            { key: "Declined", label: "Declined Tasks", color: "red" }, // ✅ NEW
            { key: "DeclinedTrash", label: "Declined Trash", color: "gray" },
            { key: "Reissue", label: "Reissue Tasks", color: "pink" },
            { key: "Cancelled", label: "Cancelled Tasks", color: "red" },
            { key: "Complete", label: "Complete Tasks", color: "green" },
            { key: "Future", label: "Future Tasks", color: "indigo" },
        ];
    }

    // Admin & Employee Tabs
    const baseTabs = [
        { key: "Running", label: "Running Tasks", color: "blue" },
        { key: "Pending", label: "Pending Tasks", color: "yellow" },
        { key: "Cancelled", label: "Cancelled Tasks", color: "red" },
        { key: "Complete", label: "Complete Tasks", color: "green" },
        { key: "TodayComplete", label: "Today Complete", color: "teal" },
    ];

    if (props.userRole === 'admin') {
        baseTabs.unshift({ key: "All", label: "All Tasks", color: "gray" }); // ← new tab
        baseTabs.splice(4, 0, { key: "New", label: "New Tasks", color: "purple" });
        baseTabs.splice(5, 0, { key: "Declined", label: "Declined Tasks", color: "red" });
        baseTabs.splice(6, 0, { key: "DeclinedTrash", label: "Declined Trash", color: "gray" });
    }

    baseTabs.push({ key: "Reissue", label: "Reissue Tasks", color: "pink" });

    if (props.userRole === 'admin') {
        baseTabs.push({ key: "Future", label: "Future Tasks", color: "indigo" });
    }

    if (props.userRole === 'admin' || props.userRole === 'staff') {
        baseTabs.push({ key: "Staff", label: "Staff Tasks", color: "orange" });
    }

    return baseTabs;
});

const parsedCompleteNotes = computed(() => {
    const completeNoteData = selectedTask.value?.task?.complete_note;
    if (!completeNoteData) return [];

    try {
        const notes = JSON.parse(completeNoteData);

        // Optional: sort by submitted_at if needed
        return notes.sort((a, b) => new Date(a.submitted_at) - new Date(b.submitted_at));
    } catch (error) {
        console.error("Failed to parse complete_note:", error);
        return [];
    }
});

const parsedReissueComments = computed(() => {
    const reissueData = selectedTask.value?.task?.reissue_comment;
    if (!reissueData) return [];

    try {
        const comments = JSON.parse(reissueData);

        // Optional: sort by submitted_at if needed
        return comments.sort((a, b) => new Date(a.submitted_at) - new Date(b.submitted_at));
    } catch (error) {
        console.error("Failed to parse reissue_comment:", error);
        return [];
    }
});

const formatBDDateTime = (dateStr: string | null) => {
    if (!dateStr) return '-';

    const date = new Date(dateStr); // JS automatically parses UTC 'Z' if present

    // Convert to BD timezone using toLocaleString
    const options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZone: 'Asia/Dhaka', // BD timezone
    };

    return date.toLocaleString('en-GB', options);
};

const tasksForActiveTab = computed(() => {
    // 🔹 Start with search results if searchKey is set
    const baseTasks = searchKey.value.trim() ? searchedTasks.value : allTasks.value;

    if (props.userRole === 'staff') {
        switch (activeTab.value) {
            case 'All':
                return baseTasks.filter(
                    t =>
                        t.status === "Staff" ||
                        t.task?.status === "Staff" ||
                        t.employee?.id === props.userId
                );
            case 'Declined': // ✅ NEW
                return baseTasks.filter(
                    t =>
                        t.employee?.id === props.userId &&
                        t.staff_decision === 'Declined'
                );
            case 'DeclinedTrash':
                return baseTasks.filter(
                    t =>
                        t.employee?.id === props.userId &&
                        t.staff_decision === 'Declined Trash'
                );
            case 'Cancelled':
                return baseTasks.filter(
                    t => t.employee?.id === props.userId && t.status === 'Cancelled'
                );
            case 'Complete':
                return baseTasks.filter(
                    t => t.employee?.id === props.userId && (t.status === 'Complete' || t.status === 'Approved')
                );
            case 'Reissue':
                return baseTasks.filter(
                    t => t.employee?.id === props.userId && t.status === 'Reissue'
                );
            default:
                return baseTasks.filter(t =>
                    t.status === "Staff" &&
                    !["Declined", "Declined Trash"].includes(t.staff_decision)
                );
        }
    }

    // Admin & Employee
    switch (activeTab.value) {
       case 'All':
            if (props.userRole === 'admin') {
                // ❌ Exclude Complete & Approved from All tab
                return adminAllFilteredTasks.value.filter(
                    t => !['Complete', 'Approved'].includes(t.status)
                );
            }
            return baseTasks;
        case 'Running':
            return baseTasks.filter(t => t.status === 'Working');
        case 'Pending':
            return baseTasks.filter(t => t.status === 'Pending' || t.status === 'Assigned');
        case 'Cancelled':
            return baseTasks.filter(t => t.status === 'Cancelled');
        case 'Complete':
            return baseTasks.filter(t => t.status === 'Complete' || t.status === 'Approved');
        case 'TodayComplete':
            const today = new Date().toISOString().split('T')[0];
            return baseTasks.filter(t =>
                (t.status === 'Complete' || t.status === 'Approved') &&
                t.updated_at?.split('T')[0] === today
            );
        case 'New':
            return baseTasks.filter(t => t.status === 'New');
        case 'Reissue':
            return baseTasks.filter(t => t.status === 'Reissue');
        case 'Future':
            return baseTasks.filter(t => t.status === 'Future');
        case 'Staff':
            return baseTasks.filter(t => ["Assigned", "Pending", "Working", "Staff"].includes(t.status));
        default:
            return baseTasks;
    }
});

// Status color classes
function statusClasses(status: string) {
    switch (status) {
        case 'Working': return 'border-yellow-500 bg-yellow-50 text-yellow-700';
        case 'Pending': return 'border-yellow-500 bg-yellow-50 text-yellow-700';
        case 'Assigned': return 'border-yellow-500 bg-yellow-50 text-yellow-700';
        case 'Complete': return 'border-blue-500 bg-blue-50 text-blue-700';
        case 'Approved': return 'border-green-500 bg-green-50 text-green-700';
        case 'Cancelled': return 'border-red-500 bg-red-50 text-red-700';
        case 'Reissue': return 'border-pink-500 bg-pink-50 text-pink-700';
        case 'New': return 'border-purple-500 bg-purple-50 text-purple-700';
        case 'Future': return 'border-indigo-500 bg-indigo-50 text-indigo-700';
        case 'Staff': return 'border-orange-500 bg-orange-50 text-orange-700';
        default: return 'border-gray-400 bg-gray-50 text-gray-700';
    }
}

const getWorkingBadge = (task) => {
    if (task.task.status !== "Working") return task.task.status;

    // If there's an active session anywhere
    if (task.task.active_session) return "Working";

    // Otherwise
    return "Working Process";
};

const getTaskCount = (tabKey: string) => {
    // 🔥 If searching AND All tab → show filtered (searched) count
    if (tabKey === "All" && searchKey.value.trim()) {
        return searchedTasks.value.length;
    }

    if (!allTasks.value || !Array.isArray(allTasks.value)) return 0;

    return allTasks.value.filter(t => {
        const status = t.status || t.task?.status;

        switch (tabKey) {
            case "All":
                // ✅ Include everything except Complete/Approved and Declined Staff
                if (["Complete", "Approved"].includes(status)) return false;
                // Exclude Staff tasks with declined decisions
                if (status === "Staff" && ["Declined", "Declined Trash"].includes(t.staff_decision)) return false;

                return true;

            case "Declined":
                return t.staff_decision === "Declined";

            case "DeclinedTrash":
                return t.staff_decision === "Declined Trash";

            case "Running":
                return status === "Working";

            case "Pending":
                return ["Pending", "Assigned"].includes(status);

            case "Cancelled":
                return status === "Cancelled";

            case "Complete":
                return ["Complete", "Approved"].includes(status);

            case "TodayComplete":
                const today = new Date().toISOString().split('T')[0];
                return ["Complete", "Approved"].includes(status)
                    && t.updated_at?.split('T')[0] === today;

            case "New":
                return status === "New";

            case "Reissue":
                return status === "Reissue";

            case "Future":
                return status === "Future";

            case "Staff":
                return status === "Staff" &&
                    !["Declined", "Declined Trash"].includes(t.staff_decision);

            default:
                return false;
        }
    }).length;
};

const getUserName = (userId) => {
    // Assuming `employees` contains all users fetched
    const user = employees.value.find(u => u.id === userId);
    return user ? user.name : 'Unknown';
};

const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "name", label: "Name", align: "left" },
    { key: "service_type", label: "Service Type", align: "center" },
    { key: "next_follow_up_date", label: "Next Follow Up", align: "center" },
    { key: "staff_status", label: "Staff Status", align: "center", type: "select" },
    { key: "actions", label: "Actions", align: "center" },
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

const getStaffName = (staffId: number | null, fallback = 'Staff') => {
    if (!staffId) return fallback;
    const staff = users.value.find(u => u.id === staffId);
    return staff ? staff.name : fallback;
};

const usersMap = computed(() => {
    const map: Record<number, any> = {};
    users.value.forEach(user => {
        map[user.id] = user;
    });
    return map;
});

const searchQuery = ref("");
const searchCustomerResults = ref<any[]>([]);
const searching = ref(false);

// 🔹 Debounce timer
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

const searchCustomer = async () => {
    if (!searchQuery.value || searchQuery.value.length < 2) {
        searchCustomerResults.value = [];
        return;
    }

    searching.value = true;

    try {
        const { data } = await axios.get('/api/customers/search', {
            params: { q: searchQuery.value }
        });
        searchCustomerResults.value = data.map((c: any) => ({
            ...c,

            // ✅ ALWAYS normalize service_type
            service_type: (() => {
                try {
                    return Array.isArray(c.service_type)
                        ? c.service_type
                        : JSON.parse(c.service_type || "[]");
                } catch {
                    return [];
                }
            })(),
        }));
    } catch (e) {
        console.error(e);
    } finally {
        searching.value = false;
    }
};

// 🔹 Watch with debounce
watch(searchQuery, (newVal) => {
    if (debounceTimer) clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        searchCustomer();
    }, 500); // 3000ms = 3 seconds
});

const resetSearch = () => {
    searchQuery.value = "";
    searchCustomerResults.value = [];
};

const searchResultsWithSN = computed(() =>
    tableData.value.map((c, index) => ({
        sn: index + 1,
        ...c,

        // numbers display
        numbers: c.numbers ?? [],
        numbers_text: c.numbers
            ?.map((n: any) => `${n.full_number} (${n.type})`)
            .join(", ") ?? '-',

        // ✅ formatted dates (SAFE)
        next_follow_up_date_formatted: c.next_follow_up_date
            ? formatDate(c.next_follow_up_date)
            : '-',

        last_contact_date_formatted: c.last_contact_date
            ? formatDate(c.last_contact_date)
            : '-',
    }))
);

const tableData = computed(() => {
    return searchCustomerResults.value.map(customer => {
        const staff = usersMap.value[customer.assigned_staff_id];

        return {
            ...customer,

            // ✅ MUST MATCH column.key
            assigned_staff: staff
                ? `${staff.name}`
                : '—',

            // ✅ overwrite original fields
            next_follow_up_date: customer.next_follow_up_date
                ? formatDate(customer.next_follow_up_date)
                : '-',

            last_contact_date: customer.last_contact_date
                ? formatDate(customer.last_contact_date)
                : '-',

        };
    });
});

const showHistoryModal = ref(false);
const modalTitle = ref("");
const modalContent = ref("");

const showPreviewModal = ref(false);
const previewTitle = ref("");
const previewContent = ref("");

const openModal = (title: string, content: string) => {
    previewTitle.value = title;
    previewContent.value = content?.trim() || "";
    showPreviewModal.value = true;
};

const historyData = ref<any[]>([]);

const openHistoryModal = async (customer: any) => {
    try {
        const { data } = await axios.get(`/api/customers/${customer.id}/history`);
        modalTitle.value = `History of ${customer.name}`;
        historyData.value = data; // store raw array
        showHistoryModal.value = true;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch history', life: 3000 });
    }
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

// Modal state
const showNotes = ref(false);
const taskNotes = ref([]);
const newNote = ref('');
const currentTask = ref(null);
const unreadNotes = ref({}); // { taskId: count }

// Open modal and fetch notes
const openNotesModal = async (task) => {
    currentTask.value = task;
    showNotes.value = true;
    await fetchNotes(task.id);
};

// Fetch notes from API
const fetchNotes = async (taskId) => {
    const { data } = await axios.get(`/api/tasks/${taskId}/notes`);
    taskNotes.value = data.notes;
    unreadNotes.value[taskId] = data.unreadCount;
};

// Fetch unread note counts for all tasks
const fetchUnreadNotes = async () => {
    try {
        if (!Array.isArray(StaffTasks.value)) return; // safety check

        for (const task of StaffTasks.value) {
            const { data } = await axios.get(`/api/tasks/${task.task.id}/notes`);
            unreadNotes.value[task.task.id] = data.unreadCount || 0;
        }
    } catch (err) {
        console.error("Failed to fetch unread notes", err);
    }
};

// Watch StaffTasks for changes
watch(StaffTasks, async (newTasks, oldTasks) => {
    if (!Array.isArray(newTasks)) return;
    await fetchUnreadNotes(); // fetch unread notes for all tasks whenever StaffTasks changes
}, { deep: true });

// Add new note
const addNote = async () => {
    if (!newNote.value.trim()) return;

    await axios.post(`/api/tasks/${currentTask.value.id}/notes`, {
        note: newNote.value
    });

    newNote.value = '';
    await fetchNotes(currentTask.value.id);

    // Update unread count for the button immediately
    unreadNotes.value[currentTask.value.id] = taskNotes.value.filter(n => !n.is_read).length;
};

// Mark a single note as read
const markAsRead = async (noteId) => {
    await axios.post(`/api/tasks/${currentTask.value.id}/notes/mark-read`, {
        note_ids: [noteId]
    });
    await fetchNotes(currentTask.value.id);

    unreadNotes.value[currentTask.value.id] = taskNotes.value.filter(n => !n.is_read).length;
};

const markAllAsRead = async () => {
    const unreadIds = taskNotes.value.filter(n => !n.is_read).map(n => n.id);
    if (unreadIds.length === 0) return;

    await axios.post(`/api/tasks/${currentTask.value.id}/notes/mark-read`, {
        note_ids: unreadIds
    });
    await fetchNotes(currentTask.value.id);

    unreadNotes.value[currentTask.value.id] = 0;
};

const showDeclineModal = ref(false)
const declineNote = ref('')
const selectedTaskId = ref(null)

const showDeclinedTrashModal = ref(false)
const declinedTrashNote = ref('')

const showApproveModal = ref(false)
const approveNote = ref('')

const openApproveModal = (task) => {
    selectedTaskId.value = task.id
    showApproveModal.value = true
}

const openDeclinedTrashModal = (task) => {
    selectedTaskId.value = task.id
    showDeclinedTrashModal.value = true
}

const openDeclineModal = (task) => {
    selectedTaskId.value = task.id
    showDeclineModal.value = true
}

const submitApprove = async () => {
    if (!approveNote.value) return alert("Approve note required");

    await axios.post(`/api/tasks/${selectedTaskId.value}/staff-decision`, {
        decision: 'Approved',
        note: approveNote.value
    });

    const task = StaffTasks.value.find(t => t.task.id === selectedTaskId.value);
    if (task) {
        task.task.staff_decision = 'Approved';
        task.task.approve_note = approveNote.value;
    }

    toast.add({
        severity: "success",
        summary: "Approved",
        detail: "Staff status decision approved successfully",
        life: 3000
    });

    fetchStaffTasks(); // refresh tasks to reflect changes

    showApproveModal.value = false;
    approveNote.value = '';
};

const submitDecline = async () => {
    if (!declineNote.value) return alert("Decline note required");

    await axios.post(`/api/tasks/${selectedTaskId.value}/staff-decision`, {
        decision: 'Declined',
        note: declineNote.value
    });

    const task = StaffTasks.value.find(t => t.task.id === selectedTaskId.value);
    if (task) {
        task.task.staff_decision = 'Declined';
        task.task.decline_note = declineNote.value;
    }

    toast.add({
        severity: "warn",
        summary: "Declined",
        detail: "Staff status decision declined successfully",
        life: 3000
    });

    showDeclineModal.value = false;
    declineNote.value = '';

    fetchStaffTasks(); // refresh tasks to reflect changes

    activeTab.value = "Declined"; // ✅ move tab
};

const submitDeclinedTrash = async () => {
    if (!declinedTrashNote.value) return alert("Trash note required");

    await axios.post(`/api/tasks/${selectedTaskId.value}/staff-decision`, {
        decision: 'Declined Trash',
        note: declinedTrashNote.value
    });

    const task = StaffTasks.value.find(t => t.task.id === selectedTaskId.value);
    if (task) {
        task.task.staff_decision = 'Declined Trash';
        task.task.declined_trash_note = declinedTrashNote.value;
    }

    toast.add({
        severity: "error",
        summary: "Declined Trash",
        detail: "Staff status decision moved to Declined Trash",
        life: 3000
    });

    showDeclinedTrashModal.value = false;
    declinedTrashNote.value = '';

    fetchStaffTasks(); // refresh tasks to reflect changes

    activeTab.value = "DeclinedTrash"; // ✅ move tab
};

const staffAllVisibleTasks = computed(() => {
    return filteredTasks(allStaffTasks.value).filter(
        t => !['Declined', 'Declined Trash'].includes(t.staff_decision)
    );
});
</script>

<template>

    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbItems" @search="handleClientSearch">
        <DemoPresenterDashboard v-if="props.userRole === 'demo_presenter'" />

        <template v-else>
            <Toast />

            <!-- 🔥 SEARCH RESULT AREA -->
            <div v-if="!showDashboard" class="p-4 space-y-6">

                <div v-if="searchResults.length === 0" class="text-gray-500 text-center py-10">
                    No clients found.
                </div>

                <div v-for="client in searchResults" :key="client.id"
                    class="bg-white shadow-lg rounded-2xl border border-gray-200 overflow-hidden">

                    <!-- Client Header -->
                    <div
                        class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-5 bg-indigo-50 border-b border-indigo-200">
                        <div>
                            <h4 class="text-2xl font-bold text-gray-900">{{ client.name }}</h4>
                            <p class="text-gray-600 text-sm mt-1">
                            Operator: {{ client.oparetor_number }} | Area: {{ client.area_name }} | Project: {{
                                client.project_name }}
                        </p>
                    </div>

                    <!-- Status Tabs -->
                    <div class="flex flex-wrap gap-2 mt-3 sm:mt-0">
                        <template
                            v-for="status in ['Running', 'Working', 'Pending', 'Complete', 'Cancelled', 'Reissue', 'New', 'Future', 'Staff', 'Approved']"
                            :key="status">
                            <button @click="selectedStatus[client.id] = status" :class="[
                                'px-3 py-1 rounded-full text-sm font-semibold transition-all duration-200 shadow hover:shadow-md',
                                selectedStatus[client.id] === status
                                    ? 'ring-2 ring-offset-1 ring-indigo-500 bg-indigo-100 text-indigo-700'
                                    : 'bg-white text-gray-700',
                                statusClasses(status)
                            ]">
                                {{ status }} ({{ getTaskCount(client.tasks, status) }})
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Filtered Tasks -->
                <div class="p-5 space-y-5">
                    <div v-for="task in filteredTasks(client.tasks, selectedStatus[client.id])" :key="task.id"
                        class="relative flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 border rounded-xl bg-white hover:bg-indigo-50 transition-all shadow-sm hover:shadow-md">
                        <!-- Left: Task Info -->
                        <div class="flex-1">
                            <h5 class="text-lg font-bold text-gray-800">{{ task.title }}</h5>
                            <p class="text-gray-700 mt-1" v-html="task.details"></p>
                            <p class="text-gray-500 text-sm mt-2">
                                Created By: {{ getUserName(task.created_by) }} | Date: {{ formatDate(task.created_at) }}
                            </p>

                            <!-- Notes Section -->
                            <div v-if="task.approved_note || task.complete_note || task.reissue_comment"
                                class="mt-3 space-y-2">
                                <div v-if="task.approved_note"
                                    class="flex items-center gap-2 bg-green-50 border-l-4 border-green-400 p-2 rounded-md text-sm text-green-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span><strong>Approved Note:</strong> {{ task.approved_note }}</span>
                                </div>

                                <div v-if="task.complete_note"
                                    class="flex items-center gap-2 bg-blue-50 border-l-4 border-blue-400 p-2 rounded-md text-sm text-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>
                                        <strong>Complete Notes:</strong>
                                        <span v-for="n in JSON.parse(task.complete_note)" :key="n.submitted_at">{{
                                            n.note }} ({{ formatBDDateTime(n.submitted_at) }}) </span>
                                    </span>
                                </div>

                                <div v-if="task.reissue_comment"
                                    class="flex items-center gap-2 bg-yellow-50 border-l-4 border-yellow-400 p-2 rounded-md text-sm text-yellow-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12c0 4.418-3.582 8-8 8s-8-3.582-8-8 3.582-8 8-8 8 3.582 8 8z" />
                                    </svg>
                                    <span>
                                        <strong>Reissue Comments:</strong>
                                        <span v-for="r in JSON.parse(task.reissue_comment)" :key="r.submitted_at">{{
                                            r.comment }} ({{ formatBDDateTime(r.submitted_at) }}) </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Middle: Image -->
                        <div v-if="task.image_url"
                            class="w-32 h-32 flex-shrink-0 rounded-lg overflow-hidden border border-gray-200 flex items-center justify-center">
                            <img :src="task.image_url" alt="Task Image" class="w-full h-full object-cover">
                        </div>

                        <!-- Right Top: Status Badge -->
                        <span
                            :class="['absolute top-2 right-2 px-3 py-1 rounded-full text-sm font-semibold shadow', statusClasses(task.status)]">
                            {{ task.status }}
                        </span>
                    </div>
                </div>

            </div>
        </div>


        <div v-if="showDashboard && props.userRole !== 'demo_presenter'">
            <!-- Tab Buttons -->
            <div class="flex flex-wrap gap-2 p-5">
                <button v-for="tab in tabs" :key="tab.key" @click="() => handleTabClick(tab.key)"
                    class="py-2 px-4 font-semibold rounded-t-lg transition-all duration-200 border text-sm sm:text-base flex items-center gap-2"
                    :class="[
                        activeTab === tab.key
                            ? `bg-${tab.color}-100 border-b-4 border-${tab.color}-400 text-${tab.color}-700`
                            : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
                    ]">
                    <span>{{ tab.label }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                        {{ getTaskCount(tab.key) }}
                    </span>
                </button>
            </div>

            <!-- 🔍 Search Filters -->
            <div class="mb-6 w-full px-5">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-end
               bg-white p-4 rounded-2xl shadow-sm border">

                    <!-- 🔎 Search Customer -->
                    <div class="xl:col-span-3 flex flex-col relative hidden">
                        <label class="text-sm font-medium mb-1">Search Customer</label>

                        <div class="relative">
                            <InputText v-model="searchQuery" placeholder="Search by name or phone" class="w-full h-[44px] pr-20 rounded-xl border border-gray-300
                                    focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                    shadow-sm transition" />

                            <!-- 🔍 Search Icon -->
                            <i
                                class="pi pi-search absolute right-10 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                            </i>

                            <!-- ❌ Clear Button -->
                            <button v-if="searchQuery" type="button" @click="resetSearch" class="absolute right-2 top-1/2 -translate-y-1/2
                                    h-7 w-7 flex items-center justify-center
                                    rounded-full hover:bg-gray-200 transition">
                                <i class="pi pi-times text-sm text-gray-500"></i>
                            </button>
                        </div>

                        <!-- 🔽 Search Result Table (Floating, Full Width) -->
                        <transition name="fade">
                            <div v-if="searchCustomerResults.length" class="fixed left-1/2 top-[300px] -translate-x-1/2 z-50
                                w-[95vw] max-w-[1400px]
                                bg-white rounded-2xl shadow-2xl border
                                max-h-[70vh] overflow-auto">

                                <DataTable title="Customer Search Results" :columns="columns"
                                    :rows="searchResultsWithSN" :showSearch="false" @openModal="openModal">

                                    <!-- Name + Designation -->
                                    <template #cell-name="{ row }">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-gray-800">
                                                {{ row.name }}
                                            </span>
                                            <span class="text-sm text-gray-500">
                                                {{ row.designation || '-' }}
                                            </span>
                                        </div>
                                    </template>

                                    <!-- Service Type -->
                                    <template #cell-service_type="{ row }">
                                        <div class="flex flex-col text-sm text-gray-700 text-center">
                                            <span v-for="(type, idx) in row.service_type" :key="idx">
                                                {{ type }}
                                            </span>
                                        </div>
                                    </template>

                                    <!-- Staff Status -->
                                    <template #cell-staff_status="{ row }">
                                        <span class="text-sm">
                                            {{ row.staff_status || '-' }}
                                        </span>
                                    </template>

                                    <!-- Numbers -->
                                    <template #cell-numbers="{ row }">
                                        <div class="flex flex-col text-sm text-gray-700">
                                            <span v-for="(num, idx) in row.numbers" :key="idx">
                                                {{ num.fullNumber || num.number }} ({{ num.type }})
                                            </span>
                                        </div>
                                    </template>

                                    <!-- Next Follow-up -->
                                    <template #cell-next_follow_up_date="{ row }">
                                        <span class="text-sm text-gray-600">
                                            {{
                                                row.next_follow_up_date
                                                    ? new Intl.DateTimeFormat('en-GB', {
                                                        day: '2-digit',
                                                        month: 'short',
                                                        year: 'numeric'
                                                    }).format(new Date(row.next_follow_up_date))
                                                    : '-'
                                            }}
                                        </span>
                                    </template>

                                    <!-- Last Contact -->
                                    <template #cell-last_contact_date="{ row }">
                                        <span class="text-sm text-gray-600">
                                            {{
                                                row.last_contact_date
                                                    ? new Intl.DateTimeFormat('en-GB', {
                                                        day: '2-digit',
                                                        month: 'short',
                                                        year: 'numeric'
                                                    }).format(new Date(row.last_contact_date))
                                                    : '-'
                                            }}
                                        </span>
                                    </template>

                                    <!-- Actions -->
                                    <template #cell-actions="{ row }">
                                        <button class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                                            @click="openHistoryModal(row)">
                                            <i class="pi pi-history"></i>
                                        </button>
                                    </template>

                                </DataTable>
                            </div>
                        </transition>
                    </div>

                    <!-- 👤 Search Employee (Admin Only) -->
                    <div v-if="props.userRole === 'admin' && !['Future', 'New', 'Staff'].includes(activeTab)"
                        class="xl:col-span-4 flex flex-col">
                        <label class="text-sm font-medium mb-1">Search Employee</label>

                        <Multiselect v-model="selectedEmployee" :options="employeeOptions" label="name" track-by="id"
                            placeholder="Filter by Assigned User" class="h-[44px] rounded-xl border border-green-300" />
                    </div>

                    <!-- 🏪 Search Shop -->
                    <div class="xl:col-span-4 flex flex-col">
                        <label class="text-sm font-medium mb-1">Search by Shop</label>

                        <Multiselect v-model="selectedShop" :options="shopOptionsByTab" label="shop_name" track-by="id"
                            placeholder="Filter by Shop Name" class="h-[44px] rounded-xl border border-blue-300" />
                    </div>

                    <!-- 🔹 Status Filter -->
                    <div v-if="props.userRole === 'admin' && activeTab === 'All'"
                        class="xl:col-span-3 flex flex-col">
                        <label class="text-sm font-medium mb-1">Status</label>

                        <Multiselect v-model="selectedStatusFilter" :options="statusOptions" :searchable="false"
                            :close-on-select="true"
                            :show-no-results="false"
                            placeholder="Select Status"
                            class="h-[44px] rounded-xl border border-purple-300"
                            label="label"
                            track-by="value"
                            />
                    </div>

                    <!-- 🔄 Reset -->
                    <div class="xl:col-span-1 flex items-center gap-2">
                        <Button
                            icon="pi pi-refresh"
                            class="h-[44px] w-full rounded-xl bg-red-100"
                            @click="resetAllFilters"
                        />
                    </div>
                </div>
            </div>

            <div class="flex justify-center items-center gap-3 px-5">
                <!-- 🔢 Result Count Badge (ADMIN + All tab only) -->
                <span v-if="props.userRole === 'admin'
                    && activeTab === 'All'"
                    class="px-5 py-2 rounded-lg bg-indigo-600 text-white text-base font-semibold">
                    Total Found- ({{ adminAllFilteredTasks.length }})
                </span>
            </div>

            <!-- All Tasks (ADMIN ONLY) -->
            <div v-if="activeTab === 'All' && props.userRole === 'admin'" class="p-4 sm:p-6">
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-400 rounded-2xl shadow-lg overflow-hidden">

                    <!-- Header -->
                    <div class="flex justify-between items-start sm:items-center p-4 gap-3 text-white">
                        <h3 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
                            <i class="pi pi-list"></i> All Tasks (Admin)
                        </h3>
                        <span
                            class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                            {{ tasksForActiveTab.length }}
                        </span>
                    </div>

                    <!-- Task List -->
                    <div
                        :class="['p-4 bg-indigo-50 transition-all duration-300', { 'max-h-auto sm:max-h-110 overflow-y-auto pr-2': tasksForActiveTab.length > 9 }]">

                        <transition-group name="fade" tag="div" class="space-y-3">
                            <div
                                v-for="(task, index) in tasksForActiveTab"
                                :key="task.id"
                                class="rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-300 hover:shadow-lg border-l-4"
                                :class="statusClasses(task.status)"
                            >
                                <!-- Title -->
                                <div class="flex justify-between items-center mb-2">
                                    <h2 class="font-semibold text-lg sm:text-xl">
                                        {{ index + 1 }}. {{ task.task.title }}
                                    </h2>

                                    <div class="" >
                                        <span
                                            v-if="(task.status === 'Staff' || task.task?.status === 'Staff') && task.staff_decision === 'Approved'"
                                            class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                            ✅ Approved by Admin
                                        </span>

                                        <span
                                            v-if="(task.status === 'Staff' || task.task?.status === 'Staff') && task.staff_decision === 'Pending'"
                                            class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">
                                            ⏳ Waiting for Admin Decision
                                        </span>

                                        <!-- Status Badge -->
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full shadow"
                                            :class="statusClasses(task.status)"
                                        >
                                            {{ task.status }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Details -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm font-medium">

                                    <!-- LEFT -->
                                    <div class="flex flex-col gap-1">
                                        <p>
                                            <i class="pi pi-user mr-1"></i>
                                            Created By:
                                            <span class="font-semibold">
                                                <!-- If task status is Staff, show task.created_by -->
                                                <span v-if="task.status === 'Staff'">
                                                    {{ getUserName(task.created_by) }}
                                                </span>

                                                <!-- Otherwise, try task.task.created_by, fallback to task.created_by -->
                                                <span v-else>
                                                    {{ getUserName(task.task.created_by) }}
                                                </span>
                                            </span>
                                        </p>

                                        <p>
                                            <i class="pi pi-calendar mr-1"></i>
                                            Task Created:
                                            <span>
                                                {{ formatDateBD(task.created_at) || 'N/A' }}
                                            </span>
                                        </p>

                                        <p>
                                            <i class="pi pi-shop mr-1"></i>
                                            Shop:
                                            <span>
                                                {{ task.task.shop_name || 'Unknown' }}
                                            </span>
                                        </p>

                                        <p v-if="task.employee">
                                            <i class="pi pi-users mr-1"></i>
                                            Assigned To:
                                            <span class="font-semibold capitalize">
                                                {{ task.employee.name }} - ({{ task.employee.designation }})
                                            </span>
                                        </p>
                                    </div>

                                    <!-- RIGHT -->
                                    <div class="flex flex-col gap-1">

                                        <p v-if="task.task.start_date">
                                            <i class="pi pi-calendar-plus mr-1"></i>
                                            Start Date:
                                            <span>
                                                {{ formatDate(task.task.start_date) }}
                                            </span>
                                        </p>

                                        <p v-if="task.assigned_at">
                                            <i class="pi pi-clock mr-1"></i>
                                            Assigned At:
                                            <span>
                                                {{ formatDateBD(task.assigned_at) }}
                                            </span>
                                        </p>

                                        <p v-if="task.completed_at">
                                            <i class="pi pi-check-circle mr-1"></i>
                                            Completed At:
                                            <span>
                                                {{ formatDateBD(task.completed_at) }}
                                            </span>
                                        </p>

                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-wrap justify-end gap-2 mt-4">
                                    <Button
                                        label="Details"
                                        icon="pi pi-eye"
                                        outlined
                                        severity="info"
                                        class="!text-sm !px-3 !py-1.5"
                                        @click="openDetails(task)"
                                    />
                                    <Button
                                        label="Work History"
                                        icon="pi pi-clock"
                                        outlined
                                        severity="warning"
                                        class="!text-sm !px-3 !py-1.5"
                                        @click="openWorkHistory(task)"
                                    />
                                    <Button label="Notes" icon="pi pi-note" :badge="unreadNotes[task.id]"
                                            badgeClass="bg-red-600 text-white !text-xs !w-5 !h-5 flex items-center justify-center"
                                            class="p-button-sm" @click="openNotesModal(task)" />
                                </div>

                            </div>
                        </transition-group>

                        <!-- Empty -->
                        <div v-if="tasksForActiveTab.length === 0"
                            class="text-center py-6 text-gray-500 italic border-t border-indigo-200 mt-2">
                            No tasks available.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Running Tasks -->
            <div v-if="activeTab === 'Running'">
                <div class="p-4 sm:p-6">
                    <!-- Section Card -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-400 rounded-2xl shadow-lg overflow-hidden">
                        <!-- Header -->
                        <div class="flex justify-between items-start sm:items-center p-4 text-white gap-2 sm:gap-0">
                            <h3 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
                                <i class="pi pi-spinner text-lg"></i> Running Tasks
                            </h3>
                            <span
                                class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                                {{ filteredTasks(runningTasks).length }}
                            </span>
                        </div>

                        <!-- Task List -->
                        <div
                            :class="['p-4 bg-blue-50 transition-all duration-300', { 'max-h-96 overflow-y-auto pr-2': runningTasks.length > 6 }]">
                            <transition-group name="fade" tag="div" class="space-y-4">
                                <div v-for="(task, index) in filteredTasks(runningTasks)" :key="task.id"
                                    :class="['rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-500 hover:shadow-lg border-l-4',
                                        task.isWorkingSession ? 'border-green-600 bg-green-50 animate-pulse-ring' : 'border-blue-500 bg-white']">

                                    <!-- Title + Status -->
                                    <div class="flex justify-between items-start sm:items-center mb-3 gap-2">
                                        <h2 class="font-semibold text-blue-800 text-lg sm:text-xl">
                                            {{ index + 1 }}. {{ task.task.title }}
                                        </h2>
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full border border-blue-300 bg-blue-100 text-blue-800">
                                            {{ task.task.status }}
                                        </span>
                                    </div>

                                    <!-- Task Details Grid -->
                                    <div :class="[
                                        'grid gap-5 text-blue-700 font-medium text-sm sm:text-md transition-all duration-300',
                                        task.isWorkingSession ? 'grid-cols-1 sm:grid-cols-3' : 'grid-cols-1 sm:grid-cols-2'
                                    ]">
                                        <!-- Left Side -->
                                        <div class="flex flex-col gap-1">
                                            <p>
                                                <i class="pi pi-user mr-1"></i>
                                                Created By:
                                                <span class="font-semibold">
                                                    <!-- If task status is Staff, show task.created_by -->
                                                    <span v-if="task.status === 'Staff'">
                                                        {{ getUserName(task.created_by) }}
                                                    </span>

                                                    <!-- Otherwise, try task.task.created_by, fallback to task.created_by -->
                                                    <span v-else>
                                                        {{ getUserName(task.task?.created_by) ||
                                                        getUserName(task.created_by) }}
                                                    </span>
                                                </span>
                                            </p>

                                            <p>
                                                <i class="pi pi-calendar mr-1"></i>
                                                Task Created:
                                                <span>
                                                    {{ formatDateBD(task.created_at) || 'N/A' }}
                                                </span>
                                            </p>

                                            <p>
                                                <i class="pi pi-shop mr-1"></i>
                                                Shop:
                                                <span>
                                                    {{ task.task.shop_name || 'Unknown' }}
                                                </span>
                                            </p>

                                            <p v-if="task.employee">
                                                <i class="pi pi-users mr-1"></i>
                                                Assigned To:
                                                <span class="font-semibold capitalize">
                                                    {{ task.employee.name }} - ({{ task.employee.designation }})
                                                </span>
                                            </p>
                                        </div>

                                        <!-- Right Side -->
                                        <div class="flex flex-col gap-2 lg:pl-10">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar-plus mr-1"></i>
                                                <p>Client Start: {{ formatDateBD(task.task.start_date) }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-clipboard-list"></i>
                                                <p>Task Assigned: {{ formatDateBD(task.assigned_at) || 'N/A' }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-hourglass-start"></i>
                                                <p>Employee Start: {{ formatDateBD(task.start_date) }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-hourglass-end"></i>
                                                <p>Employee End: {{ formatDateBD(task.end_date) }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-clock mr-1"></i>
                                                <p>Committed: {{ task.committed_hours }} hrs</p>
                                                <span v-if="task.worked_minutes">| Worked: {{
                                                    formatDuration(task.worked_minutes)
                                                }}</span>
                                            </div>
                                        </div>

                                        <!-- Live Timer -->
                                        <div v-if="task.isWorkingSession"
                                            class="flex flex-col items-center justify-center bg-green-50 border border-green-300 rounded-xl p-4 shadow-sm hover:shadow-md transition-all duration-300">
                                            <div
                                                class="flex items-center justify-center w-10 h-10 rounded-full bg-green-200 text-green-700 shadow-inner animate-pulse">
                                                <i class="fa-solid fa-stopwatch text-lg"></i>
                                            </div>
                                            <span
                                                class="text-xs font-medium text-gray-500 uppercase tracking-wide mt-2">
                                                Working Time
                                            </span>
                                            <button class="font-bold text-green-700 bg-white border border-green-500 rounded-lg px-4 py-2 mt-2
                                                shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105 flex items-center gap-2">
                                                <i class="fa-regular fa-clock"></i>
                                                {{ runningTimers[task.id] !== undefined ?
                                                    formatTime(runningTimers[task.id])
                                                    : '-'
                                                }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-wrap justify-end gap-2 mt-4">
                                        <Button label="Details" icon="pi pi-eye" outlined severity="info"
                                            class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                                        <Button v-if="props.userRole === 'employee'" label="Mark Complete"
                                            icon="pi pi-check" severity="info" class="!text-sm !px-3 !py-1.5"
                                            @click="openCompleteDialog(task)" />
                                        <Button v-if="props.userRole === 'employee' && !task.isWorkingSession"
                                            label="Start Work" icon="pi pi-play" class="!text-sm !px-3 !py-1.5"
                                            @click="startWork(task)" />
                                        <Button v-else-if="props.userRole === 'employee'" label="Stop Work"
                                            icon="pi pi-stop" severity="danger" class="!text-sm !px-3 !py-1.5"
                                            @click="stopWork(task)" />
                                        <Button label="History" icon="pi pi-clock" outlined severity="info"
                                            class="!text-sm !px-3 !py-1.5" @click="openWorkHistory(task)" />
                                    </div>
                                </div>
                            </transition-group>

                            <!-- Empty State -->
                            <div v-if="filteredTasks(runningTasks).length === 0"
                                class="text-center py-6 text-gray-500 italic border-t border-blue-200 mt-2">
                                No running tasks available.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wrok Time History Dialog -->
            <Dialog header="Work History" v-model:visible="showWorkHistoryDialog" :modal="true" :closable="true"
                class="w-[450px] md:w-[600px]">
                <div v-if="workHistory.length === 0" class="text-gray-500 italic text-center py-4">
                    {{ workHistoryMessage || "No working history." }}
                </div>

                <div v-else class="space-y-3">
                    <div v-for="(session, idx) in formattedWorkHistory" :key="idx"
                        class="p-4 rounded-2xl shadow-sm border border-gray-200 hover:shadow-md transition duration-300 bg-white">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-500">Session {{ idx + 1 }}</span>
                            <span class="text-xs font-medium px-2 py-1 rounded-full" :class="session.status === 'active'
                                ? 'bg-green-100 text-green-600'
                                : 'bg-gray-100 text-gray-600'">
                                {{ session.status }}
                            </span>
                        </div>

                        <div class="text-sm text-gray-700 space-y-1">
                            <p>
                                <span class="font-medium text-gray-600">Work Start:</span>
                                {{ session.formatted_start }}
                            </p>
                            <p>
                                <span class="font-medium text-gray-600">Work Stop:</span>
                                {{ session.formatted_stop }}
                            </p>
                            <p>
                                <span class="font-medium text-gray-600">Work Duration:</span>
                                {{ session.formatted_duration }}
                            </p>
                        </div>
                    </div>
                </div>
            </Dialog>

            <!-- Pending Tasks -->
            <div v-if="activeTab === 'Pending'">
                <div class="p-4 sm:p-6">
                    <!-- Section Card -->
                    <div class="bg-gradient-to-r from-yellow-600 to-yellow-400 rounded-2xl shadow-lg overflow-hidden">
                        <!-- Header -->
                        <div class="flex justify-between items-start sm:items-center p-4 text-white gap-2 sm:gap-0">
                            <h3 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
                                <i class="pi pi-clock text-lg"></i> Pending Tasks
                            </h3>
                            <span
                                class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                                {{ filteredTasks(pendingTasks).length }}
                            </span>
                        </div>

                        <!-- Task List -->
                        <div
                            :class="['p-4 bg-yellow-50 transition-all duration-300', { 'max-h-96 overflow-y-auto pr-2': pendingTasks.length > 6 }]">
                            <transition-group name="fade" tag="div" class="space-y-4">
                                <div v-for="(task, index) in filteredTasks(pendingTasks)" :key="task.id"
                                    class="rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-300 hover:shadow-lg border-l-4 border-yellow-500 bg-white text-yellow-700">

                                    <!-- Title + Status -->
                                    <div class="flex justify-between items-start sm:items-center mb-3 gap-2">
                                        <h2 class="font-semibold text-yellow-800 text-lg sm:text-xl">
                                            {{ index + 1 }}. {{ task.task.title }}
                                        </h2>
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full border border-yellow-300 bg-yellow-100">
                                            {{ task.task.status }}
                                        </span>
                                    </div>

                                    <!-- Task Details Grid -->
                                    <div :class="[
                                        'grid gap-5 text-yellow-700 font-medium text-sm sm:text-md transition-all duration-300',
                                        'grid-cols-1 sm:grid-cols-2'
                                    ]">
                                        <!-- Left Side -->
                                        <div class="flex flex-col gap-1">
                                            <p>
                                                <i class="pi pi-user mr-1"></i>
                                                Created By:
                                                <span class="font-semibold">
                                                    <!-- If task status is Staff, show task.created_by -->
                                                    <span v-if="task.status === 'Staff'">
                                                        {{ getUserName(task.created_by) }}
                                                    </span>

                                                    <!-- Otherwise, try task.task.created_by, fallback to task.created_by -->
                                                    <span v-else>
                                                        {{ getUserName(task.task?.created_by) ||
                                                            getUserName(task.created_by) }}
                                                    </span>
                                                </span>
                                            </p>

                                            <p>
                                                <i class="pi pi-calendar mr-1"></i>
                                                Task Created:
                                                <span>
                                                    {{ formatDateBD(task.created_at) || 'N/A' }}
                                                </span>
                                            </p>

                                            <p>
                                                <i class="pi pi-shop mr-1"></i>
                                                Shop:
                                                <span>
                                                    {{ task.task.shop_name || 'Unknown' }}
                                                </span>
                                            </p>

                                            <p v-if="task.employee">
                                                <i class="pi pi-users mr-1"></i>
                                                Assigned To:
                                                <span class="font-semibold capitalize">
                                                    {{ task.employee.name }} - ({{ task.employee.designation }})
                                                </span>
                                            </p>
                                        </div>

                                        <!-- Right Side -->
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar-plus mr-1"></i>
                                                <p>Client Project Start Date:
                                                    <span :class="!task.task.start_date ? 'text-gray-400 italic' : ''">
                                                        {{ formatDate(task.task.start_date) || 'Not Provided' }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar-plus mr-1"></i>
                                                <p>Task Assigned :
                                                    <span>
                                                        {{ formatDateBD(task.assigned_at) || 'Not Provided' }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-hourglass-start"></i>
                                                <p>Employee Start Date: {{ formatDate(task.start_date) }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-hourglass-end"></i>
                                                <p>Employee End Date: {{ formatDate(task.end_date) }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-clock mr-1"></i>
                                                <p>Committed Hours: {{ task.committed_hours }} hrs</p>
                                                <span v-if="task.worked_minutes">| Worked: {{
                                                    formatDuration(task.worked_minutes)
                                                    }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-wrap justify-end gap-2 mt-4">
                                        <Button label="Details" icon="pi pi-eye" outlined severity="warn"
                                            class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                                        <Button v-if="props.userRole === 'employee'" label="Select For Work"
                                            icon="pi pi-check" severity="warn" class="!text-sm !px-3 !py-1.5"
                                            @click="changeStatus(task, 'Working')" />
                                        <Button v-if="props.userRole === 'admin'" label="Cancelled Work"
                                            icon="pi pi-times-circle" severity="danger" class="!text-sm !px-3 !py-1.5"
                                            @click="openCancel(task)" />
                                    </div>
                                </div>
                            </transition-group>

                            <!-- Empty State -->
                            <div v-if="filteredTasks(pendingTasks).length === 0"
                                class="text-center py-6 text-gray-500 italic border-t border-yellow-200 mt-2">
                                No pending tasks available.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cancelled Tasks -->
            <div v-if="activeTab === 'Cancelled'">
                <div class="p-4 sm:p-6">
                    <!-- Section Card -->
                    <div class="bg-gradient-to-r from-red-600 to-red-400 rounded-2xl shadow-lg overflow-hidden">
                        <!-- Header -->
                        <div class="flex justify-between items-start sm:items-center p-4 text-white gap-2 sm:gap-0">
                            <h3 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
                                <i class="pi pi-times text-lg"></i> Cancelled Tasks
                            </h3>
                            <span
                                class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                                {{ filteredTasks(cancelledTasks).length }}
                            </span>
                        </div>

                        <!-- Task List -->
                        <div
                            :class="['p-4 bg-red-50 transition-all duration-300', { 'max-h-96 overflow-y-auto pr-2': cancelledTasks.length > 6 }]">
                            <transition-group name="fade" tag="div" class="space-y-4">
                                <div v-for="(task, index) in filteredTasks(cancelledTasks)" :key="task.id"
                                    class="rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-300 hover:shadow-lg border-l-4 border-red-500 bg-white text-red-700">

                                    <!-- Title + Status -->
                                    <div
                                        class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                                        <h2 class="font-semibold text-red-800 text-lg sm:text-xl">
                                            {{ index + 1 }}. {{ task.task.title }}
                                        </h2>
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full border border-red-300 bg-red-100">
                                            {{ task.task.status }}
                                        </span>
                                    </div>

                                    <!-- Task Details Grid -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-red-700 font-medium text-sm sm:text-md">
                                        <!-- Left Side -->
                                        <div class="flex flex-col gap-1">
                                            <p>
                                                <i class="pi pi-user mr-1"></i>
                                                Created By:
                                                <span class="font-semibold">
                                                    <!-- If task status is Staff, show task.created_by -->
                                                    <span v-if="task.status === 'Staff'">
                                                        {{ getUserName(task.created_by) }}
                                                    </span>

                                                    <!-- Otherwise, try task.task.created_by, fallback to task.created_by -->
                                                    <span v-else>
                                                        {{ getUserName(task.task?.created_by) ||
                                                            getUserName(task.created_by) }}
                                                    </span>
                                                </span>
                                            </p>

                                            <p>
                                                <i class="pi pi-calendar mr-1"></i>
                                                Task Created:
                                                <span>
                                                    {{ formatDateBD(task.created_at) || 'N/A' }}
                                                </span>
                                            </p>

                                            <p>
                                                <i class="pi pi-shop mr-1"></i>
                                                Shop:
                                                <span>
                                                    {{ task.task.shop_name || 'Unknown' }}
                                                </span>
                                            </p>

                                            <p v-if="task.employee">
                                                <i class="pi pi-users mr-1"></i>
                                                Assigned To:
                                                <span class="font-semibold capitalize">
                                                    {{ task.employee.name }} - ({{ task.employee.designation }})
                                                </span>
                                            </p>
                                        </div>

                                        <!-- Right Side -->
                                        <div v-if="props.userRole !== 'staff'" class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar-plus mr-1"></i>
                                                <p>Client Project Start Date:
                                                    <span :class="!task.task.start_date ? 'text-gray-400 italic' : ''">
                                                        {{ formatDate(task.task.start_date) || 'Not Provided' }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-hourglass-start"></i>
                                                <p>Employee Start Date: {{ formatDate(task.start_date) }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-hourglass-end"></i>
                                                <p>Employee End Date: {{ formatDate(task.end_date) }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-clock mr-1"></i>
                                                <p>Committed Hours: {{ task.committed_hours }} hrs</p>
                                                <span v-if="task.worked_minutes">| Worked: {{
                                                    formatDuration(task.worked_minutes)
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-wrap justify-end gap-2 mt-4">
                                        <Button label="Details" icon="pi pi-eye" outlined severity="danger"
                                            class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                                        <Button v-if="props.userRole !== 'staff'" label="Work History"
                                            icon="pi pi-clock" outlined severity="info" class="!text-sm !px-3 !py-1.5"
                                            @click="openWorkHistory(task)" />
                                    </div>

                                </div>
                            </transition-group>

                            <!-- Empty State -->
                            <div v-if="filteredTasks(cancelledTasks).length === 0"
                                class="text-center py-6 text-gray-500 italic border-t border-red-200 mt-2">
                                No cancelled tasks available.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Complete Tasks -->
            <div v-if="activeTab === 'Complete'">
                <div class="lg:p-6 p-2">

                    <!-- Nested Tabs -->
                    <div class="flex space-x-4 mb-4 border-b border-gray-200">
                        <button
                            :class="['px-4 py-2 rounded-t-lg font-medium', nestedTab === 'Complete' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700']"
                            @click="nestedTab = 'Complete'">
                            Complete
                        </button>
                        <button
                            :class="['px-4 py-2 rounded-t-lg font-medium', nestedTab === 'Approved' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700']"
                            @click="nestedTab = 'Approved'">
                            Approved
                        </button>
                    </div>

                    <!-- Nested Tab Content -->
                    <div>
                        <!-- Completed Tasks -->
                        <div v-if="nestedTab === 'Complete'" class="lg:p-4 sm:p-0">
                            <div
                                class="bg-gradient-to-r from-green-600 to-green-400 rounded-2xl shadow-lg overflow-hidden">
                                <!-- Header -->
                                <div
                                    class="flex justify-between items-start sm:items-center p-4 text-white gap-2 sm:gap-0">
                                    <h3 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
                                        <i class="pi pi-check text-lg"></i> Completed Tasks
                                    </h3>
                                    <span
                                        class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                                        {{ filteredTasksByStatus('Complete').length }}
                                    </span>
                                </div>

                                <!-- Task List -->
                                <div
                                    :class="['p-4 bg-green-50 transition-all duration-300', { 'max-h-96 overflow-y-auto pr-2': filteredTasksByStatus('Complete').length > 6 }]">
                                    <transition-group name="fade" tag="div" class="space-y-4">
                                        <div v-for="(task, index) in filteredTasksByStatus('Complete')" :key="task.id"
                                            class="rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-300 hover:shadow-lg border-l-4 border-green-500 bg-white text-green-700">

                                            <!-- Title + Status -->
                                            <div
                                                class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                                                <h2 class="font-semibold text-green-800 text-lg sm:text-xl">
                                                    {{ index + 1 }}. {{ task.task.title }}
                                                </h2>
                                                <span v-if="props.userRole === 'admin'" :class="[
                                                    'px-3 py-1 text-xs font-semibold rounded-full border',
                                                    task.status === 'Approved'
                                                        ? 'border-green-600 bg-green-200 text-green-800'
                                                        : 'border-green-300 bg-green-100 text-green-700'
                                                ]">
                                                    {{ task.status }}
                                                </span>
                                                <span v-if="props.userRole === 'employee'" :class="[
                                                    'px-3 py-1 text-xs font-semibold rounded-full border',
                                                    task.status === 'Approved'
                                                        ? 'border-green-600 bg-green-200 text-green-800'
                                                        : 'border-green-300 bg-green-100 text-green-700'
                                                ]">
                                                    Admin Pending
                                                </span>
                                            </div>

                                            <!-- Task Details -->
                                            <div
                                                class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-green-700 font-medium text-sm sm:text-md">
                                                <!-- Left Side -->
                                                <div class="flex flex-col gap-1">
                                                    <p>
                                                        <i class="pi pi-user mr-1"></i>
                                                        Created By:
                                                        <span class="font-semibold">
                                                            <!-- If task status is Staff, show task.created_by -->
                                                            <span v-if="task.status === 'Staff'">
                                                                {{ getUserName(task.created_by) }}
                                                            </span>

                                                            <!-- Otherwise, try task.task.created_by, fallback to task.created_by -->
                                                            <span v-else>
                                                                {{ getUserName(task.task?.created_by) ||
                                                                    getUserName(task.created_by) }}
                                                            </span>
                                                        </span>
                                                    </p>

                                                    <p>
                                                        <i class="pi pi-calendar mr-1"></i>
                                                        Task Created:
                                                        <span>
                                                            {{ formatDateBD(task.created_at) || 'N/A' }}
                                                        </span>
                                                    </p>

                                                    <p>
                                                        <i class="pi pi-shop mr-1"></i>
                                                        Shop:
                                                        <span>
                                                            {{ task.task.shop_name || 'Unknown' }}
                                                        </span>
                                                    </p>

                                                    <p v-if="task.employee">
                                                        <i class="pi pi-users mr-1"></i>
                                                        Assigned To:
                                                        <span class="font-semibold capitalize">
                                                            {{ task.employee.name }} - ({{ task.employee.designation }})
                                                        </span>
                                                    </p>
                                                </div>

                                                <!-- Right Side -->
                                                <div v-if="props.userRole !== 'staff'" class="flex flex-col gap-2">
                                                    <div class="flex items-center gap-2">
                                                        <i class="pi pi-calendar-plus mr-1"></i>
                                                        <p>Client Project Start Date:
                                                            <span
                                                                :class="!task.task.start_date ? 'text-gray-400 italic' : ''">
                                                                {{ formatDate(task.task.start_date) || 'Not Provided' }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <i class="pi pi-calendar-plus mr-1"></i>
                                                        <p>Task Completed:
                                                            <span>
                                                                {{ formatDateBD(task.completed_at) || 'Not Provided' }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <i class="fa-solid fa-hourglass-start"></i>
                                                        <p>Employee Start Date: {{ formatDate(task.start_date) }}</p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <i class="fa-solid fa-hourglass-end"></i>
                                                        <p>Employee End Date: {{ formatDate(task.end_date) }}</p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <i class="pi pi-clock mr-1"></i>
                                                        <p>Committed Hours: {{ task.committed_hours }} hrs</p>
                                                        <span v-if="task.worked_minutes">| Worked: {{
                                                            formatDuration(task.worked_minutes) }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex flex-wrap justify-end gap-2 mt-4">
                                                <Button label="Details" icon="pi pi-eye" outlined severity="success"
                                                    class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                                                <Button v-if="props.userRole === 'admin' && task.status === 'Complete'"
                                                    label="Reissue" icon="pi pi-refresh" severity="warning"
                                                    @click="openReissue(task)" />
                                                <Button v-if="props.userRole === 'admin' && task.status === 'Complete'"
                                                    label="Approve" icon="pi pi-check-circle" severity="success"
                                                    class="!text-sm !px-3 !py-1.5" @click="openApproved(task)" />
                                                <Button v-if="props.userRole === 'admin' && task.status === 'Complete'"
                                                    label="Cancel" icon="pi pi-times-circle" severity="danger"
                                                    class="!text-sm !px-3 !py-1.5" @click="openCancel(task)" />
                                                <Button v-if="props.userRole !== 'staff'" label="Work History"
                                                    icon="pi pi-clock" outlined severity="info"
                                                    class="!text-sm !px-3 !py-1.5" @click="openWorkHistory(task)" />
                                            </div>
                                        </div>
                                    </transition-group>

                                    <!-- Empty State -->
                                    <div v-if="filteredTasksByStatus('Complete').length === 0"
                                        class="text-center py-6 text-gray-500 italic border-t border-green-200 mt-2">
                                        No completed tasks available.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Tasks -->
                        <div v-if="nestedTab === 'Approved'" class="p-4 sm:p-6">
                            <div
                                class="bg-gradient-to-r from-green-600 to-green-400 rounded-2xl shadow-lg overflow-hidden">
                                <!-- Header -->
                                <div
                                    class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 gap-3">
                                    <h3
                                        class="w-full text-lg sm:text-xl font-semibold flex items-center justify-between gap-2 text-white ">
                                        <span>
                                            <i class="pi pi-check-circle text-lg"></i> Approved Tasks
                                        </span>

                                        <span
                                            class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner lg:hidden sm:block">
                                            {{ filteredApprovedTasks.length }}
                                        </span>
                                    </h3>

                                    <!-- Month Filter -->
                                    <div
                                        class="flex items-center gap-2 bg-white/20 px-3 py-2 rounded-xl backdrop-blur-sm w-full sm:w-auto">
                                        <i class="pi pi-calendar text-white text-sm"></i>
                                        <Calendar v-model="selectedMonth" view="month" dateFormat="MM yy"
                                            placeholder="Select Month"
                                            class="w-full sm:w-[160px] bg-transparent border-none text-white placeholder-white" />
                                        <Button icon="pi pi-refresh"
                                            class="!bg-white/30 hover:!bg-white/40 border-none text-white p-2 rounded-lg"
                                            @click="selectedMonth = null" />
                                    </div>

                                    <span
                                        class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner lg:block hidden">
                                        {{ filteredApprovedTasks.length }}
                                    </span>
                                </div>

                                <!-- Task List -->
                                <div
                                    :class="['p-4 bg-green-50 transition-all duration-300', { 'max-h-auto overflow-y-auto pr-2': filteredApprovedTasks.length > 6 }]">
                                    <transition-group name="fade" tag="div" class="space-y-4">
                                        <div v-for="(task, index) in filteredApprovedTasks" :key="task.id"
                                            class="rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-300 hover:shadow-lg border-l-4 border-green-500 bg-white text-green-700">

                                            <!-- Title + Status -->
                                            <div
                                                class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                                                <h2 class="font-semibold text-green-800 text-lg sm:text-xl">
                                                    {{ index + 1 }}. {{ task.task.title }}
                                                </h2>
                                                <span
                                                    class="px-3 py-1 text-xs font-semibold rounded-full border border-green-600 bg-green-200 text-green-800">
                                                    {{ task.status }}
                                                </span>
                                            </div>

                                            <!-- Task Details -->
                                            <div
                                                class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-green-700 font-medium text-sm sm:text-md">
                                                <!-- Left Side -->
                                                <div class="flex flex-col gap-1">
                                                    <p>
                                                        <i class="pi pi-user mr-1"></i>
                                                        Created By:
                                                        <span class="font-semibold">
                                                            <!-- If task status is Staff, show task.created_by -->
                                                            <span v-if="task.status === 'Staff'">
                                                                {{ getUserName(task.created_by) }}
                                                            </span>

                                                            <!-- Otherwise, try task.task.created_by, fallback to task.created_by -->
                                                            <span v-else>
                                                                {{ getUserName(task.task?.created_by) ||
                                                                    getUserName(task.created_by) }}
                                                            </span>
                                                        </span>
                                                    </p>

                                                    <p>
                                                        <i class="pi pi-calendar mr-1"></i>
                                                        Task Created:
                                                        <span>
                                                            {{ formatDateBD(task.created_at) || 'N/A' }}
                                                        </span>
                                                    </p>

                                                    <p>
                                                        <i class="pi pi-shop mr-1"></i>
                                                        Shop:
                                                        <span>
                                                            {{ task.task.shop_name || 'Unknown' }}
                                                        </span>
                                                    </p>

                                                    <p v-if="task.employee">
                                                        <i class="pi pi-users mr-1"></i>
                                                        Assigned To:
                                                        <span class="font-semibold">
                                                            {{ task.employee.name }} - ({{ task.employee.designation }})
                                                        </span>
                                                    </p>
                                                </div>

                                                <!-- Right Side -->
                                                <div class="flex flex-col gap-2">
                                                    <div class="flex items-center gap-2">
                                                        <i class="pi pi-calendar-plus mr-1"></i>
                                                        <p>Client Project Start Date:
                                                            <span
                                                                :class="!task.task.start_date ? 'text-gray-400 italic' : ''">
                                                                {{ formatDate(task.task.start_date) || 'Not Provided'
                                                                }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <i class="fa-solid fa-hourglass-start"></i>
                                                        <p>
                                                            Employee Start Date: {{ formatDate(task.start_date) ||
                                                                'Not Provided'
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <i class="fa-solid fa-hourglass-end"></i>
                                                        <p>
                                                            Employee End Date: {{ formatDate(task.end_date) || 'Not Provided' }}
                                                        </p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <i class="pi pi-clock mr-1"></i>
                                                        <p>Committed Hours: {{ task.committed_hours || 0 }} hrs</p>
                                                        <span v-if="task.worked_minutes">| Worked: {{
                                                            formatDuration(task.worked_minutes) }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex flex-wrap justify-end gap-2 mt-4">
                                                <Button label="Details" icon="pi pi-eye" outlined severity="success"
                                                    class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                                                <Button v-if="props.userRole !== 'staff'" label="Work History"
                                                    icon="pi pi-clock" outlined severity="info"
                                                    class="!text-sm !px-3 !py-1.5" @click="openWorkHistory(task)" />
                                            </div>
                                        </div>
                                    </transition-group>

                                    <!-- Empty State -->
                                    <div v-if="filteredApprovedTasks.length === 0"
                                        class="text-center py-6 text-gray-500 italic border-t border-green-200 mt-2">
                                        No approved tasks found for this month.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- New Tasks -->
            <div v-if="props.userRole === 'admin' && activeTab === 'New'">
                <div class="p-6">
                    <!-- Section Card -->
                    <div class="bg-gradient-to-r from-purple-600 to-purple-400 rounded-2xl shadow-lg overflow-hidden">
                        <!-- Header -->
                        <div class="flex justify-between items-center p-4 text-white">
                            <h3 class="text-xl font-semibold flex items-center gap-2">
                                <i class="pi pi-briefcase text-lg"></i>
                                New Tasks
                            </h3>
                            <span
                                class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                                {{ filteredTasks(newTasks).length }}
                            </span>
                        </div>

                        <!-- Task List -->
                        <div
                            :class="['p-4 bg-purple-50 transition-all duration-300', { 'max-h-auto overflow-y-auto pr-2': newTasks.length > 8 }]">
                            <transition-group name="fade" tag="div" class="space-y-3">
                                <div v-for="(task, index) in filteredTasks(newTasks)" :key="task.id"
                                    class="bg-white border border-purple-200 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-purple-400 transition-all duration-200">
                                    <!-- Title -->
                                    <div class="flex justify-between items-center mb-2">
                                        <h2 class="font-semibold text-purple-800 text-xl">
                                            {{ index + 1 }}. {{ task.task.title }}
                                        </h2>
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 border border-purple-300">
                                            {{ task.task.status }}
                                        </span>
                                    </div>

                                    <!-- Task Details -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-purple-700 font-medium text-md">

                                        <!-- Left Side -->
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <p class="text-gray-500 text-sm mt-2 flex items-center gap-2">
                                                    <i class="pi pi-user text-blue-500"></i>
                                                    <span>Created By: {{ getUserName(task.created_by) }}</span>
                                                    <i class="pi pi-calendar text-purple-500 ml-4"></i>
                                                    <span>Date: {{ formatDate(task.created_at) }}</span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-store"></i>
                                                <p>Shop: {{ task.task.shop_name || 'Unknown' }}</p>
                                            </div>
                                        </div>

                                        <!-- Right Side (Dates + Hours) -->
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar-plus mr-1"></i>
                                                <p>Task Created Date :
                                                    <span>
                                                        {{ formatDateBD(task.created_at) || 'Not Provided' }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar-plus mr-1"></i>
                                                <p>Project Start Date:
                                                    <span :class="!task.task.start_date ? 'text-gray-400 italic' : ''">
                                                        {{ formatDate(task.task.start_date) || 'Not Provided' }}
                                                    </span>
                                                </p>
                                            </div>

                                        </div>

                                    </div>

                                    <!-- Actions -->
                                    <div class="flex justify-end gap-2 mt-4">
                                        <Button label="Details" icon="pi pi-eye" outlined severity="help"
                                            class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                                        <Button label="Assign" icon="pi pi-user-plus" severity="help"
                                            class="!text-sm !px-3 !py-1.5" @click="openAssign(task)" />
                                    </div>
                                </div>
                            </transition-group>

                            <!-- Empty State -->
                            <div v-if="filteredTasks(newTasks).length === 0"
                                class="text-center py-6 text-gray-500 italic border-t border-purple-200 mt-2">
                                No new tasks available.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Tasks -->
            <div v-if="props.userRole === 'admin' && activeTab === 'Staff'">
                <div class="p-6">
                    <!-- Section Card -->
                    <div class="bg-gradient-to-r from-orange-600 to-orange-400 rounded-2xl shadow-lg overflow-hidden">
                        <!-- Header -->
                        <div class="flex justify-between items-center p-4 text-white">
                            <h3 class="text-xl font-semibold flex items-center gap-2">
                                <i class="pi pi-briefcase text-lg"></i>
                                Staff Tasks
                            </h3>
                            <span
                                class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                                {{ filteredTasks(StaffTasks).length }}
                            </span>
                        </div>

                        <!-- Task List -->
                        <div
                            :class="['p-4 bg-orange-50 transition-all duration-300', { 'max-h-auto overflow-y-auto pr-2': tasksForActiveTab.length > 8 }]">
                            <transition-group name="fade" tag="div" class="space-y-3">
                                <div v-for="(task, index) in filteredTasks(StaffTasks)" :key="task.id"
                                    class="bg-white border border-orange-200 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-orange-400 transition-all duration-200">

                                    <!-- Title -->
                                    <div class="flex justify-between items-center mb-2">
                                        <h2 class="font-semibold text-orange-800 text-xl">
                                            {{ index + 1 }}. {{ task.task.title }}
                                        </h2>
                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 border border-orange-300">
                                            {{ task.task.status }}
                                        </span>
                                    </div>
                                    <!-- Task Details -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-orange-700 font-medium text-md">
                                        <!-- Left Side -->
                                        <div class="flex flex-col gap-1">
                                            <p>
                                                <i class="pi pi-user mr-1"></i>
                                                Created By:
                                                <span class="font-semibold">
                                                    <!-- If task status is Staff, show task.created_by -->
                                                    <span v-if="task.status === 'Staff'">
                                                        {{ getUserName(task.created_by) }}
                                                    </span>

                                                    <!-- Otherwise, try task.task.created_by, fallback to task.created_by -->
                                                    <span v-else>
                                                        {{ getUserName(task.task?.created_by) ||
                                                        getUserName(task.created_by) }}
                                                    </span>
                                                </span>
                                            </p>

                                            <p>
                                                <i class="pi pi-calendar mr-1"></i>
                                                Task Created:
                                                <span>
                                                    {{ formatDateBD(task.created_at) || 'N/A' }}
                                                </span>
                                            </p>

                                            <p>
                                                <i class="pi pi-shop mr-1"></i>
                                                Shop:
                                                <span>
                                                    {{ task.task.shop_name || 'Unknown' }}
                                                </span>
                                            </p>
                                        </div>

                                        <!-- Right Side (Dates + Hours) -->
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar-plus mr-1"></i>
                                                <p>Start Date:
                                                    <span :class="!task.task.start_date ? 'text-gray-400 italic' : ''">
                                                        {{ formatDate(task.task.start_date) || 'Not Provided' }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Actions -->
                                    <div class="flex justify-end gap-2 mt-4">
                                        <!-- Hide Work History button for staff -->
                                        <Button label="Details" icon="pi pi-eye" outlined severity="help"
                                            class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                                        <Button label="Assign" icon="pi pi-user-plus" severity="help"
                                            class="!text-sm !px-3 !py-1.5" @click="openAssign(task)" />
                                        <Button v-if="props.userRole !== 'staff'" label="Edit" icon="pi pi-pencil"
                                            severity="warning" class="!text-sm !px-3 !py-1.5"
                                            @click="editEntry(task)" />
                                        <Button label="Notes" icon="pi pi-note" :badge="unreadNotes[task.id]"
                                            badgeClass="bg-red-600 text-white !text-xs !w-5 !h-5 flex items-center justify-center"
                                            class="p-button-sm" @click="openNotesModal(task)" />
                                        <!-- Decision Area -->
                                        <div class="flex items-center gap-2">
                                            <!-- PENDING: show both buttons -->
                                            <template v-if="task.staff_decision === 'Pending'">
                                                <Button label="Approve" icon="pi pi-check" severity="success"
                                                    @click="openApproveModal(task.task)" />

                                                <Button label="Decline" icon="pi pi-times" severity="danger"
                                                    @click="openDeclineModal(task.task)" />
                                            </template>

                                            <!-- APPROVED: show label only -->
                                            <span v-else-if="task.staff_decision === 'Approved'"
                                                class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-semibold text-sm">
                                                ✅ Approved
                                            </span>

                                            <!-- DECLINED: show declined label + allow approve later -->
                                            <template v-else-if="task.staff_decision === 'Declined'">
                                                <span
                                                    class="px-3 py-1 rounded-full bg-red-100 text-red-700 font-semibold text-sm">
                                                    ❌ Declined
                                                </span>

                                                <Button label="Approve" icon="pi pi-check" severity="success"
                                                    @click="openApproveModal(task.task)" />
                                            </template>

                                        </div>
                                    </div>
                                </div>
                            </transition-group>

                            <!-- Empty State -->
                            <div v-if="StaffTasks.length === 0"
                                class="text-center py-6 text-gray-500 italic border-t border-indigo-200 mt-2">
                                No staff tasks available.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today Complete Tasks -->
            <div v-if="activeTab === 'TodayComplete'" class="p-4 sm:p-6">
                <div class="bg-gradient-to-r from-teal-600 to-teal-400 rounded-2xl shadow-lg overflow-hidden">
                    <!-- Header -->
                    <div class="flex justify-between items-start sm:items-center p-4 gap-3 text-white">
                        <h3 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
                            <i class="pi pi-check-circle text-lg"></i> Today Completed
                        </h3>
                        <span
                            class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                            {{ filteredTasks(todayCompleteTasks).length }}
                        </span>
                    </div>

                    <!-- Task List -->
                    <div
                        :class="['p-4 bg-teal-50 transition-all duration-300', { 'max-h-auto sm:max-h-96 overflow-y-auto pr-2': todayCompleteTasks.length > 8 }]">
                        <transition-group name="fade" tag="div" class="space-y-3">
                            <div v-for="(task, index) in filteredTasks(todayCompleteTasks)" :key="task.id"
                                class="rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-300 hover:shadow-lg border-l-4 border-teal-500 bg-white text-teal-700">

                                <!-- Title + Status -->
                                <div
                                    class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2 gap-2">
                                    <h2 class="font-semibold text-teal-800 text-lg sm:text-xl">
                                        {{ index + 1 }}. {{ task.task.title }}
                                    </h2>
                                    <span
                                        class="px-3 py-1 text-xs font-semibold rounded-full border border-teal-300 bg-teal-100">
                                        {{ task.task.status }}
                                    </span>
                                </div>

                                <!-- Task Details -->
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-teal-700 font-medium text-sm sm:text-md">
                                    <!-- Left Side -->
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-store"></i>
                                            <p>Shop: {{ task.task.shop_name || 'Unknown' }}</p>
                                        </div>

                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-user"></i>
                                                <span>Created By:
                                                    <span class="font-semibold"> {{ getUserName(task.task.created_by)
                                                        }}</span>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar "></i>
                                                <span>Task Created Date:
                                                    <span class="font-semibold">{{ formatDateBD(task.task.created_at) }}</span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-user"></i>
                                            <p>
                                                {{ props.userRole === 'admin' ? 'Assigned To:' : 'Assigned By:' }}
                                                <span class="font-semibold">
                                                    {{ props.userRole === 'admin' ? task.employee.name :
                                                        task.assigner.name
                                                    }}
                                                </span>
                                                <small v-if="props.userRole === 'admin'" class="text-gray-500">
                                                    ({{ task.employee.designation }})
                                                </small>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Right Side (Dates + Hours) -->
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-calendar-plus mr-1"></i>
                                            <p>Client Project Start Date:
                                                <span :class="!task.task.start_date ? 'text-gray-400 italic' : ''">
                                                    {{ formatDate(task.task.start_date) || 'Not Provided' }}
                                                </span>
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-hourglass-start"></i>
                                            <p>Employee Start Date: {{ formatDate(task.start_date) }}</p>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-hourglass-end"></i>
                                            <p>Employee End Date: {{ formatDate(task.end_date) }}</p>
                                        </div>


                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-hourglass-end"></i>
                                            <p>Completed Task Date: {{ formatDateBD(task.completed_at) }}</p>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-clock mr-1"></i>
                                            <p>Committed Hours: {{ task.committed_hours }} hrs</p>
                                            <span v-if="task.worked_minutes">
                                                | Worked: {{ formatDuration(task.worked_minutes) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-wrap justify-end gap-2 mt-4">
                                    <Button label="Details" icon="pi pi-eye" outlined
                                        class="!text-sm !px-3 !py-1.5 border border-teal-500 text-teal-700"
                                        @click="openDetails(task)" />
                                    <Button v-if="props.userRole === 'admin'" label="Reissue" icon="pi pi-refresh"
                                        class="bg-teal-700 text-white !text-sm !px-3 !py-1.5"
                                        @click="openReissue(task)" />
                                    <Button label="Work History" icon="pi pi-clock" outlined severity="info"
                                        class="!text-sm !px-3 !py-1.5" @click="openWorkHistory(task)" />
                                </div>
                            </div>
                        </transition-group>

                        <!-- Empty State -->
                        <div v-if="filteredTasks(todayCompleteTasks).length === 0"
                            class="text-center py-6 text-gray-500 italic border-t border-teal-200 mt-2">
                            No tasks completed today.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reissue Tasks -->
            <div v-if="activeTab === 'Reissue'" class="p-4 sm:p-6">
                <div class="bg-gradient-to-r from-pink-600 to-pink-400 rounded-2xl shadow-lg overflow-hidden">

                    <!-- Header -->
                    <div class="flex justify-between items-start sm:items-center p-4 gap-3 text-white">
                        <h3 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
                            <i class="pi pi-refresh text-lg"></i> Reissue Tasks
                        </h3>
                        <span
                            class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                            {{ filteredTasks(reissueTasks).length }}
                        </span>
                    </div>

                    <!-- Task List -->
                    <div
                        :class="['p-4 bg-pink-50 transition-all duration-300', { 'max-h-auto sm:max-h-96 overflow-y-auto pr-2': reissueTasks.length > 8 }]">
                        <transition-group name="fade" tag="div" class="space-y-3">

                            <div v-for="(task, index) in filteredTasks(reissueTasks)" :key="task.id"
                                class="rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-300 hover:shadow-lg border-l-4 border-pink-500 bg-white text-pink-700">

                                <!-- Title + Status -->
                                <div
                                    class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2 gap-2">
                                    <h2 class="font-semibold text-pink-800 text-lg sm:text-xl">
                                        {{ index + 1 }}. {{ task.task.title }}
                                    </h2>
                                    <span
                                        class="px-3 py-1 text-xs font-semibold rounded-full border border-pink-300 bg-pink-100">
                                        {{ task.task.status }}
                                    </span>
                                </div>

                                <!-- Task Details -->
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-pink-700 font-medium text-sm sm:text-md">

                                    <!-- Left Side -->
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-1">
                                            <i class="fa-solid fa-store"></i>
                                            <p>Shop: {{ task.task.shop_name || 'Unknown' }}</p>
                                        </div>

                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-1">
                                                <i class="pi pi-user"></i>
                                                <span>Created By:
                                                    <span class="font-semibold"> {{ getUserName(task.task.created_by)
                                                        }}</span>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <i class="pi pi-calendar "></i>
                                                <span>Task Created Date:
                                                    <span class="font-semibold">{{ formatDateBD(task.created_at)
                                                        }}</span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Only show if user is not staff -->
                                        <div v-if="props.userRole !== 'staff'" class="flex items-center gap-1">
                                            <i class="fa-solid fa-user"></i>
                                            <p>
                                                {{ props.userRole === 'admin' ? 'Assigned To:' : 'Assigned By:' }}
                                                <span class="font-semibold">
                                                    {{ props.userRole === 'admin' ? task.employee.name :
                                                        task.assigner.name
                                                    }}
                                                </span>
                                                <small v-if="props.userRole === 'admin'" class="text-gray-500 font-semibold">
                                                    ({{ task.employee.designation }})
                                                </small>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Right Side (Dates + Hours) -->
                                    <div v-if="props.userRole !== 'staff'" class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-calendar-plus mr-1"></i>
                                            <p>Client Project Start Date: {{ formatDate(task.task.start_date) }}</p>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-calendar-plus mr-1"></i>
                                            <p>Task Re-Assigned :
                                                <span>
                                                    {{ formatDateBD(task.assigned_at) || 'Not Provided' }}
                                                </span>
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-hourglass-start"></i>
                                            <p>Employee Start Date: {{ formatDate(task.start_date) }}</p>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-hourglass-end"></i>
                                            <p>Employee End Date: {{ formatDate(task.end_date) }}</p>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-clock mr-1"></i>
                                            <p>Committed Hours: {{ task.committed_hours }} hrs</p>
                                            <span v-if="task.worked_minutes">
                                                | Worked: {{ formatDuration(task.worked_minutes) }}
                                            </span>
                                        </div>
                                    </div>

                                </div>

                                <!-- Actions -->
                                <div class="flex flex-wrap justify-end gap-2 mt-4">
                                    <Button label="Details" icon="pi pi-eye" outlined severity="danger"
                                        class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                                    <Button v-if="props.userRole === 'employee'" label="Start Working"
                                        icon="pi pi-check" severity="danger" class="!text-sm !px-3 !py-1.5"
                                        @click="changeStatus(task, 'Working')" />
                                    <Button label="Work History" icon="pi pi-clock" outlined severity="info"
                                        class="!text-sm !px-3 !py-1.5" @click="openWorkHistory(task)" />
                                </div>

                            </div>
                        </transition-group>

                        <!-- Empty State -->
                        <div v-if="filteredTasks(reissueTasks).length === 0"
                            class="text-center py-6 text-gray-500 italic border-t border-pink-200 mt-2">
                            No reissue tasks available.
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Tasks -->
            <div v-if="activeTab === 'All' && props.userRole === 'staff'" class="p-4 sm:p-6">
                <div class="bg-gradient-to-r from-blue-600 to-blue-400 rounded-2xl shadow-lg overflow-hidden">

                    <!-- Header -->
                    <div class="flex justify-between items-start sm:items-center p-4 gap-3 text-white">
                        <h3 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
                            <i class="pi pi-list"></i> All Tasks
                        </h3>
                        <span
                            class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                            {{ filteredTasks(staffAllVisibleTasks).length }}
                        </span>
                    </div>

                    <!-- Task List -->
                    <div
                        :class="['p-4 bg-blue-50 transition-all duration-300', { 'max-h-auto sm:max-h-96 overflow-y-auto pr-2': tasksForActiveTab.length > 8 }]">
                        <transition-group name="fade" tag="div" class="space-y-3">
                            <div v-for="(task, index) in staffAllVisibleTasks" :key="task.id" class="rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-300 hover:shadow-lg border-l-4 border-blue-500 bg-white text-blue-700">
                                <div>

                                    <!-- Title + Status -->
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2 gap-2">

                                        <h2 class="font-semibold text-blue-800 text-lg sm:text-xl">
                                            {{ index + 1 }}. {{ task.task.title }}
                                        </h2>

                                        <div class="flex items-center gap-2">
                                            <!-- APPROVED -->
                                            <span v-if="task.staff_decision === 'Approved'"
                                                class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                                ✅ Approved by Admin
                                            </span>

                                            <!-- DECLINED -->
                                            <span v-else-if="task.staff_decision === 'Declined'"
                                                class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                                ❌ Declined by Admin
                                            </span>

                                            <!-- PENDING -->
                                            <div class="flex sm:flex-row items-start sm:items-center gap-2">
                                                <span
                                                    class="px-3 py-1 text-xs font-semibold rounded-full border border-blue-300 bg-blue-100">
                                                    {{ getWorkingBadge(task) }}
                                                </span>

                                                <span v-if="task.staff_decision === 'Pending'"
                                                    class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">
                                                    ⏳ Waiting for Admin Decision
                                                </span>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Task Details -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-blue-700 font-medium text-sm sm:text-md">

                                        <!-- Left Side -->
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-store"></i>
                                                <p>Shop: {{ task.task.shop_name || 'Unknown' }}</p>
                                            </div>
                                        </div>

                                        <!-- Right Side -->
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar-plus mr-1"></i>
                                                <p>Task Created Date :
                                                    <span>
                                                        {{ formatDateBD(task.created_at) || 'Not Provided' }}
                                                    </span>
                                                </p>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar-plus mr-1"></i>
                                                <p>Start Date:
                                                    <span :class="!task.task.start_date ? 'text-gray-400 italic' : ''">
                                                        {{ formatDateBD(task.task.start_date) || 'Not Provided' }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>

                                        <div v-if="task.task.status === 'Working'" class="flex flex-col gap-2">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-calendar-plus mr-1"></i>
                                                <p>
                                                    Start Date: {{ formatDateBD(task.task.task_assignments[0]?.start_date)
                                                        || 'N/A'
                                                    }}
                                                </p>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-calendar-check"></i>
                                                <p>
                                                    End Date: {{ formatDateBD(task.task.task_assignments[0]?.end_date) ||
                                                        'N/A' }}
                                                </p>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-wrap justify-end gap-2 mt-4">
                                        <Button label="Details" icon="pi pi-eye" outlined severity="danger"
                                            class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                                        <!-- Note add modal button -->
                                        <Button label="Notes" icon="pi pi-note" :badge="unreadNotes[task.id]"
                                            badgeClass="bg-red-600 text-white !text-xs !w-5 !h-5 flex items-center justify-center"
                                            class="!text-sm !px-3 !py-1.5 text-white" @click="openNotesModal(task)" />
                                        <Button v-if="props.userRole === 'employee'" label="Start Working"
                                            icon="pi pi-check" severity="danger" class="!text-sm !px-3 !py-1.5"
                                            @click="changeStatus(task, 'Working')" />
                                        <Button v-if="props.userRole !== 'staff'" label="Work History" icon="pi pi-clock"
                                            outlined severity="info" class="!text-sm !px-3 !py-1.5"
                                            @click="openWorkHistory(task)" />
                                    </div>

                                </div>
                            </div>
                        </transition-group>

                        <!-- Empty State -->
                        <div v-if="allStaffTasks.length === 0"
                            class="text-center py-6 text-gray-500 italic border-t border-blue-200 mt-2">
                            No tasks available.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Declined Tasks -->
            <div v-if="activeTab === 'Declined'" class="p-4 sm:p-6">
                <div class="bg-gradient-to-r from-red-600 to-red-400 rounded-2xl shadow-lg overflow-hidden">

                    <!-- Header -->
                    <div class="flex justify-between items-start sm:items-center p-4 gap-3 text-white">
                        <h3 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
                            <i class="pi pi-times-circle"></i> Declined Tasks
                        </h3>
                        <span
                            class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                            {{filteredTasks(allStaffTasks).filter(t => t.staff_decision === 'Declined').length}}
                        </span>
                    </div>

                    <!-- Task List -->
                    <div class="p-4 bg-red-50 transition-all duration-300">
                        <transition-group name="fade" tag="div" class="space-y-3">

                            <div v-for="(task, index) in filteredTasks(allStaffTasks).filter(t => t.staff_decision === 'Declined')"
                                :key="task.id"
                                class="rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-300 hover:shadow-lg border-l-4 border-red-500 bg-white text-red-700">

                                <!-- Title + Status -->
                                <div
                                    class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2 gap-2">
                                    <h2 class="font-semibold text-red-800 text-lg sm:text-xl">
                                        {{ index + 1 }}. {{ task.task.title }}
                                    </h2>

                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                        ❌ Declined by Admin
                                    </span>
                                </div>

                                <!-- Decline Reason -->
                                <p v-if="task.decline_note" class="text-sm text-red-600 italic mb-2">
                                    Reason: {{ task.decline_note }}
                                </p>

                                <!-- Task Details -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm font-medium">

                                    <!-- LEFT -->
                                    <div class="flex flex-col gap-1">
                                        <p>
                                            <i class="pi pi-user mr-1"></i>
                                            Created By:
                                            <span class="font-semibold">
                                                    {{ getUserName(task.created_by) }}
                                            </span>
                                        </p>

                                        <p>
                                            <i class="pi pi-calendar mr-1"></i>
                                            Task Created:
                                            <span>
                                                {{ formatDateBD(task.created_at) || 'N/A' }}
                                            </span>
                                        </p>

                                        <p>
                                            <i class="pi pi-shop mr-1"></i>
                                            Shop:
                                            <span>
                                                {{ task.task.shop_name || 'Unknown' }}
                                            </span>
                                        </p>

                                        <p v-if="task.employee">
                                            <i class="pi pi-users mr-1"></i>
                                            Assigned To:
                                            <span class="font-semibold">
                                                {{ task.employee.name }}
                                            </span>
                                        </p>
                                    </div>

                                    <!-- RIGHT -->
                                    <div class="flex flex-col gap-1">

                                        <p v-if="task.task.start_date">
                                            <i class="pi pi-calendar-plus mr-1"></i>
                                            Start Date:
                                            <span>
                                                {{ formatDate(task.task.start_date) }}
                                            </span>
                                        </p>

                                        <p v-if="task.assigned_at">
                                            <i class="pi pi-clock mr-1"></i>
                                            Assigned At:
                                            <span>
                                                {{ formatDateBD(task.assigned_at) }}
                                            </span>
                                        </p>

                                        <p v-if="task.completed_at">
                                            <i class="pi pi-check-circle mr-1"></i>
                                            Completed At:
                                            <span>
                                                {{ formatDateBD(task.completed_at) }}
                                            </span>
                                        </p>

                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex justify-end gap-2 mt-4">
                                    <Button label="Details" icon="pi pi-eye" outlined severity="danger"
                                        class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />

                                    <Button label="Edit" icon="pi pi-pencil"
                                        severity="warning" class="!text-sm !px-3 !py-1.5"
                                        @click="editEntry(task)" />

                                    <Button label="Notes" icon="pi pi-note" :badge="unreadNotes[task.id]"
                                        badgeClass="bg-red-600 text-white !text-xs !w-5 !h-5 flex items-center justify-center"
                                        class="!text-sm !px-3 !py-1.5" @click="openNotesModal(task)" />

                                    <Button v-if="props.userRole === 'admin'" label="Approve" icon="pi pi-check" severity="success"
                                        @click="openApproveModal(task)" />

                                    <Button label="Declined Trash" icon="pi pi-trash" severity="danger"
                                        class="!text-sm !px-3 !py-1.5" @click="openDeclinedTrashModal(task)"
                                    />
                                </div>

                            </div>

                        </transition-group>

                        <!-- Empty -->
                        <div v-if="filteredTasks(allStaffTasks).filter(t => t.staff_decision === 'Declined').length === 0"
                            class="text-center py-6 text-gray-500 italic border-t border-red-200 mt-2">
                            No declined tasks.
                        </div>

                    </div>
                </div>
            </div>

            <!-- Declined Trash Tasks -->
            <div v-if="activeTab === 'DeclinedTrash'" class="p-4 sm:p-6">
                <div class="bg-gradient-to-r from-gray-700 to-gray-500 rounded-2xl shadow-lg overflow-hidden">

                    <div class="flex justify-between items-center p-4 text-white">
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <i class="pi pi-trash"></i> Declined Trash
                        </h3>
                        <span class="px-3 py-1 rounded-full bg-white/20 text-sm">
                            {{filteredTasks(allStaffTasks).filter(t => t.staff_decision === 'Declined Trash').length}}
                        </span>
                    </div>

                    <div class="p-4 bg-gray-100 space-y-3">
                        <div v-for="(task, index) in filteredTasks(allStaffTasks).filter(t => t.staff_decision === 'Declined Trash')"
                            :key="task.id" class="rounded-xl p-4 bg-white border-l-4 border-gray-600">

                            <h2 class="font-semibold text-gray-800">
                                {{ index + 1 }}. {{ task.task.title }}
                            </h2>

                            <p class="text-sm text-gray-600 italic py-1 pb-2">
                                Trash Reason: {{ task.declined_trash_note }}
                            </p>

                            <!-- Task Details -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm font-medium">

                                <!-- LEFT -->
                                <div class="flex flex-col gap-1">
                                    <p>
                                        <i class="pi pi-user mr-1"></i>
                                        Created By:
                                        <span class="font-semibold">
                                            {{ getUserName(task.created_by) }}
                                        </span>
                                    </p>

                                    <p>
                                        <i class="pi pi-calendar mr-1"></i>
                                        Task Created:
                                        <span>
                                            {{ formatDateBD(task.created_at) || 'N/A' }}
                                        </span>
                                    </p>

                                    <p>
                                        <i class="pi pi-shop mr-1"></i>
                                        Shop:
                                        <span>
                                            {{ task.task.shop_name || 'Unknown' }}
                                        </span>
                                    </p>

                                    <p v-if="task.employee">
                                        <i class="pi pi-users mr-1"></i>
                                        Assigned To:
                                        <span class="font-semibold">
                                            {{ task.employee.name }}
                                        </span>
                                    </p>
                                </div>

                                <!-- RIGHT -->
                                <div class="flex flex-col gap-1">

                                    <p v-if="task.task.start_date">
                                        <i class="pi pi-calendar-plus mr-1"></i>
                                        Start Date:
                                        <span>
                                            {{ formatDate(task.task.start_date) }}
                                        </span>
                                    </p>

                                    <p v-if="task.assigned_at">
                                        <i class="pi pi-clock mr-1"></i>
                                        Assigned At:
                                        <span>
                                            {{ formatDateBD(task.assigned_at) }}
                                        </span>
                                    </p>

                                    <p v-if="task.completed_at">
                                        <i class="pi pi-check-circle mr-1"></i>
                                        Completed At:
                                        <span>
                                            {{ formatDateBD(task.completed_at) }}
                                        </span>
                                    </p>

                                </div>
                            </div>

                            <div class="flex justify-end mt-3">
                                <Button label="Details" icon="pi pi-eye" outlined severity="secondary"
                                    class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                            </div>
                        </div>

                        <div v-if="filteredTasks(allStaffTasks).filter(t => t.staff_decision === 'Declined Trash').length === 0"
                            class="text-center py-6 text-gray-500 italic">
                            No Declined Trash tasks.
                        </div>
                    </div>
                </div>
            </div>


            <!-- Future Tasks -->
            <div v-if="activeTab === 'Future'" class="p-4 sm:p-6">
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-400 rounded-2xl shadow-lg overflow-hidden">

                    <!-- Header -->
                    <div class="flex justify-between items-start sm:items-center p-4 gap-3 text-white">
                        <h3 class="text-lg sm:text-xl font-semibold flex items-center gap-2">
                            <i class="pi pi-clock"></i> Future Tasks
                        </h3>
                        <span
                            class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium backdrop-blur-sm shadow-inner">
                            {{ filteredTasks(futureTasks).length }}
                        </span>
                    </div>

                    <!-- Task List -->
                    <div
                        :class="['p-4 bg-indigo-50 transition-all duration-300', { 'max-h-80 sm:max-h-96 overflow-y-auto pr-2': futureTasks.length > 6 }]">
                        <transition-group name="fade" tag="div" class="space-y-3">
                            <div v-for="(task, index) in filteredTasks(futureTasks)" :key="task.id"
                                class="rounded-2xl shadow-md p-4 sm:p-5 transition-all duration-300 hover:shadow-lg border-l-4 border-indigo-500 bg-white text-indigo-700">

                                <!-- Title + Status -->
                                <div
                                    class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2 gap-2">
                                    <h2 class="font-semibold text-indigo-800 text-lg sm:text-xl">
                                        {{ index + 1 }}. {{ task.task.title }}
                                    </h2>
                                    <span
                                        class="px-3 py-1 text-xs font-semibold rounded-full border border-indigo-300 bg-indigo-100">
                                        {{ task.task.status }}
                                    </span>
                                </div>

                                <!-- Task Details -->
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-indigo-700 font-medium text-sm sm:text-md">

                                    <!-- Left Side -->
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-store"></i>
                                            <p>Shop: {{ task.task.shop_name || 'Unknown' }}</p>
                                        </div>
                                    </div>

                                    <!-- Right Side (Dates + Hours) -->
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-calendar-plus mr-1"></i>
                                            <p>
                                                Client Project Start Date:
                                                <span :class="!task.task.start_date ? 'text-gray-400 italic' : ''">
                                                    {{ formatDate(task.task.start_date) || 'Not Provided' }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-wrap justify-end gap-2 mt-4">
                                    <div class="flex justify-end gap-2 mt-4">
                                        <Button label="Details" icon="pi pi-eye" outlined severity="help"
                                            class="!text-sm !px-3 !py-1.5" @click="openDetails(task)" />
                                        <Button label="Assign" icon="pi pi-user-plus" severity="help"
                                            class="!text-sm !px-3 !py-1.5" @click="openAssign(task)" />
                                        <Button v-if="props.userRole !== 'staff'" label="Edit" icon="pi pi-pencil"
                                            severity="warning" class="!text-sm !px-3 !py-1.5"
                                            @click="editEntry(task)" />
                                    </div>
                                </div>

                            </div>
                        </transition-group>

                        <!-- Empty State -->
                        <div v-if="futureTasks.length === 0"
                            class="text-center py-6 text-gray-500 italic border-t border-indigo-200 mt-2">
                            No future tasks available.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Modal -->
            <Dialog v-model:visible="showNotes" header="Task Notes" :modal="true"
                class="w-full sm:w-3/5 md:w-1/2 lg:w-2/5 p-0 rounded-xl shadow-xl overflow-hidden" :closable="true">

                <!-- Notes List -->
                <div class="space-y-3 max-h-96 overflow-y-auto p-4 bg-gray-50">
                    <div v-for="note in taskNotes" :key="note.id"
                        class="flex justify-between items-start p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 border border-gray-100">

                        <!-- Note Content -->
                        <div class="flex-1 pr-2">
                            <p class="text-gray-800 text-sm">
                                <span class="font-semibold text-indigo-600">{{ note.user.name }}:</span>
                                {{ note.note }}
                            </p>
                            <small class="text-gray-500 italic text-xs font-medium mt-1 block">
                                {{ formatDateBD(note.created_at) }}
                            </small>
                        </div>

                        <!-- Mark as Read -->
                        <button v-if="!note.is_read" @click="markAsRead(note.id)" title="Mark as read" class="w-7 h-7 flex items-center justify-center
                            bg-indigo-100 text-indigo-600 rounded-full
                            hover:bg-indigo-200 hover:text-indigo-800
                            transition shadow-sm">
                            <i class="fa-solid fa-check text-xs"></i>
                        </button>
                    </div>

                    <!-- Mark All as Read -->
                    <div v-if="taskNotes.some(note => !note.is_read)"
                        class="flex justify-end">

                        <button
                            @click="markAllAsRead"
                            class="flex items-center gap-2 px-4 text-sm font-semibold
                                text-indigo-700 rounded-lg hover:text-indigo-900
                                transition-all duration-200 cursor-pointer"
                        >
                            <i class="fa-solid fa-check-double"></i>
                            Mark All as Read
                        </button>
                    </div>

                    <!-- Empty State -->
                    <div v-if="taskNotes.length === 0" class="text-center py-6 text-gray-400 italic">
                        No notes yet. Add your first note below.
                    </div>
                </div>

                <!-- Add New Note -->
                <div class="flex flex-col sm:flex-row gap-3 p-4 border-t border-gray-200 bg-white">
                    <Textarea v-model="newNote" placeholder="Add a note..." rows="3"
                        class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 rounded-lg" />
                    <Button label="Add" icon="pi pi-plus" severity="success" class="!px-6 !py-2 rounded-lg"
                        @click="addNote" />
                </div>
            </Dialog>

            <!-- Approved Note Modal -->
            <Dialog v-model:visible="showApproveModal" header="Approve Task" modal :style="{ width: '30rem' }">
                <Textarea v-model="approveNote" rows="4" placeholder="Write approval note..." class="w-full"/>

                <div class="flex justify-end gap-2 mt-3">
                    <Button label="Cancel" severity="secondary" @click="showApproveModal = false" />
                    <Button label="Submit" severity="success" @click="submitApprove" />
                </div>
            </Dialog>

            <!-- Declined Note Modal -->
            <Dialog v-model:visible="showDeclineModal" header="Decline Task" modal :style="{ width: '30rem' }">
                <Textarea v-model="declineNote" rows="4" placeholder="Write decline reason..." class="w-full" />

                <div class="flex justify-end gap-2 mt-3">
                    <Button label="Cancel" severity="secondary" @click="showDeclineModal = false" />
                    <Button label="Submit" severity="danger" @click="submitDecline" />
                </div>
            </Dialog>

            <!-- Declined Trash Note Modal -->
            <Dialog v-model:visible="showDeclinedTrashModal" modal header="Declined Trash Note" :style="{ width: '30rem' }">
                <Textarea v-model="declinedTrashNote" rows="4" placeholder="Enter trash reason..." class="w-full" />

                <div class="flex justify-end gap-2 mt-4">
                    <Button label="Cancel" severity="secondary" @click="showDeclinedTrashModal = false" />
                    <Button label="Submit Trash" severity="danger" @click="submitDeclinedTrash" />
                </div>
            </Dialog>

            <!-- Task Details Dialog -->
            <Dialog v-model:visible="showDetails" modal header="Task Details" :style="{ width: '50rem' }">
                <div v-if="selectedTask" :class="[
                    'rounded-b-lg shadow-lg p-6 flex flex-col gap-4 transition-all duration-300 ',
                    'max-h-[70vh] overflow-auto wrap-anywhere',
                    {
                        'bg-green-50 border-l-4 border-green-400': selectedTask.status === 'Complete',
                        'bg-blue-50 border-l-4 border-blue-400': selectedTask.status === 'Working',
                        'bg-yellow-50 border-l-4 border-yellow-400': selectedTask.status === 'Pending',
                        'bg-yellow-100 border-l-4 border-yellow-400': selectedTask.status === 'Assigned',
                        'bg-red-50 border-l-4 border-red-400': selectedTask.status === 'Cancelled',
                        'bg-purple-50 border-l-4 border-purple-400': selectedTask.task.status === 'New',
                        'bg-pink-50 border-l-4 border-pink-400': selectedTask.task.status === 'Reissue',
                        'bg-indigo-50 border-l-4 border-indigo-400': selectedTask.status === 'Future',
                        'bg-orange-50 border-l-4 border-orange-400': selectedTask.status === 'Staff'
                    }
                ]">
                    <!-- Title & Status -->
                    <div class="flex justify-between items-center">
                        <h2 :class="{
                            'text-green-800': selectedTask.status === 'Complete',
                            'text-blue-800': selectedTask.status === 'Working',
                            'text-yellow-800': selectedTask.status === 'Pending',
                            'text-yellow-700': selectedTask.status === 'Pending',
                            'text-red-800': selectedTask.status === 'Cancelled',
                            'text-purple-800': selectedTask.status === 'New',
                            'text-pink-800': selectedTask.status === 'Reissue',
                            'text-indigo-800': selectedTask.status === 'Future',
                            'text-orange-800': selectedTask.status === 'Staff',
                        }" class="text-xl font-bold">
                            <span>Title :</span>
                            {{ selectedTask.task.title }}
                        </h2>

                        <span :class="{
                            'bg-green-200 text-green-900': selectedTask.status === 'Complete',
                            'bg-blue-200 text-blue-900': selectedTask.status === 'Working',
                            'bg-yellow-200 text-yellow-900': selectedTask.status === 'Pending',
                            'bg-yellow-300 text-yellow-900': selectedTask.status === 'Assigned',
                            'bg-red-200 text-red-900': selectedTask.status === 'Cancelled',
                            'bg-purple-200 text-purple-900': selectedTask.task.status === 'New',
                            'bg-pink-200 text-pink-900': selectedTask.task.status === 'Reissue',
                            'bg-indigo-200 text-indigo-900': selectedTask.status === 'Future',
                            'bg-orange-200 text-orange-900': selectedTask.status === 'Staff',
                        }" class="px-3 py-1 rounded-full font-semibold text-sm">
                            {{ selectedTask.status }}
                        </span>
                    </div>

                    <!-- Shop -->
                    <div class="flex items-center gap-2">
                        <i class="pi pi-building text-gray-600"></i>
                        <p class="text-gray-700"><strong>Shop Client:</strong> {{ selectedTask.task.shop_name }}</p>
                    </div>

                    <!-- Start Date -->
                    <div class="flex items-center gap-2">
                        <i class="pi pi-calendar text-gray-600"></i>
                        <p class="text-gray-700">
                            <strong>Start Date:</strong> {{ formatDateBD(selectedTask.task.start_date) || 'Not Set' }}
                        </p>
                    </div>

                    <!-- Details -->
                    <div class="flex items-start gap-2">
                        <i class="pi pi-align-left text-gray-600 mt-1"></i>
                        <div class="text-gray-700">
                            <strong>Details:</strong>
                            <div v-html="selectedTask.task.details" class="p-1"></div>
                        </div>
                    </div>

                    <!-- Image -->
                    <div v-if="selectedTask.task.image_url" class="mt-3">
                        <img :src="selectedTask.task.image_url" alt="Task image"
                            class="max-w-full max-h-80 rounded-lg shadow" />

                    </div>

                    <!-- Complete Note -->
                    <div v-if="parsedCompleteNotes.length"
                        class="bg-gray-50 border-l-4 border-green-500 p-4 rounded-lg mt-4 shadow-sm">
                        <h3 class="text-green-800 font-semibold mb-2">Complete Notes History</h3>
                        <ul class="flex flex-col gap-3">
                            <li v-for="(n, index) in parsedCompleteNotes" :key="index"
                                class="bg-white p-3 rounded-lg shadow hover:shadow-md transition flex flex-col sm:flex-row sm:justify-between sm:items-center">
                                <p class="text-gray-700 flex-1">{{ n.note }}</p>
                                <span class="text-gray-500 text-sm mt-1 sm:mt-0">
                                    {{ formatBDDateTime(n.submitted_at) }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <!-- Reissue Comments (Optional) -->
                    <div v-if="parsedReissueComments.length"
                        class="bg-gray-50 border-l-4 border-yellow-500 p-4 rounded-lg mt-4 shadow-sm">
                        <h3 class="text-yellow-800 font-semibold mb-2">Reissue Comments History</h3>
                        <ul class="flex flex-col gap-3">
                            <li v-for="(n, index) in parsedReissueComments" :key="index"
                                class="bg-white p-3 rounded-lg shadow hover:shadow-md transition flex flex-col sm:flex-row sm:justify-between sm:items-center">
                                <p class="text-gray-700 flex-1">{{ n.comment }}</p>
                                <span class="text-gray-500 text-sm mt-1 sm:mt-0">
                                    {{ formatBDDateTime(n.submitted_at) }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="selectedTask.cancelled_note || (userRole === 'staff' && selectedTask.task?.cancelled_note)"
                        class="bg-white/70 border-l-4 border-red-500 p-3 rounded mt-2">
                        <p class="text-red-800 font-medium"><strong>Cancelled Comment:</strong></p>

                        <!-- Show general approved_note -->
                        <p v-if="selectedTask.cancelled_note" class="text-gray-700">{{ selectedTask.cancelled_note }}
                        </p>

                        <!-- Show nested approved_note only for staff -->
                        <p v-if="userRole === 'staff' && selectedTask.task?.cancelled_note" class="text-gray-700">
                            {{ selectedTask.task.cancelled_note }}
                        </p>
                    </div>

                    <div v-if="selectedTask.decline_note || (userRole === 'staff' && selectedTask.decline_note)"
                        class="bg-white/70 border-l-4 border-red-500 p-3 rounded mt-2">
                        <p class="text-red-800 font-medium"><strong>Declined Comment:</strong></p>

                        <!-- Show general approved_note -->
                        <p v-if="selectedTask.decline_note" class="text-gray-700">{{ selectedTask.decline_note }}
                        </p>
                    </div>

                    <div v-if="selectedTask.approve_note || (userRole === 'staff' && selectedTask.task?.approve_note)"
                        class="bg-white/70 border-l-4 border-green-500 p-3 rounded mt-2">
                        <p class="text-green-800 font-medium"><strong>Approved Comment:</strong></p>

                        <!-- Show general approved_note -->
                        <p v-if="selectedTask.approve_note" class="text-gray-700">{{ selectedTask.approve_note }}
                        </p>
                    </div>

                    <div v-if="selectedTask.declined_trash_note || (userRole === 'staff' && selectedTask.task?.declined_trash_note)"
                        class="bg-white/70 border-l-4 border-red-500 p-3 rounded mt-2">
                        <p class="text-red-800 font-medium"><strong>Declined Trash Note:</strong></p>

                        <!-- Show general declined_trash_note -->
                        <p v-if="selectedTask.declined_trash_note" class="text-gray-700">{{ selectedTask.declined_trash_note }}
                        </p>
                    </div>

                    <div v-if="selectedTask.approved_note || (userRole === 'staff' && selectedTask.task?.approved_note)"
                        class="bg-white/70 border-l-4 border-red-500 p-3 rounded mt-2">

                        <p class="text-red-800 font-medium"><strong>Approved Comment:</strong></p>

                        <!-- Show general approved_note -->
                        <p v-if="selectedTask.approved_note" class="text-gray-700">{{ selectedTask.approved_note }}</p>

                        <!-- Show nested approved_note only for staff -->
                        <p v-if="userRole === 'staff' && selectedTask.task?.approved_note" class="text-gray-700">
                            {{ selectedTask.task.approved_note }}
                        </p>

                    </div>
                </div>
            </Dialog>

            <!-- Reissue Dialog -->
            <Dialog v-model:visible="showReissueDialog" modal header="Reissue Task" :style="{ width: '35rem' }">
                <div class="space-y-4">
                    <p class="text-gray-700">Add your reissue comment below:</p>
                    <Textarea v-model="reissueComment" rows="4" class="w-full"
                        placeholder="Enter reason for reissue..." />
                    <div class="flex justify-end gap-3">
                        <Button label="Cancel" severity="secondary" @click="showReissueDialog = false" />
                        <Button label="Reissue Task" severity="warning" @click="submitReissue" />
                    </div>
                </div>
            </Dialog>


            <!-- Cancelled Dialog -->
            <Dialog v-model:visible="showCancelDialog" modal header="Cancel Task" :style="{ width: '35rem' }">
                <div class="space-y-4">
                    <p class="text-gray-700">Add your cancelled comment below:</p>
                    <Textarea v-model="cancelComment" rows="4" class="w-full"
                        placeholder="Enter reason for cancelled..." />
                    <div class="flex justify-end gap-3">
                        <Button label="Cancel" severity="secondary" @click="showCancelDialog = false" />
                        <Button label="Cancelled Task" severity="warning" @click="submitCancelled" />
                    </div>
                </div>
            </Dialog>

            <!-- Approved Dialog -->
            <Dialog v-model:visible="showApprovedDialog" modal header="Approved Task" :style="{ width: '35rem' }">
                <div class="space-y-4">
                    <p class="text-gray-700">Add your approved comment below:</p>
                    <Textarea v-model="approvedComment" rows="4" class="w-full" placeholder="Enter the approved note" />
                    <div class="flex justify-end gap-3">
                        <Button label="Cancel" severity="secondary" @click="showApprovedDialog = false" />
                        <Button label="Approved Task" severity="warning" @click="submitApproved" />
                    </div>
                </div>
            </Dialog>

            <Dialog v-model:visible="showAssignDialog" modal header="Assign Task" :style="{ width: '35rem' }">
                <div class="space-y-4">
                    <p class="text-gray-700">Assign task to an employee and set committed hours:</p>

                    <!-- Employee Dropdown -->
                    <div>
                        <label class="font-semibold block mb-2">Employee</label>
                        <Multiselect v-model="assignedEmployee" :options="employeeOptions" label="name" track-by="id"
                            placeholder="Select Employee" :loading="loadingEmployees">
                            <!-- Custom template for showing name, designation, and mobile -->
                            <template #option="{ option }">
                                <div class="flex flex-col">
                                    <span class="font-semibold">{{ option.name }}</span>
                                    <span class="text-sm text-gray-500">
                                        {{ option.designation ? option.designation : 'No Designation' }}
                                        • {{ option.mobile ? option.mobile : 'No Number' }}
                                    </span>
                                </div>
                            </template>

                            <!-- Custom label when selected -->
                            <template #singleLabel="{ option }">
                                <div>
                                    {{ option.name }}
                                    <span class="text-sm text-gray-500">
                                        ({{ option.designation || 'No Designation' }})
                                    </span>
                                </div>
                            </template>
                        </Multiselect>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label class="font-semibold block mb-2">Start Date</label>
                        <Calendar v-model="startDate" dateFormat="yy-mm-dd" showIcon class="w-full" />
                    </div>

                    <!-- End Date -->
                    <div>
                        <label class="font-semibold block mb-2">End Date</label>
                        <Calendar v-model="endDate" dateFormat="yy-mm-dd" showIcon class="w-full" />
                    </div>

                    <!-- Committed Hours -->
                    <div>
                        <label class="font-semibold block mb-2">Committed Hours</label>
                        <InputText v-model.number="committedHours" type="number" min="1"
                            placeholder="Enter committed hours" class="w-full" />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3 mt-4">
                        <Button label="Cancel" severity="secondary" @click="showAssignDialog = false" />
                        <Button label="Assign Task" severity="success" @click="submitAssign" />
                    </div>
                </div>
            </Dialog>

            <Dialog v-model:visible="showCompleteDialog" modal header="Complete Task" :style="{ width: '35rem' }">
                <div class="space-y-4">
                    <p class="text-gray-700">Add a note for completing this task:</p>
                    <Textarea v-model="completeNote" rows="4" class="w-full"
                        placeholder="Enter work complete note..." />
                    <div class="flex justify-end gap-3">
                        <Button label="Cancel" severity="secondary" @click="showCompleteDialog = false" />
                        <Button label="Submit" severity="success" @click="submitCompleteTask" />
                    </div>
                </div>
            </Dialog>

            <!-- Task Edit Dialog -->
            <Dialog v-model:visible="showEditDialog" header="Edit Task" modal :style="{ width: '550px' }">

                <!-- Shop Name Multiselect -->
                <!-- <div class="mb-3">
                    <label class="font-medium">Shop Name</label>
                    <Multiselect v-model="editSelectedShop" :options="clientOptions" label="name" track-by="id"
                        placeholder="Search shop name..." :searchable="true" @search-change="onClientSearch" />
                </div> -->

                <!-- Title -->
                <div class="mb-3">
                    <label class="font-medium">Title</label>
                    <InputText v-model="editForm.title" class="w-full" />
                </div>

                <!-- Details Editor -->
                <div class="mb-3">
                    <label class="font-medium">Details</label>
                    <Editor v-model="editForm.details" :style="{ height: '200px' }" />
                </div>

                <!-- Start Date -->
                <div class="mb-3 mt-20">
                    <label class="font-medium">Start Date</label>
                    <Calendar v-model="editForm.start_date" dateFormat="yy-mm-dd" class="w-full" />
                </div>

                <!-- Image Upload -->
                <div class="mb-4">
                    <label class="font-medium">Image</label>
                    <input type="file" class="w-full mt-1 border border-[#cbd5e1] rounded p-2"
                        @change="handleEditImage" />

                    <div v-if="editImagePreview" class="mt-3">
                        <img :src="editImagePreview" class="h-24 rounded shadow" />
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex justify-end gap-2 mt-4">
                    <Button label="Cancel" severity="secondary" @click="showEditDialog = false" />
                    <Button label="Update" severity="primary" @click="submitEdit" />
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

            <!--HISTORY  MODAL -->
            <Dialog v-model:visible="showHistoryModal" modal :header="modalTitle" :style="{ width: '50rem' }">

                <div class="max-h-96 overflow-y-auto space-y-4">
                    <div v-if="historyData.length" class="space-y-4">
                        <div v-for="(item, idx) in historyData" :key="idx" class="relative pl-8">
                            <!-- Timeline Dot -->
                            <div class="absolute left-0 top-1.5 w-3 h-3 bg-blue-600 rounded-full"></div>
                            <!-- Timeline Line -->
                            <div v-if="idx !== historyData.length - 1"
                                class="absolute left-1.5 top-6 w-0.5 h-full bg-gray-300">
                            </div>

                            <!-- History Card -->
                            <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm text-gray-500">{{ new Date(item.created_at).toLocaleString()
                                    }}</span>

                                    <span class="text-sm font-semibold text-blue-600">
                                        {{ getStaffName(item.staff_id) }}
                                    </span>
                                </div>

                                <div v-if="item.note && item.note.trim()"
                                    class="text-gray-800 whitespace-pre-line mb-2">
                                    <strong>Note:</strong> {{ item.note }}
                                </div>

                                <div v-if="item.old_data && Object.keys(item.old_data).length"
                                    class="bg-gray-50 p-2 rounded border-l-4 border-gray-300 text-sm text-gray-600">
                                    <strong>Changed Fields:</strong>
                                    <ul class="list-disc list-inside mt-1">
                                        <li v-for="(value, key) in item.old_data" :key="key" class="flex gap-2">
                                            <span class="font-medium capitalize">
                                                {{ key.replace(/_/g, ' ') }}:
                                            </span>

                                            <!-- HTML content -->
                                            <span v-if="typeof value === 'string' && value.includes('<')"
                                                v-html="formatHistoryValue(key, value)"
                                                class="prose prose-sm max-w-none"></span>

                                            <!-- Normal text / formatted date -->
                                            <span v-else class="text-gray-700">
                                                {{ formatHistoryValue(key, value) }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center text-gray-500 py-8">
                        No history available
                    </div>
                </div>

                <div class="text-right mt-6">
                    <button
                        class="px-5 py-2 bg-blue-600 text-white font-medium rounded-md shadow hover:bg-blue-700 transition"
                        @click="showHistoryModal = false">
                        Close
                    </button>
                </div>
            </Dialog>

        </div>
        </template>
    </AppLayout>
</template>


<style scoped>
@keyframes pulse-ring {
    0% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
    }

    70% {
        box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
    }

    100% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
    }
}

.animate-pulse-ring {
    animation: pulse-ring 1.5s infinite;
}
</style>
