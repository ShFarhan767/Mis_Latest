<script setup lang="ts">
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Password settings', href: edit().url },
];

const password = ref('');
const confirmPassword = ref('');
const passwordScore = ref(0); // 0 to 3
const passwordsMatch = ref(true);

// Compute password score
watch(password, (val) => {
    let score = 0;
    if (val.length >= 6) score++; // basic length
    if (/[A-Z]/.test(val) && /\d/.test(val)) score++; // medium: uppercase + number
    if (/[\W_]/.test(val) && val.length >= 8) score++; // strong: special char + long
    passwordScore.value = score;
});

// Watch confirm password
watch([password, confirmPassword], () => {
    passwordsMatch.value = password.value === confirmPassword.value;
});

// Individual segment colors
const segmentColors = computed(() => {
    return [
        passwordScore.value >= 1 ? 'bg-red-500' : 'bg-gray-300',
        passwordScore.value >= 2 ? 'bg-yellow-400' : 'bg-gray-300',
        passwordScore.value >= 3 ? 'bg-green-500' : 'bg-gray-300',
    ];
});

// Strength text
const strengthText = computed(() => {
    switch (passwordScore.value) {
        case 1: return 'Weak';
        case 2: return 'Medium';
        case 3: return 'Strong';
        default: return '';
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Password settings" />

        <SettingsLayout>
            <div class="space-y-6 p-6 shadow-lg bg-gray-50 rounded-xl max-w-lg mx-auto">
                <HeadingSmall title="Update password"
                    description="Ensure your account is using a long, random password to stay secure" />

                <Form v-bind="PasswordController.update.form()" :options="{ preserveScroll: true }" reset-on-success
                    :reset-on-error="['password', 'password_confirmation', 'current_password']" class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }">

                    <!-- Current password -->
                    <div class="grid gap-1">
                        <Label for="current_password">Current password</Label>
                        <Input id="current_password" name="current_password" type="password" class="mt-1 block w-full"
                            autocomplete="current-password" placeholder="Current password" />
                        <InputError :message="errors.current_password" />
                    </div>

                    <!-- New password -->
                    <div class="grid gap-1 relative">
                        <Label for="password">New password</Label>
                        <Input id="password" name="password" type="password" v-model="password"
                            class="mt-1 block w-full" autocomplete="new-password" placeholder="New password" />
                        <InputError :message="errors.password" />

                        <!-- Advanced Strength Bar -->
                        <div class="flex mt-2 h-2 w-full gap-1">
                            <div v-for="(color, index) in segmentColors" :key="index" :class="color"
                                class="flex-1 rounded-full transition-all duration-300"></div>
                        </div>
                        <div class="mt-1 text-sm font-medium" :class="segmentColors[passwordScore.value - 1]">
                            {{ strengthText }}
                        </div>
                    </div>

                    <!-- Confirm password -->
                    <div class="grid gap-1 relative">
                        <Label for="password_confirmation">Confirm password</Label>
                        <Input id="password_confirmation" name="password_confirmation" type="password"
                            v-model="confirmPassword" class="mt-1 block w-full" autocomplete="new-password"
                            placeholder="Confirm password" />
                        <InputError :message="errors.password_confirmation" />
                        <div v-if="confirmPassword.length" class="mt-1 text-sm font-medium" :class="{
                            'text-green-600': passwordsMatch,
                            'text-red-600': !passwordsMatch
                        }">
                            {{ passwordsMatch ? 'Passwords match' : 'Passwords do not match' }}
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="processing" class="bg-blue-600 hover:bg-blue-700">Save
                            password</Button>
                        <p v-if="recentlySuccessful" class="text-sm text-green-600 font-medium">Saved successfully!</p>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
