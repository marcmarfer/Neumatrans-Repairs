<script setup>
import { Head } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';

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
</script>

<template>
    <Head title="Albaranes" />

    <div class="container mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            Albaranes
        </h1>

        <DataTable 
            :data="delivery_notes"
            :columns="columns"
            :items-per-page="10"
        />
    </div>
</template> 