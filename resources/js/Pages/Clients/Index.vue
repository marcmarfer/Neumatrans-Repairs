<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import DataTable from "@/Components/DataTable.vue";
import DateRangeSearch from "@/Components/DateRangeSearch.vue";
import DarkButton from "@/Components/DarkButton.vue";
import LightButton from "@/Components/LightButton.vue";
import DefaultInput from "@/Components/DefaultInput.vue";

const props = defineProps({
  clients: {
    type: Array,
    required: true,
  },
});

const columns = [
  { key: "id", label: "ID" },
  { key: "DNI", label: "DNI" },
  { key: "name", label: "Nombre" },
  { key: "email", label: "Email" },
  { key: "telephone", label: "Teléfono" },
  { key: "city", label: "Ciudad" },
  { key: "postal_code", label: "Código Postal" },
  { key: "registered_at", label: "Fecha de Registro" },
];

const dateRange = ref({
  startDate: "",
  endDate: "",
});

const searchQuery = ref("");
const isModalOpen = ref(false);

const form = useForm({
  DNI: "",
  name: "",
  email: "",
  telephone: "",
  city: "",
  postal_code: "",
});

function filterClients() {
  let filteredClients = props.clients;

  // Filter by date range
  if (dateRange.value.startDate || dateRange.value.endDate) {
    filteredClients = filteredClients.filter((client) => {
      const registeredDate = new Date(client.registered_at);
      const startDate = dateRange.value.startDate
        ? new Date(dateRange.value.startDate)
        : null;
      const endDate = dateRange.value.endDate ? new Date(dateRange.value.endDate) : null;

      if (startDate && endDate) {
        return registeredDate >= startDate && registeredDate <= endDate;
      } else if (startDate) {
        return registeredDate >= startDate;
      } else if (endDate) {
        return registeredDate <= endDate;
      }

      return true;
    });
  }

  // Filter by search query (DNI or name)
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filteredClients = filteredClients.filter(
      (client) =>
        client.DNI.toLowerCase().includes(query) ||
        client.name.toLowerCase().includes(query)
    );
  }

  return filteredClients;
}

function addNewClient() {
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  form.reset();
}

function submitForm() {
  form.post(route("clients.store"), {
    onSuccess: () => {
      closeModal();
    },
  });
}
</script>

<template>
  <Head title="Clientes" />

  <div class="container mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Clientes</h1>

    <div class="mb-6 space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Buscar por DNI o nombre..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <DarkButton @click="addNewClient"> Añadir Cliente </DarkButton>
      </div>

      <DateRangeSearch
        start-label="Fecha de registro desde"
        end-label="Fecha de registro hasta"
        @update:dateRange="(newRange) => (dateRange = newRange)"
      />
    </div>

    <DataTable :data="filterClients()" :columns="columns" :items-per-page="10" />

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Añadir Nuevo Cliente</h2>
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
            <DefaultInput
              id="dni"
              v-model="form.DNI"
              label="DNI"
              required
              :error="form.errors.DNI"
            />

            <DefaultInput
              id="name"
              v-model="form.name"
              label="Nombre"
              required
              :error="form.errors.name"
            />

            <DefaultInput
              id="email"
              v-model="form.email"
              type="email"
              label="Email"
              :error="form.errors.email"
            />

            <DefaultInput
              id="telephone"
              v-model="form.telephone"
              type="tel"
              label="Teléfono"
              required
              :error="form.errors.telephone"
            />

            <DefaultInput
              id="city"
              v-model="form.city"
              label="Ciudad"
              :error="form.errors.city"
            />

            <DefaultInput
              id="postal_code"
              v-model="form.postal_code"
              label="Código Postal"
              :error="form.errors.postal_code"
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
