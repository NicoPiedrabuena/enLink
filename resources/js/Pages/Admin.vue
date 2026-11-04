<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

type AdminUser = { id: number; name: string; email: string; role: string; status: string; created_at: string; credit_balance?: { balance: number }; link_page?: { username: string; status: string } };
type Page = { id: number; username: string; display_name: string; status: string; suspension_reason?: string; user: { name: string; email: string } };
type Package = { id: number; name: string; credits: number; price: string; currency: string; is_active: boolean; sort_order: number };
type Order = { id: number; package_name: string; credits: number; amount: string; currency: string; status: string; created_at: string; user: { name: string; email: string } };
type Audit = { id: number; action: string; reason?: string; created_at: string; actor?: { name: string } };

const props = defineProps<{ summary: { users: number; publishedPages: number; credits: number; approvedRevenue: string }; users: AdminUser[]; pages: Page[]; packages: Package[]; orders: Order[]; auditLogs: Audit[]; filters: { search: string } }>();
const packageForms = reactive<Record<number, Package>>(Object.fromEntries(props.packages.map((item) => [item.id, { ...item }])));
const newPackage = useForm({ name: '', credits: 10, price: '', currency: 'ARS', is_active: false, sort_order: props.packages.length });
const creditForm = useForm({ amount: 10, reason: '' });
const search = ref(props.filters.search);
const selectedUser = ref<AdminUser | null>(null);
const money = (amount: string, currency = 'ARS') => new Intl.NumberFormat('es-AR', { style: 'currency', currency }).format(Number(amount));
const date = (value: string) => new Intl.DateTimeFormat('es-AR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
const activeClients = computed(() => props.users.filter((user) => user.role !== 'admin' && user.status === 'active').length);

const searchClients = () => router.get('/admin', { search: search.value || undefined }, { preserveState: true, replace: true });
const openCreditForm = (user: AdminUser) => {
    selectedUser.value = user;
    creditForm.reset();
    creditForm.amount = 10;
    creditForm.clearErrors();
};
const closeCreditForm = () => {
    if (!creditForm.processing) selectedUser.value = null;
};
const grantCredits = () => {
    if (!selectedUser.value || creditForm.amount < 1 || !creditForm.reason.trim()) return;
    creditForm.post(`/admin/users/${selectedUser.value.id}/credit-adjustments`, {
        onSuccess: () => { selectedUser.value = null; },
    });
};

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
const savePackage = (item: Package) => router.patch(`/admin/credit-packages/${item.id}`, packageForms[item.id]);
const createPackage = () => newPackage.post('/admin/credit-packages', { onSuccess: () => newPackage.reset() });
</script>

<template>
    <Head title="Super Admin" />
    <AuthenticatedLayout>
        <template #header><div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl border-2 border-[#171713] bg-[#b9ff66] text-lg">✦</span><div><p class="text-xs font-black uppercase tracking-[0.18em] text-[#413d59]">Acceso restringido</p><h2 class="text-xl font-black leading-tight text-[#171713]">Super Admin</h2></div></div></template>
        <div class="py-8"><div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-[28px] border-2 border-[#171713] bg-[#171713] p-6 text-white shadow-[6px_6px_0_#6e35ff] sm:p-8"><div class="flex flex-col justify-between gap-6 md:flex-row md:items-end"><div><p class="text-sm font-black uppercase tracking-[0.14em] text-[#b9ff66]">Panel de control de Enlink</p><h1 class="mt-2 max-w-2xl text-3xl font-black tracking-[-0.04em] sm:text-4xl">Asigná créditos a cada cliente desde un único lugar.</h1><p class="mt-3 max-w-xl text-sm text-white/75">Cada acreditación queda registrada con el motivo, el administrador y el saldo resultante.</p></div><div class="rounded-2xl bg-white/10 px-5 py-4 text-sm"><p class="font-bold text-white/70">Clientes activos en esta vista</p><p class="mt-1 text-3xl font-black text-[#b9ff66]">{{ activeClients }}</p></div></div></section>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><article v-for="(value, label) in { Clientes: summary.users, 'Enlinks publicados': summary.publishedPages, 'Créditos asignados': summary.credits, 'Ingresos aprobados': money(summary.approvedRevenue) }" :key="label" class="rounded-2xl border-2 border-[#171713] bg-white p-5 shadow-[3px_3px_0_#171713]"><p class="text-sm font-bold text-slate-600">{{ label }}</p><p class="mt-2 text-2xl font-black text-[#171713]">{{ value }}</p></article></section>

            <section class="rounded-[24px] border-2 border-[#171713] bg-white p-5 shadow-[4px_4px_0_#171713] sm:p-6"><div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"><div><p class="text-xs font-black uppercase tracking-[0.14em] text-[#6e35ff]">Acreditación manual</p><h3 class="mt-1 text-xl font-black text-[#171713]">Clientes y Enlinks</h3><p class="mt-1 text-sm text-slate-600">Buscá por nombre, correo o URL del Enlink y elegí a quién acreditar.</p></div><form class="flex w-full max-w-lg gap-2" @submit.prevent="searchClients"><label class="sr-only" for="client-search">Buscar cliente o Enlink</label><input id="client-search" v-model="search" class="min-w-0 flex-1 rounded-xl border-2 border-[#171713] px-4 py-2.5 text-sm focus:border-[#6e35ff] focus:ring-[#6e35ff]" placeholder="Ej.: maria@correo.com o mi-enlink" /><button class="rounded-xl bg-[#b9ff66] px-4 py-2.5 text-sm font-black text-[#171713] transition hover:bg-[#a7eb58]">Buscar</button></form></div>
                <div class="mt-5 overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead class="border-b-2 border-[#171713] text-xs font-black uppercase tracking-[0.09em] text-slate-600"><tr><th class="pb-3">Cliente</th><th class="pb-3">Enlink</th><th class="pb-3">Estado</th><th class="pb-3">Saldo actual</th><th class="pb-3 text-right">Acción</th></tr></thead><tbody class="divide-y divide-slate-200"><tr v-for="user in users" :key="user.id" class="transition hover:bg-[#f6f4ee]"><td class="py-4"><strong class="font-black text-[#171713]">{{ user.name }}</strong><br><span class="text-slate-500">{{ user.email }}</span></td><td class="py-4"><span v-if="user.link_page" class="rounded-full bg-[#eeeaff] px-3 py-1 font-bold text-[#4f21c5]">/{{ user.link_page.username }}</span><span v-else class="text-slate-400">Sin Enlink</span></td><td class="py-4"><span class="rounded-full px-3 py-1 text-xs font-black" :class="user.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">{{ user.status === 'active' ? 'Activo' : 'Suspendido' }}</span></td><td class="py-4 text-lg font-black text-[#171713]">{{ user.credit_balance?.balance ?? 0 }}</td><td class="py-4 text-right"><button v-if="user.role !== 'admin'" class="rounded-xl bg-[#6e35ff] px-4 py-2.5 font-black text-white transition hover:-translate-y-0.5 hover:bg-[#5d27e8] disabled:cursor-not-allowed disabled:opacity-50" :disabled="user.status !== 'active'" @click="openCreditForm(user)">Acreditar créditos</button><span v-else class="text-xs font-bold text-slate-400">Super Admin</span></td></tr></tbody></table></div><p v-if="!users.length" class="py-10 text-center text-sm font-semibold text-slate-500">No encontramos clientes o Enlinks con esa búsqueda.</p></section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="text-lg font-bold text-slate-950">Páginas</h3><div class="mt-4 divide-y divide-slate-100"><div v-for="page in pages" :key="page.id" class="flex flex-wrap items-center justify-between gap-4 py-3"><div><p class="font-bold text-slate-900">/{{ page.username }}</p><p class="text-sm text-slate-500">{{ page.user.email }} · {{ page.status }}</p></div><button class="text-sm font-bold" :class="page.status === 'active' ? 'text-rose-600' : 'text-emerald-600'" @click="changePageStatus(page)">{{ page.status === 'active' ? 'Suspender página' : 'Reactivar página' }}</button></div></div></section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="text-lg font-bold text-slate-950">Paquetes de créditos</h3><form class="mt-4 grid gap-2 rounded-xl bg-slate-50 p-4 md:grid-cols-6" @submit.prevent="createPackage"><input v-model="newPackage.name" required class="rounded-lg border-slate-300" placeholder="Nombre" /><input v-model.number="newPackage.credits" required type="number" min="1" class="rounded-lg border-slate-300" placeholder="Créditos" /><input v-model="newPackage.price" required type="number" min="0.01" step="0.01" class="rounded-lg border-slate-300" placeholder="Precio ARS" /><input v-model.number="newPackage.sort_order" type="number" min="0" class="rounded-lg border-slate-300" placeholder="Orden" /><label class="flex items-center gap-2 text-sm"><input v-model="newPackage.is_active" type="checkbox"> Activo</label><button class="rounded-lg bg-violet-600 px-3 py-2 font-bold text-white">Crear paquete</button></form><div class="mt-4 space-y-3"><div v-for="item in packages" :key="item.id" class="grid gap-2 rounded-xl border border-slate-200 p-3 md:grid-cols-6"><input v-model="packageForms[item.id].name" class="rounded-lg border-slate-300" /><input v-model.number="packageForms[item.id].credits" type="number" class="rounded-lg border-slate-300" /><input v-model="packageForms[item.id].price" type="number" step="0.01" class="rounded-lg border-slate-300" /><input v-model.number="packageForms[item.id].sort_order" type="number" class="rounded-lg border-slate-300" /><label class="flex items-center gap-2 text-sm"><input v-model="packageForms[item.id].is_active" type="checkbox"> Activo</label><button class="rounded-lg bg-slate-900 px-3 py-2 font-bold text-white" @click="savePackage(item)">Guardar</button></div></div></section>

            <section class="grid gap-6 lg:grid-cols-2"><div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="font-bold text-slate-950">Órdenes recientes</h3><div class="mt-4 divide-y divide-slate-100"><div v-for="order in orders" :key="order.id" class="flex justify-between gap-4 py-3 text-sm"><div><strong>{{ order.user.email }}</strong><p class="text-slate-500">{{ order.package_name }} · {{ date(order.created_at) }}</p></div><div class="text-right"><strong>{{ money(order.amount, order.currency) }}</strong><p class="text-slate-500">{{ order.status }}</p></div></div><p v-if="!orders.length" class="text-sm text-slate-500">Sin órdenes todavía.</p></div></div><div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h3 class="font-bold text-slate-950">Auditoría</h3><div class="mt-4 divide-y divide-slate-100"><div v-for="log in auditLogs" :key="log.id" class="py-3 text-sm"><strong>{{ log.action }}</strong><p class="text-slate-500">{{ log.actor?.name ?? 'Sistema' }} · {{ date(log.created_at) }}</p><p v-if="log.reason" class="text-slate-600">{{ log.reason }}</p></div><p v-if="!auditLogs.length" class="text-sm text-slate-500">Sin acciones administrativas todavía.</p></div></div></section>
        </div></div>
    </AuthenticatedLayout>

    <Modal :show="selectedUser !== null" max-width="md" panel-class="!rounded-[24px] !border-2 !border-[#171713] !bg-[#f6f4ee]" @close="closeCreditForm">
        <form class="p-6" @submit.prevent="grantCredits"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[0.14em] text-[#6e35ff]">Nueva acreditación</p><h3 class="mt-1 text-2xl font-black text-[#171713]">Sumar créditos</h3></div><button type="button" class="grid size-9 place-items-center rounded-full border-2 border-[#171713] font-black" aria-label="Cerrar" @click="closeCreditForm">×</button></div><div class="mt-5 rounded-2xl border-2 border-[#171713] bg-white p-4"><p class="font-black text-[#171713]">{{ selectedUser?.name }}</p><p class="text-sm text-slate-600">{{ selectedUser?.email }}<template v-if="selectedUser?.link_page"> · /{{ selectedUser.link_page.username }}</template></p><p class="mt-3 text-sm font-bold text-slate-600">Saldo actual: <span class="text-lg text-[#171713]">{{ selectedUser?.credit_balance?.balance ?? 0 }} créditos</span></p></div><label class="mt-5 block text-sm font-black text-[#171713]" for="credit-amount">Cantidad a acreditar</label><input id="credit-amount" v-model.number="creditForm.amount" required min="1" max="10000" type="number" class="mt-2 block w-full rounded-xl border-2 border-[#171713] px-4 py-3 text-lg font-black focus:border-[#6e35ff] focus:ring-[#6e35ff]" /><p v-if="creditForm.errors.amount" class="mt-1 text-sm font-bold text-rose-700">{{ creditForm.errors.amount }}</p><label class="mt-5 block text-sm font-black text-[#171713]" for="credit-reason">Motivo de la acreditación</label><textarea id="credit-reason" v-model="creditForm.reason" required maxlength="500" rows="3" class="mt-2 block w-full rounded-xl border-2 border-[#171713] px-4 py-3 focus:border-[#6e35ff] focus:ring-[#6e35ff]" placeholder="Ej.: Bonificación por soporte" /><p class="mt-2 text-xs font-semibold text-slate-500">Este motivo quedará registrado en la auditoría.</p><p v-if="creditForm.errors.reason" class="mt-1 text-sm font-bold text-rose-700">{{ creditForm.errors.reason }}</p><div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" class="rounded-xl px-4 py-3 text-sm font-black text-[#171713]" :disabled="creditForm.processing" @click="closeCreditForm">Cancelar</button><button class="rounded-xl bg-[#6e35ff] px-5 py-3 text-sm font-black text-white transition hover:bg-[#5d27e8] disabled:opacity-60" :disabled="creditForm.processing || creditForm.amount < 1 || !creditForm.reason.trim()">{{ creditForm.processing ? 'Acreditando…' : `Acreditar ${creditForm.amount || 0} créditos` }}</button></div></form>
    </Modal>
</template>
