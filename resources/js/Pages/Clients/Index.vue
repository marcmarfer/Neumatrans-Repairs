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
import DNIInput from "@/Components/DNIInput.vue";
import PhoneInput from "@/Components/PhoneInput.vue";

const props = defineProps({
  clients: {
    type: Object,
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

const formatTelephone = (phoneString) => {
  if (!phoneString) return '';
  const digits = phoneString.replace(/\s+/g, '');
  const match = digits.match(/^(\+\d{1,3})(\d+)$/);
  return match ? `${match[1]} ${match[2]}` : digits;
};

const columns = [
  { key: "id", label: "ID" },
  { key: "DNI", label: "DNI" },
  { key: "name", label: "Nombre" },
  { key: "email", label: "Email" },
  { key: "telephone", label: "Teléfono", formatter: formatTelephone },
  { key: "city", label: "Ciudad" },
  { key: "postal_code", label: "Código Postal" },
  { 
    key: "registered_at", 
    label: "Fecha de Registro",
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
const clientToDelete = ref(null);
const isEditing = ref(false);

const today = new Date().toISOString().split("T")[0];

const form = useForm({
  id: "",
  DNI: "",
  country: "ES",
  name: "",
  email: "",
  telephone: "",
  city: "",
  postal_code: "",
  registered_at: today,
});

// ── Server-side data fetching ──────────────────────────────────────

const paginationMeta = computed(() => ({
  current_page: props.clients.current_page,
  last_page: props.clients.last_page,
  from: props.clients.from,
  to: props.clients.to,
  total: props.clients.total,
  per_page: props.clients.per_page,
}));

function fetchData(page = null) {
  const params = {};
  if (searchQuery.value) params.q = searchQuery.value;
  if (dateRange.value.startDate) params.start = dateRange.value.startDate;
  if (dateRange.value.endDate) params.end = dateRange.value.endDate;
  if (page && page > 1) params.page = page;

  router.get(route('clients.index'), params, {
    preserveState: true,
    preserveScroll: true,
    only: ['clients', 'filters'],
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

function addNewClient() {
  isEditing.value = false;
  form.reset();
  form.country = "ES";
  form.registered_at = today;
  isModalOpen.value = true;
}

function editClient(client) {
  isEditing.value = true;
  form.reset();
  form.id = client.id;
  form.DNI = client.DNI;
  form.country = client.country || "ES";
  form.name = client.name;
  form.email = client.email;
  form.telephone = client.telephone;
  form.city = client.city;
  form.postal_code = client.postal_code;
  form.registered_at = client.registered_at;
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  form.reset();
  isEditing.value = false;
}

function submitForm() {
  if (isEditing.value) {
    form.put(route("clients.update", form.id), {
      onSuccess: () => {
        closeModal();
      },
    });
  } else {
    form.post(route("clients.store"), {
      onSuccess: () => {
        closeModal();
      },
    });
  }
}

function confirmDelete(client) {
  clientToDelete.value = client;
  isDeleteModalOpen.value = true;
}

function cancelDelete() {
  isDeleteModalOpen.value = false;
  clientToDelete.value = null;
}

function deleteClient() {
  if (clientToDelete.value) {
    router.delete(route("clients.destroy", clientToDelete.value.id), {
      onSuccess: () => {
        isDeleteModalOpen.value = false;
        clientToDelete.value = null;
      },
    });
  }
}

function formatPostalCode(event) {
  let value = event.target.value;
  value = value.replace(/[^0-9]/g, '');
  if (value.length > 5) {
    value = value.substring(0, 5);
  }
  form.postal_code = value;
}
</script>

<template>
  <Head title="Clientes" />
  <GoBackButton type="button" @click="router.visit(route('dashboard.show'))">
    Volver
  </GoBackButton>
  <div class="container mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Clientes</h1>

    <div class="mb-6 space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <input
          type="text"
          v-model="searchQuery"
          @input="onSearchInput"
          placeholder="Buscar por DNI o nombre..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <DarkButton @click="addNewClient"> Añadir Cliente </DarkButton>
      </div>

      <DateRangeSearch
        start-label="Fecha de registro desde"
        end-label="Fecha de registro hasta"
        :initial-start-date="filters?.start ?? ''"
        :initial-end-date="filters?.end ?? ''"
        @update:dateRange="onDateRangeChange"
      />
    </div>

    <DataTable 
      :data="clients.data" 
      :columns="columns" 
      :server-side="true"
      :meta="paginationMeta"
      @delete="confirmDelete"
      @edit="editClient"
      @page-change="onPageChange"
    />

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10 max-h-[80vh] overflow-y-auto overscroll-contain">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">{{ isEditing ? 'Editar Cliente' : 'Añadir Nuevo Cliente' }}</h2>
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
            <DNIInput
              id="dni"
              v-model="form.DNI"
              v-model:country="form.country"
              label="DNI"
              required
              :error="form.errors.DNI"
            />

            <DefaultInput
              id="name"
              v-model="form.name"
              label="Nombre"
              placeholder="Nombre completo"
              pattern="[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+"
              title="Solo letras y espacios"
              maxlength="255"
              minlength="2"
              required
              :error="form.errors.name"
            />

            <DefaultInput
              id="email"
              v-model="form.email"
              type="email"
              label="Email"
              placeholder="correo@ejemplo.com"
              maxlength="255"
              :error="form.errors.email"
            />

            <PhoneInput
              id="telephone"
              v-model="form.telephone"
              label="Teléfono"
              required
              :error="form.errors.telephone"
            />

            <DefaultInput
              id="city"
              v-model="form.city"
              label="Ciudad (Opcional)"
              placeholder="Ej: Madrid, Barcelona"
              pattern="[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\-\']+"
              title="Solo letras, espacios, guiones y apostrofes"
              maxlength="100"
              minlength="2"
              :error="form.errors.city"
            />

            <DefaultInput
              id="postal_code"
              v-model="form.postal_code"
              label="Código Postal (Opcional)"
              placeholder="Ej: 28001"
              pattern="[0-9]{5}"
              title="5 dígitos"
              maxlength="5"
              minlength="5"
              :error="form.errors.postal_code"
              @input="formatPostalCode"
            />

            <DefaultInput
              id="registered_at"
              v-model="form.registered_at"
              type="date"
              label="Fecha de Registro"
              :max="today"
              required
              :error="form.errors.registered_at"
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
          <p class="text-gray-700">¿Estás seguro que deseas eliminar el cliente <span class="font-bold">{{ clientToDelete?.name }}</span>?</p>
          <p class="text-sm text-red-500 mt-2">Esta acción no se puede deshacer.</p>
        </div>

        <div class="flex justify-end space-x-3">
          <LightButton type="button" @click="cancelDelete">Cancelar</LightButton>
          <DeleteButton type="button" @click="deleteClient">Eliminar</DeleteButton>
        </div>
      </div>
    </div>
  </div>
</template>
