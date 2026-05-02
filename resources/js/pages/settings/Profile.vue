<script setup lang="ts">
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
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
            <div class="rounded-3xl bg-gradient-to-r from-cyan-500 via-sky-500 to-emerald-500 p-[1px] shadow-[0_18px_55px_-28px_rgba(14,165,233,0.85)]">
                <div class="rounded-3xl border border-white/40 bg-white/85 p-5 backdrop-blur md:p-7">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <HeadingSmall title="Profile information" description="Update your name, email, and mobile number" />

                        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white/70 px-4 py-3 shadow-sm">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500 to-emerald-500 text-sm font-bold text-white">
                                {{ String(user?.name ?? 'U').slice(0, 1).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ user?.name ?? 'User' }}</p>
                                <p class="truncate text-xs text-slate-500">{{ user?.email ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="mt-6 space-y-6">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <!-- Name -->
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="form.name" required placeholder="Full name" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <!-- Mobile -->
                            <div class="grid gap-2">
                                <Label for="mobile">Mobile number</Label>
                                <Input id="mobile" v-model="form.mobile" required placeholder="Mobile number" />
                                <InputError :message="form.errors.mobile" />
                            </div>

                            <!-- Email -->
                            <div class="grid gap-2 md:col-span-2">
                                <Label for="email">Email address</Label>
                                <Input id="email" type="email" v-model="form.email" required placeholder="Email address" />
                                <InputError :message="form.errors.email" />
                            </div>
                        </div>

                        <!-- Email verification -->
                        <div v-if="mustVerifyEmail && !user.email_verified_at" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                            <p class="text-sm text-amber-900">
                                Your email address is unverified.
                                <Link :href="route('verification.send')" as="button" class="font-semibold underline underline-offset-2">
                                    Click here to resend verification email
                                </Link>
                            </p>
                            <div v-if="page.props.status === 'verification-link-sent'" class="mt-2 text-sm font-semibold text-emerald-700">
                                A new verification link has been sent to your email address.
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p v-if="form.recentlySuccessful" class="text-sm font-semibold text-emerald-700">Saved.</p>
                            <Button type="submit" :disabled="form.processing" class="min-w-28">
                                {{ form.processing ? 'Saving...' : 'Save changes' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
