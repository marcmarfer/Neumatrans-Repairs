<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import DataTable from "@/Components/DataTable.vue";
import DateRangeSearch from "@/Components/DateRangeSearch.vue";
import DarkButton from "@/Components/DarkButton.vue";
import LightButton from "@/Components/LightButton.vue";
import DefaultInput from "@/Components/DefaultInput.vue";
import DefaultSelect from "@/Components/DefaultSelect.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";
import GoBackButton from "@/Components/GoBackButton.vue";
import DeleteButton from "@/Components/DeleteButton.vue";
import axios from 'axios';

const props = defineProps({
  repair_orders: {
    type: Array,
    required: true,
  },
  clients: {
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
  { 
    key: "repairs", 
    label: "Vehículos", 
    formatter: (repairs) => {
      if (!repairs || repairs.length === 0) return 'Sin vehículos';
      
      const uniqueVehicles = {};
      repairs.forEach(repair => {
        if (repair.vehicle) {
          uniqueVehicles[repair.vehicle.plate_number] = repair.vehicle;
        }
      });
      
      return Object.values(uniqueVehicles)
        .map(v => {
          const brandName = v.brand?.name || '';
          const modelName = v.model?.name || '';
          const plate = v.plate_number || '';
          const name = [brandName, modelName].filter(Boolean).join(' ');
          return name ? `${name} (${plate})` : plate;
        })
        .join(', ');
    }
  },
  { 
    key: "status", 
    label: "Estado",
    formatter: (status) => {
      const statusMap = {
        'reception': 'En recepción',
        'in_repair': 'En reparación',
        'finished': 'Finalizado'
      };
      return statusMap[status] || status;
    }
  },
  { key: "observations", label: "Observaciones" },
  { 
    key: "created_at", 
    label: "Fecha de Creación",
    formatter: formatDate
  },
];

const completedColumns = [
  ...columns.slice(0, -1),
  { 
    key: "created_at", 
    label: "Fecha de Creación",
    formatter: formatDate
  },
  { 
    key: "completed_at", 
    label: "Fecha de Finalización",
    formatter: formatDate
  },
];

const searchQuery = ref("");
const dateRange = ref({
  startDate: "",
  endDate: "",
});
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const repairOrderToDelete = ref(null);
const isEditing = ref(false);
const activeTab = ref("inProgress");
const activeCategory = ref("orders");
const filteredVehicles = ref([]);
const selectedVehicle = ref(null);
const loadingResend = ref(false);
const isEditCompletionModalOpen = ref(false);
const originalStatus = ref('');

const form = useForm({
  id: "",
  client_id: "",
  vehicle_id: "",
  observations: "",
  status: "reception",
  send_email: true,
  send_completion_email: false,
  repairs: [
    {
      repair_type_id: "",
      observations: ""
    }
  ]
});

const completionForm = useForm({
  client_id: "",
  vehicle_id: "",
  observations: "",
  status: "finished",
  send_completion_email: true,
  repairs: []
});

const isResendModalOpen = ref(false);
const isCompletionModalOpen = ref(false);
const loadingCompletion = ref(false);
const actionOrder = ref(null);

function inProgressRepairOrders() {
  return filterRepairOrders().filter(order => !order.completed_at);
}

function completedRepairOrders() {
  return filterRepairOrders().filter(order => order.completed_at);
}

watch(() => form.client_id, (newClientId) => {
  if (newClientId) {
    filteredVehicles.value = props.vehicles
      .filter(vehicle => vehicle.client_id.toString() === newClientId.toString())
      .map(vehicle => ({
        ...vehicle,
        displayName: (() => {
          const plate = vehicle.plate_number || '';
          const brandName = vehicle.brand?.name || '';
          const modelName = vehicle.model?.name || '';
          const full = [brandName, modelName].filter(Boolean).join(' ');
          return full ? `${plate} - ${full}` : plate;
        })()
      }));
    
    if (form.vehicle_id && !filteredVehicles.value.some(v => v.id.toString() === form.vehicle_id.toString())) {
      form.vehicle_id = "";
      selectedVehicle.value = null;
    }
  } else {
    filteredVehicles.value = [];
    form.vehicle_id = "";
    selectedVehicle.value = null;
  }
});

watch(() => form.vehicle_id, (newVehicleId) => {
  if (newVehicleId) {
    selectedVehicle.value = filteredVehicles.value.find(v => v.id.toString() === newVehicleId.toString());
  } else {
    selectedVehicle.value = null;
  }
});

function filterRepairOrders() {
  let filtered = props.repair_orders;

  if (dateRange.value.startDate || dateRange.value.endDate) {
    filtered = filtered.filter((order) => {
      const createdDate = new Date(order.created_at);
      const startDate = dateRange.value.startDate
        ? new Date(dateRange.value.startDate)
        : null;
      const endDate = dateRange.value.endDate ? new Date(dateRange.value.endDate) : null;

      if (startDate && endDate) {
        return createdDate >= startDate && createdDate <= endDate;
      } else if (startDate) {
        return createdDate >= startDate;
      } else if (endDate) {
        return createdDate <= endDate;
      }

      return true;
    });
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(
      (order) =>
        order.client?.name?.toLowerCase().includes(query) ||
        order.repairs?.some(repair => 
          repair.vehicle?.plate_number?.toLowerCase().includes(query) ||
          repair.vehicle?.brand?.name?.toLowerCase().includes(query)
        )
    );
  }

  return filtered;
}

function setActiveTab(tab) {
  activeTab.value = tab;
}

function setActiveCategory(category) {
  activeCategory.value = category;
  if (category === 'individual') {
    router.visit(route('repairs.index'));
  }
}

function addNewRepairOrder() {
  isEditing.value = false;
  form.reset();
  form.status = "reception";
  form.repairs = [{ repair_type_id: "", observations: "" }];
  isModalOpen.value = true;
}

function addRepair() {
  form.repairs.push({ repair_type_id: "", observations: "" });
}

function removeRepair(index) {
  if (form.repairs.length > 1) {
    form.repairs.splice(index, 1);
  }
}

function editRepairOrder(order) {
  originalStatus.value = order.status;
  isEditing.value = true;
  form.reset();
  form.id = order.id;
  form.client_id = order.client_id.toString();
  form.observations = order.observations;
  form.status = order.status;
  
  filteredVehicles.value = props.vehicles
    .filter(vehicle => vehicle.client_id === order.client_id)
    .map(vehicle => ({
      ...vehicle,
      displayName: (() => {
        const plate = vehicle.plate_number || '';
        const brandName = vehicle.brand?.name || '';
        const modelName = vehicle.model?.name || '';
        const full = [brandName, modelName].filter(Boolean).join(' ');
        return full ? `${plate} - ${full}` : plate;
      })()
    }));
  
  if (order.repairs && order.repairs.length > 0) {
    const vehicleGroups = {};
    order.repairs.forEach(repair => {
      if (!vehicleGroups[repair.vehicle_id]) {
        vehicleGroups[repair.vehicle_id] = [];
      }
      vehicleGroups[repair.vehicle_id].push(repair);
    });
    
    const firstVehicleId = Object.keys(vehicleGroups)[0];
    if (firstVehicleId) {
      form.vehicle_id = firstVehicleId.toString();
      selectedVehicle.value = filteredVehicles.value.find(v => v.id.toString() === firstVehicleId.toString());
      
      form.repairs = vehicleGroups[firstVehicleId].map(repair => ({
        id: repair.id,
        repair_type_id: repair.repair_type_id.toString(),
        observations: repair.observations
      }));
    } else {
      form.repairs = [{ repair_type_id: "", observations: "" }];
    }
  } else {
    form.repairs = [{ repair_type_id: "", observations: "" }];
  }
  
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  form.reset();
  isEditing.value = false;
}

function submitForm() {
  if (isEditing.value && form.status === 'finished' && originalStatus.value !== 'finished') {
    isEditCompletionModalOpen.value = true;
    return;
  }
  if (isEditing.value) {
    form.put(route("repair-orders.update", form.id), {
      onSuccess: () => {
        closeModal();
      },
    });
  } else {
    form.post(route("repair-orders.store"), {
      onSuccess: () => {
        closeModal();
      },
    });
  }
}

function confirmDelete(order) {
  repairOrderToDelete.value = order;
  isDeleteModalOpen.value = true;
}

function cancelDelete() {
  isDeleteModalOpen.value = false;
  repairOrderToDelete.value = null;
}

function deleteRepairOrder() {
  if (repairOrderToDelete.value) {
    router.delete(route("repair-orders.destroy", repairOrderToDelete.value.id), {
      onSuccess: () => {
        isDeleteModalOpen.value = false;
        repairOrderToDelete.value = null;
      },
    });
  }
}

function isOrderCompleted() {
  return form.status === 'finished';
}

function openResendModal(order) {
  actionOrder.value = order;
  isResendModalOpen.value = true;
}

function cancelResend() {
  isResendModalOpen.value = false;
  actionOrder.value = null;
}

async function resendCreated() {
  loadingResend.value = true;
  try {
    await axios.post(route('repair-orders.resendEmail', actionOrder.value.id), { type: 'created' });
    cancelResend();
  } catch (error) {
    console.error(error);
  } finally {
    loadingResend.value = false;
  }
}

async function resendCompleted() {
  loadingResend.value = true;
  try {
    await axios.post(route('repair-orders.resendEmail', actionOrder.value.id), { type: 'completed' });
    cancelResend();
  } catch (error) {
    console.error(error);
  } finally {
    loadingResend.value = false;
  }
}

function openCompletionModal(order) {
  actionOrder.value = order;
  completionForm.reset();
  completionForm.client_id = order.client_id.toString();
  completionForm.vehicle_id = order.repairs.length > 0 ? order.repairs[0].vehicle_id.toString() : "";
  completionForm.observations = order.observations || "";
  completionForm.status = 'finished';
  completionForm.send_completion_email = true;
  completionForm.repairs = order.repairs.map(r => ({
    id: r.id,
    repair_type_id: r.repair_type_id.toString(),
    observations: r.observations
  }));
  isCompletionModalOpen.value = true;
}

async function confirmCompletion(sendEmail = true) {
  loadingCompletion.value = true;
  try {
    await axios.put(route('repair-orders.update', actionOrder.value.id), {
      client_id: completionForm.client_id,
      vehicle_id: completionForm.vehicle_id,
      observations: completionForm.observations,
      status: 'finished',
      send_completion_email: sendEmail,
      repairs: completionForm.repairs,
    });
    cancelCompletion();
    router.reload({ preserveState: true, preserveScroll: true });
  } catch (error) {
    console.error(error);
  } finally {
    loadingCompletion.value = false;
  }
}

function cancelCompletion() {
  isCompletionModalOpen.value = false;
  actionOrder.value = null;
}

function cancelEditCompletion() {
  isEditCompletionModalOpen.value = false;
}

function confirmEdit(sendEmail) {
  form.send_completion_email = sendEmail;
  form.put(route("repair-orders.update", form.id), {
    onSuccess: () => {
      cancelEditCompletion();
      closeModal();
    },
  });
}
</script>

<template>
  <Head title="Órdenes de Reparación" />
  <GoBackButton type="button" @click="router.visit(route('dashboard.show'))">
    Volver
  </GoBackButton>
  <div class="container mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Órdenes de Reparación</h1>

    <div class="mb-6 space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Buscar por cliente o matrícula..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <DarkButton @click="addNewRepairOrder"> Añadir Orden de Reparación </DarkButton>
      </div>

      <DateRangeSearch
        start-label="Fecha desde"
        end-label="Fecha hasta"
        @update:dateRange="(newRange) => (dateRange = newRange)"
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
          En Progreso ({{ inProgressRepairOrders().length }})
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
          Completadas ({{ completedRepairOrders().length }})
        </button>
      </div>
    </div>

    <div v-if="activeTab === 'inProgress'">
      <DataTable 
        :data="inProgressRepairOrders()" 
        :columns="columns" 
        :items-per-page="10" 
        @delete="confirmDelete"
        @edit="editRepairOrder"
        @complete="openCompletionModal"
        @resend="openResendModal"
      />
      <p v-if="inProgressRepairOrders().length === 0" class="text-center text-gray-500 my-8">
        No hay órdenes de reparación en progreso
      </p>
    </div>

    <div v-if="activeTab === 'completed'">
      <DataTable 
        :data="completedRepairOrders()" 
        :columns="completedColumns" 
        :items-per-page="10" 
        @delete="confirmDelete"
        @edit="editRepairOrder"
        @complete="openCompletionModal"
        @resend="openResendModal"
      />
      <p v-if="completedRepairOrders().length === 0" class="text-center text-gray-500 my-8">
        No hay órdenes de reparación completadas
      </p>
    </div>

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-xl mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">{{ isEditing ? 'Editar Orden de Reparación' : 'Añadir Nueva Orden de Reparación' }}</h2>
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
          <div class="space-y-6">
            <div class="bg-gray-50 p-4 rounded-lg">
              <h3 class="font-bold text-lg mb-3">Información General</h3>
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
                />

                <SearchableSelect
                  id="vehicle_id"
                  v-model="form.vehicle_id"
                  label="Vehículo"
                  :options="filteredVehicles"
                  value-field="id"
                  label-field="displayName"
                  placeholder="Seleccione un vehículo"
                  search-placeholder="Buscar por matrícula o tipo de coche..."
                  required
                  :error="form.errors.vehicle_id"
                  :disabled="!form.client_id"
                >
                  <template #option="{ option }">
                    {{ option.displayName }}
                  </template>
                </SearchableSelect>

                <DefaultInput
                  id="observations"
                  v-model="form.observations"
                  label="Observaciones Generales"
                  :error="form.errors.observations"
                  isTextarea
                  :rows="2"
                />

                <SearchableSelect
                  id="status"
                  v-model="form.status"
                  label="Estado"
                  :options="[
                    { id: 'reception', name: 'En recepción' },
                    { id: 'diagnosing', name: 'Diagnóstico' },
                    { id: 'in_repair', name: 'En reparación' },
                    { id: 'finished', name: 'Finalizado' }
                  ]"
                  value-field="id"
                  label-field="name"
                  placeholder="Seleccione un estado"
                  :searchable="false"
                  required
                  :error="form.errors.status"
                  :disabled="originalStatus === 'finished'"
                />
              </div>
            </div>

            <div v-if="form.vehicle_id" class="bg-gray-50 p-4 rounded-lg">
              <div class="flex justify-between items-center mb-3">
                <h3 class="font-bold text-lg">Reparaciones para {{ selectedVehicle?.brand?.name || '' }} {{ selectedVehicle?.model?.name || '' }} ({{ selectedVehicle?.plate_number || '' }})</h3>
                <DarkButton 
                  v-if="!isOrderCompleted()" 
                  type="button" 
                  @click="addRepair" 
                  class="text-sm py-1 px-2"
                >
                  + Añadir Reparación
                </DarkButton>
                <span v-else class="text-sm text-gray-500 italic">
                  No se pueden añadir reparaciones a una orden completada
                </span>
              </div>
              
              <div v-for="(repair, index) in form.repairs" :key="index" class="p-3 bg-white rounded-lg mb-3 border border-gray-200">
                <div class="flex justify-between items-center mb-2">
                  <h4 class="font-semibold">Reparación #{{ index + 1 }}</h4>
                  <button 
                    v-if="form.repairs.length > 1 && !isOrderCompleted()" 
                    type="button" 
                    @click="removeRepair(index)" 
                    class="text-red-500 hover:text-red-700"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
                
                <div class="space-y-3">
                  <SearchableSelect
                    :id="`repair_type_id_${index}`"
                    v-model="repair.repair_type_id"
                    label="Tipo de Reparación"
                    :options="props.repair_types"
                    value-field="id"
                    label-field="name"
                    placeholder="Seleccione un tipo de reparación"
                    search-placeholder="Buscar tipo de reparación..."
                    required
                    :error="form.errors[`repairs.${index}.repair_type_id`]"
                    :disabled="isOrderCompleted()"
                  />
                  
                  <DefaultInput
                    :id="`observations_${index}`"
                    v-model="repair.observations"
                    label="Observaciones de la Reparación"
                    :error="form.errors[`repairs.${index}.observations`]"
                    isTextarea
                    :rows="2"
                    :disabled="isOrderCompleted()"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <div v-if="!isEditing" class="flex-grow">
              <label class="inline-flex items-center">
                <input 
                  type="checkbox" 
                  v-model="form.send_email" 
                  class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                >
                <span class="ml-2 text-gray-700">Enviar correo al cliente sobre su progreso</span>
              </label>
            </div>
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
          <p class="text-gray-700">¿Estás seguro que deseas eliminar la orden de reparación para el cliente <span class="font-bold">{{ repairOrderToDelete?.client?.name }}</span>?</p>
          <p class="text-sm text-red-500 mt-2">Esta acción eliminará también todas las reparaciones asociadas y no se puede deshacer.</p>
        </div>

        <div class="flex justify-end space-x-3">
          <LightButton type="button" @click="cancelDelete">Cancelar</LightButton>
          <DeleteButton type="button" @click="deleteRepairOrder">Eliminar</DeleteButton>
        </div>
      </div>
    </div>

    <div v-if="isResendModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="cancelResend"></div>
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Reenviar Correo</h2>
          <button @click="cancelResend" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <p>
          {{ actionOrder.status !== 'finished'
            ? '¿Deseas reenviar el correo de nueva orden?'
            : '¿Deseas reenviar el correo de reparación completada?'
          }}
        </p>
        <div class="mt-6 flex justify-end space-x-3">
          <LightButton type="button" @click="cancelResend">No</LightButton>
          <DarkButton
            type="button"
            @click="actionOrder.status !== 'finished' ? resendCreated() : resendCompleted()"
            :disabled="loadingResend"
          >
            {{ loadingResend ? 'Enviando...' : 'Sí' }}
          </DarkButton>
        </div>
      </div>
    </div>

    <div v-if="isCompletionModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="cancelCompletion"></div>
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Finalizar Reparación</h2>
          <button @click="cancelCompletion" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <p>¿Deseas marcar la reparación como finalizada y enviar el correo de reparación completada?</p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
          <LightButton type="button" class="flex-1" @click="cancelCompletion">No</LightButton>
          <LightButton type="button" class="flex-1" @click="confirmCompletion(false)" :disabled="loadingCompletion">
            Finalizar sin correo
          </LightButton>
          <DarkButton type="button" class="flex-1" @click="confirmCompletion(true)" :disabled="loadingCompletion">
            {{ loadingCompletion ? 'Actualizando...' : 'Finalizar y enviar correo' }}
          </DarkButton>
        </div>
      </div>
    </div>

    <div v-if="isEditCompletionModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="cancelEditCompletion"></div>
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg mx-4 z-10 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Finalizar Orden de Reparación</h2>
          <button @click="cancelEditCompletion" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <p class="mb-4">¿Deseas finalizar la orden sin enviar correo o también enviar correo de finalización?</p>
        <div class="mt-4 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
          <LightButton
            type="button"
            class="flex-1 px-3 py-2 text-sm"
            @click="cancelEditCompletion"
          >Cancelar</LightButton>
          <LightButton
            type="button"
            class="flex-1 px-3 py-2 text-sm"
            @click="confirmEdit(false)"
            :disabled="form.processing"
          >Finalizar sin correo</LightButton>
          <DarkButton
            type="button"
            class="flex-1 px-3 py-2 text-sm"
            @click="confirmEdit(true)"
            :disabled="form.processing"
          >{{ form.processing ? 'Actualizando...' : 'Finalizar y enviar correo' }}</DarkButton>
        </div>
      </div>
    </div>
  </div>
</template> 