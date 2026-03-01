<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    code: {
        type: [String, Number],
        default: 403,
    },
    message: {
        type: String,
        default: 'You are not authorized to access this page.',
    },
})

// Generate 50 random stars
const stars = Array.from({ length: 50 }, () => ({
    top: Math.random() * 100 + '%',
    left: Math.random() * 100 + '%',
    size: Math.random() * 2 + 1 + 'px',
    delay: Math.random() * 5 + 's',
    duration: Math.random() * 3 + 2 + 's'
}))

// Generate 10 shooting stars
const shootingStars = Array.from({ length: 10 }, () => ({
    top: Math.random() * 80 + '%',
    left: Math.random() * 100 + '%',
    delay: Math.random() * 10 + 's'
}))
</script>

<template>
    <div
        class="relative flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-950 text-white overflow-hidden px-4">

        <!-- Stars -->
        <div v-for="(s, i) in stars" :key="i" class="absolute rounded-full bg-white opacity-70" :style="{
            top: s.top,
            left: s.left,
            width: s.size,
            height: s.size,
            animation: `twinkle ${s.duration}s infinite alternate`,
            animationDelay: s.delay
        }"></div>

        <!-- Shooting Stars -->
        <div v-for="(s, i) in shootingStars" :key="'shoot-' + i"
            class="absolute w-1 h-1 bg-white rounded-full animate-shoot" :style="{
                top: s.top,
                left: s.left,
                animationDelay: s.delay
            }"></div>

        <!-- Floating Nebula Circles -->
        <div class="absolute w-96 h-96 bg-purple-600/20 rounded-full animate-float-slow -top-32 -left-32"></div>
        <div class="absolute w-72 h-72 bg-blue-600/20 rounded-full animate-float-fast -bottom-32 -right-32"></div>

        <!-- Central Illustration / Icon -->
        <div class="mb-6 animate-bounce-slow">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-28 h-28 text-red-500 drop-shadow-lg" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.5 10.5V7.5a4.5 4.5 0 00-9 0v3m-2.25 0A2.25 2.25 0 003 12.75v7.5A2.25 2.25 0 005.25 22.5h13.5A2.25 2.25 0 0021 20.25v-7.5A2.25 2.25 0 0018.75 10.5H5.25z" />
            </svg>
        </div>

        <!-- Error Code -->
        <h1 class="text-6xl md:text-7xl font-extrabold mb-4 text-center opacity-0 animate-fade-in delay-100">
            {{ code }}
        </h1>

        <!-- Message -->
        <p class="text-gray-300 text-lg md:text-xl text-center max-w-md opacity-0 animate-fade-in delay-300">
            {{ message }}
        </p>

        <!-- Go Home Button -->
        <Link href="/"
            class="mt-8 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl text-white font-semibold shadow-lg hover:scale-105 transition-transform opacity-0 animate-fade-in delay-500">
        Go Home
        </Link>
    </div>
</template>

<style scoped>
/* Fade-in animation */
@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(10px);
    }

    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.8s forwards;
}

.delay-100 {
    animation-delay: 0.1s;
}

.delay-300 {
    animation-delay: 0.3s;
}

.delay-500 {
    animation-delay: 0.5s;
}

/* Floating background circles */
@keyframes floatSlow {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-20px);
    }
}

@keyframes floatFast {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-40px);
    }
}

.animate-float-slow {
    animation: floatSlow 6s ease-in-out infinite;
}

.animate-float-fast {
    animation: floatFast 4s ease-in-out infinite;
}

/* Bounce Icon */
@keyframes bounceSlow {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-10px);
    }
}

.animate-bounce-slow {
    animation: bounceSlow 2s ease-in-out infinite;
}

/* Twinkle stars */
@keyframes twinkle {
    0% {
        opacity: 0.2;
    }

    50% {
        opacity: 1;
    }

    100% {
        opacity: 0.2;
    }
}

/* Shooting stars */
@keyframes shoot {
    0% {
        transform: translate(0, 0) rotate(45deg);
        opacity: 1;
    }

    100% {
        transform: translate(400px, -200px) rotate(45deg);
        opacity: 0;
    }
}

.animate-shoot {
    width: 2px;
    height: 2px;
    border-radius: 50%;
    background: white;
    position: absolute;
    animation: shoot 2s linear infinite;
}
</style>
