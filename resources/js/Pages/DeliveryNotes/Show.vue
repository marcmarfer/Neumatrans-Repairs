<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import DateRangeSearch from '@/Components/DateRangeSearch.vue';

const props = defineProps({
    delivery_notes: {
        type: Array,
        required: true
    }
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Tipo' },
    { key: 'supplier', label: 'Proveedor' },
    { key: 'family', label: 'Familia' },
    { key: 'RRP', label: 'PVP' },
    { key: 'cost', label: 'Coste' },
    { key: 'margin', label: 'Margen' },
    { key: 'profit', label: 'Beneficio' },
    { key: 'added_at', label: 'Fecha de Alta' }
];

const searchQuery = ref('');
const dateRange = ref({
    startDate: '',
    endDate: ''
});

function calculateTotals() {
    const filteredData = filterDeliveryNotes();
    return {
        totalSold: filteredData.reduce((sum, note) => sum + parseFloat(note.RRP), 0),
        totalSpent: filteredData.reduce((sum, note) => sum + parseFloat(note.cost), 0),
        totalProfit: filteredData.reduce((sum, note) => sum + parseFloat(note.profit), 0),
        averageMargin: filteredData.length > 0 
            ? (filteredData.reduce((sum, note) => sum + parseFloat(note.margin), 0) / filteredData.length)
            : 0
    };
}

function filterDeliveryNotes() {
    let filteredNotes = props.delivery_notes;

    // Filter by date range
    if (dateRange.value.startDate || dateRange.value.endDate) {
        filteredNotes = filteredNotes.filter(note => {
            const addedDate = new Date(note.added_at);
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

    // Filter by search query (family or supplier)
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filteredNotes = filteredNotes.filter(note => 
            note.family.toLowerCase().includes(query) || 
            note.supplier.toLowerCase().includes(query)
        );
    }

    return filteredNotes;
}
</script>

<template>
    <Head title="Albaranes" />

    <div class="container mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            Albaranes
        </h1>

        <div class="mb-6 space-y-4">
            <div class="flex items-center space-x-4">
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Buscar por familia o proveedor..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <DateRangeSearch
                start-label="Fecha de alta desde"
                end-label="Fecha de alta hasta"
                @update:dateRange="newRange => dateRange = newRange"
            />
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Resumen de datos filtrados</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Total Vendido (PVP)</p>
                    <p class="text-xl font-bold">{{ calculateTotals().totalSold.toFixed(2) }}€</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Total Gastado</p>
                    <p class="text-xl font-bold">{{ calculateTotals().totalSpent.toFixed(2) }}€</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Beneficio Total</p>
                    <p class="text-xl font-bold">{{ calculateTotals().totalProfit.toFixed(2) }}€</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Margen Promedio</p>
                    <p class="text-xl font-bold">{{ calculateTotals().averageMargin.toFixed(2) }}%</p>
                </div>
            </div>
        </div>

        <DataTable 
            :data="filterDeliveryNotes()"
            :columns="columns"
            :items-per-page="10"
        />
    </div>
</template> 