<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <header>
        <h2 class="mb-0.5 text-lg font-semibold">{{ title }}</h2>
        <p v-if="description" class="text-base text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
