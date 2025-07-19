<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DefaultInput from '@/Components/DefaultInput.vue';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar Sesión" />

    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="w-96 p-4">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">NTC Car Service</h1>
                <p class="text-gray-600 mt-2">Inicia sesión en tu cuenta</p>
            </div>
            <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 rounded-lg text-red-700 text-sm">
                {{ $page.props.flash.error }}
            </div>

            <div v-if="status" class="mb-4 p-4 bg-green-100 rounded-lg text-green-700 text-sm">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <DefaultInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    placeholder="Email"
                    required
                    :error="form.errors.email"
                />

                <DefaultInput
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    v-model="form.password"
                    placeholder="Contraseña"
                    required
                    :error="form.errors.password"
                >
                    <template #suffix>
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">
                            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.269-2.943-9.543-7a9.958 9.958 0 012.083-3.23M6.343 6.343A9.955 9.955 0 0112 5c4.45 0 8.219 2.89 9.485 6.843M3 3l18 18"/>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </template>
                </DefaultInput>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="rounded border-gray-300 text-red-600" v-model="form.remember" />
                        <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                    </label>

                    <Link v-if="canResetPassword" :href="route('password.request')"
                        class="text-sm text-red-600 hover:text-red-800">
                    ¿Olvidaste tu contraseña?
                    </Link>
                </div>

                <button type="submit"
                    class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    :disabled="form.processing">
                    {{ form.processing ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
                </button>

                <div class="text-center mt-4" v-if="$page.props.viteAppEnv === 'demo'">
                    <Link :href="route('register')" class="text-sm text-gray-600 hover:text-red-600">
                    ¿No tienes cuenta? Regístrate
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
