<script setup lang="ts">
import { ref, computed } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'

const products = ref([
    {
        id: 1,
        name: 'Wireless Headphones',
        price: 129.99,
        oldPrice: 159.99,
        tag: 'Hot 🔥',
        sizes: ['S', 'M', 'L'],
        colors: [
            { name: 'White', image: 'https://assets.gadgetandgear.com/upload/media/edifier-w800bt-pro-wireless-headphone498.jpeg' },
            { name: 'White', image: 'https://assets.gadgetandgear.com/upload/media/edifier-w800bt-pro-wireless-headphone-1268.jpeg' },
            { name: 'White', image: 'https://assets.gadgetandgear.com/upload/media/edifier-w800bt-pro-wireless-headphone-2817.jpeg' }
        ],
        description: 'Immerse yourself in pure sound with advanced noise cancellation, deep bass, and 20-hour playtime.'
    },
    {
        id: 2,
        name: 'Smart Watch',
        price: 99.99,
        oldPrice: 129.99,
        tag: 'New ✨',
        sizes: ['S', 'M', 'L'],
        colors: [
            { name: 'Gold', image: 'https://images.unsplash.com/photo-1600185362082-893b7b3c896f?auto=format&fit=crop&w=400&q=80' },
            { name: 'Silver', image: 'https://images.unsplash.com/photo-1598970434795-0c54fe7c0642?auto=format&fit=crop&w=400&q=80' },
            { name: 'Black', image: 'https://images.unsplash.com/photo-1585386959984-a4155222c49d?auto=format&fit=crop&w=400&q=80' }
        ],
        description: 'Track fitness, monitor health, and stay connected with style. Water resistant and supports notifications.'
    },
    {
        id: 3,
        name: 'Gaming Mouse',
        price: 59.99,
        oldPrice: 79.99,
        tag: 'Sale 💸',
        sizes: ['S', 'M', 'L'],
        colors: [
            { name: 'Red', image: 'https://images.unsplash.com/photo-1595427932002-2d7d9b2fa47d?auto=format&fit=crop&w=400&q=80' },
            { name: 'Green', image: 'https://images.unsplash.com/photo-1617196038891-ec3ffb7cb9e4?auto=format&fit=crop&w=400&q=80' },
            { name: 'Blue', image: 'https://images.unsplash.com/photo-1624892530004-44de86cd79a0?auto=format&fit=crop&w=400&q=80' }
        ],
        description: 'RGB gaming mouse with ultra-fast response and 16000 DPI sensor for precision gaming control.'
    }
])

const showQuickView = ref(false)
const selectedProduct = ref<any>(null)
const selectedColor = ref<string | null>(null)
const selectedSize = ref<string | null>(null)
const hoverImage = ref<string | null>(null)

const openQuickView = (product: any) => {
    selectedProduct.value = product
    selectedColor.value = product.colors[0].name
    hoverImage.value = product.colors[0].image
    selectedSize.value = product.sizes[0]
    showQuickView.value = true
}

const changeColor = (color: any) => {
    selectedColor.value = color.name
    hoverImage.value = color.image
}

// Computed for unique colors by name
const uniqueColors = computed(() => {
    if (!selectedProduct.value) return []
    const map = new Map()
    selectedProduct.value.colors.forEach(color => {
        if (!map.has(color.name)) {
            map.set(color.name, color)
        }
    })
    return Array.from(map.values())
})
</script>

<template>
    <section class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-4xl font-extrabold text-center mb-14 text-gray-800 tracking-tight">
                <span class="text-rose-500">Gaming Garage</span> Featured Picks
            </h2>

            <!-- Product Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-10">
                <div v-for="product in products" :key="product.id"
                    class="group relative rounded-3xl overflow-hidden bg-white border border-gray-100 hover:shadow-2xl transition-all duration-700 hover:-translate-y-2 cursor-pointer">

                    <div class="relative">
                        <img :src="hoverImage && selectedProduct?.id === product.id ? hoverImage : product.colors[0].image"
                            @mouseenter="hoverImage = product.colors[1]?.image"
                            @mouseleave="hoverImage = product.colors[0]?.image" :alt="product.name"
                            class="w-full h-64 object-cover transition-all duration-700 group-hover:scale-110" />

                        <!-- Buttons on Hover -->
                        <div
                            class="absolute inset-0 flex items-center justify-center gap-4 opacity-0 group-hover:opacity-100 transition-all duration-700 bg-black/40">
                            <Button icon="pi pi-eye" label="Quick View"
                                class="bg-white/90 text-gray-800 border-none font-semibold px-5 py-2 rounded-lg shadow-md hover:scale-110 transition"
                                @click.stop="openQuickView(product)" />
                            <Button icon="pi pi-shopping-cart" label="Add to Cart"
                                class="bg-gradient-to-r from-amber-500 to-orange-400 border-none text-white font-semibold px-5 py-2 rounded-lg shadow-md hover:scale-110 transition" />
                        </div>

                        <!-- Tag -->
                        <span
                            class="absolute top-3 left-3 bg-gradient-to-r from-rose-500 to-pink-400 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md">
                            {{ product.tag }}
                        </span>
                    </div>

                    <!-- Product Info -->
                    <div class="p-6 text-center">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ product.name }}</h3>
                        <div class="flex justify-center items-center gap-2">
                            <span class="text-xl font-bold text-rose-500">${{ product.price }}</span>
                            <span class="text-sm text-gray-400 line-through">${{ product.oldPrice }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick View Dialog -->
        <Dialog v-model:visible="showQuickView" modal dismissableMask
            class="max-w-5xl w-full rounded-3xl bg-white/95 backdrop-blur-xl shadow-2xl">
            <div v-if="selectedProduct" class="grid grid-cols-1 md:grid-cols-2 gap-10 p-6">

                <!-- Left: Main Image + Thumbnails -->
                <div>
                    <img :src="hoverImage" :alt="selectedProduct.name"
                        class="w-full h-[400px] rounded-2xl object-cover shadow-lg transition-all mb-4" />

                    <div class="flex gap-3 overflow-x-auto">
                        <img v-for="color in selectedProduct.colors" :key="color.name" :src="color.image"
                            @click="changeColor(color)"
                            class="w-25 h-25 rounded-xl cursor-pointer border-2 transition-all hover:scale-110"
                            :class="selectedColor === color.name ? 'border-rose-500' : 'border-gray-300'" />
                    </div>
                </div>

                <!-- Right: Product Details -->
                <div class="flex flex-col justify-center space-y-5">
                    <h2 class="text-3xl font-bold text-gray-800">{{ selectedProduct.name }}</h2>
                    <p class="text-gray-600 leading-relaxed">{{ selectedProduct.description }}</p>

                    <div class="flex items-center gap-3 mt-3">
                        <span class="text-3xl font-bold text-rose-500">${{ selectedProduct.price }}</span>
                        <span class="text-lg text-gray-400 line-through">${{ selectedProduct.oldPrice }}</span>
                    </div>

                    <!-- Size Selection -->
                    <div>
                        <p class="font-semibold text-gray-700 mb-2">Size</p>
                        <div class="flex gap-3 mb-4">
                            <span v-for="size in selectedProduct.sizes" :key="size"
                                class="px-4 py-2 rounded-xl cursor-pointer border transition-all hover:scale-105"
                                :class="selectedSize === size ? 'bg-rose-500 text-white border-rose-500' : 'border-gray-300 text-gray-700'"
                                @click="selectedSize = size">
                                {{ size }}
                            </span>
                        </div>

                        <!-- Color Selection under Size -->
                        <p v-if="selectedProduct.colors.length" class="font-semibold text-gray-700 mb-2">Color</p>
                        <!-- Only show color options based on unique color names -->
                        <div class="flex gap-3">
                            <span v-for="(color, index) in uniqueColors" :key="index" @click="changeColor(color)"
                                class="w-10 h-10 rounded-full cursor-pointer border-2 transition-all hover:scale-110"
                                :style="{ backgroundColor: color.name.toLowerCase() }"
                                :class="selectedColor === color.name ? 'border-rose-500' : 'border-gray-300'">
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-6">
                        <Button label="Add to Cart" icon="pi pi-shopping-cart"
                            class="bg-gradient-to-r from-amber-500 to-orange-400 border-none text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:scale-105 transition-all" />
                        <Button label="Buy Now" icon="pi pi-bolt"
                            class="bg-gradient-to-r from-rose-500 to-pink-400 border-none text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:scale-105 transition-all" />
                    </div>

                    <div class="pt-6 text-sm text-gray-500 border-t border-gray-200">
                        ✅ Free shipping on orders over $50 <br />
                        🕒 Estimated delivery: 3–5 business days
                    </div>
                </div>
            </div>
        </Dialog>
    </section>
</template>
