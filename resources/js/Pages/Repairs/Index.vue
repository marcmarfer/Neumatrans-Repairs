<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import DataTable from "@/Components/DataTable.vue";
import DateRangeSearch from "@/Components/DateRangeSearch.vue";
import DarkButton from "@/Components/DarkButton.vue";
import LightButton from "@/Components/LightButton.vue";
import DefaultInput from "@/Components/DefaultInput.vue";
import DefaultSelect from "@/Components/DefaultSelect.vue";

const props = defineProps({
  repairs: {
    type: Array,
    required: true,
  },
  vehicles: {
    type: Array,
    required: true,
  },
  repair_types: {
    type: Array,
    required: true,
  },
});

const columns = [
  { key: "id", label: "ID" },
  { key: "vehicle.client.name", label: "Cliente" },
  { key: "vehicle.brand", label: "Marca" },
  { key: "vehicle.plate_number", label: "Matrícula" },
  { key: "repair_type.name", label: "Tipo de Reparación" },
  { key: "observations", label: "Observaciones" },
  { key: "status", label: "Estado" },
  { key: "started_at", label: "Fecha de Inicio" },
];

const searchQuery = ref("");
const dateRange = ref({
  startDate: "",
  endDate: "",
});
const isModalOpen = ref(false);

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  vehicle_id: "",
  repair_type_id: "",
  observations: "",
  status: "pending",
  started_at: today,
});

function filterRepairs() {
  let filteredRepairs = props.repairs;

  // Filter by date range
  if (dateRange.value.startDate || dateRange.value.endDate) {
    filteredRepairs = filteredRepairs.filter((repair) => {
      const startedDate = new Date(repair.started_at);
      const startDate = dateRange.value.startDate
        ? new Date(dateRange.value.startDate)
        : null;
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
    filteredRepairs = filteredRepairs.filter(
      (repair) =>
        repair.vehicle?.client?.name?.toLowerCase().includes(query) ||
        repair.vehicle?.plate_number?.toLowerCase().includes(query)
    );
  }

  return filteredRepairs;
}

function addNewRepair() {
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  form.reset();
}

function submitForm() {
  form.post(route("repairs.store"), {
    onSuccess: () => {
      closeModal();
    },
  });
}
</script>

<template>
  <Head title="Reparaciones" />

  <div class="container mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Reparaciones</h1>

    <div class="mb-6 space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Buscar por cliente o matrícula..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <DarkButton @click="addNewRepair"> Añadir Reparación </DarkButton>
      </div>

      <DateRangeSearch
        start-label="Fecha de inicio desde"
        end-label="Fecha de inicio hasta"
        @update:dateRange="(newRange) => (dateRange = newRange)"
      />
    </div>

    <DataTable :data="filterRepairs()" :columns="columns" :items-per-page="10" />

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Añadir Nueva Reparación</h2>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitForm">
          <div class="space-y-4">
            <DefaultSelect
              id="vehicle_id"
              v-model="form.vehicle_id"
              label="Vehículo"
              :options="vehicles"
              value-field="id"
              placeholder="Seleccione un vehículo"
              required
              :error="form.errors.vehicle_id"
            >
              <template #option="{ option }">
                {{ option.plate_number }} - {{ option.brand }} {{ option.model }} ({{
                  option.client?.name
                }})
              </template>
            </DefaultSelect>

            <DefaultSelect
              id="repair_type_id"
              v-model="form.repair_type_id"
              label="Tipo de Reparación"
              :options="repair_types"
              value-field="id"
              label-field="name"
              placeholder="Seleccione un tipo de reparación"
              required
              :error="form.errors.repair_type_id"
            />

            <DefaultInput
              id="observations"
              v-model="form.observations"
              label="Observaciones"
              :error="form.errors.observations"
              isTextarea
              :rows="3"
            />

            <DefaultSelect
              id="status"
              v-model="form.status"
              label="Estado"
              :options="[
                { value: 'pending', label: 'Pendiente' },
                { value: 'in_progress', label: 'En Progreso' },
                { value: 'completed', label: 'Completada' },
              ]"
              required
              :error="form.errors.status"
            />

            <DefaultInput
              id="started_at"
              v-model="form.started_at"
              type="date"
              label="Fecha de Inicio"
              :error="form.errors.started_at"
            />
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <LightButton type="button" @click="closeModal"> Cancelar </LightButton>
            <DarkButton type="submit" :disabled="form.processing">
              {{ form.processing ? "Guardando..." : "Guardar" }}
            </DarkButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
