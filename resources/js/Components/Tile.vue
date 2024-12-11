<script setup>
defineProps({
    title: String,
    description: String,
    link: String,
    width: String,
    bgColor: String,
});
</script>

<template>
    <a 
        :href="link" 
        class="tile-container" 
        :style="{
            backgroundColor: bgColor || 'rgba(255, 255, 255, 0.90)',
            width: width || '100%',
        }"
    >
        <div class="tile-content">
            <h3 class="tile-title">{{ title }}</h3>
            <p class="tile-description">{{ description }}</p>
        </div>
    </a>
</template>


<style scoped>
.tile-container {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
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
    font-size: 1.25rem;
    font-weight: bold;
    color: #333;
}

.tile-description {
    font-size: 1rem;
    color: #666;
}
</style>
