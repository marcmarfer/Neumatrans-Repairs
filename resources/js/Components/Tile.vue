<script setup>
const emit = defineEmits(['click']);
defineProps({
    title: String,
    description: String,
});
</script>

<template>
    <div class="group shadow-xl rounded-xl h-40 cursor-pointer transition-all duration-300 ease-in-out hover:-translate-y-1" @click="emit('click')">
    <div 
        class="relative flex flex-col justify-center items-start rounded-xl overflow-hidden text-white p-6 bg-gradient-to-br from-red-500 to-red-600 shadow-2xl h-full w-full" 
    >
        <div class="relative z-10 w-full">
            <h3 class="text-lg sm:text-xl font-bold mb-1">{{ title }}</h3>
            <p class="text-sm text-white/80">{{ description }}</p>
        </div>
        
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-300 ease-in-out"></div>
        
        <div class="absolute bottom-4 right-4 opacity-50 group-hover:opacity-100 transition-all duration-300 transform group-hover:translate-x-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </div>
</div>
</template>

<style scoped>
</style>
