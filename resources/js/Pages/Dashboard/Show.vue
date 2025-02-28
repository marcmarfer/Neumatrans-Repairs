<script setup>
import { Head, router } from '@inertiajs/vue3';
import Tile from '@/Components/Tile.vue';

const props = defineProps({
    user: Object,
    required: true
});

const navigateToClients = () => router.get('/clients');
const navigateToVehicles = () => router.get('/vehicles');
const navigateToRepairs = () => router.get('/repairs');
const navigateToDeliveryNotes = () => router.get('/delivery-notes');

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>

    <Head title="Dashboard" />

    <div class="min-h-screen flex flex-col px-4 py-12 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto w-full">
            <div class="flex justify-between items-center mb-12">
                <h1 class="text-black text-2xl sm:text-3xl font-bold">
                    Bienvenido a <span class="text-red-600">Neumatrans</span>, {{ props.user.name }}
                </h1>
                <button @click="logout"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Cerrar Sesión
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <Tile title="Clientes" description="Gestión de clientes" @click="navigateToClients" />
                <Tile title="Vehículos" description="Gestión de vehículos" @click="navigateToVehicles" />
                <Tile title="Reparaciones" description="Gestión de reparaciones" @click="navigateToRepairs" />
                <Tile title="Compras" description="Gestión de compras" @click="navigateToDeliveryNotes" />
                <Tile title="Compras" description="Gestión de reparaciones" @click="navigateToDeliveryNotes" />
                <Tile title="Compras" description="Gestión de compras" @click="navigateToDeliveryNotes" />
            </div>
        </div>
    </div>
</template>
