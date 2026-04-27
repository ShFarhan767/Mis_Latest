<script setup lang="ts">
import { ref, watch, computed, nextTick, onMounted, onBeforeUnmount } from "vue";
import axios from "axios";
import Dialog from "primevue/dialog";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";

const toast = useToast();

const props = defineProps<{
    visible: boolean;
    customerId: number | null;
    customerName?: string;
    unreadCount?: number;
}>();

const emit = defineEmits<{
    (e: "update:visible", v: boolean): void;
    (e: "marked-read", payload: { customerId: number | null }): void;
}>();

const notes = ref<any[]>([]);
const loading = ref(false);
const sending = ref(false);
const markingRead = ref(false);
const message = ref("");
const currentUser = ref<any | null>(null);
const scrollEl = ref<HTMLElement | null>(null);
const pollTimer = ref<ReturnType<typeof setInterval> | null>(null);
const lastSeenNoteId = ref<number>(0);
const audioCtx = ref<AudioContext | null>(null);

const title = computed(() => props.customerName ? `Demo Notes — ${props.customerName}` : "Demo Notes");
const unreadCount = computed(() => Number(props.unreadCount ?? 0));

const safeGetNotes = (data: any): any[] | null => {
    if (Array.isArray(data?.notes)) return data.notes;
    if (Array.isArray(data)) return data;
    return null;
};

const fetchCurrentUser = async () => {
    if (currentUser.value) return;
    try {
        const { data } = await axios.get("/api/current-user", {
            headers: { Accept: "application/json" },
        });
        currentUser.value = data;
    } catch {
        currentUser.value = null;
    }
};

const ensureAudioUnlocked = async () => {
    try {
        if (!audioCtx.value) {
            const Ctx = (window as any).AudioContext || (window as any).webkitAudioContext;
            if (!Ctx) return;
            audioCtx.value = new Ctx();
        }
        if (audioCtx.value?.state === "suspended") await audioCtx.value.resume();
    } catch {
        // ignore
    }
};

const unlockHandler = () => void ensureAudioUnlocked();
onMounted(() => {
    // Ensure sound can work even when the new message arrives from polling (not a user gesture).
    // This "primes" audio on the user's first interaction anywhere.
    window.addEventListener("pointerdown", unlockHandler, { once: true, capture: true });
});

onBeforeUnmount(() => {
    window.removeEventListener("pointerdown", unlockHandler, true);
    stopPolling();
});

const playNotifySound = async () => {
    // Try to resume/unlock once; if still blocked by autoplay policies, fail silently.
    await ensureAudioUnlocked();
    const ctx = audioCtx.value;
    if (!ctx) return;
    if (ctx.state !== "running") return;

    const oscillator = ctx.createOscillator();
    const gain = ctx.createGain();
    oscillator.type = "sawtooth";
    oscillator.frequency.value = 880;
    gain.gain.setValueAtTime(0.0001, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.18);
    oscillator.connect(gain);
    gain.connect(ctx.destination);
    oscillator.start();
    oscillator.stop(ctx.currentTime + 0.2);
};

const stopPolling = () => {
    if (pollTimer.value) {
        clearInterval(pollTimer.value);
        pollTimer.value = null;
    }
};

const fetchNotes = async (opts?: { playSound?: boolean; silent?: boolean }) => {
    if (!props.customerId) return;
    const silent = opts?.silent ?? false;
    if (!silent) loading.value = true;
    try {
        const prevMaxId = lastSeenNoteId.value || 0;
        const { data } = await axios.get(`/api/customers/${props.customerId}/demo-notes`, {
            headers: { Accept: "application/json" },
        });
        const incoming = safeGetNotes(data);
        if (!incoming) {
            if (!silent) {
                const isHtml = typeof data === "string" && data.includes("<");
                toast.add({
                    severity: "error",
                    summary: "Notes Error",
                    detail: isHtml
                        ? "Your session may have expired. Please refresh and try again."
                        : "Unexpected response from server while loading notes.",
                    life: 4000,
                });
            }
            return;
        }
        notes.value = incoming;

        const maxId = incoming.reduce((m: number, n: any) => Math.max(m, Number(n?.id || 0)), 0);
        const hasNewFromOther =
            (opts?.playSound ?? false) &&
            maxId > prevMaxId &&
            incoming.some((n: any) => Number(n?.id || 0) > prevMaxId && n?.user_id !== currentUser.value?.id);

        lastSeenNoteId.value = Math.max(prevMaxId, maxId);
        if (hasNewFromOther) void playNotifySound();

        await nextTick();
        if (scrollEl.value) scrollEl.value.scrollTop = scrollEl.value.scrollHeight;
    } catch (error: any) {
        if (silent) return;

        if (error?.response?.status === 403) {
            toast.add({
                severity: "error",
                summary: "Forbidden",
                detail: "You don't have permission to view these demo notes.",
                life: 3000,
            });
            return;
        }

        toast.add({
            severity: "error",
            summary: "Error",
            detail: error?.response?.data?.message || "Failed to load demo notes.",
            life: 3000,
        });
        notes.value = [];
    } finally {
        if (!silent) loading.value = false;
    }
};

const markAsRead = async () => {
    if (!props.customerId || markingRead.value || unreadCount.value === 0) return;

    markingRead.value = true;
    try {
        await axios.put(`/api/customers/${props.customerId}/demo-notes/mark-read`);
        emit("marked-read", { customerId: props.customerId });
        toast.add({
            severity: "success",
            summary: "Marked Read",
            detail: "Demo note notifications cleared.",
            life: 2000,
        });
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error?.response?.data?.message || "Failed to mark demo notes as read.",
            life: 3000,
        });
    } finally {
        markingRead.value = false;
    }
};

const send = async () => {
    if (!props.customerId) return;
    const text = message.value.trim();
    if (!text) return;

    sending.value = true;
    try {
        const { data } = await axios.post(`/api/customers/${props.customerId}/demo-notes`, {
            message: text,
        });
        if (data?.note) notes.value.push(data.note);
        message.value = "";
        lastSeenNoteId.value = Math.max(lastSeenNoteId.value || 0, Number(data?.note?.id || 0));
        await nextTick();
        if (scrollEl.value) scrollEl.value.scrollTop = scrollEl.value.scrollHeight;
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

watch(
    () => props.visible,
    async (v) => {
        if (!v) {
            stopPolling();
            return;
        }
        lastSeenNoteId.value = 0;
        await fetchCurrentUser();
        // This runs on modal open; actual audio unlocking may require a user click inside the modal.
        await ensureAudioUnlocked();
        await fetchNotes({ playSound: false, silent: false });
        stopPolling();
        pollTimer.value = setInterval(() => void fetchNotes({ playSound: true, silent: true }), 3000);
    }
);

watch(
    () => props.customerId,
    async (id) => {
        if (!props.visible || !id) return;
        lastSeenNoteId.value = 0;
        await fetchNotes({ playSound: false, silent: false });
    }
);

const isMine = (note: any) => {
    return currentUser.value?.id && note?.user_id === currentUser.value.id;
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
</script>

<template>
    <Dialog
        :header="title"
        :visible="visible"
        modal
        :style="{ width: '56rem', maxWidth: '96vw' }"
        @show="ensureAudioUnlocked"
        @update:visible="emit('update:visible', $event)"
    >
        <Toast />

        <div class="h-[72vh] flex flex-col" @mousedown="ensureAudioUnlocked" @touchstart="ensureAudioUnlocked">
            <!-- Messages -->
            <div class="flex-1 overflow-hidden rounded-3xl border border-slate-200 bg-white flex flex-col">
                <div class="border-b border-slate-100 px-5 py-3 flex items-center justify-between">
                    <div class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-900">
                        <span><i class="pi pi-comments mr-2" />Note Chat History</span>
                        <span
                            v-if="unreadCount > 0"
                            class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-[11px] font-semibold text-red-700"
                        >
                            Unread: {{ unreadCount }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-2xl bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="markingRead || unreadCount === 0"
                            @click="markAsRead"
                        >
                            {{ markingRead ? "Marking..." : "Mark Read" }}
                        </button>
                        <div class="text-xs text-slate-500">Enter to send · Shift+Enter for new line</div>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden bg-gradient-to-b from-slate-50 to-white px-4 py-4">
                    <div ref="scrollEl" class="h-full overflow-y-auto pr-2">
                        <div v-if="loading" class="text-sm text-slate-500 text-center py-14">Loading...</div>

                        <div v-else-if="notes.length === 0" class="text-sm text-slate-500 text-center py-14">
                            No messages yet.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="n in notes"
                                :key="n.id"
                                class="flex items-end gap-2"
                                :class="isMine(n) ? 'justify-end' : 'justify-start'"
                            >
                                <div v-if="!isMine(n)" class="h-9 w-9 shrink-0 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-slate-700 font-semibold">
                                    {{ (n.user?.name || 'U').trim().slice(0,1).toUpperCase() }}
                                </div>

                                <div
                                    class="max-w-[78%] rounded-3xl px-4 py-3 shadow-sm border"
                                    :class="isMine(n) ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-800 border-slate-200'"
                                >
                                    <div class="mb-1 flex items-center justify-between gap-4 text-[11px] opacity-90">
                                        <span class="font-semibold truncate">
                                            {{ isMine(n) ? 'You' : (n.user?.name || 'User') }}
                                            <span class="font-normal opacity-80" v-if="n.user?.role">({{ n.user.role }})</span>
                                        </span>
                                        <span class="shrink-0 opacity-80">{{ formatTime(n.created_at) }}</span>
                                    </div>
                                    <div class="text-sm whitespace-pre-line leading-relaxed">{{ n.message }}</div>
                                </div>

                                <div v-if="isMine(n)" class="h-9 w-9 shrink-0 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-semibold">
                                    {{ (currentUser?.name || 'Y').trim().slice(0,1).toUpperCase() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Composer (fixed, modal doesn't scroll) -->
            <div class="shrink-0 mt-4 rounded-3xl border border-slate-200 bg-white px-4 py-4">
                <div class="flex gap-2">
                    <div class="flex-1 rounded-3xl border border-slate-200 bg-white shadow-sm focus-within:ring-2 focus-within:ring-purple-300">
                        <textarea
                            v-model="message"
                            rows="2"
                            class="w-full resize-none rounded-3xl bg-transparent px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none"
                            placeholder="Write a message..."
                            @focus="ensureAudioUnlocked"
                            @keydown.enter.exact.prevent="send"
                        />
                    </div>
                    <button
                        class="inline-flex items-center justify-center rounded-3xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 transition disabled:opacity-60 disabled:cursor-not-allowed"
                        :disabled="sending || !message.trim()"
                        type="button"
                        @click="send"
                    >
                        <i class="pi pi-send mr-2" />
                        {{ sending ? "Sending..." : "Send" }}
                    </button>
                    <button
                        class="px-5 py-3 bg-slate-900 text-white rounded-3xl hover:bg-slate-800 transition"
                        type="button"
                        @click="emit('update:visible', false)"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Dialog>
</template>
