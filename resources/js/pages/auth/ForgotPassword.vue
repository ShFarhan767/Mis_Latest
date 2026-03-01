<script setup lang="ts">
import { useForm, Head } from "@inertiajs/vue3";
import AuthLayout from "@/layouts/AuthLayout.vue";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import InputError from "@/components/InputError.vue";

const form = useForm({
    identifier: "",
});
</script>

<template>
    <AuthLayout title="Forgot Password" description="Enter your email or mobile number">

        <Head title="Forgot Password" />

        <form @submit.prevent="form.post('/forgot-password')" class="grid gap-4">
            <div>
                <Label for="identifier" class="mb-2">Email or Mobile</Label>
                <Input id="identifier" v-model="form.identifier" placeholder="Enter email or phone" />
                <InputError :message="form.errors.identifier" />
            </div>

            <Button :disabled="form.processing" class="w-full">
                <span v-if="!form.processing">Send OTP</span>
                <span v-else>Sending...</span>
            </Button>
        </form>
    </AuthLayout>
</template>
