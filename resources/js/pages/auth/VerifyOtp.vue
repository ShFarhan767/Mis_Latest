<script setup lang="ts">
import { useForm, Head } from "@inertiajs/vue3";
import AuthLayout from "@/layouts/AuthLayout.vue";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import InputError from "@/components/InputError.vue";
import { onMounted } from "vue";

const props = defineProps({
    identifier: String,
});

const form = useForm({
    identifier: props.identifier || "",
    otp: "",
});
</script>

<template>
    <AuthLayout title="Verify OTP" description="Enter the 6-digit OTP sent to your email or phone">

        <Head title="Verify OTP" />

        <form @submit.prevent="form.post('/verify-otp')" class="grid gap-4">
            <div>
                <Label for="otp" class="mb-2">OTP Code</Label>
                <Input id="otp" v-model="form.otp" type="text" maxlength="6" placeholder="Enter your OTP" />
                <InputError :message="form.errors.otp" />
            </div>

            <input type="hidden" v-model="form.identifier" />

            <Button :disabled="form.processing" class="w-full">
                <span v-if="!form.processing">Verify OTP</span>
                <span v-else>Verifying...</span>
            </Button>
        </form>
    </AuthLayout>
</template>
