<script setup>
import { Head } from "@inertiajs/vue3";

const props = defineProps({
  repair: Object,
  vehicle: Object,
  client: Object,
  repairType: Object,
  repairOrder: Object,
  statusLabels: Object,
});

function isCompleted() {
  return props.repair.completed_at !== null;
}

function isReadyForPickup() {
  return isCompleted() && props.repairOrder?.status === 'finished';
}

function getStatusLabel() {
  if (!props.repairOrder) return 'En proceso';
  return props.statusLabels[props.repairOrder.status] || props.repairOrder.status;
}

function getStatusClass() {
  if (!props.repairOrder) return 'bg-yellow-500';
  
  const statusClasses = {
    'reception': 'bg-yellow-500',
    'diagnosing': 'bg-yellow-500',
    'in_repair': 'bg-orange-500',
    'finished': 'bg-green-600'
  };
  
  return statusClasses[props.repairOrder.status] || 'bg-yellow-500';
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date);
};
</script>

<template>
  <Head title="Seguimiento de Reparación" />

  <div class="bg-gray-100 min-h-screen">
    <div class="container mx-auto py-8 px-4">
      <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="bg-red-500 py-4 px-6 text-white">
          <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold">NTC Car Service</h1>
          </div>
        </div>

        <!-- Estado de la reparación destacado -->
        <div :class="[getStatusClass(), 'py-3 px-6 text-white']">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Estado: {{ getStatusLabel() }}</h2>
            <div v-if="isCompleted()" class="bg-gray-800 px-4 py-1 rounded-full text-sm font-bold">
              Completada
            </div>
            <div v-else class="bg-white text-gray-800 px-4 py-1 rounded-full text-sm font-bold">
              En Progreso
            </div>
          </div>
        </div>

        <div class="p-6">
          <div class="mb-6">
            <h3 class="text-lg font-bold mb-3">Vehículo</h3>
            <div class="bg-gray-50 p-4 rounded-lg">
              <p><span class="font-semibold">Matrícula:</span> {{ vehicle.plate_number }}</p>
              <p><span class="font-semibold">Marca:</span> {{ vehicle.brand }}</p>
              <p><span class="font-semibold">Modelo:</span> {{ vehicle.model }}</p>
              <p v-if="vehicle.VIN"><span class="font-semibold">VIN:</span> {{ vehicle.VIN }}</p>
            </div>
          </div>

          <!-- Mostrar todas las reparaciones asociadas -->
          <div class="mb-6">
            <h3 class="text-lg font-bold mb-3">Reparaciones</h3>
            <div v-if="repairOrder && repairOrder.repairs && repairOrder.repairs.length > 0">
              <div v-for="(repairItem, index) in repairOrder.repairs" :key="index" class="bg-gray-50 p-4 rounded-lg mb-2">
                <p><span class="font-semibold">Tipo de Reparación:</span> {{ repairItem.repair_type?.name }}</p>
                <p v-if="repairItem.observations"><span class="font-semibold">Observaciones:</span> {{ repairItem.observations }}</p>
                <p><span class="font-semibold">Fecha de Inicio:</span> {{ formatDate(repairItem.started_at) }}</p>
                <p v-if="repairItem.completed_at"><span class="font-semibold">Fecha de Finalización:</span> {{ formatDate(repairItem.completed_at) }}</p>
              </div>
            </div>
            <div v-else class="bg-gray-50 p-4 rounded-lg">
              <p><span class="font-semibold">Tipo de Reparación:</span> {{ repairType.name }}</p>
              <p><span class="font-semibold">Fecha de Inicio:</span> {{ formatDate(repair.started_at) }}</p>
              <p v-if="isCompleted()"><span class="font-semibold">Fecha de Finalización:</span> {{ formatDate(repair.completed_at) }}</p>
              <p v-if="repair.observations"><span class="font-semibold">Observaciones:</span> {{ repair.observations }}</p>
            </div>
          </div>

          <div v-if="isReadyForPickup()" class="bg-green-50 border border-green-200 p-4 rounded-lg mb-6">
            <h3 class="text-lg font-bold text-green-800 mb-2">¡Su vehículo está listo para recoger!</h3>
            <p class="text-gray-700">
              Puede pasar por nuestro taller en horario de atención:
              <br />
              <span class="font-semibold">Lunes a Viernes:</span> 9:00 - 19:00
              <br />
              <span class="font-semibold">Sábados:</span> 9:00 - 13:00
            </p>
          </div>

          <div class="border-t border-gray-200 pt-4 text-sm text-gray-500 text-center">
            <p>Para cualquier consulta, por favor contacte con nosotros:</p>
            <p class="font-semibold">Teléfono: 666 666 666 | Email: jm@neumatrans.es</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template> 