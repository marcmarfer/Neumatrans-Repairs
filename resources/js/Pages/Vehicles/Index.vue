<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import DataTable from "@/Components/DataTable.vue";
import DateRangeSearch from "@/Components/DateRangeSearch.vue";
import DarkButton from "@/Components/DarkButton.vue";
import LightButton from "@/Components/LightButton.vue";
import DefaultInput from "@/Components/DefaultInput.vue";
import DefaultSelect from "@/Components/DefaultSelect.vue";
import GoBackButton from "@/Components/GoBackButton.vue";
import DeleteButton from "@/Components/DeleteButton.vue";
const props = defineProps({
  vehicles: {
    type: Array,
    required: true,
  },
  clients: {
    type: Array,
    required: true,
  },
});

const columns = [
  { key: "id", label: "ID" },
  { key: "client.name", label: "Cliente" },
  { key: "plate_number", label: "Matrícula" },
  { key: "brand", label: "Marca" },
  { key: "model", label: "Modelo" },
  { key: "VIN", label: "VIN" },
  { key: "motor_type", label: "Tipo de Motor" },
  { key: "added_at", label: "Fecha de Alta" },
];

const searchQuery = ref("");
const dateRange = ref({
  startDate: "",
  endDate: "",
});
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const vehicleToDelete = ref(null);

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  client_id: "",
  plate_number: "",
  brand: "",
  model: "",
  VIN: "",
  motor_type: "",
  added_at: today,
});

function filterVehicles() {
  let filteredVehicles = props.vehicles;

  // Filter by date range
  if (dateRange.value.startDate || dateRange.value.endDate) {
    filteredVehicles = filteredVehicles.filter((vehicle) => {
      const addedDate = new Date(vehicle.added_at);
      const startDate = dateRange.value.startDate
        ? new Date(dateRange.value.startDate)
        : null;
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
    filteredVehicles = filteredVehicles.filter(
      (vehicle) =>
        vehicle.client?.name?.toLowerCase().includes(query) ||
        vehicle.plate_number.toLowerCase().includes(query)
    );
  }

  return filteredVehicles;
}

function addNewVehicle() {
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  form.reset();
}

function submitForm() {
  form.post(route("vehicles.store"), {
    onSuccess: () => {
      closeModal();
    },
  });
}

function confirmDelete(vehicle) {
  vehicleToDelete.value = vehicle;
  isDeleteModalOpen.value = true;
}

function cancelDelete() {
  isDeleteModalOpen.value = false;
  vehicleToDelete.value = null;
}

function deleteVehicle() {
  if (vehicleToDelete.value) {
    router.delete(route("vehicles.destroy", vehicleToDelete.value.id), {
      onSuccess: () => {
        isDeleteModalOpen.value = false;
        vehicleToDelete.value = null;
      },
    });
  }
}
</script>

<template>
  <Head title="Vehículos" />
  <GoBackButton type="button" @click="router.visit(route('dashboard.show'))">
    Volver
  </GoBackButton>
  <div class="container mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Vehículos</h1>

    <div class="mb-6 space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Buscar por cliente o matrícula..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <DarkButton @click="addNewVehicle"> Añadir Vehículo </DarkButton>
      </div>

      <DateRangeSearch
        start-label="Fecha de alta desde"
        end-label="Fecha de alta hasta"
        @update:dateRange="(newRange) => (dateRange = newRange)"
      />
    </div>

    <DataTable 
      :data="filterVehicles()" 
      :columns="columns" 
      :items-per-page="10" 
      @delete="confirmDelete"
    />

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Añadir Nuevo Vehículo</h2>
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
              id="client_id"
              v-model="form.client_id"
              label="Cliente"
              :options="clients"
              value-field="id"
              placeholder="Seleccione un cliente"
              required
              :error="form.errors.client_id"
            >
              <template #option="{ option }">
                {{ option.name }} - {{ option.DNI }}
              </template>
            </DefaultSelect>

            <DefaultInput
              id="plate_number"
              v-model="form.plate_number"
              label="Matrícula"
              required
              :error="form.errors.plate_number"
            />

            <DefaultInput
              id="brand"
              v-model="form.brand"
              label="Marca"
              required
              :error="form.errors.brand"
            />

            <DefaultInput
              id="model"
              v-model="form.model"
              label="Modelo"
              required
              :error="form.errors.model"
            />

            <DefaultInput
              id="VIN"
              v-model="form.VIN"
              label="VIN"
              :error="form.errors.VIN"
            />

            <DefaultInput
              id="motor_type"
              v-model="form.motor_type"
              label="Tipo de Motor"
              :error="form.errors.motor_type"
            />

            <DefaultInput
              id="added_at"
              v-model="form.added_at"
              type="date"
              label="Fecha de Alta"
              :error="form.errors.added_at"
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

    <!-- Modal de confirmación de eliminación -->
    <div v-if="isDeleteModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="cancelDelete"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Confirmar eliminación</h2>
          <button @click="cancelDelete" class="text-gray-500 hover:text-gray-700">
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

        <div class="mb-6">
          <p class="text-gray-700">¿Estás seguro que deseas eliminar el vehículo <span class="font-bold">{{ vehicleToDelete?.plate_number }}</span>?</p>
          <p class="text-sm text-red-500 mt-2">Esta acción no se puede deshacer.</p>
        </div>

        <div class="flex justify-end space-x-3">
          <LightButton type="button" @click="cancelDelete">Cancelar</LightButton>
          <DeleteButton type="button" @click="deleteDeliveryNote">Eliminar</DeleteButton>
        </div>
      </div>
    </div>
  </div>
</template>
