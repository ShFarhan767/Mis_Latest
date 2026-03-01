<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const form = useForm({
    mobile: '',
    password: '',
});

const page = usePage();
const name = page.props.name;
const quote = page.props.quote || {
    message: "Teamwork makes the dream work.",
    author: "John C. Maxwell"
};

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>

    <Head title="Employee Login" />

    <!-- Fullscreen container -->
    <div class="h-screen w-screen flex flex-col md:flex-row overflow-hidden bg-white">
        <!-- Left: Image Section (hidden on mobile) -->
        <div class="hidden md:flex w-1/2 relative overflow-hidden flex-col">
            <img src="/images/login_bg/login.jpg" alt="Team Work" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-[#58b8de]/30"></div>

            <!-- Quote at the bottom (optional) -->
            <!--<div v-if="quote" class="absolute bottom-10 left-10 z-20 text-white max-w-xs">
                <blockquote class="space-y-2">
                    <p class="text-lg font-medium">&ldquo;{{ quote.message }}&rdquo;</p>
                    <footer class="text-sm text-white/70 font-light mt-1">
                        — {{ quote.author }}
                    </footer>
                </blockquote>
            </div> -->
        </div>

        <!-- Right: Login Section -->
        <div
            class="flex-1 flex flex-col justify-center items-center bg-gradient-to-br from-[#eaf6fb] via-white to-[#f0faff] relative lg:p-0 p-4">

            <div class="flex justify-center mb-4">
                <img src="/images/logo/logo.png" alt="" class="h-12">
            </div>

            <!-- Soft glowing circle -->
            <div class="absolute top-20 -left-24 w-96 h-96 bg-[#58b8de]/10 rounded-full blur-3xl pointer-events-none hidden sm:block">
            </div>

            <!-- Unified Card: Welcome + Form -->
            <div class="relative z-10 w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">

                <!-- Welcome Section -->
                <div class="p-6 text-center border-b border-gray-100">
                    <h2 class="text-4xl font-semibold text-[#58b8de]">Welcome Back</h2>
                    <p class="text-gray-500 mt-2">
                        Sign in to access your account dashboard
                    </p>
                </div>

                <!-- Login Form -->
                <form @submit.prevent="form.post(route('employee.login.store'))" class="space-y-6 p-8">
                    <div class="flex flex-col space-y-1">
                        <label for="mobile" class="text-gray-700 font-medium">Mobile Number</label>
                        <input v-model="form.mobile" type="text" id="mobile" placeholder="Enter your mobile number"
                            required
                            class="w-full px-5 py-3 rounded-xl border border-gray-200 shadow-sm focus:ring-4 focus:ring-[#58b8de]/40 focus:outline-none transition-all" />
                    </div>

                    <div class="flex flex-col space-y-1">
                        <label for="password" class="text-gray-700 font-medium">Password</label>
                        <input v-model="form.password" type="password" id="password" minlength="5" placeholder="Enter your password"
                            required
                            class="w-full px-5 py-3 rounded-xl border border-gray-200 shadow-sm focus:ring-4 focus:ring-[#58b8de]/40 focus:outline-none transition-all" />
                        <div class="text-right mt-2">
                            <Link href="/forgot-password" class="text-sm text-[#58b8de] hover:underline">Forgot
                            Password?</Link>
                        </div>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full py-3 rounded-xl bg-[#58b8de] hover:bg-[#4aa8cd] text-white font-semibold shadow-lg transition-transform transform hover:scale-[1.02]">
                        Log In
                    </button>

                    <p v-if="form.errors.message" class="text-red-600 text-center text-sm">
                        {{ form.errors.message }}
                    </p>
                </form>

            </div>
        </div>
    </div>
</template>

<style scoped>
html,
body {
    margin: 0;
    padding: 0;
    overflow: hidden;
    /* Prevent scroll */
    height: 100%;
}

.blur-3xl {
    filter: blur(80px);
}
</style>
