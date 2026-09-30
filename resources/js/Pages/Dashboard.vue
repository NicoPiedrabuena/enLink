<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

type EditorTab = 'links' | 'appearance' | 'profile';
type ButtonStyle = 'solid' | 'outline' | 'shadow' | 'transparent';
type Theme = { background: string; primary: string; text: string; radius: 'rounded' | 'soft' | 'square'; button_style: ButtonStyle };
type LinkIcon = 'link' | 'google' | 'linkedin' | 'maps' | 'instagram' | 'facebook' | 'whatsapp' | 'youtube' | 'tiktok' | 'spotify' | 'email' | 'calendar';
type LinkItem = { id: number; title: string; icon: LinkIcon; url: string; type: 'link' | 'reservation'; settings: { whatsapp_number?: string } | null; whatsapp_number?: string; position: number; is_active: boolean };
type Publication = { version: number; username: string; published_at: string };
type LinkPage = { username: string; display_name: string; bio: string | null; avatar_url: string | null; theme: Theme; links: LinkItem[]; active_publication: Publication | null };

const props = defineProps<{ page: LinkPage; credits: { balance: number }; publicUrl: string | null }>();
const themes: Array<{ name: string; theme: Theme }> = [
    { name: 'Noche', theme: { background: '#0f172a', primary: '#8b5cf6', text: '#f8fafc', radius: 'rounded', button_style: 'solid' } },
    { name: 'Enlink', theme: { background: '#f6f4ee', primary: '#6e35ff', text: '#171713', radius: 'rounded', button_style: 'shadow' } },
    { name: 'Minimal', theme: { background: '#ffffff', primary: '#171713', text: '#171713', radius: 'square', button_style: 'transparent' } },
    { name: 'Bosque', theme: { background: '#ecfdf5', primary: '#047857', text: '#064e3b', radius: 'soft', button_style: 'outline' } },
    { name: 'Atardecer', theme: { background: '#fff1e8', primary: '#ea580c', text: '#431407', radius: 'rounded', button_style: 'solid' } },
    { name: 'Lila pop', theme: { background: '#faf5ff', primary: '#7e22ce', text: '#3b0764', radius: 'soft', button_style: 'shadow' } },
];
const iconOptions: Array<{ value: LinkIcon; label: string; glyph: string }> = [
    { value: 'link', label: 'Enlace', glyph: '↗' },
    { value: 'google', label: 'Google', glyph: 'G' },
    { value: 'linkedin', label: 'LinkedIn', glyph: 'in' },
    { value: 'maps', label: 'Google Maps', glyph: '⌖' },
    { value: 'instagram', label: 'Instagram', glyph: '◎' },
    { value: 'facebook', label: 'Facebook', glyph: 'f' },
    { value: 'whatsapp', label: 'WhatsApp', glyph: '◉' },
    { value: 'youtube', label: 'YouTube', glyph: '▶' },
    { value: 'tiktok', label: 'TikTok', glyph: '♪' },
    { value: 'spotify', label: 'Spotify', glyph: '≋' },
    { value: 'email', label: 'Correo', glyph: '@' },
    { value: 'calendar', label: 'Calendario', glyph: '31' },
];
const iconGlyph = (icon: LinkIcon) => iconOptions.find((item) => item.value === icon)?.glyph ?? '↗';
const detectIcon = (url: string, type: LinkItem['type'] = 'link'): LinkIcon => {
    if (type === 'reservation') return 'calendar';
    const value = url.trim().toLowerCase();
    if (!value) return 'link';
    if (/^(mailto:)|(@[^/\s]+$)/.test(value)) return 'email';
    if (/(google\.[^/]+\/maps|maps\.google\.|goo\.gl\/maps)/.test(value)) return 'maps';
    if (/(linkedin\.com|linked\.in)/.test(value)) return 'linkedin';
    if (/(instagram\.com|instagr\.am)/.test(value)) return 'instagram';
    if (/(facebook\.com|fb\.com|fb\.me)/.test(value)) return 'facebook';
    if (/(wa\.me|whatsapp\.com)/.test(value)) return 'whatsapp';
    if (/(youtube\.com|youtu\.be)/.test(value)) return 'youtube';
    if (/tiktok\.com/.test(value)) return 'tiktok';
    if (/spotify\.com/.test(value)) return 'spotify';
    if (/(calendar\.google\.|calendly\.com)/.test(value)) return 'calendar';
    if (/google\./.test(value)) return 'google';
    return 'link';
};
const editorTab = ref<EditorTab>('links');
const form = useForm({ username: props.page.username, display_name: props.page.display_name, bio: props.page.bio ?? '', theme: { ...props.page.theme, button_style: props.page.theme.button_style ?? 'solid' as ButtonStyle } });
const newLink = useForm({ title: '', icon: 'link' as LinkIcon, url: '', type: 'link' as 'link' | 'reservation', whatsapp_number: '' });
const avatarForm = useForm<{ avatar: File | null }>({ avatar: null });
const links = ref<LinkItem[]>([]);
const avatarUrl = ref<string | null>(props.page.avatar_url);
const publishProcessing = ref(false);
const publishError = ref<string | null>(null);
const draggedLinkId = ref<number | null>(null);
const orderChanged = ref(false);

watch(() => props.page.links, (value) => { links.value = value.map((link) => ({ ...link, type: link.type ?? 'link', whatsapp_number: link.settings?.whatsapp_number ?? '' })).sort((a, b) => a.position - b.position); }, { immediate: true });
watch(() => props.page.avatar_url, (value) => { avatarUrl.value = value; });

const activeLinks = computed(() => links.value.filter((link) => link.is_active));
const hasPublishedVersion = computed(() => props.page.active_publication !== null);
const isOnboarding = computed(() => !hasPublishedVersion.value && links.value.length === 0);
const publishLabel = computed(() => props.credits.balance > 0 ? 'Publicar por 1 crédito' : 'Publicar · Pack $5.000');
const saveProfile = () => form.put('/dashboard/page', { preserveScroll: true });
const useTheme = (theme: Theme) => { form.theme = { ...theme }; };
const addLink = () => newLink.post('/dashboard/links', { preserveScroll: true, onSuccess: () => newLink.reset() });
const updateLink = (link: LinkItem) => {
    const editedField = document.activeElement as HTMLInputElement | null;
    if (editedField?.type === 'url') link.icon = detectIcon(link.url, link.type);
    router.put(`/dashboard/links/${link.id}`, { ...link, whatsapp_number: link.whatsapp_number ?? link.settings?.whatsapp_number ?? '' }, { preserveScroll: true });
};
const detectNewLinkIcon = () => { newLink.icon = detectIcon(newLink.url, newLink.type); };
const toggleLink = (link: LinkItem) => { link.is_active = !link.is_active; updateLink(link); };
const removeLink = (link: LinkItem) => router.delete(`/dashboard/links/${link.id}`, { preserveScroll: true });
const saveOrder = () => router.put('/dashboard/links/reorder', { links: links.value.map((link) => link.id) }, { preserveScroll: true });

const moveDraggedLink = (event: PointerEvent) => {
    if (draggedLinkId.value === null) return;
    const target = document.elementFromPoint(event.clientX, event.clientY)?.closest<HTMLElement>('[data-link-id]');
    if (!target) return;
    const targetId = Number(target.dataset.linkId);
    const currentIndex = links.value.findIndex((link) => link.id === draggedLinkId.value);
    const targetIndex = links.value.findIndex((link) => link.id === targetId);
    if (currentIndex < 0 || targetIndex < 0 || currentIndex === targetIndex) return;
    const [link] = links.value.splice(currentIndex, 1);
    links.value.splice(targetIndex, 0, link);
    orderChanged.value = true;
};

const finishDragging = () => {
    if (draggedLinkId.value === null) return;
    draggedLinkId.value = null;
    document.removeEventListener('pointermove', moveDraggedLink);
    document.removeEventListener('pointerup', finishDragging);
    document.removeEventListener('pointercancel', finishDragging);
    if (orderChanged.value) saveOrder();
    orderChanged.value = false;
};

const startDragging = (link: LinkItem, event: PointerEvent) => {
    if (links.value.length < 2) return;
    event.preventDefault();
    draggedLinkId.value = link.id;
    orderChanged.value = false;
    document.addEventListener('pointermove', moveDraggedLink);
    document.addEventListener('pointerup', finishDragging);
    document.addEventListener('pointercancel', finishDragging);
};

onBeforeUnmount(finishDragging);
const previewButtonStyle = computed(() => {
    const base = { color: form.theme.button_style === 'solid' ? '#ffffff' : form.theme.primary };
    if (form.theme.button_style === 'outline') return { ...base, background: 'transparent', border: `2px solid ${form.theme.primary}` };
    if (form.theme.button_style === 'shadow') return { ...base, background: form.theme.primary, border: `2px solid ${form.theme.text}`, boxShadow: `4px 4px 0 ${form.theme.text}` };
    if (form.theme.button_style === 'transparent') return { ...base, background: 'transparent', border: '1px solid transparent', textDecoration: 'underline', textUnderlineOffset: '5px' };
    return { ...base, background: form.theme.primary, border: '2px solid transparent' };
});
const uploadAvatar = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    avatarForm.avatar = file;
    avatarForm.post('/dashboard/avatar', { forceFormData: true, preserveScroll: true, onSuccess: () => { avatarUrl.value = URL.createObjectURL(file); input.value = ''; } });
};
const publish = () => router.post('/dashboard/publish', {}, {
    preserveScroll: true,
    onStart: () => { publishProcessing.value = true; publishError.value = null; },
    onError: (errors) => { publishError.value = errors.publish ?? 'No fue posible publicar los cambios.'; },
    onFinish: () => { publishProcessing.value = false; },
});
</script>

<template>
    <Head title="Editor de Enlink" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><p class="text-xs font-black uppercase tracking-[0.18em] text-[#3e207f]">Tu espacio creativo</p><h2 class="mt-1 text-3xl font-black tracking-[-0.045em] text-[#171713]">Enlink Studio</h2><p class="mt-1 text-sm font-semibold text-[#171713]/60">Creá y actualizá tu página en un solo lugar.</p></div>
                <div class="rounded-full border-2 border-[#171713] bg-[#b9ff66] px-5 py-2.5 text-sm font-black shadow-[3px_3px_0_#171713]">{{ props.credits.balance }} {{ props.credits.balance === 1 ? 'crédito' : 'créditos' }}</div>
            </div>
        </template>

        <main class="panel-studio min-h-[calc(100vh-8rem)] bg-[#f6f4ee] px-4 py-8 sm:px-6">
            <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[minmax(0,1fr)_390px]">
                <section class="min-w-0 space-y-5">
                    <section class="rounded-[2rem] border-2 border-[#171713] bg-[#171713] p-5 text-white shadow-[6px_6px_0_#b6a7ff] sm:flex sm:items-center sm:justify-between sm:gap-6 sm:p-6">
                        <div><p class="text-xs font-black uppercase tracking-[0.16em] text-[#b9ff66]">Estado de tu página</p><h3 class="mt-2 text-xl font-black">{{ hasPublishedVersion ? `Versión ${props.page.active_publication?.version} publicada` : 'Tu página está lista para publicar' }}</h3><p class="mt-1 max-w-xl text-sm text-white/65">{{ props.credits.balance > 0 ? 'Cada publicación consume 1 crédito.' : 'Editá y guardá gratis. Para publicar, comprá el pack de 3 créditos por $5.000.' }}</p></div>
                        <button class="mt-4 w-full rounded-full bg-[#b9ff66] px-6 py-3 text-sm font-black text-[#171713] transition hover:-translate-y-1 hover:bg-white disabled:cursor-not-allowed disabled:opacity-50 sm:mt-0 sm:w-auto" :disabled="publishProcessing" @click="publish">{{ publishProcessing ? 'Procesando…' : publishLabel }} →</button>
                        <p v-if="publishError" class="mt-3 text-sm font-medium text-rose-300 sm:hidden">{{ publishError }}</p>
                    </section>
                    <p v-if="publishError" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 sm:block">{{ publishError }}</p>

                    <div class="rounded-2xl border-2 border-[#171713] bg-white p-2 shadow-[4px_4px_0_#171713]"><nav class="grid grid-cols-3 gap-1" aria-label="Secciones del editor"><button class="rounded-xl px-3 py-3 text-sm font-black transition" :class="editorTab === 'links' ? 'bg-[#6e35ff] text-white' : 'text-[#171713]/60 hover:bg-[#b9ff66]'" :aria-pressed="editorTab === 'links'" @click="editorTab = 'links'">Enlaces</button><button class="rounded-xl px-3 py-3 text-sm font-black transition" :class="editorTab === 'appearance' ? 'bg-[#6e35ff] text-white' : 'text-[#171713]/60 hover:bg-[#b9ff66]'" :aria-pressed="editorTab === 'appearance'" @click="editorTab = 'appearance'">Apariencia</button><button class="rounded-xl px-3 py-3 text-sm font-black transition" :class="editorTab === 'profile' ? 'bg-[#6e35ff] text-white' : 'text-[#171713]/60 hover:bg-[#b9ff66]'" :aria-pressed="editorTab === 'profile'" @click="editorTab = 'profile'">Perfil</button></nav></div>

                    <section v-if="editorTab === 'links'" class="space-y-4">
                        <div v-if="isOnboarding" class="rounded-2xl border-2 border-[#171713] bg-[#ffd568] p-5 text-[#171713] shadow-[4px_4px_0_#171713]"><h3 class="font-black">Completá tu primera página</h3><p class="mt-1 text-sm">Podés editar y guardar todo gratis. Agregá al menos un enlace activo antes de publicar.</p></div>
                        <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="text-lg font-bold text-slate-950">Tus enlaces</h3><p class="mt-1 text-sm text-slate-500">Sumá enlaces comunes o una reserva que termine en WhatsApp.</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ activeLinks.length }} activos</span></div>
                            <form class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-2" @submit.prevent="addLink">
                                <label class="grid gap-1 text-sm font-bold text-slate-700">Tipo<select v-model="newLink.type" class="rounded-xl border-slate-300 bg-white" @change="detectNewLinkIcon"><option value="link">Enlace normal</option><option value="reservation">Reserva por WhatsApp</option></select></label>
                                <label class="grid gap-1 text-sm font-bold text-slate-700">Texto del botón<input v-model="newLink.title" class="rounded-xl border-slate-300 bg-white text-sm" maxlength="100" placeholder="Ej: Reservá tu mesa" required /></label>
                                <label class="grid gap-1 text-sm font-bold text-slate-700 sm:col-span-2">Ícono <span class="text-xs font-normal text-slate-500">Se detecta desde la URL; podés cambiarlo.</span><select v-model="newLink.icon" class="rounded-xl border-slate-300 bg-white"><option v-for="icon in iconOptions" :key="icon.value" :value="icon.value">{{ icon.glyph }} · {{ icon.label }}</option></select></label>
                                <label v-if="newLink.type === 'link'" class="grid gap-1 text-sm font-bold text-slate-700 sm:col-span-2">Dirección web<input v-model="newLink.url" class="rounded-xl border-slate-300 bg-white text-sm" type="url" placeholder="https://ejemplo.com" required @input="detectNewLinkIcon" /></label>
                                <label v-else class="grid gap-1 text-sm font-bold text-slate-700 sm:col-span-2">WhatsApp del negocio<input v-model="newLink.whatsapp_number" class="rounded-xl border-slate-300 bg-white text-sm" inputmode="numeric" placeholder="5491112345678" required /><span class="text-xs font-normal text-slate-500">Código de país + área + número, solamente dígitos. Ejemplo Argentina: 54911…</span></label>
                                <button class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-violet-700 disabled:opacity-50 sm:col-span-2" :disabled="newLink.processing">+ Agregar</button>
                            </form>
                            <p v-if="newLink.errors.url || newLink.errors.whatsapp_number" class="mt-2 text-sm text-rose-600">{{ newLink.errors.url || newLink.errors.whatsapp_number }}</p>
                            <div v-if="links.length" class="mt-5 space-y-3">
                                <p class="flex items-center gap-2 text-xs font-semibold text-slate-500"><span aria-hidden="true">⠿</span> Arrastrá desde los puntos para cambiar el orden.</p>
                                <article v-for="link in links" :key="link.id" :data-link-id="link.id" class="rounded-2xl border bg-white p-4 transition" :class="draggedLinkId === link.id ? 'scale-[1.01] border-[#6e35ff] opacity-80 shadow-lg' : 'border-slate-200 hover:border-slate-300'">
                                    <div class="grid gap-3 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-start">
                                        <button type="button" class="drag-handle grid min-h-11 min-w-11 cursor-grab touch-none place-items-center rounded-xl border border-slate-200 bg-slate-50 text-xl font-black text-slate-500 active:cursor-grabbing" :aria-label="`Arrastrar ${link.title} para cambiar su posición`" title="Arrastrar para ordenar" @pointerdown="startDragging(link, $event)">⠿</button>
                                        <div class="grid gap-2"><span class="w-fit rounded-full bg-[#b9ff66] px-2.5 py-1 text-[11px] font-black uppercase">{{ link.type === 'reservation' ? 'Reserva por WhatsApp' : 'Enlace' }}</span><div class="grid grid-cols-[86px_1fr] gap-2"><label class="grid gap-1 text-xs font-bold text-slate-500">Ícono<select v-model="link.icon" class="rounded-xl border-slate-300 text-sm" title="Ícono del botón" @change="updateLink(link)"><option v-for="icon in iconOptions" :key="icon.value" :value="icon.value">{{ icon.glyph }} {{ icon.label }}</option></select></label><label class="grid gap-1 text-xs font-bold text-slate-500">Texto<input v-model="link.title" class="rounded-xl border-slate-300 text-sm font-semibold" maxlength="100" placeholder="Título" @change="updateLink(link)" /></label></div><input v-if="link.type === 'link'" v-model="link.url" class="rounded-xl border-slate-300 text-sm text-slate-500" type="url" placeholder="https://..." @change="updateLink(link)" /><input v-else v-model="link.whatsapp_number" class="rounded-xl border-slate-300 text-sm text-slate-500" inputmode="numeric" placeholder="5491112345678" @change="updateLink(link)" /></div>
                                        <div class="flex items-center justify-between gap-3 sm:flex-col sm:items-end"><button class="relative h-7 w-12 rounded-full transition" :class="link.is_active ? 'bg-emerald-500' : 'bg-slate-300'" role="switch" :aria-checked="link.is_active" :title="link.is_active ? 'Desactivar enlace' : 'Activar enlace'" @click="toggleLink(link)"><span class="absolute top-1 h-5 w-5 rounded-full bg-white shadow transition" :class="link.is_active ? 'left-6' : 'left-1'" /></button><button class="text-sm font-bold text-rose-600 hover:text-rose-800" @click="removeLink(link)">Eliminar</button></div>
                                    </div>
                                </article>
                            </div>
                            <p v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Aún no hay enlaces. Agregá el primero arriba.</p>
                        </section>
                    </section>

                    <section v-else-if="editorTab === 'appearance'" class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6"><div><h3 class="text-lg font-bold text-slate-950">Apariencia</h3><p class="mt-1 text-sm text-slate-500">Elegí un estilo predeterminado y después personalizalo.</p></div><div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><button v-for="preset in themes" :key="preset.name" class="rounded-2xl border-2 p-3 text-left transition hover:-translate-y-1" :style="{ background: preset.theme.background, color: preset.theme.text, borderColor: preset.theme.text }" @click="useTheme(preset.theme)"><span class="block h-9 px-3 py-2 text-center text-xs font-black" :class="{ 'rounded-full': preset.theme.radius === 'rounded', 'rounded-lg': preset.theme.radius === 'soft' }" :style="{ background: preset.theme.button_style === 'solid' || preset.theme.button_style === 'shadow' ? preset.theme.primary : 'transparent', color: preset.theme.button_style === 'solid' ? '#fff' : preset.theme.primary, border: `2px solid ${preset.theme.primary}`, boxShadow: preset.theme.button_style === 'shadow' ? `3px 3px 0 ${preset.theme.text}` : 'none' }">Mi enlace</span><span class="mt-3 block text-sm font-black">{{ preset.name }}</span></button></div><div class="mt-6 grid gap-4 rounded-2xl bg-slate-50 p-4 sm:grid-cols-2"><label class="grid gap-1 text-sm font-semibold text-slate-700">Fondo<input v-model="form.theme.background" type="color" class="h-10 w-full rounded-lg border-0 bg-transparent" /></label><label class="grid gap-1 text-sm font-semibold text-slate-700">Color del botón<input v-model="form.theme.primary" type="color" class="h-10 w-full rounded-lg border-0 bg-transparent" /></label><label class="grid gap-1 text-sm font-semibold text-slate-700">Texto<input v-model="form.theme.text" type="color" class="h-10 w-full rounded-lg border-0 bg-transparent" /></label><label class="grid gap-1 text-sm font-semibold text-slate-700">Forma<select v-model="form.theme.radius" class="rounded-xl border-slate-300"><option value="rounded">Redondeados</option><option value="soft">Suaves</option><option value="square">Rectos</option></select></label><label class="grid gap-1 text-sm font-semibold text-slate-700 sm:col-span-2">Estilo del botón<select v-model="form.theme.button_style" class="rounded-xl border-slate-300"><option value="solid">Color pleno</option><option value="outline">Transparente con borde</option><option value="shadow">Remarcado estilo Enlink</option><option value="transparent">Solo texto transparente</option></select></label></div><button class="mt-6 rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white disabled:opacity-50" :disabled="form.processing" @click="saveProfile">Guardar apariencia</button></section>

                    <section v-else class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6"><div class="flex flex-wrap items-center justify-between gap-4"><div><h3 class="text-lg font-bold text-slate-950">Tu perfil</h3><p class="mt-1 text-sm text-slate-500">La información que encabeza tu página.</p></div><label class="cursor-pointer rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-200">Cambiar foto<input class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" @change="uploadAvatar" /></label></div><div class="mt-6 grid gap-5 sm:grid-cols-[104px_1fr]"><div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-2xl font-bold text-slate-500"><img v-if="avatarUrl" :src="avatarUrl" alt="Vista previa del avatar" class="h-full w-full object-cover" /><span v-else>{{ form.display_name.slice(0, 1).toUpperCase() }}</span></div><div class="grid gap-4"><label class="grid gap-1 text-sm font-semibold text-slate-700">Nombre público<input v-model="form.display_name" class="rounded-xl border-slate-300" maxlength="80" placeholder="Tu nombre" /></label><label class="grid gap-1 text-sm font-semibold text-slate-700">Usuario<input v-model="form.username" class="rounded-xl border-slate-300" maxlength="30" pattern="[a-zA-Z-]+" placeholder="tu-enlink" /><span class="text-xs font-normal text-slate-500">Solo letras y guiones; se guarda en minúsculas.</span></label><label class="grid gap-1 text-sm font-semibold text-slate-700">Bio<textarea v-model="form.bio" class="rounded-xl border-slate-300" maxlength="180" rows="4" placeholder="Contá quién sos" /></label></div></div><p v-if="form.errors.username" class="mt-3 text-sm text-rose-600">{{ form.errors.username }}</p><p v-if="avatarForm.errors.avatar" class="mt-3 text-sm text-rose-600">{{ avatarForm.errors.avatar }}</p><button class="mt-6 rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white disabled:opacity-50" :disabled="form.processing" @click="saveProfile">Guardar perfil</button></section>
                </section>

                <aside class="lg:sticky lg:top-6 lg:h-fit"><div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><div class="mb-4 flex items-center justify-between"><div><p class="text-sm font-bold text-slate-950">Vista previa</p><p class="text-xs text-slate-500">Así se verá en móvil</p></div><span class="h-2.5 w-2.5 rounded-full bg-emerald-500" title="Vista previa activa" /></div><div class="mx-auto max-w-[310px] rounded-[2.7rem] border-[9px] border-slate-950 p-5 shadow-2xl" :style="{ background: form.theme.background, color: form.theme.text }"><div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-slate-300 text-xl font-bold text-slate-500"><img v-if="avatarUrl" :src="avatarUrl" alt="" class="h-full w-full object-cover" /><span v-else>{{ form.display_name.slice(0, 1).toUpperCase() }}</span></div><h3 class="text-center text-lg font-bold">{{ form.display_name || 'Tu nombre' }}</h3><p class="mb-6 mt-1 text-center text-sm opacity-75">{{ form.bio || 'Tu bio aparecerá acá' }}</p><span v-for="link in activeLinks" :key="link.id" class="mb-3 flex items-center justify-center gap-2 px-4 py-3 text-center text-sm font-bold" :class="{ 'rounded-2xl': form.theme.radius === 'rounded', 'rounded-xl': form.theme.radius === 'soft' }" :style="previewButtonStyle"><span class="grid h-6 min-w-6 place-items-center rounded-md border border-current/25 px-1 text-[11px] font-black">{{ iconGlyph(link.icon) }}</span>{{ link.title }}</span><p v-if="!activeLinks.length" class="rounded-xl border border-dashed border-current/30 p-4 text-center text-xs opacity-60">Tus enlaces activos aparecerán acá.</p></div><a v-if="props.publicUrl" :href="props.publicUrl" target="_blank" rel="noopener noreferrer" class="mt-5 block rounded-xl bg-slate-950 px-4 py-3 text-center text-sm font-bold text-white">Abrir página publicada ↗</a><p v-else class="mt-5 text-center text-xs text-slate-400">Publicá para activar tu URL pública.</p></div></aside>
            </div>
        </main>
    </AuthenticatedLayout>
</template>

<style scoped>
.panel-studio :deep(section.rounded-3xl.bg-white),
.panel-studio :deep(aside > div.rounded-3xl) {
    border: 2px solid #171713;
    box-shadow: 6px 6px 0 #171713;
}

.panel-studio :deep(aside > div.rounded-3xl) {
    background: #b6a7ff;
}

.panel-studio :deep(form.bg-slate-50),
.panel-studio :deep(div.bg-slate-50) {
    background: #f6f4ee;
    border: 1px solid rgb(23 23 19 / 18%);
}

.panel-studio :deep(button.bg-violet-600) {
    background: #6e35ff;
    border-radius: 9999px;
    font-weight: 800;
}

.panel-studio :deep(button.bg-slate-950),
.panel-studio :deep(a.bg-slate-950) {
    background: #171713;
    border-radius: 9999px;
    font-weight: 800;
}

.panel-studio :deep(input:focus),
.panel-studio :deep(textarea:focus),
.panel-studio :deep(select:focus) {
    border-color: #6e35ff;
    box-shadow: 0 0 0 2px rgb(110 53 255 / 20%);
}
</style>
