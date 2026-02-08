<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import DataTable from "@/Components/DataTable.vue";
import DateRangeSearch from "@/Components/DateRangeSearch.vue";
import DarkButton from "@/Components/DarkButton.vue";
import LightButton from "@/Components/LightButton.vue";
import DefaultInput from "@/Components/DefaultInput.vue";
import DefaultSelect from "@/Components/DefaultSelect.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";
import GoBackButton from "@/Components/GoBackButton.vue";
import DeleteButton from "@/Components/DeleteButton.vue";
import AddButton from '@/Components/AddButton.vue';
import ExportButton from '@/Components/ExportButton.vue';

const props = defineProps({
  delivery_notes: {
    type: Object,
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
  totals: {
    type: Object,
    required: true,
  },
  top_suppliers: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const sortedSuppliers = computed(() => {
  return [...props.suppliers].sort((a, b) => a.name.localeCompare(b.name));
});

const pinnedSuppliers = computed(() => {
  return props.top_suppliers.map(name => ({ name }));
});

const sortedFamilies = computed(() => {
  return [...props.families].sort((a, b) => a.name.localeCompare(b.name));
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

function formatType(type) {
  if (type === 'corrective') return 'Correctivo';
  if (type === 'generic') return 'Genérico';
  return type;
}

const columns = [
  { key: "id", label: "ID" },
  { key: "type", label: "Tipo", formatter: formatType },
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

// Filter state — initialized from server-provided filters
const searchQuery = ref(props.filters?.q ?? "");
const dateRange = ref({
  startDate: props.filters?.start ?? "",
  endDate: props.filters?.end ?? "",
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

// ── Server-side data fetching ──────────────────────────────────────

function fetchData(page = null) {
  const params = {};

  if (searchQuery.value) {
    params.q = searchQuery.value;
  }
  if (dateRange.value.startDate) {
    params.start = dateRange.value.startDate;
  }
  if (dateRange.value.endDate) {
    params.end = dateRange.value.endDate;
  }
  if (page && page > 1) {
    params.page = page;
  }

  router.get(route('delivery_notes.index'), params, {
    preserveState: true,
    preserveScroll: true,
    only: ['delivery_notes', 'totals', 'filters'],
  });
}

// Debounced search
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
  return route('delivery_notes.exportCsv') + (query ? '?' + query : '');
});

// ── Pagination meta for DataTable ──────────────────────────────────

const paginationMeta = computed(() => ({
  current_page: props.delivery_notes.current_page,
  last_page: props.delivery_notes.last_page,
  from: props.delivery_notes.from,
  to: props.delivery_notes.to,
  total: props.delivery_notes.total,
  per_page: props.delivery_notes.per_page,
}));

// ── CRUD ────────────────────────────────────────────────────────────

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
  form.type = deliveryNote.type;
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
          @input="onSearchInput"
          placeholder="Buscar por familia o proveedor..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <div class="flex gap-4 w-full sm:w-auto justify-between sm:justify-end">
          <DarkButton @click="addNewDeliveryNote" class="sm:order-1"> Añadir Referencia </DarkButton>
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

    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h2 class="text-lg font-semibold mb-4">Resumen de datos filtrados</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-600">Total Vendido (PVP)</p>
          <p class="text-xl font-bold">{{ totals.totalSold.toFixed(2) }}€</p>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-600">Total Gastado</p>
          <p class="text-xl font-bold">{{ totals.totalSpent.toFixed(2) }}€</p>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-600">Beneficio Total</p>
          <p class="text-xl font-bold">{{ totals.totalProfit.toFixed(2) }}€</p>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-600">Margen Beneficio Total</p>
          <p class="text-xl font-bold">
            {{ totals.totalMargin.toFixed(2) }}%
          </p>
        </div>
      </div>
    </div>

    <DataTable 
      :data="delivery_notes.data" 
      :columns="columns" 
      :server-side="true"
      :meta="paginationMeta"
      @delete="confirmDelete"
      @edit="editDeliveryNote"
      @page-change="onPageChange"
    />

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

       <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
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
            <SearchableSelect
              id="type"
              v-model="form.type"
              label="Tipo"
              :options="[
                { value: 'generic', label: 'Genérico' },
                { value: 'corrective', label: 'Correctivo' },
              ]"
              value-field="value"
              label-field="label"
              placeholder="Seleccione un tipo"
              :searchable="false"
              required
              :error="form.errors.type"
            />

            <div>
              <label for="supplier" class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
              <div class="flex space-x-2">
                <div class="flex-1">
                  <SearchableSelect
                    id="supplier"
                    v-model="form.supplier"
                    :options="sortedSuppliers"
                    value-field="name"
                    label-field="name"
                    placeholder="Seleccione un proveedor"
                    search-placeholder="Buscar proveedor..."
                    :pinned-options="pinnedSuppliers"
                    pinned-label="Más utilizados"
                    required
                    :error="form.errors.supplier"
                  />
                </div>
                <AddButton @click="openNewSupplierModal" />
              </div>
              <div v-if="form.errors.supplier" class="text-sm text-red-600 mt-1">{{ form.errors.supplier }}</div>
            </div>

            <div>
              <label for="family" class="block text-sm font-medium text-gray-700 mb-1">Familia</label>
              <div class="flex space-x-2">
                <div class="flex-1">
                  <SearchableSelect
                    id="family"
                    v-model="form.family"
                    :options="sortedFamilies"
                    value-field="name"
                    label-field="name"
                    placeholder="Seleccione una familia"
                    search-placeholder="Buscar familia..."
                    required
                    :error="form.errors.family"
                  />
                </div>
                <AddButton @click="openNewFamilyModal" />
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

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
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

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
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
