<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { register } from '@/routes'
import { store } from '@/routes/login'
import { request } from '@/routes/password'
import { Form, Head } from '@inertiajs/vue3'
import { LoaderCircle } from 'lucide-vue-next'
import AppLogoIcon from '@/components/AppLogoIcon.vue'

defineProps<{
    status?: string
    canResetPassword: boolean
    showLogo?: boolean
}>()
</script>

<template>

    <Head title="Log in" />

    <div class="relative min-h-screen bg-cover bg-center bg-no-repeat flex items-center justify-center px-4 py-10"
        style="background-image: url('/images/login_bg/login.jpg')">
        <div class="absolute inset-0 bg-black/55"></div>

        <!-- Center wrapper -->
        <div class="relative w-full max-w-[26rem]">
            <!-- Logo ABOVE the card (outside) -->
            <div class="flex justify-center mb-5">
                <div class="rounded-2xl backdrop-blur-md shadow-xl px-6 py-4">
                    <AppLogoIcon class="h-12 w-auto" />
                </div>
            </div>

            <!-- Card -->
            <div class="rounded-2xl bg-white/90 backdrop-blur-md shadow-2xl border border-white/30 p-8">
                <h1 class="text-2xl font-semibold tracking-tight text-center text-gray-900">Log in</h1>
                <p class="mt-2 text-sm text-muted-foreground text-center">
                    Enter your email and password below to log in
                </p>

                <div v-if="status" class="mt-4 text-center text-sm font-medium text-green-600">
                    {{ status }}
                </div>

                <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }"
                    class="mt-6 flex flex-col gap-6">
                    <div class="grid gap-6">
                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input id="email" type="email" name="email" required autofocus :tabindex="1"
                                autocomplete="email" placeholder="email@example.com" />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label for="password">Password</Label>
                                <TextLink v-if="canResetPassword" :href="request()" class="text-sm" :tabindex="5">
                                    Forgot password?
                                </TextLink>
                            </div>
                            <Input id="password" type="password" name="password" required :tabindex="2"
                                autocomplete="current-password" placeholder="Password" />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="flex items-center justify-between">
                            <Label for="remember" class="flex items-center space-x-3">
                                <Checkbox id="remember" name="remember" :tabindex="3" />
                                <span>Remember me</span>
                            </Label>
                        </div>

                        <Button type="submit" class="mt-2 w-full" :tabindex="4" :disabled="processing"
                            data-test="login-button">
                            <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                            Log in
                        </Button>
                    </div>

                    <div class="text-center text-sm text-muted-foreground">
                        Don't have an account?
                        <TextLink :href="register()" :tabindex="5">Sign up</TextLink>
                    </div>
                </Form>
            </div>
        </div>
    </div>
</template>
