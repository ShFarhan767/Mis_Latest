<script setup lang="ts">
import { ref, onMounted } from "vue";
import type { HTMLAttributes } from "vue";
import axios from "axios";

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes["class"];
}

defineProps<Props>();

const logoUrl = ref<string | null>(null);

const fetchLogo = async () => {
    try {
        const { data } = await axios.get("/api/logos");

        if (data.length > 0) {
            // take latest logo
            logoUrl.value = "/" + data[0].logo_path;
        }
    } catch (e) {
        console.error("Failed to load logo", e);
    }
};

onMounted(fetchLogo);
</script>

<template>
    <img
        v-if="logoUrl"
        :src="logoUrl"
        alt="Logo"
        class="h-14"
    />
</template>
