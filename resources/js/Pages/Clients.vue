<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import DateRangeSearch from '@/Components/DateRangeSearch.vue';

const props = defineProps({
    clients: {
        type: Array,
        required: true
    }
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'DNI', label: 'DNI' },
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Email' },
    { key: 'telephone', label: 'Teléfono' },
    { key: 'city', label: 'Ciudad' },
    { key: 'postal_code', label: 'Código Postal' },
    { key: 'registered_at', label: 'Fecha de Registro' }
];

const dateRange = ref({
    startDate: '',
    endDate: ''
});

const searchQuery = ref('');

function filterClients() {
    let filteredClients = props.clients;

    // Filter by date range
    if (dateRange.value.startDate || dateRange.value.endDate) {
        filteredClients = filteredClients.filter(client => {
            const registeredDate = new Date(client.registered_at);
            const startDate = dateRange.value.startDate ? new Date(dateRange.value.startDate) : null;
            const endDate = dateRange.value.endDate ? new Date(dateRange.value.endDate) : null;

            if (startDate && endDate) {
                return registeredDate >= startDate && registeredDate <= endDate;
            } else if (startDate) {
                return registeredDate >= startDate;
            } else if (endDate) {
                return registeredDate <= endDate;
            }
            
            return true;
        });
    }

    // Filter by search query (DNI or name)
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filteredClients = filteredClients.filter(client => 
            client.DNI.toLowerCase().includes(query) || 
            client.name.toLowerCase().includes(query)
        );
    }

    return filteredClients;
}
</script>

<template>
    <Head title="Clientes" />

    <div class="container mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            Clientes
        </h1>

        <div class="mb-6 space-y-4">
            <div class="flex items-center space-x-4">
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Buscar por DNI o nombre..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <DateRangeSearch
                start-label="Fecha de registro desde"
                end-label="Fecha de registro hasta"
                @update:dateRange="newRange => dateRange = newRange"
            />
        </div>

        <DataTable 
            :data="filterClients()"
            :columns="columns"
            :items-per-page="10"
        />
    </div>
</template>