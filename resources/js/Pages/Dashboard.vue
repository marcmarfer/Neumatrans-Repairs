<script setup>
import { Head } from '@inertiajs/vue3';
import Tile from '@/Components/Tile.vue';
</script>

<template>
    <Head title="Dashboard" />

    <div class="dashboard-container">
        <div class="tiles-grid">
            <Tile
                title="Users"
                description="User management page"
                link="/users"
            />
            <Tile
                title="Vehicles"
                description="Vehicles management page"
                link="/vehicles"
            />
            <Tile
                title="Repairs"
                description="Repairs management page"
                link="/reports"
            />
            <Tile
                title="Buys"
                description="Buys management page"
                link="/buys"
            />
            <Tile
                title="Buys"
                description="Buys management page"
                link="/buys"
            />
            <Tile
                title="Ejemplo 1"
                description="Ejemplo 1 management page"
                link="/ejemplo1"
            />
        </div>
    </div>
</template>

<style scoped>
.dashboard-container {
    min-height: 100vh;
    background: linear-gradient(to top right, #1C4EBF, #0c178f);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
}

.tiles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    width: 80%;
    height: 90vh;
}
</style>
