<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Confirmá tu correo" />

        <div class="mb-7">
            <span class="inline-flex rounded-full bg-[#b9ff66] px-3 py-1 text-xs font-black uppercase tracking-wider">Un paso más</span>
            <h1 class="mt-4 text-3xl font-black tracking-[-0.04em]">Revisá tu correo.</h1>
            <p class="mt-2 text-sm leading-6 text-[#626259]">Te enviamos un enlace para confirmar que la dirección es tuya. Después de abrirlo, podrás entrar a tu panel.</p>
        </div>

        <div
            class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-700"
            v-if="verificationLinkSent"
        >
            Listo: enviamos un nuevo enlace de confirmación.
        </div>

        <form @submit.prevent="submit">
            <div class="grid gap-4">
                <PrimaryButton
                    class="w-full justify-center rounded-xl border-2 border-[#171713] bg-[#6e35ff] px-5 py-3 text-white shadow-[3px_3px_0_#171713]"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    Reenviar correo de confirmación
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-center text-sm font-bold text-[#626259] underline decoration-[#6e35ff] decoration-2 underline-offset-4"
                    >Cerrar sesión</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
