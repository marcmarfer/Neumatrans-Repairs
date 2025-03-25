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
const navigateToInsights = () => router.get('/insights');
const logout = () => {
    router.post(route('logout'));
};
</script>

<template>

    <Head title="Dashboard" />

    <div class="min-h-screen flex flex-col p-[50px] py-[75px] sm:px-6 lg:px-8">
        <div class="max-w-screen-2xl w-full">
                <h1 class="text-black text-2xl sm:text-3xl font-bold mb-12">
                    Bienvenido a <span class="text-red-600">Neumatrans</span>, {{ props.user.name }}
                </h1>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <Tile title="Clientes" description="Gestión de clientes" @click="navigateToClients" />
                <Tile title="Vehículos" description="Gestión de vehículos" @click="navigateToVehicles" />
                <Tile title="Reparaciones" description="Gestión de reparaciones" @click="navigateToRepairs" />
                <Tile title="Albaranes" description="Gestión de albaranes" @click="navigateToDeliveryNotes" />
                <Tile title="Estadísticas" description="Panel de estadísticas" @click="navigateToInsights" />
                <Tile title="Cerrar Sesión" description="Cerrar sesión de la cuenta" @click="logout" />
            </div>
        </div>
    </div>
</template>
