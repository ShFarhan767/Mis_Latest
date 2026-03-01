<script setup lang="ts">
import { ref } from 'vue'
import { Instagram, Facebook, Twitter, Youtube } from 'lucide-vue-next'

const form = ref({
    name: '',
    email: '',
    subject: '',
    message: ''
})

const handleSubmit = () => {
    alert('Thanks for reaching out! We’ll get back to you shortly.')
    form.value = { name: '', email: '', subject: '', message: '' }
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Hero Section -->
        <section
            class="relative bg-gradient-to-r from-red-600 via-red-500 to-red-400 text-white py-20 px-6 md:px-12 text-center overflow-hidden">
            <div
                class="absolute inset-0 backdrop-blur-[1px] opacity-20 bg-[url('/images/pattern.svg')] bg-cover bg-center">
            </div>
            <div class="relative z-10 max-w-3xl mx-auto">
                <h1 class="text-5xl font-extrabold mb-4 drop-shadow-md">Get in Touch with Us</h1>
                <p class="text-lg text-white/90 mb-6">We'd love to hear from you — whether it’s about your order,
                    feedback, or partnership.</p>
                <div class="h-1 w-24 bg-white mx-auto rounded-full"></div>
            </div>
        </section>

        <!-- Store Info Cards -->
        <section class="max-w-7xl mx-auto px-6 mt-20 grid grid-cols-1 md:grid-cols-3 gap-10">
            <!-- Card 1: Store -->
            <div
                class="relative bg-white/30 backdrop-blur-lg rounded-[2rem] p-10 shadow-2xl hover:shadow-2xl transform transition-all duration-500 hover:-translate-y-4 hover:rotate-1 group cursor-pointer overflow-hidden">
                <div
                    class="absolute -top-10 -right-10 w-32 h-32 bg-gradient-to-tr from-red-500 to-red-400 rounded-full opacity-30 blur-3xl transition-transform duration-500 group-hover:scale-125">
                </div>
                <div
                    class="w-20 h-20 flex items-center justify-center mx-auto mb-5 rounded-full bg-gradient-to-tr from-red-600 to-red-400 text-white shadow-lg group-hover:scale-110 transition-transform duration-500">
                    <!-- Location SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 11c1.656 0 3-1.344 3-3S13.656 5 12 5 9 6.344 9 8s1.344 3 3 3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 2C8.134 2 5 5.134 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.866-3.134-7-7-7z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-900 mb-3 relative inline-block">
                    Our Store
                    <span
                        class="absolute left-0 -bottom-1 w-0 h-1 bg-red-600 rounded-full transition-all duration-500 group-hover:w-full"></span>
                </h3>
                <p class="text-gray-700 text-base leading-relaxed text-center">
                    123 Fashion Avenue, <br />New York, NY 10001
                </p>
            </div>

            <!-- Card 2: Call -->
            <div
                class="relative bg-white/30 backdrop-blur-lg rounded-[2rem] p-10 shadow-2xl hover:shadow-2xl transform transition-all duration-500 hover:-translate-y-4 hover:-rotate-1 group cursor-pointer overflow-hidden">
                <div
                    class="absolute -top-12 -left-10 w-32 h-32 bg-gradient-to-tr from-red-500 to-red-400 rounded-full opacity-30 blur-3xl transition-transform duration-500 group-hover:scale-125">
                </div>
                <div
                    class="w-20 h-20 flex items-center justify-center mx-auto mb-5 rounded-full bg-gradient-to-tr from-red-600 to-red-400 text-white shadow-lg group-hover:scale-110 transition-transform duration-500">
                    <!-- Phone SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5.5a2 2 0 012-2h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V9a1 1 0 01-1 1H6a2 2 0 00-2 2v2c0 .265.105.52.293.707l2.414 2.414a1 1 0 01.293.707V18a2 2 0 01-2 2H5a2 2 0 01-2-2V5.5z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-900 mb-3 relative inline-block">
                    Call Us
                    <span
                        class="absolute left-0 -bottom-1 w-0 h-1 bg-red-600 rounded-full transition-all duration-500 group-hover:w-full"></span>
                </h3>
                <p class="text-gray-700 text-base leading-relaxed text-center">+1 (800) 123-4567</p>
                <p class="text-gray-700 text-base leading-relaxed text-center">Mon - Fri: 9am - 8pm</p>
            </div>

            <!-- Card 3: Email -->
            <div
                class="relative bg-white/30 backdrop-blur-lg rounded-[2rem] p-10 shadow-2xl hover:shadow-2xl transform transition-all duration-500 hover:-translate-y-4 hover:rotate-2 group cursor-pointer overflow-hidden">
                <div
                    class="absolute -bottom-10 -right-12 w-32 h-32 bg-gradient-to-tr from-red-500 to-red-400 rounded-full opacity-30 blur-3xl transition-transform duration-500 group-hover:scale-125">
                </div>
                <div
                    class="w-20 h-20 flex items-center justify-center mx-auto mb-5 rounded-full bg-gradient-to-tr from-red-600 to-red-400 text-white shadow-lg group-hover:scale-110 transition-transform duration-500">
                    <!-- Email SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 12H8m0 0l4 4m0-4l-4-4M21 8v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h14a2 2 0 012 2z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-900 mb-3 relative inline-block">
                    Email
                    <span
                        class="absolute left-0 -bottom-1 w-0 h-1 bg-red-600 rounded-full transition-all duration-500 group-hover:w-full"></span>
                </h3>
                <p class="text-gray-700 text-base leading-relaxed text-center">support@stylehub.com</p>
                <p class="text-gray-700 text-base leading-relaxed text-center">info@stylehub.com</p>
            </div>
        </section>

        <!-- Map & Form -->
        <section class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 px-6 mt-20 items-center">
            <!-- Map -->
            <div class="rounded-3xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-500">
                <iframe class="w-full h-[400px] border-0" style="filter: grayscale(15%) contrast(110%) brightness(95%);"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3023.464267340743!2d-74.00601568459446!3d40.71277597933027!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a316d8b0a93%3A0x93b80b3d6a46ec79!2sNew%20York%20City!5e0!3m2!1sen!2sus!4v1690574356562!5m2!1sen!2sus">
                </iframe>
            </div>

            <!-- Form -->
            <div class="bg-white p-8 rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500">
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Send us a Message</h2>
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <div class="relative">
                        <input v-model="form.name" type="text" id="name" required
                            class="peer w-full border-b-2 border-gray-300 bg-transparent py-3 focus:border-red-500 focus:outline-none placeholder-transparent"
                            placeholder="Name" />
                        <label for="name"
                            class="absolute left-0 -top-3.5 text-gray-600 text-sm transition-all peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base peer-placeholder-shown:top-3 peer-focus:-top-3.5 peer-focus:text-sm peer-focus:text-red-600">
                            Name
                        </label>
                    </div>

                    <div class="relative">
                        <input v-model="form.email" type="email" id="email" required
                            class="peer w-full border-b-2 border-gray-300 bg-transparent py-3 focus:border-red-500 focus:outline-none placeholder-transparent"
                            placeholder="Email" />
                        <label for="email"
                            class="absolute left-0 -top-3.5 text-gray-600 text-sm transition-all peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base peer-placeholder-shown:top-3 peer-focus:-top-3.5 peer-focus:text-sm peer-focus:text-red-600">
                            Email
                        </label>
                    </div>

                    <div class="relative">
                        <input v-model="form.subject" type="text" id="subject"
                            class="peer w-full border-b-2 border-gray-300 bg-transparent py-3 focus:border-red-500 focus:outline-none placeholder-transparent"
                            placeholder="Subject" />
                        <label for="subject"
                            class="absolute left-0 -top-3.5 text-gray-600 text-sm transition-all peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base peer-placeholder-shown:top-3 peer-focus:-top-3.5 peer-focus:text-sm peer-focus:text-red-600">
                            Subject
                        </label>
                    </div>

                    <div class="relative">
                        <textarea v-model="form.message" id="message" rows="5" required
                            class="peer w-full border-b-2 border-gray-300 bg-transparent py-3 focus:border-red-500 focus:outline-none placeholder-transparent resize-none"
                            placeholder="Message"></textarea>
                        <label for="message"
                            class="absolute left-0 -top-3.5 text-gray-600 text-sm transition-all peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base peer-placeholder-shown:top-3 peer-focus:-top-3.5 peer-focus:text-sm peer-focus:text-red-600">
                            Message
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-red-600 to-red-500 text-white py-3 rounded-xl font-semibold shadow-lg hover:shadow-2xl hover:scale-[1.02] transition-all duration-300">
                        Send Message
                    </button>
                </form>
            </div>
        </section>

        <!-- CTA Section -->
        <section
            class="mt-24 bg-gradient-to-r from-gray-900 via-gray-800 to-black text-white py-16 px-6 text-center rounded-t-3xl">
            <h2 class="text-4xl font-bold mb-4">Visit Our Store</h2>
            <p class="text-white/80 mb-8 max-w-2xl mx-auto">
                Come experience our latest collections in person. Discover quality, style, and comfort — all in one
                place.
            </p>
            <a href="#"
                class="inline-block bg-gradient-to-r from-red-600 to-red-500 px-8 py-3 rounded-full text-lg font-semibold hover:scale-105 shadow-md transition-all duration-300">
                Find Us Now
            </a>

            <!-- Social Media -->
            <div class="flex justify-center mt-10 gap-8 text-white/80">
                <a href="#" class="hover:text-red-500 transition">
                    <Instagram class="w-6 h-6" />
                </a>
                <a href="#" class="hover:text-red-500 transition">
                    <Facebook class="w-6 h-6" />
                </a>
                <a href="#" class="hover:text-red-500 transition">
                    <Twitter class="w-6 h-6" />
                </a>
                <a href="#" class="hover:text-red-500 transition">
                    <Youtube class="w-6 h-6" />
                </a>
            </div>
        </section>
    </div>
</template>

<style scoped>
@media (max-width: 768px) {
    .contact-container {
        padding: 0 1rem;
    }
}
</style>
