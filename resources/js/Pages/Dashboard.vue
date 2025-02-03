<script setup>
import { Head } from '@inertiajs/vue3';
import Tile from '@/Components/Tile.vue';

import axios from 'axios';
import { onMounted } from 'vue';
import { ref } from 'vue';

const userName = ref('');

onMounted(() => {
    axios.get('/api/user-logged-in')
        .then(response => {
            userName.value = response.data.name;
        });
});
</script>

<template>
    <Head title="Dashboard" />

    
    <div class="dashboard-container">
        <h1 class="">Welcome back to the Neumatrans dashboard! You are logged in with the account {{ userName }}</h1>
        <div class="tiles-grid">
            <Tile
                title="Clients"
                description="Client management page"
                link="/clients"
                bgColor="#191b69"
            />
            <Tile
                title="Vehicles"
                description="Vehicles management page"
                link="/vehicles"
                bgColor="#191b69"
            />
            <Tile
                title="Repairs"
                description="Repairs management page"
                link="/repairs"
                bgColor="#191b69"
            />
            <Tile
                title="Buys"
                description="Buys management page"
                link="/buys"
                bgColor="#191b69"
            />
            <Tile
                title="Buys"
                description="Buys management page"
                link="/buys"
                bgColor="#191b69"
            />
            <Tile
                title="Ejemplo 1"
                description="Ejemplo 1 management page"
                link="/ejemplo1"
                bgColor="#191b69"
            />
        </div>
    </div>
</template>

<style scoped>
h1 {
    color: white;
    font-size: 1.75rem;
}

.dashboard-container {
    min-height: 100vh;
    background: linear-gradient(to right, #1C4EBF, #0C1895);
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    padding: 2rem;
    gap: 2.5rem;
}

.tiles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    width: 65%;
    height: 70vh;
}
</style>
