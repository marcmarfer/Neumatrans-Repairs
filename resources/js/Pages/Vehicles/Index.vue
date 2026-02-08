<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import DataTable from "@/Components/DataTable.vue";
import DateRangeSearch from "@/Components/DateRangeSearch.vue";
import DarkButton from "@/Components/DarkButton.vue";
import LightButton from "@/Components/LightButton.vue";
import DefaultInput from "@/Components/DefaultInput.vue";
import GoBackButton from "@/Components/GoBackButton.vue";
import DeleteButton from "@/Components/DeleteButton.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";
import DefaultSelect from "@/Components/DefaultSelect.vue";
import AddButton from '@/Components/AddButton.vue';
import ExportButton from '@/Components/ExportButton.vue';
const props = defineProps({
  vehicles: Object,
  clients: Array,
  brands: Array,
  models: Array,
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  });
};

const columns = [
  { key: "id", label: "ID" },
  { key: "client.name", label: "Cliente" },
  { key: "plate_number", label: "Matrícula" },
  { key: "brand.name", label: "Marca" },
  { key: "model.name", label: "Modelo" },
  { key: "VIN", label: "VIN" },
  { key: "motor_type", label: "Tipo de Motor" },
  { 
    key: "added_at", 
    label: "Fecha de Alta",
    formatter: formatDate
  },
];

const searchQuery = ref(props.filters?.q ?? "");
const dateRange = ref({
  startDate: props.filters?.start ?? "",
  endDate: props.filters?.end ?? "",
});
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const vehicleToDelete = ref(null);
const isEditing = ref(false);

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  id: "",
  client_id: "",
  plate_number: "",
  brand_id: "",
  model_id: "",
  VIN: "",
  motor_type: "",
  added_at: today,
});

const filteredModels = computed(() =>
  form.brand_id
    ? props.models.filter((m) => m.brand_id === form.brand_id)
    : []
);

// ── Server-side data fetching ──────────────────────────────────────

const paginationMeta = computed(() => ({
  current_page: props.vehicles.current_page,
  last_page: props.vehicles.last_page,
  from: props.vehicles.from,
  to: props.vehicles.to,
  total: props.vehicles.total,
  per_page: props.vehicles.per_page,
}));

function fetchData(page = null) {
  const params = {};
  if (searchQuery.value) params.q = searchQuery.value;
  if (dateRange.value.startDate) params.start = dateRange.value.startDate;
  if (dateRange.value.endDate) params.end = dateRange.value.endDate;
  if (page && page > 1) params.page = page;

  router.get(route('vehicles.index'), params, {
    preserveState: true,
    preserveScroll: true,
    only: ['vehicles', 'filters'],
  });
}

let searchTimeout = null;
function onSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchData(1);
  }, 300);
}

function onDateRangeChange(newRange) {
  dateRange.value = newRange;
  fetchData(1);
}

function onPageChange(page) {
  fetchData(page);
}

const exportUrl = computed(() => {
  const params = new URLSearchParams();
  if (searchQuery.value) params.set('q', searchQuery.value);
  if (dateRange.value.startDate) params.set('start', dateRange.value.startDate);
  if (dateRange.value.endDate) params.set('end', dateRange.value.endDate);
  const query = params.toString();
  return route('vehicles.exportCsv') + (query ? '?' + query : '');
});

function addNewVehicle() {
  isEditing.value = false;
  form.reset();
  form.added_at = today;
  isModalOpen.value = true;
}

function editVehicle(vehicle) {
  isEditing.value = true;
  form.reset();
  form.id = vehicle.id;
  form.client_id = vehicle.client_id.toString();
  form.plate_number = vehicle.plate_number;
  form.brand_id = vehicle.brand_id;
  form.model_id = vehicle.model_id;
  form.VIN = vehicle.VIN;
  form.motor_type = vehicle.motor_type;
  form.added_at = vehicle.added_at;
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  form.reset();
  isEditing.value = false;
}

function submitForm() {
  if (isEditing.value) {
    form.put(route("vehicles.update", form.id), {
      onSuccess: () => {
        closeModal();
      },
    });
  } else {
    form.post(route("vehicles.store"), {
      onSuccess: () => {
        closeModal();
      },
    });
  }
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

function formatPlateNumber(event) {
  let value = event.target.value.toUpperCase();
  value = value.replace(/[^A-Z0-9]/g, '');
  if (value.length > 8) {
    value = value.substring(0, 8);
  }
  form.plate_number = value;
}

function formatVIN(event) {
  let value = event.target.value.toUpperCase();
  value = value.replace(/[^A-HJ-NPR-Z0-9]/g, '');
  if (value.length > 17) {
    value = value.substring(0, 17);
  }
  form.VIN = value;
}

const newBrandForm = useForm({ name: '' });
const isNewBrandModalOpen = ref(false);
const newModelForm = useForm({ brand_id: '', name: '' });
const isNewModelModalOpen = ref(false);

function openNewBrandModal() {
  newBrandForm.reset();
  isNewBrandModalOpen.value = true;
}

function closeNewBrandModal() {
  isNewBrandModalOpen.value = false;
}

function submitNewBrandForm() {
  newBrandForm.post(route('brands.store'), {
    onSuccess: () => {
      closeNewBrandModal();
      router.reload();
    },
  });
}

function openNewModelModal() {
  newModelForm.reset();
  newModelForm.brand_id = form.brand_id;
  isNewModelModalOpen.value = true;
}

function closeNewModelModal() {
  isNewModelModalOpen.value = false;
}

function submitNewModelForm() {
  newModelForm.post(route('models.store'), {
    onSuccess: () => {
      closeNewModelModal();
      router.reload();
    },
  });
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
          @input="onSearchInput"
          placeholder="Buscar por cliente o matrícula..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <div class="flex gap-4 w-full sm:w-auto justify-between sm:justify-end">
          <DarkButton @click="addNewVehicle" class="sm:order-1"> Añadir Vehículo </DarkButton>
          <ExportButton :href="exportUrl" class="sm:order-2" />
        </div>
      </div>

      <DateRangeSearch
        start-label="Fecha de alta desde"
        end-label="Fecha de alta hasta"
        :initial-start-date="filters?.start ?? ''"
        :initial-end-date="filters?.end ?? ''"
        @update:dateRange="onDateRangeChange"
      />
    </div>

    <DataTable 
      :data="vehicles.data" 
      :columns="columns" 
      :server-side="true"
      :meta="paginationMeta"
      @delete="confirmDelete"
      @edit="editVehicle"
      @page-change="onPageChange"
    />

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">{{ isEditing ? 'Editar Vehículo' : 'Añadir Nuevo Vehículo' }}</h2>
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
            <SearchableSelect
              id="client_id"
              v-model="form.client_id"
              label="Cliente"
              :options="clients"
              value-field="id"
              label-field="name"
              placeholder="Seleccione un cliente"
              search-placeholder="Buscar cliente por nombre o DNI..."
              required
              :error="form.errors.client_id"
            >
              <template #option="{ option }">
                {{ option.name }} - {{ option.DNI }}
              </template>
            </SearchableSelect>

            <DefaultInput
              id="plate_number"
              v-model="form.plate_number"
              label="Matrícula"
              placeholder="Ej: 1234ABC"
              pattern="[0-9]{4}[A-Za-z]{3}|[A-Za-z]{1,2}[0-9]{4}[A-Za-z]{2,3}"
              title="Formato: 1234ABC o M1234BC"
              maxlength="8"
              required
              :error="form.errors.plate_number"
              @input="formatPlateNumber"
            />

            <div>
              <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
              <div class="flex space-x-2">
                <div class="flex-1">
                  <SearchableSelect
                    id="brand_id"
                    v-model="form.brand_id"
                    :options="props.brands"
                    value-field="id"
                    label-field="name"
                    placeholder="Seleccione una marca"
                    search-placeholder="Buscar marca..."
                    required
                    :error="form.errors.brand_id"
                  />
                </div>
                <AddButton @click="openNewBrandModal" />
              </div>
              <div v-if="form.errors.brand_id" class="text-sm text-red-600 mt-1">{{ form.errors.brand_id }}</div>
            </div>

            <div>
              <label for="model_id" class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
              <div class="flex space-x-2">
                <div class="flex-1">
                  <SearchableSelect
                    id="model_id"
                    v-model="form.model_id"
                    :options="filteredModels"
                    value-field="id"
                    label-field="name"
                    placeholder="Seleccione un modelo"
                    search-placeholder="Buscar modelo..."
                    :disabled="!form.brand_id"
                    required
                    :error="form.errors.model_id"
                  />
                </div>
                <AddButton @click="openNewModelModal" />
              </div>
              <div v-if="form.errors.model_id" class="text-sm text-red-600 mt-1">{{ form.errors.model_id }}</div>
            </div>

            <DefaultInput
              id="VIN"
              v-model="form.VIN"
              label="VIN (Opcional)"
              placeholder="17 caracteres alfanuméricos"
              pattern="[A-HJ-NPR-Z0-9]{17}"
              title="17 caracteres alfanuméricos (sin I, O, Q)"
              maxlength="17"
              minlength="17"
              :error="form.errors.VIN"
              @input="formatVIN"
            />

            <DefaultInput
              id="motor_type"
              v-model="form.motor_type"
              label="Tipo de Motor (Opcional)"
              placeholder="Ej: 1.6 TDI, 2.0 TSI, Híbrido"
              pattern="[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\-\.\,\/\(\)]+"
              title="Letras, números y caracteres especiales básicos"
              maxlength="100"
              :error="form.errors.motor_type"
            />

            <DefaultInput
              id="added_at"
              v-model="form.added_at"
              type="date"
              label="Fecha de Alta"
              :max="today"
              required
              :error="form.errors.added_at"
            />
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <LightButton type="button" @click="closeModal"> Cancelar </LightButton>
            <DarkButton type="submit" :disabled="form.processing">
              {{ form.processing ? "Guardando..." : (isEditing ? "Actualizar" : "Guardar") }}
            </DarkButton>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div v-if="isDeleteModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="cancelDelete"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
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
          <DeleteButton type="button" @click="deleteVehicle">Eliminar</DeleteButton>
        </div>
      </div>
    </div>

    <div v-if="isNewBrandModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeNewBrandModal"></div>
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Añadir Nueva Marca</h2>
          <button @click="closeNewBrandModal" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <form @submit.prevent="submitNewBrandForm">
          <DefaultInput
            id="brand_name"
            v-model="newBrandForm.name"
            label="Nombre de la Marca"
            :error="newBrandForm.errors.name"
            required
          />
          <div class="mt-6 flex justify-end space-x-3">
            <LightButton type="button" @click="closeNewBrandModal">Cancelar</LightButton>
            <DarkButton type="submit" :disabled="newBrandForm.processing">Guardar</DarkButton>
          </div>
        </form>
      </div>
    </div>

    <div v-if="isNewModelModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeNewModelModal"></div>
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Añadir Nuevo Modelo</h2>
          <button @click="closeNewModelModal" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <form @submit.prevent="submitNewModelForm">
          <div class="space-y-4">
            <SearchableSelect
              id="new_model_brand"
              v-model="newModelForm.brand_id"
              label="Marca"
              :options="props.brands"
              placeholder="Seleccione una marca"
              :searchable="false"
              value-field="id"
              label-field="name"
              :error="newModelForm.errors.brand_id"
              required
            />
            <DefaultInput
              id="model_name"
              v-model="newModelForm.name"
              label="Nombre del Modelo"
              :error="newModelForm.errors.name"
              required
            />
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <LightButton type="button" @click="closeNewModelModal">Cancelar</LightButton>
            <DarkButton type="submit" :disabled="newModelForm.processing">Guardar</DarkButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
