<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GoogleAuthButton from '@/Components/GoogleAuthButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Ingresar" />

        <div class="mb-7">
            <span class="inline-flex rounded-full bg-[#b9ff66] px-3 py-1 text-xs font-black uppercase tracking-wider">Bienvenido</span>
            <h1 class="mt-4 text-3xl font-black tracking-[-0.04em]">Volvé a tu Enlink.</h1>
            <p class="mt-2 text-sm text-[#626259]">Ingresá para editar, guardar y publicar tu página.</p>
        </div>

        <div v-if="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-700">
            {{ status }}
        </div>

        <GoogleAuthButton />
        <div class="my-6 flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-slate-400"><span class="h-px flex-1 bg-slate-200" />o con correo<span class="h-px flex-1 bg-slate-200" /></div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Correo electrónico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full rounded-xl border-slate-300 px-4 py-3"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-5">
                <InputLabel for="password" value="Contraseña" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-2 block w-full rounded-xl border-slate-300 px-4 py-3"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-5 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400"
                        >Recordarme</span
                    >
                </label>
            </div>

            <div class="mt-6 grid gap-4">
                <PrimaryButton
                    class="w-full justify-center rounded-xl border-2 border-[#171713] bg-[#6e35ff] px-5 py-3 text-white shadow-[3px_3px_0_#171713] hover:bg-[#5d27e8]"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    Ingresar
                </PrimaryButton>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-center text-sm font-bold text-[#626259] underline decoration-[#6e35ff] decoration-2 underline-offset-4"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>
        </form>

        <p class="mt-7 border-t border-slate-200 pt-5 text-center text-sm text-[#626259]">¿Todavía no tenés cuenta? <Link :href="route('register')" class="font-black text-[#6e35ff]">Crear mi Enlink</Link></p>
    </GuestLayout>
</template>
