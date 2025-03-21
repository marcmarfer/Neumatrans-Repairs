<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

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

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const isProduction = import.meta.env.VITE_APP_ENV === 'prod';
</script>

<template>
    <Head title="Iniciar Sesión" />

    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="w-96">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Neumatrans</h1>
                <p class="text-gray-600 mt-2">Inicia sesión en tu cuenta</p>
            </div>

            <div v-if="status" class="mb-4 p-4 bg-green-100 rounded-lg text-green-700 text-sm">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <input id="email" type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                        v-model="form.email" placeholder="Email" required autofocus />
                    <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div>
                    <input id="password" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                        v-model="form.password" placeholder="Contraseña" required />
                    <div v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                        {{ form.errors.password }}
                    </div>
                </div>

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

                <div class="text-center mt-4" v-if="!isProduction">
                    <Link :href="route('register')" class="text-sm text-gray-600 hover:text-red-600">
                    ¿No tienes cuenta? Regístrate
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
