<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import GoogleAuthButton from '@/Components/GoogleAuthButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Crear cuenta" />

        <div class="mb-7">
            <span class="inline-flex rounded-full bg-[#c9b5ff] px-3 py-1 text-xs font-black uppercase tracking-wider">Empezá gratis</span>
            <h1 class="mt-4 text-3xl font-black tracking-[-0.04em]">Creá algo bien tuyo.</h1>
            <p class="mt-2 text-sm text-[#626259]">Armá tu página sin pagar. Antes de ingresar, confirmaremos que el correo sea tuyo.</p>
        </div>
        <GoogleAuthButton />
        <div class="my-6 flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-slate-400"><span class="h-px flex-1 bg-slate-200" />o con correo<span class="h-px flex-1 bg-slate-200" /></div>
        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Tu nombre" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-2 block w-full rounded-xl border-slate-300 px-4 py-3"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-5">
                <InputLabel for="email" value="Correo electrónico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full rounded-xl border-slate-300 px-4 py-3"
                    v-model="form.email"
                    required
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
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-5">
                <InputLabel
                    for="password_confirmation"
                    value="Repetir contraseña"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-2 block w-full rounded-xl border-slate-300 px-4 py-3"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-6 grid gap-4">
                <PrimaryButton
                    class="w-full justify-center rounded-xl border-2 border-[#171713] bg-[#6e35ff] px-5 py-3 text-white shadow-[3px_3px_0_#171713] hover:bg-[#5d27e8]"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    Crear mi cuenta
                </PrimaryButton>
                <Link
                    :href="route('login')"
                    class="text-center text-sm font-bold text-[#626259] underline decoration-[#6e35ff] decoration-2 underline-offset-4"
                >
                    Ya tengo una cuenta
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
