<script setup lang="ts">
import { ref, watch } from "vue";
import axios from "axios";
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";


const props = defineProps<{ clientId: number | null, visible: boolean }>();
const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void,
    (e: 'note-added'): void
}>();

const toast = useToast();
const visible = ref(false);
const newNoteContent = ref("");

const addNote = async () => {
    if (!props.clientId || !newNoteContent.value.trim()) return;
    try {
        await axios.post("/api/notes", { client_id: props.clientId, content: newNoteContent.value });
        newNoteContent.value = "";
        emit("note-added");
        emit("update:visible", false); // close modal
    } catch (error: any) {
        toast.add({ severity: "error", summary: "Error", detail: error.response?.data?.message || "Failed to add note", life: 3000 });
    }
};
</script>

<template>
    <Dialog v-model:visible="props.visible" header="Add Note" modal :style="{ width: '40vw', maxWidth: '500px' }">
        <div class="flex gap-2">
            <input v-model="newNoteContent" type="text" placeholder="Enter note..." class="border p-2 rounded w-full" />
            <Button label="Add" icon="pi pi-plus" class="p-button-sm p-button-success" @click="addNote" />
        </div>
    </Dialog>
</template>
