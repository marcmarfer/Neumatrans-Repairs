<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DefaultInput from '@/Components/DefaultInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Registro" />

    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="w-96 p-4">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Neumatrans</h1>
                <p class="text-gray-600 mt-2">Crea tu cuenta</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <DefaultInput
                    id="name"
                    type="text"
                    v-model="form.name"
                    placeholder="Nombre"
                    required
                    :error="form.errors.name"
                />

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
                    type="password"
                    v-model="form.password"
                    placeholder="Contraseña"
                    required
                    :error="form.errors.password"
                />

                <DefaultInput
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    placeholder="Confirmar Contraseña"
                    required
                />

                <button type="submit"
                    class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    :disabled="form.processing">
                    {{ form.processing ? 'Registrando...' : 'Registrarse' }}
                </button>

                <div class="text-center mt-4">
                    <Link :href="route('login')" class="text-sm text-gray-600 hover:text-red-600">
                    ¿Ya tienes cuenta? Inicia sesión
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
