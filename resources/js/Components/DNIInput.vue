<script setup>
import { ref, watch, onMounted, onUnmounted } from "vue";
import { dniFormats, dniValidations } from "@/Utils/dniValidation";

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  modelValue: String,
  country: {
    type: String,
    default: "ES",
  },
  label: {
    type: String,
    default: "Documento de identidad",
  },
  placeholder: String,
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: String,
});

const emit = defineEmits(["update:modelValue", "update:country"]);

const selectedCountry = ref("ES");
const documentNumber = ref("");
const showCountryList = ref(false);
const searchQuery = ref("");
const componentRef = ref(null);

function selectedFormat() {
  return dniFormats[selectedCountry.value] || dniFormats.ES;
}

function validationResult() {
  if (!documentNumber.value) return { ok: true, error: "" };
  const validator = dniValidations[selectedCountry.value];
  if (validator) {
    return validator(documentNumber.value);
  }
  return {
    ok: false,
    error: `El formato no es válido para ${selectedFormat().text}`,
  };
}

function isValid() {
  return validationResult().ok;
}

function validationErrorMessage() {
  return validationResult().error;
}

function formattedPlaceholder() {
  return props.placeholder || selectedFormat().format;
}

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      documentNumber.value = newValue;
    } else {
      documentNumber.value = "";
    }
  },
  { immediate: true }
);

watch(
  () => props.country,
  (newCountry) => {
    selectedCountry.value = newCountry;
  },
  { immediate: true }
);

watch(documentNumber, (newValue) => {
  emit("update:modelValue", newValue);
});

const countries = Object.entries(dniFormats).map(([code, data]) => ({
  code,
  ...data,
}));

function filteredCountries() {
  if (!searchQuery.value) return dniFormats;

  const query = searchQuery.value.toLowerCase();
  return Object.entries(dniFormats).reduce((filtered, [code, country]) => {
    if (
      country.text.toLowerCase().includes(query) ||
      country.description.toLowerCase().includes(query)
    ) {
      filtered[code] = country;
    }
    return filtered;
  }, {});
}

function selectCountry(code) {
  emit("update:country", code);
  selectedCountry.value = code;
  showCountryList.value = false;
  validateDocument();
}

function validateDocument() {
  if (!documentNumber.value) {
    emit("update:modelValue", "");
    return;
  }

  emit("update:modelValue", documentNumber.value);
}

function handleClickOutside(event) {
  if (componentRef.value && !componentRef.value.contains(event.target)) {
    showCountryList.value = false;
    searchQuery.value = "";
  }
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
  <div class="relative" ref="componentRef">
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div class="flex items-stretch">
      <button
        type="button"
        @click="showCountryList = !showCountryList"
        class="flex items-center justify-center px-3 py-2 border rounded-l-md border-gray-300 bg-gray-50 hover:bg-gray-100 min-h-[42px] min-w-[120px]"
      >
        <span
          v-html="selectedFormat().icon"
          class="w-6 h-6 mr-2 flex items-center justify-center"
        ></span>
        <span class="text-sm text-gray-600">{{ selectedFormat().text }}</span>
        <svg
          class="h-5 w-5 ml-8 text-gray-400"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3z"
            clip-rule="evenodd"
            transform="rotate(180 10 10)"
          />
        </svg>
      </button>

      <input
        :id="id"
        v-model="documentNumber"
        type="text"
        :placeholder="formattedPlaceholder()"
        :pattern="selectedFormat().pattern"
        :title="selectedFormat().description"
        :disabled="disabled"
        :required="required"
        @input="validateDocument"
        class="flex-1 px-3 py-2 border rounded-r-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 min-h-[42px]"
        :class="{
          'bg-gray-50 cursor-not-allowed': disabled,
          'border-red-300 focus:border-red-500 focus:ring-red-500': error || !isValid(),
        }"
      />
    </div>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showCountryList"
        class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg"
      >
        <div class="p-2 border-b">
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Buscar país..."
            class="w-full px-3 py-2 border rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>

        <div class="max-h-60 overflow-y-auto country-list">
          <button
            type="button"
            v-for="[code, country] in Object.entries(filteredCountries())"
            :key="code"
            @click="selectCountry(code)"
            class="w-full flex items-center px-4 py-2 hover:bg-gray-100"
          >
            <span
              v-html="country.icon"
              class="w-6 h-6 mr-2 flex items-center justify-center"
            ></span>
            <div class="flex flex-col items-start">
              <span class="text-sm font-medium">{{ country.text }}</span>
              <span class="text-xs text-gray-500">{{ country.description }}</span>
            </div>
          </button>
        </div>
      </div>
    </transition>

    <div
      v-if="documentNumber && !isValid()"
      class="absolute inset-y-0 right-0 flex items-center pr-3 top-[-14px]"
    >
      <svg
        class="w-5 h-5 text-red-500"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        ></path>
      </svg>
    </div>
    <div
      v-else-if="documentNumber && isValid()"
      class="absolute inset-y-0 right-0 flex items-center pr-3 top-3"
    >
      <svg
        class="w-5 h-5 text-green-500"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M5 13l4 4L19 7"
        ></path>
      </svg>
    </div>

    <div v-if="!error" class="mt-1 text-xs text-gray-500">
      {{ selectedFormat().description }}
    </div>

    <div v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </div>

    <div v-else-if="documentNumber && !isValid()" class="mt-1 text-sm text-red-600">
      {{ validationErrorMessage() }}
    </div>
  </div>
</template>

<style scoped>
.country-list {
  scrollbar-width: thin;
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.country-list::-webkit-scrollbar {
  width: 6px;
}

.country-list::-webkit-scrollbar-track {
  background: transparent;
}

.country-list::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}
</style>
