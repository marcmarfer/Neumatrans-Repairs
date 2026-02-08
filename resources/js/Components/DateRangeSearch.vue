<script setup>
import { ref, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    startLabel: {
        type: String,
        default: 'Start Date'
    },
    endLabel: {
        type: String,
        default: 'End Date'
    },
    initialStartDate: {
        type: String,
        default: ''
    },
    initialEndDate: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:dateRange']);

const startDate = ref(props.initialStartDate);
const endDate = ref(props.initialEndDate);

watch([startDate, endDate], ([newStartDate, newEndDate]) => {
    emit('update:dateRange', {
        startDate: newStartDate,
        endDate: newEndDate
    });
});
</script>

<template>
    <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <InputLabel :value="startLabel" class="mb-1" />
            <input
                type="date"
                v-model="startDate"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :max="endDate"
            />
        </div>
        <div class="flex-1">
            <InputLabel :value="endLabel" class="mb-1" />
            <input
                type="date"
                v-model="endDate"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :min="startDate"
            />
        </div>
    </div>
</template> 