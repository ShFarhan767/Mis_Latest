<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLogoIcon from "@/components/AppLogoIcon.vue";
import { route } from "ziggy-js";

const form = useForm({
    mobile: "",
    password: "",
});

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>

    <Head title="Employee Login" />

    <!-- Fullscreen background with centered form -->
    <div class="min-h-screen w-full flex items-center justify-center px-4 py-10 bg-cover bg-center bg-no-repeat relative"
        style="background-image: url('/images/login_bg/login.jpg')">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/55"></div>

        <!-- Card -->
        <div
            class="relative w-full max-w-md rounded-2xl bg-white/90 backdrop-blur-md shadow-2xl border border-white/30">
            <div class="p-8">
                <div class="flex justify-center mb-4">
                    <AppLogoIcon class="h-12" />
                </div>

                <form @submit.prevent="form.post(route('employee.login.store'))" class="mt-8 space-y-5">
                    <div class="space-y-1">
                        <label for="mobile" class="text-sm font-medium text-gray-700">Mobile Number</label>
                        <input v-model="form.mobile" id="mobile" type="text" placeholder="Enter your mobile number"
                            required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white shadow-sm focus:outline-none focus:ring-4 focus:ring-sky-200 focus:border-sky-400 transition" />
                    </div>

                    <div class="space-y-1">
                        <label for="password" class="text-sm font-medium text-gray-700">Password</label>
                        <input v-model="form.password" id="password" type="password" minlength="5"
                            placeholder="Enter your password" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white shadow-sm focus:outline-none focus:ring-4 focus:ring-sky-200 focus:border-sky-400 transition" />

                        <div class="text-right mt-2">
                            <Link href="/forgot-password" class="text-sm text-sky-600 hover:underline">
                                Forgot Password?
                            </Link>
                        </div>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full py-3 rounded-xl bg-gray-900 hover:bg-gray-800 text-white font-semibold shadow-lg transition disabled:opacity-60 disabled:cursor-not-allowed">
                        {{ form.processing ? "Logging in..." : "Log In" }}
                    </button>

                    <p v-if="form.errors.message" class="text-red-600 text-center text-sm">
                        {{ form.errors.message }}
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>
