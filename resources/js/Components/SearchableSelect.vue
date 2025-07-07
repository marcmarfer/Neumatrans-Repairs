<script setup>
import { ref, watch, nextTick, onUnmounted } from "vue";

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  modelValue: [String, Number],
  label: String,
  options: {
    type: Array,
    default: () => [],
  },
  valueField: {
    type: String,
    default: "id",
  },
  labelField: {
    type: String,
    default: "name",
  },
  placeholder: {
    type: String,
    default: "Seleccione una opción...",
  },
  searchPlaceholder: {
    type: String,
    default: "Buscar...",
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: String,
  searchable: {
    type: Boolean,
    default: true,
  },
  noOptionsText: {
    type: String,
    default: "No se encontraron opciones",
  },
});

const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(false);
const searchQuery = ref("");
const selectedOption = ref(null);
const searchInput = ref(null);
const dropdown = ref(null);

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      const option = props.options.find(
        (opt) => opt[props.valueField]?.toString() === newValue?.toString()
      );
      selectedOption.value = option || null;
    } else {
      selectedOption.value = null;
    }
  },
  { immediate: true }
);

function filteredOptions() {
  if (!props.searchable) return props.options;
  if (!searchQuery.value) return props.options;
  const query = searchQuery.value.toLowerCase();
  return props.options.filter((option) => {
    const label = option[props.labelField]?.toString().toLowerCase() || "";
    const value = option[props.valueField]?.toString().toLowerCase() || "";
    const searchableText = `${label} ${value}`.toLowerCase();
    return searchableText.includes(query);
  });
}

function displayText() {
  if (selectedOption.value) {
    return (
      selectedOption.value[props.labelField] || selectedOption.value[props.valueField]
    );
  }
  return props.placeholder;
}

const openDropdown = async () => {
  if (props.disabled) return;

  if (isOpen.value) {
    closeDropdown();
    return;
  }

  isOpen.value = true;
  searchQuery.value = "";

  await nextTick();
  if (searchInput.value) {
    searchInput.value.focus();
  }
};

const closeDropdown = () => {
  isOpen.value = false;
  searchQuery.value = "";
};

const selectOption = (option) => {
  selectedOption.value = option;
  emit("update:modelValue", option[props.valueField]);
  closeDropdown();
};

const handleClickOutside = (event) => {
  if (dropdown.value && !dropdown.value.contains(event.target)) {
    closeDropdown();
  }
};

watch(isOpen, (open) => {
  if (open) {
    document.addEventListener("click", handleClickOutside);
  } else {
    document.removeEventListener("click", handleClickOutside);
  }
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
  <div class="relative" ref="dropdown">
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div class="relative">
      <button
        type="button"
        :id="id"
        @click="openDropdown"
        :disabled="disabled"
        class="relative w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
        :class="{
          'bg-gray-50 cursor-not-allowed': disabled,
          'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500': error,
        }"
        :aria-expanded="isOpen"
        aria-haspopup="listbox"
      >
        <span class="block truncate" :class="{ 'text-gray-500': !selectedOption }">
          {{ displayText() }}
        </span>

        <span
          class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none"
        >
          <svg
            class="h-5 w-5 text-gray-400"
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
        </span>
      </button>

      <transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <div
          v-if="isOpen"
          class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
        >
          <div v-if="props.searchable" class="sticky top-0 bg-white border-b border-gray-200 p-2">
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              :placeholder="searchPlaceholder"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
            />
          </div>

          <div class="max-h-48 overflow-auto">
            <template v-if="filteredOptions().length > 0">
              <div
                v-for="option in filteredOptions()"
                :key="option[valueField]"
                @click="selectOption(option)"
                class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-blue-50 hover:text-blue-900"
                :class="{
                  'bg-blue-100 text-blue-900':
                    selectedOption && selectedOption[valueField] === option[valueField],
                  'text-gray-900':
                    !selectedOption || selectedOption[valueField] !== option[valueField],
                }"
              >
                <slot name="option" :option="option">
                  <span class="block truncate">
                    {{ option[labelField] || option[valueField] }}
                  </span>
                </slot>

                <span
                  v-if="
                    selectedOption && selectedOption[valueField] === option[valueField]
                  "
                  class="absolute inset-y-0 right-0 flex items-center pr-4 text-blue-600"
                >
                  <svg
                    class="h-5 w-5"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </span>
              </div>
            </template>

            <div
              v-else
              class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-700"
            >
              {{ noOptionsText }}
            </div>
          </div>
        </div>
      </transition>
    </div>

    <div v-if="error" class="mt-2 text-sm text-red-600">
      {{ error }}
    </div>
  </div>
</template>
