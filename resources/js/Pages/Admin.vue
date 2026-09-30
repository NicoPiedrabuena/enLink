<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

type AdminUser = { id: number; name: string; email: string; role: string; status: string; created_at: string; credit_balance?: { balance: number }; link_page?: { username: string; status: string } };
type Page = { id: number; username: string; display_name: string; status: string; suspension_reason?: string; user: { name: string; email: string } };
type Package = { id: number; name: string; credits: number; price: string; currency: string; is_active: boolean; sort_order: number };
type Order = { id: number; package_name: string; credits: number; amount: string; currency: string; status: string; created_at: string; user: { name: string; email: string } };
type Audit = { id: number; action: string; reason?: string; created_at: string; actor?: { name: string } };

const props = defineProps<{ summary: { users: number; publishedPages: number; credits: number; approvedRevenue: string }; users: AdminUser[]; pages: Page[]; packages: Package[]; orders: Order[]; auditLogs: Audit[] }>();
const adjustments = reactive<Record<number, { amount: number; reason: string }>>({});
const packageForms = reactive<Record<number, Package>>(Object.fromEntries(props.packages.map((item) => [item.id, { ...item }])));
const newPackage = useForm({ name: '', credits: 10, price: '', currency: 'ARS', is_active: false, sort_order: props.packages.length });
const money = (amount: string, currency = 'ARS') => new Intl.NumberFormat('es-AR', { style: 'currency', currency }).format(Number(amount));
const date = (value: string) => new Intl.DateTimeFormat('es-AR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));

props.users.forEach((user) => { adjustments[user.id] = { amount: 0, reason: '' }; });

const changeUserStatus = (user: AdminUser) => {
    const status = user.status === 'active' ? 'suspended' : 'active';
    const reason = status === 'suspended' ? window.prompt('Motivo de la suspensión:') : null;
    if (status === 'suspended' && !reason) return;
    router.patch(`/admin/users/${user.id}/status`, { status, reason });
};
const changePageStatus = (page: Page) => {
    const status = page.status === 'active' ? 'suspended' : 'active';
    const reason = status === 'suspended' ? window.prompt('Motivo de la suspensión:') : null;
    if (status === 'suspended' && !reason) return;
    router.patch(`/admin/pages/${page.id}/status`, { status, reason });
};
const adjust = (user: AdminUser) => {
    const form = adjustments[user.id];
    if (!form?.amount || !form.reason) return;
    router.post(`/admin/users/${user.id}/credit-adjustments`, form, { onSuccess: () => { adjustments[user.id] = { amount: 0, reason: '' }; } });
};
const savePackage = (item: Package) => router.patch(`/admin/credit-packages/${item.id}`, packageForms[item.id]);
const createPackage = () => newPackage.post('/admin/credit-packages', { onSuccess: () => newPackage.reset() });
</script>

<template>
    <Head title="Administración" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold leading-tight text-gray-800">Administración</h2></template>
        <div class="py-8"><div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><article v-for="(value, label) in { Usuarios: summary.users, 'Páginas públicas': summary.publishedPages, 'Créditos circulando': summary.credits, 'Ingresos aprobados': money(summary.approvedRevenue) }" :key="label" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-sm font-semibold text-slate-500">{{ label }}</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ value }}</p></article></section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="text-lg font-bold text-slate-950">Usuarios y créditos</h3><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[850px] text-left text-sm"><thead class="text-slate-500"><tr><th class="pb-3">Usuario</th><th>Rol</th><th>Estado</th><th>Saldo</th><th>Ajuste</th><th></th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="user in users" :key="user.id"><td class="py-3"><strong>{{ user.name }}</strong><br><span class="text-slate-500">{{ user.email }}</span></td><td>{{ user.role }}</td><td>{{ user.status }}</td><td>{{ user.credit_balance?.balance ?? 0 }}</td><td><div class="flex gap-2"><input v-model.number="adjustments[user.id].amount" type="number" class="w-20 rounded-lg border-slate-300" placeholder="±" /><input v-model="adjustments[user.id].reason" class="w-48 rounded-lg border-slate-300" placeholder="Motivo obligatorio" /><button class="rounded-lg bg-slate-900 px-3 text-white" @click="adjust(user)">Aplicar</button></div></td><td><button v-if="user.role !== 'admin'" class="font-bold text-rose-600" @click="changeUserStatus(user)">{{ user.status === 'active' ? 'Suspender' : 'Reactivar' }}</button></td></tr></tbody></table></div></section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="text-lg font-bold text-slate-950">Páginas</h3><div class="mt-4 divide-y divide-slate-100"><div v-for="page in pages" :key="page.id" class="flex flex-wrap items-center justify-between gap-4 py-3"><div><p class="font-bold text-slate-900">/{{ page.username }}</p><p class="text-sm text-slate-500">{{ page.user.email }} · {{ page.status }}</p></div><button class="text-sm font-bold" :class="page.status === 'active' ? 'text-rose-600' : 'text-emerald-600'" @click="changePageStatus(page)">{{ page.status === 'active' ? 'Suspender página' : 'Reactivar página' }}</button></div></div></section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="text-lg font-bold text-slate-950">Paquetes de créditos</h3><form class="mt-4 grid gap-2 rounded-xl bg-slate-50 p-4 md:grid-cols-6" @submit.prevent="createPackage"><input v-model="newPackage.name" required class="rounded-lg border-slate-300" placeholder="Nombre" /><input v-model.number="newPackage.credits" required type="number" min="1" class="rounded-lg border-slate-300" placeholder="Créditos" /><input v-model="newPackage.price" required type="number" min="0.01" step="0.01" class="rounded-lg border-slate-300" placeholder="Precio ARS" /><input v-model.number="newPackage.sort_order" type="number" min="0" class="rounded-lg border-slate-300" placeholder="Orden" /><label class="flex items-center gap-2 text-sm"><input v-model="newPackage.is_active" type="checkbox"> Activo</label><button class="rounded-lg bg-violet-600 px-3 py-2 font-bold text-white">Crear paquete</button></form><div class="mt-4 space-y-3"><div v-for="item in packages" :key="item.id" class="grid gap-2 rounded-xl border border-slate-200 p-3 md:grid-cols-6"><input v-model="packageForms[item.id].name" class="rounded-lg border-slate-300" /><input v-model.number="packageForms[item.id].credits" type="number" class="rounded-lg border-slate-300" /><input v-model="packageForms[item.id].price" type="number" step="0.01" class="rounded-lg border-slate-300" /><input v-model.number="packageForms[item.id].sort_order" type="number" class="rounded-lg border-slate-300" /><label class="flex items-center gap-2 text-sm"><input v-model="packageForms[item.id].is_active" type="checkbox"> Activo</label><button class="rounded-lg bg-slate-900 px-3 py-2 font-bold text-white" @click="savePackage(item)">Guardar</button></div></div></section>

            <section class="grid gap-6 lg:grid-cols-2"><div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="font-bold text-slate-950">Órdenes recientes</h3><div class="mt-4 divide-y divide-slate-100"><div v-for="order in orders" :key="order.id" class="flex justify-between gap-4 py-3 text-sm"><div><strong>{{ order.user.email }}</strong><p class="text-slate-500">{{ order.package_name }} · {{ date(order.created_at) }}</p></div><div class="text-right"><strong>{{ money(order.amount, order.currency) }}</strong><p class="text-slate-500">{{ order.status }}</p></div></div><p v-if="!orders.length" class="text-sm text-slate-500">Sin órdenes todavía.</p></div></div><div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="font-bold text-slate-950">Auditoría</h3><div class="mt-4 divide-y divide-slate-100"><div v-for="log in auditLogs" :key="log.id" class="py-3 text-sm"><strong>{{ log.action }}</strong><p class="text-slate-500">{{ log.actor?.name ?? 'Sistema' }} · {{ date(log.created_at) }}</p><p v-if="log.reason" class="text-slate-600">{{ log.reason }}</p></div><p v-if="!auditLogs.length" class="text-sm text-slate-500">Sin acciones administrativas todavía.</p></div></div></section>
        </div></div>
    </AuthenticatedLayout>
</template>
