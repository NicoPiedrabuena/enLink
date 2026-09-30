<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

type CreditPackage = { id: number; name: string; credits: number; price: string; currency: string };
type PaymentOrder = { id: number; package_name: string; credits: number; amount: string; currency: string; status: string; created_at: string };
type CreditTransaction = { id: number; type: string; amount: number; balance_after: number; description: string; created_at: string };

const props = defineProps<{
    balance: number;
    packages: CreditPackage[];
    orders: PaymentOrder[];
    transactions: CreditTransaction[];
}>();

const money = (amount: string, currency: string) => new Intl.NumberFormat('es-AR', { style: 'currency', currency }).format(Number(amount));
const date = (value: string) => new Intl.DateTimeFormat('es-AR', { dateStyle: 'medium' }).format(new Date(value));
const buy = (creditPackage: CreditPackage) => router.post(`/dashboard/checkout/${creditPackage.id}`);
</script>

<template>
    <Head title="Créditos" />

    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Créditos</h2></template>

        <div class="py-8"><div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm"><p class="text-sm font-semibold text-slate-300">Saldo disponible</p><p class="mt-2 text-4xl font-bold">{{ props.balance }} créditos</p><p class="mt-3 max-w-xl text-sm text-slate-300">Editar y guardar es gratis. Cada publicación, incluida la primera, consume 1 crédito.</p></section>

            <p v-if="$page.props.errors.checkout" class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm font-semibold text-amber-900">{{ $page.props.errors.checkout }}</p>

            <section><div class="mb-4"><h3 class="text-lg font-bold text-slate-950">Comprar créditos</h3><p class="mt-1 text-sm text-slate-500">El pago se completa de forma segura en Mercado Pago.</p></div>
                <div v-if="props.packages.length" class="grid gap-4 md:grid-cols-3"><article v-for="creditPackage in props.packages" :key="creditPackage.id" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-sm font-semibold text-slate-500">{{ creditPackage.name }}</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ creditPackage.credits }} créditos</p><p class="mt-3 text-lg font-semibold text-slate-700">{{ money(creditPackage.price, creditPackage.currency) }}</p><button class="mt-5 w-full rounded-xl bg-violet-600 px-4 py-3 text-sm font-bold text-white hover:bg-violet-700" @click="buy(creditPackage)">Comprar</button></article></div>
                <p v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">Estamos terminando de configurar los paquetes. Todavía no hay compras habilitadas.</p>
            </section>

            <section class="grid gap-6 lg:grid-cols-2"><div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="font-bold text-slate-950">Historial de créditos</h3><div v-if="props.transactions.length" class="mt-4 divide-y divide-slate-100"><div v-for="transaction in props.transactions" :key="transaction.id" class="flex items-center justify-between gap-4 py-3 text-sm"><div><p class="font-semibold text-slate-800">{{ transaction.description }}</p><p class="text-slate-500">{{ date(transaction.created_at) }}</p></div><div class="text-right"><p :class="transaction.amount > 0 ? 'text-emerald-600' : 'text-slate-800'" class="font-bold">{{ transaction.amount > 0 ? '+' : '' }}{{ transaction.amount }}</p><p class="text-xs text-slate-500">Saldo: {{ transaction.balance_after }}</p></div></div></div><p v-else class="mt-4 text-sm text-slate-500">Todavía no hay movimientos.</p></div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="font-bold text-slate-950">Órdenes de pago</h3><div v-if="props.orders.length" class="mt-4 divide-y divide-slate-100"><div v-for="order in props.orders" :key="order.id" class="flex items-center justify-between gap-4 py-3 text-sm"><div><p class="font-semibold text-slate-800">{{ order.package_name }}</p><p class="text-slate-500">{{ date(order.created_at) }}</p></div><div class="text-right"><p class="font-bold text-slate-800">{{ money(order.amount, order.currency) }}</p><p class="text-xs capitalize text-slate-500">{{ order.status }}</p></div></div></div><p v-else class="mt-4 text-sm text-slate-500">Todavía no generaste órdenes de pago.</p></div>
            </section>
        </div></div>
    </AuthenticatedLayout>
</template>
