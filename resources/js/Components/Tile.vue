<script setup>
import { router } from '@inertiajs/vue3';
defineProps({
    title: String,
    description: String,
    link: String,
    width: String,
    height: String,
    bgColor: String,
});
</script>

<template>
    <div 
        @click="router.get(link)"
        class="tile-container" 
        :style="{
            backgroundColor: bgColor || '#ffffff',
            width: width || '100%',
            height: height || '100%'
        }"
    >
        <div class="tile-content">
            <h3 class="tile-title">{{ title }}</h3>
            <p class="tile-description">{{ description }}</p>
        </div>
    </div>
</template>


<style scoped>
.tile-container {
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    box-shadow: rgba(0, 0, 0, 0.3) 10px 10px 15px;
    cursor: pointer;
    overflow: hidden;
    transition: transform 0.3s ease;
    border-radius: 20px;
}

.tile-container:hover {
    transform: scale(1.05);

}

.tile-content {
    text-align: center;
}

.tile-title {
    font-size: 1.5rem;
    font-weight: bold;
}

.tile-description {
    font-size: 1rem;
    color: #dfdfdf;
}
</style>
