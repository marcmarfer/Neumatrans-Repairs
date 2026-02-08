<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import DataTable from "@/Components/DataTable.vue";
import DateRangeSearch from "@/Components/DateRangeSearch.vue";
import DarkButton from "@/Components/DarkButton.vue";
import LightButton from "@/Components/LightButton.vue";
import DefaultInput from "@/Components/DefaultInput.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";
import GoBackButton from "@/Components/GoBackButton.vue";
import DeleteButton from "@/Components/DeleteButton.vue";
const props = defineProps({
  repairs: {
    type: Object,
    required: true,
  },
  in_progress_count: {
    type: Number,
    default: 0,
  },
  completed_count: {
    type: Number,
    default: 0,
  },
  vehicles: {
    type: Array,
    required: true,
  },
  repair_types: {
    type: Array,
    required: true,
  },
  repair_orders: {
    type: Array,
    required: true,
  },
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
  { key: "vehicle.client.name", label: "Cliente" },
  { key: "vehicle.brand", label: "Marca" },
  { key: "vehicle.plate_number", label: "Matrícula" },
  { key: "repair_type.name", label: "Tipo de Reparación" },
  { 
    key: "repair_order_id", 
    label: "Orden de Reparación",
    formatter: (repairOrderId) => repairOrderId ? `Orden #${repairOrderId}` : 'Sin asignar'
  },
  { key: "observations", label: "Observaciones" },
  { 
    key: "started_at", 
    label: "Fecha de Inicio",
    formatter: formatDate
  },
];

const completedColumns = [
  ...columns.slice(0, -1),
  { 
    key: "started_at", 
    label: "Fecha de Inicio",
    formatter: formatDate
  },
  { 
    key: "completed_at", 
    label: "Fecha de Finalización",
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
const repairToDelete = ref(null);
const typeSteps = ref([]);
const isEditing = ref(false);
const activeTab = ref(props.filters?.tab === 'completed' ? 'completed' : 'inProgress');
const activeCategory = ref("individual");
const filteredRepairOrders = ref([]);

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  id: "",
  vehicle_id: "",
  repair_type_id: "",
  observations: "",
  repair_order_id: "",
  started_at: today,
});

// Computed options for vehicles with full displayName
const vehicleOptions = computed(() => props.vehicles.map(vehicle => {
  const plate = vehicle.plate_number || '';
  const brandName = vehicle.brand?.name || '';
  const modelName = vehicle.model?.name || '';
  const clientName = vehicle.client?.name || '';
  const brandModel = [brandName, modelName].filter(Boolean).join(' ');
  const full = [plate, brandModel, clientName].filter(Boolean).join(' - ');
  return { ...vehicle, displayName: full };
}));

// ── Server-side data fetching ──────────────────────────────────────

const paginationMeta = computed(() => ({
  current_page: props.repairs.current_page,
  last_page: props.repairs.last_page,
  from: props.repairs.from,
  to: props.repairs.to,
  total: props.repairs.total,
  per_page: props.repairs.per_page,
}));

function fetchData(page = null) {
  const params = {};
  if (searchQuery.value) params.q = searchQuery.value;
  if (dateRange.value.startDate) params.start = dateRange.value.startDate;
  if (dateRange.value.endDate) params.end = dateRange.value.endDate;
  params.tab = activeTab.value === 'completed' ? 'completed' : 'in_progress';
  if (page && page > 1) params.page = page;

  router.get(route('repairs.index'), params, {
    preserveState: true,
    preserveScroll: true,
    only: ['repairs', 'in_progress_count', 'completed_count', 'filters'],
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

watch(() => form.repair_type_id, (newTypeId) => {
  if (newTypeId) {
    const selectedType = props.repair_types.find(type => type.id.toString() === newTypeId);
    if (!selectedType) {
      typeSteps.value = [];
    }
  } else {
    typeSteps.value = [];
  }
});

watch(() => form.vehicle_id, (newVehicleId) => {
  if (newVehicleId) {
    const vehicle = props.vehicles.find(v => v.id.toString() === newVehicleId.toString());
    if (vehicle && vehicle.client_id) {
      filteredRepairOrders.value = props.repair_orders
        .filter(repairOrder => repairOrder.client_id === vehicle.client_id && repairOrder.status !== 'finished')
        .map(repairOrder => ({
          ...repairOrder,
          displayName: `Orden #${repairOrder.id}`
        }));
      
      if (form.repair_order_id && !filteredRepairOrders.value.some(ro => ro.id.toString() === form.repair_order_id.toString())) {
        form.repair_order_id = "";
      }
    } else {
      filteredRepairOrders.value = [];
      form.repair_order_id = "";
    }
  } else {
    filteredRepairOrders.value = [];
    form.repair_order_id = "";
  }
});

function setActiveTab(tab) {
  activeTab.value = tab;
  fetchData(1);
}

function setActiveCategory(category) {
  activeCategory.value = category;
  if (category === 'orders') {
    router.visit(route('repair-orders.index'));
  }
}

function addNewRepair() {
  isEditing.value = false;
  form.reset();
  form.started_at = today;
  isModalOpen.value = true;
}

function editRepair(repair) {
  isEditing.value = true;
  form.reset();
  form.id = repair.id;
  form.vehicle_id = repair.vehicle_id.toString();
  form.repair_type_id = repair.repair_type_id.toString();
  form.observations = repair.observations;
  form.repair_order_id = repair.repair_order_id ? repair.repair_order_id.toString() : "";
  
  // Load the steps for this repair type
  const selectedType = props.repair_types.find(type => type.id === repair.repair_type_id);
  if (selectedType && selectedType.repair_type_step) {
    typeSteps.value = selectedType.repair_type_step;
  }
  
  if (repair.vehicle_id) {
    const vehicle = props.vehicles.find(v => v.id === repair.vehicle_id);
    if (vehicle && vehicle.client_id) {
      const currentOrder = props.repair_orders.find(repairOrder => repairOrder.id.toString() === form.repair_order_id);
      filteredRepairOrders.value = props.repair_orders.filter(repairOrder => 
        repairOrder.client_id === vehicle.client_id && (
          repairOrder.status !== 'finished' || 
          (currentOrder && repairOrder.id === currentOrder.id)
        )
      );
    }
  }
  
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  form.reset();
  isEditing.value = false;
}

function submitForm() {
  if (isEditing.value) {
    form.put(route("repairs.update", form.id), {
      onSuccess: () => {
        closeModal();
      },
    });
  } else {
    form.post(route("repairs.store"), {
      onSuccess: () => {
        closeModal();
      },
    });
  }
}

function confirmDelete(repair) {
  repairToDelete.value = repair;
  isDeleteModalOpen.value = true;
}

function cancelDelete() {
  isDeleteModalOpen.value = false;
  repairToDelete.value = null;
}

function deleteRepair() {
  if (repairToDelete.value) {
    router.delete(route("repairs.destroy", repairToDelete.value.id), {
      onSuccess: () => {
        isDeleteModalOpen.value = false;
        repairToDelete.value = null;
      },
    });
  }
}
</script>

<template>
  <Head title="Reparaciones" />
  <GoBackButton type="button" @click="router.visit(route('dashboard.show'))">
    Volver
  </GoBackButton>
  <div class="container mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Reparaciones</h1>

    <div class="mb-6 space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <input
          type="text"
          v-model="searchQuery"
          @input="onSearchInput"
          placeholder="Buscar por cliente o matrícula..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <DarkButton @click="addNewRepair"> Añadir Reparación </DarkButton>
      </div>

      <DateRangeSearch
        start-label="Fecha de inicio desde"
        end-label="Fecha de inicio hasta"
        :initial-start-date="filters?.start ?? ''"
        :initial-end-date="filters?.end ?? ''"
        @update:dateRange="onDateRangeChange"
      />
    </div>

    <div class="border-b border-gray-200 mb-6">
      <div class="flex flex-wrap -mb-px">
        <button
          @click="setActiveCategory('orders')"
          :class="[
            'inline-block py-4 px-6 border-b-2 font-medium text-sm',
            activeCategory === 'orders'
              ? 'border-black text-black'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          Ordenes de reparación
        </button>
        <button
          @click="setActiveCategory('individual')"
          :class="[
            'inline-block py-4 px-6 border-b-2 font-medium text-sm',
            activeCategory === 'individual'
              ? 'border-black text-black'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          Reparaciones individuales
        </button>
      </div>
    </div>

    <div class="border-b border-gray-200 mb-6">
      <div class="flex flex-wrap -mb-px">
        <button
          @click="setActiveTab('inProgress')"
          :class="[
            'inline-block py-4 px-6 border-b-2 font-medium text-sm',
            activeTab === 'inProgress'
              ? 'border-black text-black'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          En Progreso ({{ in_progress_count }})
        </button>
        <button
          @click="setActiveTab('completed')"
          :class="[
            'inline-block py-4 px-6 border-b-2 font-medium text-sm',
            activeTab === 'completed'
              ? 'border-black text-black'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          Completadas ({{ completed_count }})
        </button>
      </div>
    </div>

    <DataTable 
      :data="repairs.data" 
      :columns="activeTab === 'completed' ? completedColumns : columns" 
      :server-side="true"
      :meta="paginationMeta"
      @delete="confirmDelete"
      @edit="editRepair"
      @page-change="onPageChange"
    />
    <p v-if="repairs.data.length === 0" class="text-center text-gray-500 my-8">
      {{ activeTab === 'inProgress' ? 'No hay reparaciones en progreso' : 'No hay reparaciones completadas' }}
    </p>

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">{{ isEditing ? 'Editar Reparación' : 'Añadir Nueva Reparación' }}</h2>
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
              id="vehicle_id"
              v-model="form.vehicle_id"
              label="Vehículo"
              :options="vehicleOptions"
              value-field="id"
              label-field="displayName"
              placeholder="Seleccione un vehículo"
              search-placeholder="Buscar por matrícula, marca, modelo o cliente..."
              required
              :error="form.errors.vehicle_id"
            >
              <template #option="{ option }">
                {{ option.displayName }}
              </template>
            </SearchableSelect>

            <SearchableSelect
              id="repair_order_id"
              v-model="form.repair_order_id"
              label="Orden de Reparación"
              :options="filteredRepairOrders"
              value-field="id"
              label-field="displayName"
              placeholder="Seleccione una orden de reparación"
              search-placeholder="Buscar por ID..."
              required
              :error="form.errors.repair_order_id"
              :disabled="!form.vehicle_id"
            >
              <template #option="{ option }">
                {{ option.displayName }}
              </template>
            </SearchableSelect>

            <SearchableSelect
              id="repair_type_id"
              v-model="form.repair_type_id"
              label="Tipo de Reparación"
              :options="repair_types"
              value-field="id"
              label-field="name"
              placeholder="Seleccione un tipo de reparación"
              search-placeholder="Buscar tipo de reparación..."
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
              {{ form.processing ? "Guardando..." : (isEditing ? "Actualizar" : "Guardar") }}
            </DarkButton>
          </div>
        </form>
      </div>
    </div>

    <div v-if="isDeleteModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="cancelDelete"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
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
          <p class="text-gray-700">¿Estás seguro que deseas eliminar la reparación para el vehículo <span class="font-bold">{{ repairToDelete?.vehicle?.plate_number }}</span>?</p>
          <p class="text-sm text-red-500 mt-2">Esta acción no se puede deshacer.</p>
        </div>

        <div class="flex justify-end space-x-3">
          <LightButton type="button" @click="cancelDelete">Cancelar</LightButton>
          <DeleteButton type="button" @click="deleteRepair">Eliminar</DeleteButton>
        </div>
      </div>
    </div>
  </div>
</template>
