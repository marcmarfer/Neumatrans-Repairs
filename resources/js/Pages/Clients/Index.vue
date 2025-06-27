<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
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

const dateRange = ref({
  startDate: "",
  endDate: "",
});

const searchQuery = ref("");
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const clientToDelete = ref(null);
const isEditing = ref(false);

const today = new Date().toISOString().split("T")[0];

const form = useForm({
  id: "",
  DNI: "",
  name: "",
  email: "",
  telephone: "",
  city: "",
  postal_code: "",
  registered_at: today,
});

function filterClients() {
  let filteredClients = props.clients;

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
  isEditing.value = false;
  form.reset();
  form.registered_at = today;
  isModalOpen.value = true;
}

function editClient(client) {
  isEditing.value = true;
  form.reset();
  form.id = client.id;
  form.DNI = client.DNI;
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

    <DataTable 
      :data="filterClients()" 
      :columns="columns" 
      :items-per-page="10"
      @delete="confirmDelete"
      @edit="editClient" 
    />

    <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 z-10">
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
