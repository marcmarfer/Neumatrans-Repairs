<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import DateRangeSearch from '@/Components/DateRangeSearch.vue';

const props = defineProps({
    vehicles: {
        type: Array,
        required: true
    }
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'client.name', label: 'Cliente' },
    { key: 'plate_number', label: 'Matrícula' },
    { key: 'brand', label: 'Marca' },
    { key: 'model', label: 'Modelo' },
    { key: 'VIN', label: 'VIN' },
    { key: 'motor_type', label: 'Tipo de Motor' },
    { key: 'added_at', label: 'Fecha de Alta' }
];

const searchQuery = ref('');
const dateRange = ref({
    startDate: '',
    endDate: ''
});

function filterVehicles() {
    let filteredVehicles = props.vehicles;

    // Filter by date range
    if (dateRange.value.startDate || dateRange.value.endDate) {
        filteredVehicles = filteredVehicles.filter(vehicle => {
            const addedDate = new Date(vehicle.added_at);
            const startDate = dateRange.value.startDate ? new Date(dateRange.value.startDate) : null;
            const endDate = dateRange.value.endDate ? new Date(dateRange.value.endDate) : null;

            if (startDate && endDate) {
                return addedDate >= startDate && addedDate <= endDate;
            } else if (startDate) {
                return addedDate >= startDate;
            } else if (endDate) {
                return addedDate <= endDate;
            }
            
            return true;
        });
    }

    // Filter by search query (client name or plate number)
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filteredVehicles = filteredVehicles.filter(vehicle => 
            (vehicle.client?.name?.toLowerCase().includes(query) || 
            vehicle.plate_number.toLowerCase().includes(query))
        );
    }

    return filteredVehicles;
}
</script>

<template>
    <Head title="Vehículos" />

    <div class="container mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            Vehículos
        </h1>

        <div class="mb-6 space-y-4">
            <div class="flex items-center space-x-4">
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Buscar por cliente o matrícula..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <DateRangeSearch
                start-label="Fecha de alta desde"
                end-label="Fecha de alta hasta"
                @update:dateRange="newRange => dateRange = newRange"
            />
        </div>

        <DataTable 
            :data="filterVehicles()"
            :columns="columns"
            :items-per-page="10"
        />
    </div>
</template>