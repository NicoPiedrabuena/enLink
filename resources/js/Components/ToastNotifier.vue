<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import Swal, { type SweetAlertIcon } from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { watch } from 'vue';

type Toast = { type?: SweetAlertIcon; message?: string };

const page = usePage();
let lastNotification = '';

const show = (message: string, icon: SweetAlertIcon) => {
    const signature = `${icon}:${message}`;
    if (!message || signature === lastNotification) return;
    lastNotification = signature;

    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon,
        title: message,
        showConfirmButton: false,
        timer: 2800,
        timerProgressBar: true,
        width: 340,
    });
};

watch(
    () => page.props.flash as { toast?: Toast } | undefined,
    (flash) => {
        if (flash?.toast?.message) show(flash.toast.message, flash.toast.type ?? 'success');
    },
    { deep: true, immediate: true },
);

watch(
    () => page.props.errors as Record<string, string> | undefined,
    (errors) => {
        const message = errors ? Object.values(errors)[0] : undefined;
        if (message) show(message, 'error');
    },
    { deep: true, immediate: true },
);
</script>

<template></template>
