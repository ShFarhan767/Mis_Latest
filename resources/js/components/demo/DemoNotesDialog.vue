<script setup lang="ts">
import { ref, watch, computed, nextTick } from "vue";
import axios from "axios";
import Dialog from "primevue/dialog";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";

const toast = useToast();

const props = defineProps<{
    visible: boolean;
    customerId: number | null;
    customerName?: string;
}>();

const emit = defineEmits<{
    (e: "update:visible", v: boolean): void;
}>();

const notes = ref<any[]>([]);
const loading = ref(false);
const sending = ref(false);
const message = ref("");
const currentUser = ref<any | null>(null);
const scrollEl = ref<HTMLElement | null>(null);
const pollTimer = ref<ReturnType<typeof setInterval> | null>(null);
const lastSeenNoteId = ref<number>(0);
const audioCtx = ref<AudioContext | null>(null);

const title = computed(() => props.customerName ? `Demo Notes — ${props.customerName}` : "Demo Notes");

const fetchCurrentUser = async () => {
    if (currentUser.value) return;
    try {
        const { data } = await axios.get("/api/current-user");
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

const playNotifySound = async () => {
    await ensureAudioUnlocked();
    const ctx = audioCtx.value;
    if (!ctx) return;

    const oscillator = ctx.createOscillator();
    const gain = ctx.createGain();
    oscillator.type = "sine";
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

const fetchNotes = async (opts?: { playSound?: boolean; silent?: boolean; markRead?: boolean }) => {
    if (!props.customerId) return;
    const silent = opts?.silent ?? false;
    const markRead = opts?.markRead ?? true;
    if (!silent) loading.value = true;
    try {
        const prevMaxId = lastSeenNoteId.value || 0;
        const { data } = await axios.get(`/api/customers/${props.customerId}/demo-notes`);
        const incoming = Array.isArray(data?.notes) ? data.notes : [];
        notes.value = incoming;
        if (markRead) {
            try {
                await axios.put(`/api/customers/${props.customerId}/demo-notes/mark-read`);
            } catch {
                // ignore
            }
        }

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
        await axios.put(`/api/customers/${props.customerId}/demo-notes/mark-read`);
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
        await ensureAudioUnlocked();
        await fetchNotes({ playSound: false, silent: false, markRead: true });
        stopPolling();
        pollTimer.value = setInterval(() => void fetchNotes({ playSound: true, silent: true, markRead: false }), 3000);
    }
);

watch(
    () => props.customerId,
    async (id) => {
        if (!props.visible || !id) return;
        lastSeenNoteId.value = 0;
        await fetchNotes({ playSound: false, silent: false, markRead: true });
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
        :style="{ width: '46rem', maxWidth: '95vw' }"
        @update:visible="emit('update:visible', $event)"
    >
        <Toast />

        <div ref="scrollEl" class="h-[22rem] overflow-y-auto rounded-xl border border-gray-200 bg-gray-50 p-4">
            <div v-if="loading" class="text-sm text-gray-500 text-center py-10">Loading...</div>

            <div v-else-if="notes.length === 0" class="text-sm text-gray-500 text-center py-10">
                No messages yet.
            </div>

            <div v-else class="space-y-3">
                <div v-for="n in notes" :key="n.id" class="flex" :class="isMine(n) ? 'justify-end' : 'justify-start'">
                    <div
                        class="max-w-[75%] rounded-2xl px-4 py-3 shadow-sm border"
                        :class="isMine(n) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-800 border-gray-200'"
                    >
                        <div class="text-xs opacity-90 mb-1 flex items-center justify-between gap-3">
                            <span class="font-semibold">
                                {{ isMine(n) ? 'You' : (n.user?.name || 'User') }}
                                <span class="font-normal opacity-80" v-if="n.user?.role">({{ n.user.role }})</span>
                            </span>
                            <span class="opacity-80">{{ formatTime(n.created_at) }}</span>
                        </div>
                        <div class="text-sm whitespace-pre-line">{{ n.message }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex gap-2">
            <textarea
                v-model="message"
                rows="2"
                class="flex-1 border rounded-xl p-3 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                placeholder="Write a message..."
                @keydown.enter.exact.prevent="send"
            />
            <button
                class="px-5 py-2 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition disabled:opacity-60"
                :disabled="sending || !message.trim()"
                type="button"
                @click="send"
            >
                {{ sending ? "Sending..." : "Send" }}
            </button>
        </div>
        <p class="text-xs text-gray-500 mt-2">Tip: Press Enter to send, Shift+Enter for new line.</p>
    </Dialog>
</template>
