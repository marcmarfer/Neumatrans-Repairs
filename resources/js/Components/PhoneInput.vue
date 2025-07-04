<script setup>
import { telfPrefixes, prefixesValidations } from "@/Utils/phoneValidation";
import { ref, watch, onMounted, onUnmounted } from "vue";

const props = defineProps({
  id: { type: String, required: true },
  modelValue: { type: String, default: "" },
  label: String,
  required: { type: Boolean, default: false },
  error: { type: String, default: null },
});

const emit = defineEmits(["update:modelValue"]);

const phoneNumber = ref("");
const selectedPrefix = ref("+34");
const showCountryList = ref(false);
const searchQuery = ref("");
const placeholder = ref("612 345 678");
const validationError = ref(null);
const componentRef = ref(null);

function selectedCountry() {
  return telfPrefixes[selectedPrefix.value] || telfPrefixes["+34"];
}

function filteredCountries() {
  if (!searchQuery.value) return telfPrefixes;

  const query = searchQuery.value.toLowerCase();
  return Object.entries(telfPrefixes).reduce((filtered, [prefix, country]) => {
    if (country.text.toLowerCase().includes(query) || prefix.includes(query)) {
      filtered[prefix] = country;
    }
    return filtered;
  }, {});
}

function formattedPhoneNumber() {
  return `${selectedPrefix.value} ${phoneNumber.value}`;
}

function validatePhone() {
  emit("update:modelValue", formattedPhoneNumber());

  if (!phoneNumber.value) {
    validationError.value = null;
    return;
  }

  const validator = prefixesValidations[selectedPrefix.value];
  if (validator) {
    const result = validator(phoneNumber.value);
    validationError.value = result.ok ? null : result.error;
  } else {
    validationError.value = null;
  }
}

function selectCountry(prefix) {
  selectedPrefix.value = prefix;
  showCountryList.value = false;
  placeholder.value = telfPrefixes[prefix].format;
  validatePhone();
}

function handleClickOutside(event) {
  if (componentRef.value && !componentRef.value.contains(event.target)) {
    showCountryList.value = false;
    searchQuery.value = "";
  }
}

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      let foundPrefix = null;
      let remainingNumber = "";

      if (newValue.startsWith("+")) {
        for (const prefix of Object.keys(telfPrefixes)) {
          if (newValue.startsWith(prefix)) {
            foundPrefix = prefix;
            remainingNumber = newValue.substring(prefix.length).replace(/\s/g, "");
            break;
          }
        }
      }

      if (foundPrefix) {
        selectedPrefix.value = foundPrefix;
        phoneNumber.value = remainingNumber;
      } else if (newValue.match(/^\d+$/)) {
        selectedPrefix.value = "+34";
        phoneNumber.value = newValue;
      } else {
        const match = newValue.match(/^(\+\d{1,4})\s*(.*)$/);
        if (match) {
          selectedPrefix.value = "+34";
          phoneNumber.value = match[2].replace(/\s/g, "");
        } else {
          phoneNumber.value = newValue;
        }
      }

      validatePhone();
    } else {
      phoneNumber.value = "";
      validationError.value = null;
    }
  },
  { immediate: true }
);

onMounted(() => {
  placeholder.value = selectedCountry().format;
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
          v-html="selectedCountry().icon"
          class="w-6 h-6 mr-2 flex items-center justify-center"
        ></span>
        <span class="text-sm text-gray-600">{{ selectedPrefix }}</span>
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
        type="tel"
        v-model="phoneNumber"
        :placeholder="placeholder"
        :required="required"
        class="flex-1 px-3 py-2 border rounded-r-md border-gray-300 min-h-[42px]"
        :class="{
          'border-red-300 focus:border-red-500 focus:ring-red-500':
            error || validationError,
          'focus:ring-indigo-500 focus:border-indigo-500': !error && !validationError,
        }"
        @input="validatePhone"
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
            v-for="(country, prefix) in filteredCountries()"
            :key="prefix"
            @click="selectCountry(prefix)"
            class="w-full flex items-center px-4 py-2 hover:bg-gray-100"
          >
            <span
              v-html="country.icon"
              class="w-6 h-6 mr-2 flex items-center justify-center"
            ></span>
            <span class="text-sm">{{ country.text }}</span>
          </button>
        </div>
      </div>
    </transition>

    <div v-if="error || validationError" class="mt-1 text-sm text-red-600">
      {{ error || validationError }}
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
