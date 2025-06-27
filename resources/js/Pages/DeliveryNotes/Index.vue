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
  delivery_notes: {
    type: Array,
    required: true,
  },
  suppliers: {
    type: Array,
    required: true,
  },
  families: {
    type: Array,
    required: true,
  },
});

function getSortedSuppliers() {
  return [...props.suppliers].sort((a, b) => a.name.localeCompare(b.name));
}

function getSortedFamilies() {
  return [...props.families].sort((a, b) => a.name.localeCompare(b.name));
}

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
  { key: "type", label: "Tipo" },
  { key: "supplier", label: "Proveedor" },
  { key: "family", label: "Familia" },
  { key: "quantity", label: "Cantidad" },
  { key: "unitary_price", label: "Precio Unitario" },
  { key: "RRP", label: "PVP" },
  { key: "cost", label: "Coste" },
  { key: "margin", label: "Margen" },
  { key: "profit", label: "Beneficio" },
  { 
    key: "added_at", 
    label: "Fecha de Alta",
    formatter: formatDate
  },
];

const searchQuery = ref("");
const dateRange = ref({
  startDate: "",
  endDate: "",
});
const isModalOpen = ref(false);
const isNewSupplierModalOpen = ref(false);
const isNewFamilyModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const deliveryNoteToDelete = ref(null);
const isEditing = ref(false);
const newSupplierForm = useForm({
  name: '',
});
const newFamilyForm = useForm({
  name: '',
});

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  id: "",
  type: "generic",
  supplier: "",
  family: "",
  quantity: 1,
  unitary_price: 0,
  RRP: 0,
  cost: 0,
  margin: 0,
  profit: 0,
  added_at: today,
});

watch([() => form.quantity, () => form.unitary_price], ([newQuantity, newUnitaryPrice]) => {
  if (newQuantity > 0 && newUnitaryPrice > 0) {
    form.RRP = parseFloat(newQuantity) * parseFloat(newUnitaryPrice);
    if (form.cost > 0) {
      form.profit = form.RRP - parseFloat(form.cost);
      form.margin = (form.profit / form.RRP) * 100;
    }
  } else {
    form.RRP = 0;
    form.profit = 0;
    form.margin = 0;
  }
});

watch(() => form.cost, (newCost) => {
  if (form.RRP > 0 && newCost > 0) {
    form.profit = form.RRP - parseFloat(newCost);
    form.margin = (form.profit / form.RRP) * 100;
  } else {
    form.profit = 0;
    form.margin = 0;
  }
});

function calculateTotals() {
  const filteredData = filterDeliveryNotes();
  return {
    totalSold: filteredData.reduce((sum, note) => sum + parseFloat(note.RRP), 0),
    totalSpent: filteredData.reduce((sum, note) => sum + parseFloat(note.cost), 0),
    totalProfit: filteredData.reduce((sum, note) => sum + parseFloat(note.profit), 0),
    averageMargin:
      filteredData.length > 0
        ? filteredData.reduce((sum, note) => sum + parseFloat(note.margin), 0) /
          filteredData.length
        : 0,
  };
}

function formatType(type) {
  if (type === 'corrective') return 'Correctivo';
  if (type === 'generic') return 'Genérico';
  return type;
}

function filterDeliveryNotes() {
  let filteredNotes = [...props.delivery_notes];

  filteredNotes = filteredNotes.map(note => ({
    ...note,
    type: formatType(note.type)
  }));

  // Filter by date range
  if (dateRange.value.startDate || dateRange.value.endDate) {
    filteredNotes = filteredNotes.filter((note) => {
      const addedDate = new Date(note.added_at);
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

  // Filter by search query (family or supplier)
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filteredNotes = filteredNotes.filter(
      (note) =>
        note.family.toLowerCase().includes(query) ||
        note.supplier.toLowerCase().includes(query)
    );
  }

  return filteredNotes;
}

function addNewDeliveryNote() {
  isEditing.value = false;
  form.reset();
  form.type = "generic";
  form.quantity = 1;
  form.added_at = today;
  isModalOpen.value = true;
}

function editDeliveryNote(deliveryNote) {
  isEditing.value = true;
  form.reset();
  form.id = deliveryNote.id;
  form.type = deliveryNote.type === 'Genérico' ? 'generic' : (deliveryNote.type === 'Correctivo' ? 'corrective' : deliveryNote.type);
  form.supplier = deliveryNote.supplier;
  form.family = deliveryNote.family;
  form.quantity = deliveryNote.quantity;
  form.unitary_price = deliveryNote.unitary_price;
  form.RRP = deliveryNote.RRP;
  form.cost = deliveryNote.cost;
  form.margin = deliveryNote.margin;
  form.profit = deliveryNote.profit;
  form.added_at = deliveryNote.added_at;
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  form.reset();
  isEditing.value = false;
}

function openNewSupplierModal() {
  isNewSupplierModalOpen.value = true;
}

function closeNewSupplierModal() {
  isNewSupplierModalOpen.value = false;
  newSupplierForm.reset();
}

function submitNewSupplierForm() {
  newSupplierForm.post(route("suppliers.store"), {
    onSuccess: () => {
      closeNewSupplierModal();
      router.reload({ only: ['suppliers'] });
    },
  });
}

function submitForm() {
  if (isEditing.value) {
    form.put(route("delivery_notes.update", form.id), {
      onSuccess: () => {
        closeModal();
      },
    });
  } else {
    form.post(route("delivery_notes.store"), {
      onSuccess: () => {
        closeModal();
      },
    });
  }
}

function openNewFamilyModal() {
  isNewFamilyModalOpen.value = true;
}

function closeNewFamilyModal() {
  isNewFamilyModalOpen.value = false;
  newFamilyForm.reset();
}

function submitNewFamilyForm() {
  newFamilyForm.post(route("families.store"), {
    onSuccess: () => {
      closeNewFamilyModal();
      router.reload({ only: ['families'] });
    },
  });
}

function confirmDelete(deliveryNote) {
  deliveryNoteToDelete.value = deliveryNote;
  isDeleteModalOpen.value = true;
}

function cancelDelete() {
  isDeleteModalOpen.value = false;
  deliveryNoteToDelete.value = null;
}

function deleteDeliveryNote() {
  if (deliveryNoteToDelete.value) {
    router.delete(route("delivery_notes.destroy", deliveryNoteToDelete.value.id), {
      onSuccess: () => {
        isDeleteModalOpen.value = false;
        deliveryNoteToDelete.value = null;
      },
    });
  }
}
</script>

<template>
  <Head title="Albaranes" />
  <GoBackButton type="button" @click="router.visit(route('dashboard.show'))">
    Volver
  </GoBackButton>
  <div class="container mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Albaranes</h1>

    <div class="mb-6 space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Buscar por familia o proveedor..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <DarkButton @click="addNewDeliveryNote"> Añadir Referencia </DarkButton>
      </div>

      <DateRangeSearch
        start-label="Fecha de alta desde"
        end-label="Fecha de alta hasta"
        @update:dateRange="(newRange) => (dateRange = newRange)"
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
          <p class="text-xl font-bold">
            {{ calculateTotals().averageMargin.toFixed(2) }}%
          </p>
        </div>
      </div>
    </div>

    <DataTable 
      :data="filterDeliveryNotes()" 
      :columns="columns" 
      :items-per-page="10"
      @delete="confirmDelete"
      @edit="editDeliveryNote" 
    />

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">{{ isEditing ? 'Editar Albarán' : 'Añadir Nuevo Albarán' }}</h2>
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
              id="type"
              v-model="form.type"
              label="Tipo"
              required
              :error="form.errors.type"
              :options="[
                { value: 'generic', label: 'Genérico' },
                { value: 'corrective', label: 'Correctivo' },
              ]"
            />

            <div>
              <label for="supplier" class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
              <div class="flex space-x-2">
                <select
                  id="supplier"
                  v-model="form.supplier"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                  required
                >
                  <option value="" disabled>Selecciona un proveedor</option>
                  <option v-for="supplier in getSortedSuppliers()" :key="supplier.id" :value="supplier.name">
                    {{ supplier.name }}
                  </option>
                </select>
                <button 
                  type="button" 
                  @click="openNewSupplierModal"
                  class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Nuevo
                </button>
              </div>
              <div v-if="form.errors.supplier" class="text-sm text-red-600 mt-1">{{ form.errors.supplier }}</div>
            </div>

            <div>
              <label for="family" class="block text-sm font-medium text-gray-700 mb-1">Familia</label>
              <div class="flex space-x-2">
                <select
                  id="family"
                  v-model="form.family"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                  required
                >
                  <option value="" disabled>Selecciona una familia</option>
                  <option v-for="family in getSortedFamilies()" :key="family.id" :value="family.name">
                    {{ family.name }}
                  </option>
                </select>
                <button 
                  type="button" 
                  @click="openNewFamilyModal"
                  class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Nueva
                </button>
              </div>
              <div v-if="form.errors.family" class="text-sm text-red-600 mt-1">{{ form.errors.family }}</div>
            </div>

            <DefaultInput
              id="quantity"
              v-model="form.quantity"
              type="number"
              label="Cantidad"
              required
              :error="form.errors.quantity"
            />

            <DefaultInput
              id="unitary_price"
              v-model="form.unitary_price"
              type="number"
              label="Precio Unitario (€)"
              required
              :error="form.errors.unitary_price"
            />

            <DefaultInput
              id="RRP"
              :modelValue="form.RRP.toFixed(2)"
              label="PVP (€) [Auto-calculado]"
              readonly
            />

            <DefaultInput
              id="cost"
              v-model="form.cost"
              type="number"
              label="Coste (€)"
              required
              :error="form.errors.cost"
            />

            <DefaultInput
              id="profit"
              :modelValue="form.profit.toFixed(2)"
              label="Beneficio (€) [Auto-calculado]"
              readonly
            />

            <DefaultInput
              id="margin"
              :modelValue="form.margin.toFixed(2)"
              label="Margen (%) [Auto-calculado]"
              readonly
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
              {{ form.processing ? "Guardando..." : (isEditing ? "Actualizar" : "Guardar") }}
            </DarkButton>
          </div>
        </form>
      </div>
    </div>

    <div v-if="isNewSupplierModalOpen" class="fixed inset-0 flex items-center justify-center z-[60]">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeNewSupplierModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Añadir Nuevo Proveedor</h2>
          <button @click="closeNewSupplierModal" class="text-gray-500 hover:text-gray-700">
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

        <form @submit.prevent="submitNewSupplierForm">
          <div class="space-y-4">
            <DefaultInput
              id="supplier_name"
              v-model="newSupplierForm.name"
              label="Nombre del Proveedor"
              required
              :error="newSupplierForm.errors.name"
            />
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <LightButton type="button" @click="closeNewSupplierModal">Cancelar</LightButton>
            <DarkButton type="submit" :disabled="newSupplierForm.processing">Guardar</DarkButton>
          </div>
        </form>
      </div>
    </div>

    <div v-if="isNewFamilyModalOpen" class="fixed inset-0 flex items-center justify-center z-[60]">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeNewFamilyModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Añadir Nueva Familia</h2>
          <button @click="closeNewFamilyModal" class="text-gray-500 hover:text-gray-700">
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

        <form @submit.prevent="submitNewFamilyForm">
          <div class="space-y-4">
            <DefaultInput
              id="family_name"
              v-model="newFamilyForm.name"
              label="Nombre de la Familia"
              required
              :error="newFamilyForm.errors.name"
            />
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <LightButton type="button" @click="closeNewFamilyModal">Cancelar</LightButton>
            <DarkButton type="submit" :disabled="newFamilyForm.processing">Guardar</DarkButton>
          </div>
        </form>
      </div>
    </div>

    <div v-if="isDeleteModalOpen" class="fixed inset-0 flex items-center justify-center z-[60]">
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
          <p class="text-gray-700">¿Estás seguro que deseas eliminar el albarán de <span class="font-bold">{{ deliveryNoteToDelete?.family }}</span>?</p>
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
