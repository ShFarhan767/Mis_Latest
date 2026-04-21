<script setup lang="ts">
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import DeleteUser from '@/components/DeleteUser.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage();
const user = page.props.auth.user;

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Profile settings', href: '/settings/profile' },
];

const form = useForm({
    name: user.name || '',
    email: user.email || '',
    mobile: user.mobile || '',
});

const submit = () => {
    form.patch(route('profile.update'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6 shadow-lg p-5 bg-gray-100 rounded-lg">
                <HeadingSmall title="Profile information" description="Update your name, email, and mobile number" />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" required placeholder="Full name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input id="email" type="email" v-model="form.email" required placeholder="Email address" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Mobile -->
                    <div class="grid gap-2">
                        <Label for="mobile">Mobile Number</Label>
                        <Input id="mobile" v-model="form.mobile" required placeholder="Mobile number" />
                        <InputError :message="form.errors.mobile" />
                    </div>

                    <!-- Email verification -->
                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link :href="route('verification.send')" as="button" class="text-foreground underline">Click
                            here to resend verification email</Link>
                        </p>
                        <div v-if="page.props.status === 'verification-link-sent'" class="mt-2 text-sm text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">Save</Button>
                        <p v-if="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
