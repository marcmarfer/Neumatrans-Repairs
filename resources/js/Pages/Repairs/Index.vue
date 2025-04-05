<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import DataTable from "@/Components/DataTable.vue";
import DateRangeSearch from "@/Components/DateRangeSearch.vue";
import DarkButton from "@/Components/DarkButton.vue";
import LightButton from "@/Components/LightButton.vue";
import DefaultInput from "@/Components/DefaultInput.vue";
import DefaultSelect from "@/Components/DefaultSelect.vue";
import GoBackButton from "@/Components/GoBackButton.vue";
import DeleteButton from "@/Components/DeleteButton.vue";
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
  { key: "current_step.step_name", label: "Paso Actual" },
  { key: "observations", label: "Observaciones" },
  { key: "started_at", label: "Fecha de Inicio" },
];

const completedColumns = [
  ...columns,
  { key: "completed_at", label: "Fecha de Finalización" },
];

const searchQuery = ref("");
const dateRange = ref({
  startDate: "",
  endDate: "",
});
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const repairToDelete = ref(null);
const typeSteps = ref([]);
const isEditing = ref(false);
const activeTab = ref("inProgress");

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  id: "",
  vehicle_id: "",
  repair_type_id: "",
  observations: "",
  step_id: "",
  started_at: today,
});

function inProgressRepairs() {
  return filterRepairs().filter(repair => !repair.completed_at);
}

function completedRepairs() {
  return filterRepairs().filter(repair => repair.completed_at);
}

watch(() => form.repair_type_id, (newTypeId) => {
  if (newTypeId) {
    const selectedType = props.repair_types.find(type => type.id.toString() === newTypeId);
    if (selectedType && selectedType.repair_type_step) {
      typeSteps.value = selectedType.repair_type_step;
      if (typeSteps.value.length > 0) {
        form.step_id = typeSteps.value[0].id.toString();
      }
    } else {
      typeSteps.value = [];
      form.step_id = "";
    }
  } else {
    typeSteps.value = [];
    form.step_id = "";
  }
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

function setActiveTab(tab) {
  activeTab.value = tab;
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
  form.step_id = repair.current_step.id.toString();
  form.started_at = repair.started_at;
  
  // Load the steps for this repair type
  const selectedType = props.repair_types.find(type => type.id === repair.repair_type_id);
  if (selectedType && selectedType.repair_type_step) {
    typeSteps.value = selectedType.repair_type_step;
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
          En Progreso ({{ inProgressRepairs().length }})
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
          Completadas ({{ completedRepairs().length }})
        </button>
      </div>
    </div>

    <div v-if="activeTab === 'inProgress'">
      <DataTable 
        :data="inProgressRepairs()" 
        :columns="columns" 
        :items-per-page="10" 
        @delete="confirmDelete"
        @edit="editRepair"
      />
      <p v-if="inProgressRepairs().length === 0" class="text-center text-gray-500 my-8">
        No hay reparaciones en progreso
      </p>
    </div>

    <div v-if="activeTab === 'completed'">
      <DataTable 
        :data="completedRepairs()" 
        :columns="completedColumns" 
        :items-per-page="10" 
        @delete="confirmDelete"
        @edit="editRepair"
      />
      <p v-if="completedRepairs().length === 0" class="text-center text-gray-500 my-8">
        No hay reparaciones completadas
      </p>
    </div>

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10">
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

            <DefaultSelect
              id="step_id"
              v-model="form.step_id"
              label="Paso Actual"
              :options="typeSteps"
              value-field="id"
              label-field="step_name"
              placeholder="Seleccione el paso actual"
              required
              :error="form.errors.step_id"
              :disabled="!form.repair_type_id || typeSteps.length === 0"
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
