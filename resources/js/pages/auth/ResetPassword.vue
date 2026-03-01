<script setup lang="ts">
import { useForm, Head } from "@inertiajs/vue3";
import AuthLayout from "@/layouts/AuthLayout.vue";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import InputError from "@/components/InputError.vue";

const props = defineProps({
    identifier: String,
});

const form = useForm({
    identifier: props.identifier || "",
    password: "",
    password_confirmation: "",
});
</script>

<template>
    <AuthLayout title="Reset Password" description="Set your new password">

        <Head title="Reset Password" />

        <form @submit.prevent="form.post('/reset-password')" class="grid gap-4">
            <p class="text-center text-gray-700 mb-2">
                Changing password for:
                <strong class="text-blue-600">{{ props.identifier }}</strong>
            </p>

            <div>
                <Label for="password" class="mb-2">New Password</Label>
                <Input id="password" v-model="form.password" type="password" placeholder="Enter new password" />
                <InputError :message="form.errors.password" />
            </div>

            <div>
                <Label for="password_confirmation" class="mb-2">Confirm Password</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password"
                    placeholder="Confirm password" />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button :disabled="form.processing" class="w-full">
                <span v-if="!form.processing">Reset Password</span>
                <span v-else>Saving...</span>
            </Button>
        </form>
    </AuthLayout>
</template>
