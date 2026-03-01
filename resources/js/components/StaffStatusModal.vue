<template>
    <Dialog :header="`Update Staff Status for ${customer?.name || ''}`" :visible="localVisible" modal class="w-96"
        :closable="true" @hide="close">
        <div class="flex flex-col gap-4">
            <label class="font-semibold text-gray-700">Select Staff Status</label>
            <Dropdown v-model="selectedStatus" :options="options" optionLabel="label" placeholder="Select Status"
                class="w-full" />

            <div class="flex justify-end mt-4 gap-2">
                <Button label="Cancel" severity="secondary" outlined @click="close" />
                <Button label="Update" severity="success" @click="saveStatus" :disabled="!selectedStatus" />
            </div>
        </div>
    </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, defineProps, defineEmits } from "vue";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import Dialog from "primevue/dialog";

const props = defineProps<{
    visible: boolean;
    customer: any;
    options: string[] | { label: string; value: string }[];
}>();

const emit = defineEmits<{
    (e: "update", customerId: number, newStatus: string): void;
    (e: "update:visible", value: boolean): void;
}>();

// 👈 Mirror prop locally
const localVisible = ref(props.visible);
watch(
    () => props.visible,
    (val) => {
        localVisible.value = val;
    }
);

const selectedStatus = ref<string | null>(null);

// Initialize selected status when modal opens
watch(
    () => props.customer,
    (newVal) => {
        selectedStatus.value = newVal?.staff_status || null;
    },
    { immediate: true }
);

// Close modal
const close = () => {
    localVisible.value = false;
    emit("update:visible", false);
};

// Save status and emit
const saveStatus = () => {
    if (!props.customer || !selectedStatus.value) return;

    emit("update", props.customer.id, selectedStatus.value);
    close();
};
</script>


<style scoped>
/* Optional: adjust dialog width on small screens */
@media (max-width: 640px) {
    .p-dialog.w-96 {
        width: 90% !important;
    }
}
</style>