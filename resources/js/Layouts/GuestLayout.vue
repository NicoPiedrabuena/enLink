<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <main class="relative min-h-screen overflow-hidden bg-[#f6f4ee] px-4 py-6 text-[#171713] sm:grid sm:place-items-center sm:px-8">
        <div class="pointer-events-none absolute -left-28 top-[-7rem] h-80 w-80 rounded-full bg-[#b9ff66]/70 blur-3xl" />
        <div class="pointer-events-none absolute -right-28 bottom-[-8rem] h-96 w-96 rounded-full bg-[#c9b5ff]/70 blur-3xl" />
        <div class="relative mx-auto w-full max-w-md">
            <Link href="/" class="mb-7 inline-flex items-center gap-2 text-xl font-black tracking-[-0.04em]">
                <span class="grid h-10 w-10 place-items-center rounded-xl border-2 border-[#171713] bg-[#b9ff66] shadow-[3px_3px_0_#171713]">e.</span>
                enlink
            </Link>
            <section class="rounded-[2rem] border-2 border-[#171713] bg-white p-6 shadow-[8px_8px_0_#171713] sm:p-8">
                <slot />
            </section>
            <p class="mt-7 text-center text-xs font-semibold text-[#626259]">Tu página, tus enlaces, tu estilo.</p>
        </div>
    </main>
</template>
