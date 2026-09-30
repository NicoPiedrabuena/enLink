<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

type DayMetric = { date: string; visits: number; clicks: number };
type LinkMetric = { key: string; title: string; clicks: number };

const props = defineProps<{
    summary: { visits: number; clicks: number; clickRate: number };
    timeline: DayMetric[];
    links: LinkMetric[];
}>();

const recentTimeline = computed(() => props.timeline.slice(-14));
const maximum = computed(() => Math.max(1, ...recentTimeline.value.flatMap((day) => [day.visits, day.clicks])));
const height = (value: number) => `${Math.max(value ? 8 : 0, (value / maximum.value) * 100)}%`;
const day = (value: string) => new Intl.DateTimeFormat('es-AR', { day: '2-digit', month: '2-digit' }).format(new Date(`${value}T12:00:00`));
</script>

<template>
    <Head title="Analytics" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Analytics</h2></template>

        <div class="py-8"><div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-3">
                <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-sm font-semibold text-slate-500">Visitas (30 días)</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ props.summary.visits }}</p></article>
                <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-sm font-semibold text-slate-500">Clics (30 días)</p><p class="mt-2 text-3xl font-bold text-violet-700">{{ props.summary.clicks }}</p></article>
                <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-sm font-semibold text-slate-500">Clics por visita</p><p class="mt-2 text-3xl font-bold text-emerald-600">{{ props.summary.clickRate }}%</p></article>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-bold text-slate-950">Últimos 14 días</h3><p class="mt-1 text-sm text-slate-500">Azul: visitas · Violeta: clics</p></div></div>
                <div class="mt-6 grid h-52 grid-cols-[repeat(14,minmax(0,1fr))] items-end gap-2 border-b border-slate-200">
                    <div v-for="metric in recentTimeline" :key="metric.date" class="flex h-full min-w-0 flex-col justify-end">
                        <div class="flex h-[calc(100%-24px)] items-end justify-center gap-0.5"><div class="w-2 rounded-t bg-sky-400" :style="{ height: height(metric.visits) }" :title="`${metric.visits} visitas`"></div><div class="w-2 rounded-t bg-violet-500" :style="{ height: height(metric.clicks) }" :title="`${metric.clicks} clics`"></div></div>
                        <p class="mt-2 truncate text-center text-[10px] text-slate-500">{{ day(metric.date) }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="font-bold text-slate-950">Clics por enlace</h3><div v-if="props.links.length" class="mt-4 divide-y divide-slate-100"><div v-for="link in props.links" :key="link.key" class="flex items-center justify-between gap-4 py-3"><p class="font-semibold text-slate-800">{{ link.title }}</p><span class="rounded-full bg-violet-50 px-3 py-1 text-sm font-bold text-violet-700">{{ link.clicks }} clics</span></div></div><p v-else class="mt-4 text-sm text-slate-500">Todavía no hay clics registrados. Compartí tu página pública para comenzar.</p></section>

            <p class="text-xs text-slate-500">Enlink cuenta eventos totales. No guarda direcciones IP ni intenta identificar visitantes.</p>
        </div></div>
    </AuthenticatedLayout>
</template>
