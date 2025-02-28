<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import DateRangeSearch from '@/Components/DateRangeSearch.vue';

const props = defineProps({
    repairs: {
        type: Array,
        required: true
    }
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'vehicle.client.name', label: 'Cliente' },
    { key: 'vehicle.brand', label: 'Marca' },
    { key: 'vehicle.plate_number', label: 'Matrícula' },
    { key: 'repair_type.name', label: 'Tipo de Reparación' },
    { key: 'observations', label: 'Observaciones' },
    { key: 'status', label: 'Estado' },
    { key: 'started_at', label: 'Fecha de Inicio' }
];

const searchQuery = ref('');
const dateRange = ref({
    startDate: '',
    endDate: ''
});

function filterRepairs() {
    let filteredRepairs = props.repairs;

    // Filter by date range
    if (dateRange.value.startDate || dateRange.value.endDate) {
        filteredRepairs = filteredRepairs.filter(repair => {
            const startedDate = new Date(repair.started_at);
            const startDate = dateRange.value.startDate ? new Date(dateRange.value.startDate) : null;
            const endDate = dateRange.value.endDate ? new Date(dateRange.value.endDate) : null;

            if (startDate && endDate) {
                return startedDate >= startDate && startedDate <= endDate;
            } else if (startDate) {
                return startedDate >= startDate;
            } else if (endDate) {
                return startedDate <= endDate;
            }
            
            return true;
        });
    }

    // Filter by search query (client name or plate number)
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filteredRepairs = filteredRepairs.filter(repair => 
            (repair.vehicle?.client?.name?.toLowerCase().includes(query) || 
            repair.vehicle?.plate_number?.toLowerCase().includes(query))
        );
    }

    return filteredRepairs;
}
</script>

<template>
    <Head title="Reparaciones" />

    <div class="container mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            Reparaciones
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
                start-label="Fecha de inicio desde"
                end-label="Fecha de inicio hasta"
                @update:dateRange="newRange => dateRange = newRange"
            />
        </div>

        <DataTable 
            :data="filterRepairs()"
            :columns="columns"
            :items-per-page="10"
        />
    </div>
</template>
