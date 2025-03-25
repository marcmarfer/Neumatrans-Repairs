<script setup>
const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: "",
  },
  id: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    default: "",
  },
  options: {
    type: Array,
    default: () => [],
  },
  valueField: {
    type: String,
    default: "value",
  },
  labelField: {
    type: String,
    default: "label",
  },
  placeholder: {
    type: String,
    default: "Seleccione una opción",
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: "",
  },
});

const emit = defineEmits(["update:modelValue"]);

function updateValue(event) {
  emit("update:modelValue", event.target.value);
}

function selectClasses() {
  return [
    "mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500",
    props.error ? "border-red-500" : "border-gray-300",
  ].join(" ");
}
</script>

<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700">
      {{ label }} <span v-if="required" class="text-red-500">*</span>
    </label>

    <select
      :id="id"
      :value="modelValue"
      @input="updateValue"
      :required="required"
      :class="selectClasses"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="option[valueField]"
        :value="option[valueField]"
      >
        <slot name="option" :option="option">
          {{ option[labelField] }}
        </slot>
      </option>
    </select>

    <div v-if="error" class="text-red-500 text-sm mt-1">{{ error }}</div>
  </div>
</template>
