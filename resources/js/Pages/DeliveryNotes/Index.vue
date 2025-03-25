<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import DataTable from "@/Components/DataTable.vue";
import DateRangeSearch from "@/Components/DateRangeSearch.vue";
import DarkButton from "@/Components/DarkButton.vue";
import LightButton from "@/Components/LightButton.vue";
import DefaultInput from "@/Components/DefaultInput.vue";
import DefaultSelect from "@/Components/DefaultSelect.vue";

const props = defineProps({
  delivery_notes: {
    type: Array,
    required: true,
  },
});

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
  { key: "added_at", label: "Fecha de Alta" },
];

const searchQuery = ref("");
const dateRange = ref({
  startDate: "",
  endDate: "",
});
const isModalOpen = ref(false);

const form = useForm({
  type: "generic",
  supplier: "",
  family: "",
  quantity: 1,
  unitary_price: 0,
  RRP: 0,
  cost: 0,
  margin: 0,
  profit: 0,
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

function filterDeliveryNotes() {
  let filteredNotes = props.delivery_notes;

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
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  form.reset();
}

function submitForm() {
  form.post(route("delivery_notes.store"), {
    onSuccess: () => {
      closeModal();
    },
  });
}
</script>

<template>
  <Head title="Albaranes" />

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
        <DarkButton @click="addNewDeliveryNote"> Añadir Albarán </DarkButton>
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

    <DataTable :data="filterDeliveryNotes()" :columns="columns" :items-per-page="10" />

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Añadir Nuevo Albarán</h2>
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

            <DefaultInput
              id="supplier"
              v-model="form.supplier"
              label="Proveedor"
              required
              :error="form.errors.supplier"
            />

            <DefaultInput
              id="family"
              v-model="form.family"
              label="Familia"
              required
              :error="form.errors.family"
            />

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
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <LightButton type="button" @click="closeModal"> Cancelar </LightButton>
            <DarkButton type="submit" :disabled="form.processing"> Guardar </DarkButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
