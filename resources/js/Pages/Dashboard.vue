<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type ButtonStyle = 'solid' | 'outline' | 'shadow' | 'transparent';
type Theme = { background: string; primary: string; text: string; button_text?: string; background_image?: string | null; radius: 'rounded' | 'soft' | 'square'; button_style: ButtonStyle };
type LinkIcon = 'link' | 'google' | 'linkedin' | 'maps' | 'instagram' | 'facebook' | 'whatsapp' | 'youtube' | 'tiktok' | 'spotify' | 'gmail' | 'email' | 'calendar' | 'telegram' | 'x' | 'threads' | 'twitch' | 'discord' | 'pinterest' | 'snapchat' | 'calendly';
type SocialNetwork = 'instagram' | 'facebook' | 'whatsapp' | 'youtube' | 'tiktok' | 'spotify' | 'linkedin' | 'gmail' | 'telegram' | 'x' | 'threads' | 'twitch' | 'discord' | 'pinterest' | 'snapchat';
type SocialLink = { network: SocialNetwork; url: string };
type LinkItem = { id: number; title: string; icon: LinkIcon; icon_image_url?: string | null; url: string; type: 'link' | 'reservation'; settings: { whatsapp_number?: string; layout?: 'button' | 'banner' } | null; whatsapp_number?: string; position: number; is_active: boolean };
type Publication = { version: number; username: string; published_at: string };
type LinkPage = { username: string; display_name: string; bio: string | null; avatar_url: string | null; background_image_url: string | null; theme: Theme; social_links: SocialLink[] | null; links: LinkItem[]; active_publication: Publication | null };
type PageForm = { username: string; display_name: string; bio: string; theme: Theme; social_links: SocialLink[] };

const props = defineProps<{ page: LinkPage; credits: { balance: number }; publicUrl: string | null }>();

const themes: Array<{ name: string; theme: Theme }> = [
    { name: 'Noche', theme: { background: '#0f172a', primary: '#8b5cf6', text: '#f8fafc', button_text: '#ffffff', radius: 'rounded', button_style: 'solid' } },
    { name: 'Enlink', theme: { background: '#f6f4ee', primary: '#6e35ff', text: '#171713', button_text: '#ffffff', radius: 'rounded', button_style: 'shadow' } },
    { name: 'Minimal', theme: { background: '#ffffff', primary: '#171713', text: '#171713', button_text: '#171713', radius: 'square', button_style: 'transparent' } },
    { name: 'Bosque', theme: { background: '#ecfdf5', primary: '#047857', text: '#064e3b', button_text: '#064e3b', radius: 'soft', button_style: 'outline' } },
    { name: 'Atardecer', theme: { background: '#fff1e8', primary: '#ea580c', text: '#431407', button_text: '#ffffff', radius: 'rounded', button_style: 'solid' } },
    { name: 'Lila pop', theme: { background: '#faf5ff', primary: '#7e22ce', text: '#3b0764', button_text: '#ffffff', radius: 'soft', button_style: 'shadow' } },
];

const iconOptions: Array<{ value: LinkIcon; label: string }> = [
    { value: 'link', label: 'Sitio web' },
    { value: 'instagram', label: 'Instagram' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'linkedin', label: 'LinkedIn' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'youtube', label: 'YouTube' },
    { value: 'gmail', label: 'Gmail' },
    { value: 'telegram', label: 'Telegram' },
    { value: 'tiktok', label: 'TikTok' },
    { value: 'spotify', label: 'Spotify' },
    { value: 'x', label: 'X' },
    { value: 'threads', label: 'Threads' },
    { value: 'twitch', label: 'Twitch' },
    { value: 'discord', label: 'Discord' },
    { value: 'pinterest', label: 'Pinterest' },
    { value: 'snapchat', label: 'Snapchat' },
    { value: 'google', label: 'Google' },
    { value: 'maps', label: 'Google Maps' },
    { value: 'calendar', label: 'Google Calendar' },
    { value: 'calendly', label: 'Calendly' },
    { value: 'email', label: 'Correo' },
];

const socialNetworks: Array<{ value: SocialNetwork; label: string }> = [
    { value: 'instagram', label: 'Instagram' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'linkedin', label: 'LinkedIn' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'youtube', label: 'YouTube' },
    { value: 'gmail', label: 'Gmail' },
    { value: 'telegram', label: 'Telegram' },
    { value: 'tiktok', label: 'TikTok' },
    { value: 'spotify', label: 'Spotify' },
    { value: 'x', label: 'X' },
    { value: 'threads', label: 'Threads' },
    { value: 'twitch', label: 'Twitch' },
    { value: 'discord', label: 'Discord' },
    { value: 'pinterest', label: 'Pinterest' },
    { value: 'snapchat', label: 'Snapchat' },
];

const socialLabel = (network: SocialNetwork) => socialNetworks.find((item) => item.value === network)?.label ?? 'Red social';
const iconAssets: Partial<Record<LinkIcon | SocialNetwork, string>> = {
    maps: 'googlemaps',
    calendar: 'googlecalendar',
    email: 'gmail',
};
const iconAsset = (icon: LinkIcon | SocialNetwork) => iconAssets[icon] ?? icon;
const iconStyle = (icon: LinkIcon | SocialNetwork) => ({ '--brand-icon': `url("/social-icons/${iconAsset(icon)}.svg")` });
const radiusClass = (radius: Theme['radius'] = 'rounded') => ({ rounded: 'rounded-[1.35rem]', soft: 'rounded-xl', square: 'rounded-none' })[radius];

const buttonTextColor = (background: string) => {
    const hex = background.replace('#', '');
    if (!/^[0-9a-f]{6}$/i.test(hex)) return '#171713';
    const channels = [0, 2, 4].map((offset) => parseInt(hex.slice(offset, offset + 2), 16) / 255);
    const luminance = channels
        .map((channel) => channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4)
        .reduce((total, channel, index) => total + channel * [0.2126, 0.7152, 0.0722][index], 0);
    return (1.05 / (luminance + 0.05)) >= ((luminance + 0.05) / 0.059) ? '#ffffff' : '#171713';
};

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
    if (/(t\.me|telegram\.me|telegram\.org)/.test(value)) return 'telegram';
    if (/(x\.com|twitter\.com)/.test(value)) return 'x';
    if (/threads\.net/.test(value)) return 'threads';
    if (/twitch\.tv/.test(value)) return 'twitch';
    if (/discord\.(gg|com)/.test(value)) return 'discord';
    if (/pinterest\.(com|es)/.test(value)) return 'pinterest';
    if (/snapchat\.com/.test(value)) return 'snapchat';
    if (/calendly\.com/.test(value)) return 'calendly';
    if (/calendar\.google\./.test(value)) return 'calendar';
    if (/google\./.test(value)) return 'google';
    return 'link';
};

const form = useForm<PageForm>({
    username: props.page.username,
    display_name: props.page.display_name,
    bio: props.page.bio ?? '',
    theme: { ...props.page.theme, button_style: props.page.theme.button_style ?? 'solid', button_text: props.page.theme.button_text ?? buttonTextColor(props.page.theme.primary) },
    social_links: props.page.social_links ?? [],
});
const linkForm = useForm({ title: '', icon: 'link' as LinkIcon, icon_image: null as File | null, remove_icon_image: false, layout: 'button' as 'button' | 'banner', url: '', type: 'link' as 'link' | 'reservation', whatsapp_number: '', is_active: true });
const avatarForm = useForm<{ avatar: File | null }>({ avatar: null });
const backgroundImageForm = useForm<{ background_image: File | null }>({ background_image: null });

const links = ref<LinkItem[]>([]);
const avatarUrl = ref<string | null>(props.page.avatar_url);
const backgroundImageUrl = ref<string | null>(props.page.background_image_url);
const showProfileModal = ref(false);
const showSocialModal = ref(false);
const showLinkModal = ref(false);
const showAppearanceModal = ref(false);
const socialEditingIndex = ref<number | null>(null);
const linkEditingId = ref<number | null>(null);
const socialDraft = ref<SocialLink>({ network: 'instagram', url: '' });
const linkIconPreviewUrl = ref<string | null>(null);
const publishProcessing = ref(false);
const publishError = ref<string | null>(null);
const isVisitorPreview = ref(false);

watch(() => props.page.links, (value) => {
    links.value = value.map((link) => ({ ...link, type: link.type ?? 'link', whatsapp_number: link.settings?.whatsapp_number ?? '' })).sort((a, b) => a.position - b.position);
}, { immediate: true });
watch(() => props.page.avatar_url, (value) => { avatarUrl.value = value; });
watch(() => props.page.background_image_url, (value) => { backgroundImageUrl.value = value; });

const activeLinks = computed(() => links.value.filter((link) => link.is_active));
const hasPublishedVersion = computed(() => props.page.active_publication !== null);
const isOnboarding = computed(() => !hasPublishedVersion.value && links.value.length === 0);
const publishLabel = computed(() => props.credits.balance > 0 ? 'Publicar por 1 crédito' : 'Publicar · Pack $5.000');
const previewButtonStyle = computed(() => {
    const base = { color: form.theme.button_text ?? buttonTextColor(form.theme.primary) };
    if (form.theme.button_style === 'outline') return { ...base, background: 'transparent', border: `2px solid ${form.theme.primary}` };
    if (form.theme.button_style === 'shadow') return { ...base, background: form.theme.primary, border: `2px solid ${form.theme.text}`, boxShadow: `5px 5px 0 ${form.theme.text}` };
    if (form.theme.button_style === 'transparent') return { ...base, background: 'transparent', border: '2px solid transparent', textDecoration: 'underline', textUnderlineOffset: '6px' };
    return { ...base, background: form.theme.primary, border: '2px solid transparent' };
});
const previewPageStyle = computed(() => ({
    backgroundColor: form.theme.background,
    color: form.theme.text,
    backgroundImage: backgroundImageUrl.value ? `linear-gradient(rgb(4 7 19 / 28%), rgb(4 7 19 / 28%)), url("${backgroundImageUrl.value}")` : 'none',
    backgroundPosition: 'center',
    backgroundSize: 'cover',
}));

const savePage = (onSuccess?: () => void) => form.put('/dashboard/page', { preserveScroll: true, onSuccess });
const useTheme = (theme: Theme) => {
    form.theme = { ...theme, button_text: theme.button_text ?? buttonTextColor(theme.primary), background_image: form.theme.background_image };
};
const openProfileModal = () => { form.clearErrors(); showProfileModal.value = true; };
const openSocialModal = (index?: number) => {
    form.clearErrors();
    socialEditingIndex.value = index ?? null;
    socialDraft.value = index === undefined ? { network: 'instagram', url: '' } : { ...form.social_links[index] };
    showSocialModal.value = true;
};
const saveSocial = () => {
    const socialLinks = [...form.social_links];
    if (socialEditingIndex.value === null) socialLinks.push({ ...socialDraft.value });
    else socialLinks.splice(socialEditingIndex.value, 1, { ...socialDraft.value });
    form.social_links = socialLinks;
    savePage(() => { showSocialModal.value = false; });
};
const removeSocial = () => {
    if (socialEditingIndex.value === null) return;
    form.social_links = form.social_links.filter((_, index) => index !== socialEditingIndex.value);
    savePage(() => { showSocialModal.value = false; });
};
const resetLinkForm = () => {
    linkForm.clearErrors();
    linkForm.title = '';
    linkForm.icon = 'link';
    linkForm.icon_image = null;
    linkForm.remove_icon_image = false;
    linkForm.layout = 'button';
    linkIconPreviewUrl.value = null;
    linkForm.url = '';
    linkForm.type = 'link';
    linkForm.whatsapp_number = '';
    linkForm.is_active = true;
};
const openNewLink = () => { resetLinkForm(); linkEditingId.value = null; showLinkModal.value = true; };
const openEditLink = (link: LinkItem) => {
    linkForm.clearErrors();
    linkEditingId.value = link.id;
    linkForm.title = link.title;
    linkForm.layout = link.settings?.layout ?? 'button';
    linkForm.icon = link.icon;
    linkForm.icon_image = null;
    linkForm.remove_icon_image = false;
    linkIconPreviewUrl.value = link.icon_image_url ?? null;
    linkForm.url = link.url;
    linkForm.type = link.type;
    linkForm.whatsapp_number = link.whatsapp_number ?? link.settings?.whatsapp_number ?? '';
    linkForm.is_active = link.is_active;
    showLinkModal.value = true;
};
const detectLinkIcon = () => { linkForm.icon = detectIcon(linkForm.url, linkForm.type); };
const uploadLinkIconImage = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    linkForm.icon_image = file;
    linkForm.remove_icon_image = false;
    linkIconPreviewUrl.value = URL.createObjectURL(file);
};
const removeLinkIconImage = () => {
    linkForm.icon_image = null;
    linkForm.remove_icon_image = true;
    linkIconPreviewUrl.value = null;
};
const saveLink = () => {
    const options = { forceFormData: true, preserveScroll: true, onSuccess: () => { showLinkModal.value = false; resetLinkForm(); } };
    const path = linkEditingId.value === null ? '/dashboard/links' : '/dashboard/links/' + linkEditingId.value;
    linkForm.transform((data) => linkEditingId.value === null ? data : { ...data, _method: 'put' }).post(path, options);
};
const deleteLink = () => {
    if (linkEditingId.value === null) return;
    router.delete(`/dashboard/links/${linkEditingId.value}`, { preserveScroll: true, onSuccess: () => { showLinkModal.value = false; resetLinkForm(); } });
};
const uploadAvatar = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    avatarForm.avatar = file;
    avatarForm.post('/dashboard/avatar', { forceFormData: true, preserveScroll: true, onSuccess: () => { avatarUrl.value = URL.createObjectURL(file); input.value = ''; } });
};
const uploadBackgroundImage = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    backgroundImageForm.background_image = file;
    backgroundImageForm.post('/dashboard/background-image', { forceFormData: true, preserveScroll: true, onSuccess: () => { backgroundImageUrl.value = URL.createObjectURL(file); input.value = ''; } });
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
        <main class="studio-shell min-h-[calc(100vh-74px)] bg-[#f6f4ee] px-4 py-5 sm:px-6 lg:py-8">
            <div class="mx-auto max-w-7xl">
                <header class="studio-toolbar mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#171713] text-lg font-black text-[#b9ff66]">e</span><div class="min-w-0"><p class="truncate text-sm font-black text-[#171713]">Página de {{ form.display_name || 'tu marca' }}</p><p class="text-xs font-semibold text-[#171713]/55">{{ isVisitorPreview ? 'Así la ve quien llega a tu enlace' : 'Editá sobre tu página real' }}</p></div></div>
                    <div class="flex items-center gap-2">
                        <span class="hidden rounded-full bg-[#e8e3da] px-3 py-2 text-xs font-black text-[#171713] sm:inline">{{ props.credits.balance }} créditos</span>
                        <button class="toolbar-preview-button" type="button" @click="isVisitorPreview = !isVisitorPreview"><span aria-hidden="true">{{ isVisitorPreview ? '✎' : '◌' }}</span>{{ isVisitorPreview ? 'Volver a editar' : 'Vista cliente' }}</button>
                        <button class="rounded-full bg-[#171713] px-4 py-2.5 text-sm font-black text-white transition hover:-translate-y-0.5 disabled:opacity-50" :disabled="publishProcessing" @click="publish">{{ publishProcessing ? 'Procesando…' : publishLabel }}</button>
                    </div>
                </header>

                <p v-if="publishError" class="mb-5 rounded-2xl border-2 border-rose-500 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ publishError }}</p>

                <div class="items-start" :class="isVisitorPreview ? 'block' : 'grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]'">
                    <section class="canvas-workspace min-w-0 overflow-hidden rounded-[1.65rem] border-2 border-[#171713]">
                        <div class="canvas-topbar"><span>{{ isVisitorPreview ? 'Vista cliente' : 'Editor activo' }}</span><p>{{ isVisitorPreview ? 'Los controles están ocultos.' : 'Tocá un elemento para editarlo.' }}</p></div>
                        <article class="live-page mx-auto min-h-[590px] w-full max-w-[680px] px-4 py-12 text-center sm:px-8 sm:py-14" :class="{ 'live-page--visitor': isVisitorPreview }" :style="previewPageStyle">
                            <button class="group mx-auto mb-4 grid h-24 w-24 place-items-center overflow-hidden rounded-full bg-white/30 text-3xl font-black shadow-[0_12px_30px_rgb(15_23_42_/_24%)] ring-2 ring-white/30 transition hover:scale-105 focus:outline-none focus:ring-4 focus:ring-[#b9ff66]" type="button" :disabled="isVisitorPreview" aria-label="Editar foto de perfil" @click="openProfileModal">
                                <img v-if="avatarUrl" :src="avatarUrl" alt="Foto de perfil" class="h-full w-full object-cover" />
                                <span v-else>{{ (form.display_name || 'T').slice(0, 1).toUpperCase() }}</span>
                                <span v-if="!isVisitorPreview" class="edit-badge">+</span>
                            </button>

                            <button v-if="form.display_name" class="group relative block w-full text-center focus:outline-none" type="button" :disabled="isVisitorPreview" @click="openProfileModal">
                                <span class="text-3xl font-black tracking-[-0.045em] sm:text-[2.15rem]">{{ form.display_name }}</span><span class="inline-edit">Editar</span>
                            </button>
                            <button v-else-if="!isVisitorPreview" class="empty-copy mx-auto flex items-center gap-2 text-2xl font-black" type="button" @click="openProfileModal"><span>+</span> Agregá tu nombre</button>
                            <h1 v-else class="text-3xl font-black tracking-[-0.045em] sm:text-[2.15rem]">Tu nombre</h1>

                            <button v-if="form.bio" class="group relative mx-auto mt-3 block max-w-[540px] text-center text-base leading-relaxed opacity-80 focus:outline-none" type="button" :disabled="isVisitorPreview" @click="openProfileModal"><span class="whitespace-pre-line">{{ form.bio }}</span><span class="inline-edit">Editar</span></button>
                            <button v-else-if="!isVisitorPreview" class="empty-copy mx-auto mt-3 flex items-center gap-2 text-sm font-semibold opacity-70" type="button" @click="openProfileModal"><span>+</span> Agregá una bio</button>

                            <div class="mt-7 flex flex-wrap justify-center gap-2.5">
                                <button v-for="(social, index) in form.social_links" :key="`${social.network}-${index}`" class="social-editor-button" type="button" :disabled="isVisitorPreview" :aria-label="`Editar ${socialLabel(social.network)}`" :title="socialLabel(social.network)" @click="openSocialModal(index)"><span class="brand-icon" :style="iconStyle(social.network)" aria-hidden="true" /></button>
                                <button v-if="!isVisitorPreview" class="social-add-button" type="button" aria-label="Agregar red social" title="Agregar red social" @click="openSocialModal()"><span>+</span></button>
                            </div>
                            <button v-if="!form.social_links.length && !isVisitorPreview" class="mt-2 text-xs font-bold underline underline-offset-4 opacity-65" type="button" @click="openSocialModal()">Sumá tus redes sociales</button>

                            <section class="mx-auto mt-9 grid max-w-[600px] gap-3" aria-label="Tus enlaces">
                                <button v-for="link in activeLinks" :key="link.id" class="group relative flex min-h-14 items-center justify-center px-16 py-3 text-center text-base font-extrabold transition hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-[#b9ff66]" :class="[radiusClass(form.theme.radius), { 'preview-banner': link.settings?.layout === 'banner' && link.icon_image_url }]" :style="previewButtonStyle" type="button" :disabled="isVisitorPreview" @click="openEditLink(link)">
                                    <img v-if="link.icon_image_url" :src="link.icon_image_url" :class="link.settings?.layout === 'banner' ? 'preview-banner-image' : 'link-button-image'" alt="" /><span v-else class="brand-icon brand-icon--button link-button-icon" :style="iconStyle(link.icon)" aria-hidden="true" /><span>{{ link.title }}</span><span class="link-edit-icon">✎</span>
                                </button>
                                <p v-if="links.length && !activeLinks.length" class="rounded-2xl border border-dashed border-current/40 p-4 text-sm opacity-65">Tus enlaces están pausados. Tocá uno desde el panel para activarlo.</p>
                                <button v-if="!isVisitorPreview" class="add-link-card flex min-h-14 items-center justify-center gap-2 rounded-[1.35rem] border-2 border-dashed px-5 py-3 text-sm font-black transition hover:bg-white/20" type="button" @click="openNewLink"><span class="grid h-6 w-6 place-items-center rounded-full bg-current text-sm leading-none"><span :style="{ color: form.theme.background }">+</span></span> Agregar enlace</button>
                            </section>
                            <p class="mt-10 text-xs font-bold opacity-50">Hecho con Enlink</p>
                        </article>
                    </section>

                    <aside v-if="!isVisitorPreview" class="space-y-4 lg:sticky lg:top-5">
                        <section class="status-card p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div><p class="text-xs font-black uppercase tracking-[0.14em] text-[#6e35ff]">Estado</p><h2 class="mt-1 text-lg font-black">{{ hasPublishedVersion ? 'Página publicada' : 'Borrador' }}</h2></div>
                                <span class="mt-1 h-3 w-3 rounded-full" :class="hasPublishedVersion ? 'bg-emerald-500' : 'bg-amber-400'" />
                            </div>
                            <p class="mt-2 text-sm leading-relaxed text-[#171713]/60">{{ hasPublishedVersion ? `Versión ${props.page.active_publication?.version} visible para tus visitas.` : 'Editá libremente y publicá cuando esté lista para compartir.' }}</p>
                            <a v-if="props.publicUrl" :href="props.publicUrl" target="_blank" rel="noopener noreferrer" class="mt-4 block rounded-full border-2 border-[#171713] px-4 py-2.5 text-center text-sm font-black transition hover:bg-[#b9ff66]">Abrir página publicada ↗</a>
                        </section>

                        <section class="control-card overflow-hidden p-2">
                            <button class="control-action" type="button" @click="openProfileModal"><span class="control-icon">◉</span><span><strong>Perfil</strong><small>Foto, nombre y bio</small></span><b>→</b></button>
                            <button class="control-action" type="button" @click="openSocialModal()"><span class="control-icon">◎</span><span><strong>Redes sociales</strong><small>{{ form.social_links.length ? `${form.social_links.length} conectada${form.social_links.length === 1 ? '' : 's'}` : 'Agregá accesos rápidos' }}</small></span><b>→</b></button>
                            <button class="control-action" type="button" @click="openNewLink"><span class="control-icon">↗</span><span><strong>Enlaces</strong><small>{{ activeLinks.length }} activo{{ activeLinks.length === 1 ? '' : 's' }}</small></span><b>→</b></button>
                            <button class="control-action" type="button" @click="showAppearanceModal = true"><span class="control-icon">✦</span><span><strong>Apariencia</strong><small>Colores, formas y fondo</small></span><b>→</b></button>
                        </section>

                        <p v-if="isOnboarding" class="rounded-2xl border-2 border-[#171713] bg-[#ffd568] p-4 text-sm font-semibold shadow-[4px_4px_0_#171713]">Tu primer paso: completá el perfil, sumá un enlace y publicá cuando te guste cómo quedó.</p>
                    </aside>
                </div>
            </div>
        </main>
    </AuthenticatedLayout>

    <Modal :show="showProfileModal" max-width="lg" panel-class="studio-modal-frame" @close="showProfileModal = false">
        <section class="modal-panel p-5 sm:p-7">
            <div class="flex items-start justify-between gap-4"><div><p class="modal-eyebrow">Perfil</p><h2 class="modal-title">Contá quién sos</h2><p class="modal-subtitle">Esto aparece primero en tu página.</p></div><button class="modal-close" type="button" aria-label="Cerrar" @click="showProfileModal = false">×</button></div>
            <div class="mt-6 grid gap-5 sm:grid-cols-[112px_1fr]">
                <div class="text-center"><div class="mx-auto grid h-24 w-24 place-items-center overflow-hidden rounded-full bg-[#e9e6dd] text-3xl font-black text-[#171713]/50"><img v-if="avatarUrl" :src="avatarUrl" alt="Foto de perfil" class="h-full w-full object-cover" /><span v-else>{{ (form.display_name || 'T').slice(0, 1).toUpperCase() }}</span></div><label class="mt-3 inline-block cursor-pointer text-sm font-black text-[#6e35ff] underline underline-offset-4">Cambiar foto<input class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" @change="uploadAvatar" /></label></div>
                <div class="grid gap-4">
                    <label class="field-label">Nombre público<input v-model="form.display_name" maxlength="80" placeholder="Ej: Cliente Demo" /></label>
                    <label class="field-label">Tu bio<textarea v-model="form.bio" maxlength="180" rows="4" placeholder="Contá brevemente quién sos o qué ofrecés" /></label>
                    <label class="field-label">Usuario <span class="field-hint">Solo letras y guiones.</span><input v-model="form.username" maxlength="30" pattern="[a-zA-Z-]+" placeholder="tu-enlink" /></label>
                </div>
            </div>
            <p v-if="form.errors.display_name || form.errors.username || avatarForm.errors.avatar" class="mt-4 text-sm font-semibold text-rose-600">{{ form.errors.display_name || form.errors.username || avatarForm.errors.avatar }}</p>
            <div class="mt-7 flex justify-end gap-3"><button class="secondary-modal-button" type="button" @click="showProfileModal = false">Cancelar</button><button class="primary-modal-button" type="button" :disabled="form.processing" @click="savePage(() => { showProfileModal = false; })">Guardar cambios</button></div>
        </section>
    </Modal>

    <Modal :show="showSocialModal" max-width="md" panel-class="studio-modal-frame" @close="showSocialModal = false">
        <section class="modal-panel p-5 sm:p-7">
            <div class="flex items-start justify-between gap-4"><div><p class="modal-eyebrow">Redes sociales</p><h2 class="modal-title">{{ socialEditingIndex === null ? 'Sumá un acceso rápido' : 'Editá tu red social' }}</h2><p class="modal-subtitle">Se muestran arriba de tus enlaces, como en tu bio real.</p></div><button class="modal-close" type="button" aria-label="Cerrar" @click="showSocialModal = false">×</button></div>
            <div class="mt-6 grid gap-4">
                <fieldset class="field-label"><legend>Elegí una red social</legend><div class="icon-library"><button v-for="network in socialNetworks" :key="network.value" class="icon-choice" :class="{ 'icon-choice--selected': socialDraft.network === network.value }" type="button" :title="network.label" @click="socialDraft.network = network.value"><span class="brand-icon" :style="iconStyle(network.value)" aria-hidden="true" /><span class="icon-choice-label">{{ network.label }}</span></button></div></fieldset>
                <label class="field-label">Enlace<input v-model="socialDraft.url" type="url" placeholder="https://instagram.com/tu-cuenta" /></label>
            </div>
            <p v-if="form.errors['social_links'] || form.errors['social_links.0.url']" class="mt-4 text-sm font-semibold text-rose-600">{{ form.errors['social_links'] || form.errors['social_links.0.url'] }}</p>
            <div class="mt-7 flex flex-wrap justify-between gap-3"><button v-if="socialEditingIndex !== null" class="text-sm font-black text-rose-600 underline underline-offset-4" type="button" :disabled="form.processing" @click="removeSocial">Eliminar</button><span v-else></span><div class="flex gap-3"><button class="secondary-modal-button" type="button" @click="showSocialModal = false">Cancelar</button><button class="primary-modal-button" type="button" :disabled="form.processing" @click="saveSocial">Guardar</button></div></div>
        </section>
    </Modal>

    <Modal :show="showLinkModal" max-width="lg" panel-class="studio-modal-frame" @close="showLinkModal = false">
        <section class="modal-panel p-5 sm:p-7">
            <div class="flex items-start justify-between gap-4"><div><p class="modal-eyebrow">Enlace</p><h2 class="modal-title">{{ linkEditingId === null ? 'Agregá un enlace' : 'Editá tu enlace' }}</h2><p class="modal-subtitle">Elegí cómo querés que se vea en tu página.</p></div><button class="modal-close" type="button" aria-label="Cerrar" @click="showLinkModal = false">×</button></div>
            <form class="mt-6 grid gap-4" @submit.prevent="saveLink">
                <label class="field-label">Tipo<select v-model="linkForm.type" @change="detectLinkIcon"><option value="link">Enlace normal</option><option value="reservation">Reserva por WhatsApp</option></select></label>
                <label class="field-label">Nombre del botón<input v-model="linkForm.title" maxlength="100" placeholder="Ej: Visitá mi tienda" required /></label>
                <label class="field-label">Formato<select v-model="linkForm.layout"><option value="button">Botón con imagen a la izquierda</option><option value="banner">Banner con imagen grande</option></select><span v-if="linkForm.layout === 'banner'" class="field-hint">Subí una imagen para mostrar el banner.</span></label>
                <fieldset class="field-label"><legend>Elegí un ícono</legend><div class="icon-library icon-library--links"><button v-for="icon in iconOptions" :key="icon.value" class="icon-choice" :class="{ 'icon-choice--selected': linkForm.icon === icon.value && !linkIconPreviewUrl }" type="button" :title="icon.label" @click="linkForm.icon = icon.value; removeLinkIconImage()"><span class="brand-icon" :style="iconStyle(icon.value)" aria-hidden="true" /><span class="icon-choice-label">{{ icon.label }}</span></button></div></fieldset>
                <div class="field-label"><span>Imagen del enlace <span class="field-hint">Opcional · JPG, PNG o WEBP · hasta 1 MB</span></span><div class="custom-icon-upload"><img v-if="linkIconPreviewUrl" :src="linkIconPreviewUrl" class="custom-icon-preview" alt="" /><span v-else class="custom-icon-placeholder">▧</span><div><label class="cursor-pointer rounded-full border-2 border-[#171713] bg-white px-4 py-2 text-sm font-black">Subir imagen<input class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" @change="uploadLinkIconImage" /></label><button v-if="linkIconPreviewUrl" class="ml-2 text-sm font-black text-rose-600 underline underline-offset-4" type="button" @click="removeLinkIconImage">Quitar</button></div></div></div>
                <label v-if="linkForm.type === 'link'" class="field-label">Dirección web<input v-model="linkForm.url" type="url" placeholder="https://ejemplo.com" required @input="detectLinkIcon" /></label>
                <label v-else class="field-label">WhatsApp del negocio<input v-model="linkForm.whatsapp_number" inputmode="numeric" placeholder="5491112345678" required /><span class="field-hint">Código de país + área + número, solo dígitos.</span></label>
                <label v-if="linkEditingId !== null" class="flex items-center gap-3 rounded-xl bg-[#f6f4ee] px-4 py-3 text-sm font-bold"><input v-model="linkForm.is_active" type="checkbox" class="rounded border-[#171713] text-[#6e35ff] focus:ring-[#6e35ff]" /> Mostrar este enlace en mi página</label>
                <p v-if="linkForm.errors.title || linkForm.errors.url || linkForm.errors.whatsapp_number || linkForm.errors.icon_image || linkForm.errors.layout" class="text-sm font-semibold text-rose-600">{{ linkForm.errors.title || linkForm.errors.url || linkForm.errors.whatsapp_number || linkForm.errors.icon_image || linkForm.errors.layout }}</p>
                <div class="mt-3 flex flex-wrap justify-between gap-3"><button v-if="linkEditingId !== null" class="text-sm font-black text-rose-600 underline underline-offset-4" type="button" :disabled="linkForm.processing" @click="deleteLink">Eliminar</button><span v-else></span><div class="flex gap-3"><button class="secondary-modal-button" type="button" @click="showLinkModal = false">Cancelar</button><button class="primary-modal-button" type="submit" :disabled="linkForm.processing">{{ linkForm.processing ? 'Guardando…' : 'Guardar enlace' }}</button></div></div>
            </form>
        </section>
    </Modal>

    <Modal :show="showAppearanceModal" max-width="2xl" panel-class="studio-modal-frame" @close="showAppearanceModal = false">
        <section class="modal-panel p-5 sm:p-7">
            <div class="flex items-start justify-between gap-4"><div><p class="modal-eyebrow">Apariencia</p><h2 class="modal-title">Diseñá tu Enlink</h2><p class="modal-subtitle">La vista de la derecha es la página real: no muestra controles de edición.</p></div><button class="modal-close" type="button" aria-label="Cerrar" @click="showAppearanceModal = false">×</button></div>
            <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.9fr)]">
                <div>
                    <p class="field-label">Estilos predeterminados</p>
                    <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <button v-for="preset in themes" :key="preset.name" class="theme-swatch text-left" :style="{ background: preset.theme.background, color: preset.theme.text, borderColor: preset.theme.text }" type="button" @click="useTheme(preset.theme)"><span class="block px-2 py-2 text-center text-[11px] font-black" :class="radiusClass(preset.theme.radius)" :style="{ background: preset.theme.button_style === 'solid' || preset.theme.button_style === 'shadow' ? preset.theme.primary : 'transparent', color: preset.theme.button_text, border: `2px solid ${preset.theme.primary}`, boxShadow: preset.theme.button_style === 'shadow' ? `3px 3px 0 ${preset.theme.text}` : 'none' }">Mi enlace</span><strong class="mt-2 block text-sm">{{ preset.name }}</strong></button>
                    </div>
                    <div class="mt-5 grid gap-4 rounded-2xl bg-[#f6f4ee] p-4 sm:grid-cols-2">
                        <label class="field-label">Fondo<input v-model="form.theme.background" type="color" class="color-field" /></label>
                        <label class="field-label">Color del botón<input v-model="form.theme.primary" type="color" class="color-field" /></label>
                        <label class="field-label">Texto de la página<input v-model="form.theme.text" type="color" class="color-field" /></label>
                        <label class="field-label">Texto del botón<input v-model="form.theme.button_text" type="color" class="color-field" /></label>
                        <label class="field-label">Forma<select v-model="form.theme.radius"><option value="rounded">Redondeados</option><option value="soft">Suaves</option><option value="square">Rectos</option></select></label>
                        <label class="field-label">Estilo del botón<select v-model="form.theme.button_style"><option value="solid">Color pleno</option><option value="outline">Transparente con borde</option><option value="shadow">Remarcado estilo Enlink</option><option value="transparent">Solo texto transparente</option></select></label>
                        <div class="field-label sm:col-span-2"><span>Imagen de fondo</span><span class="field-hint">JPG, PNG o WEBP · hasta 5 MB</span><div class="mt-2 flex flex-wrap items-center gap-3"><span v-if="backgroundImageUrl" class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-800">Imagen cargada</span><label class="cursor-pointer rounded-full border-2 border-[#171713] bg-white px-4 py-2 text-sm font-black">Subir imagen<input class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" @change="uploadBackgroundImage" /></label></div><p v-if="backgroundImageForm.errors.background_image" class="mt-2 text-xs font-semibold text-rose-600">{{ backgroundImageForm.errors.background_image }}</p></div>
                    </div>
                </div>
                <div class="appearance-preview-shell"><p class="mb-3 text-center text-xs font-black uppercase tracking-[0.14em] text-[#171713]/55">Vista real</p><article class="live-page live-page--clean min-h-[510px] px-5 py-12 text-center" :style="previewPageStyle"><div class="mx-auto mb-4 grid h-24 w-24 place-items-center overflow-hidden rounded-full bg-white/30 text-3xl font-black shadow-[0_12px_30px_rgb(15_23_42_/_18%)]"><img v-if="avatarUrl" :src="avatarUrl" alt="" class="h-full w-full object-cover" /><span v-else>{{ (form.display_name || 'T').slice(0, 1).toUpperCase() }}</span></div><h3 class="text-3xl font-black tracking-[-0.045em]">{{ form.display_name || 'Tu nombre' }}</h3><p v-if="form.bio" class="mx-auto mt-3 max-w-[540px] whitespace-pre-line text-base leading-relaxed opacity-80">{{ form.bio }}</p><div v-if="form.social_links.length" class="mt-7 flex flex-wrap justify-center gap-2.5"><a v-for="(social, index) in form.social_links" :key="`${social.network}-${index}`" class="social-real-button" :href="social.url" target="_blank" rel="noopener noreferrer" :aria-label="socialLabel(social.network)"><span class="brand-icon" :style="iconStyle(social.network)" aria-hidden="true" /></a></div><section class="mx-auto mt-9 grid max-w-[600px] gap-3"><span v-for="link in activeLinks" :key="link.id" class="relative flex min-h-14 items-center justify-center px-16 py-3 text-center text-base font-extrabold" :class="[radiusClass(form.theme.radius), { 'preview-banner': link.settings?.layout === 'banner' && link.icon_image_url }]" :style="previewButtonStyle"><img v-if="link.icon_image_url" :src="link.icon_image_url" :class="link.settings?.layout === 'banner' ? 'preview-banner-image' : 'link-button-image'" alt="" /><span v-else class="brand-icon brand-icon--button link-button-icon" :style="iconStyle(link.icon)" aria-hidden="true" />{{ link.title }}</span></section><p class="mt-10 text-xs font-bold opacity-50">Hecho con Enlink</p></article></div>
            </div>
            <div class="mt-7 flex justify-end gap-3"><button class="secondary-modal-button" type="button" @click="showAppearanceModal = false">Cancelar</button><button class="primary-modal-button" type="button" :disabled="form.processing" @click="savePage(() => { showAppearanceModal = false; })">Guardar apariencia</button></div>
        </section>
    </Modal>
</template>

<style scoped>
.studio-toolbar { min-height: 66px; border-bottom: 1px solid rgb(23 23 19 / 18%); padding-bottom: 14px; }
.toolbar-preview-button { display: inline-flex; align-items: center; gap: 7px; border: 1.5px solid #171713; border-radius: 999px; background: #fff; padding: 9px 13px; color: #171713; font-size: 13px; font-weight: 900; transition: background-color .18s ease, transform .18s ease; }
.toolbar-preview-button:hover { transform: translateY(-1px); background: #b9ff66; }
.canvas-workspace { background: #e9e6dd; box-shadow: 5px 5px 0 #171713; }
.canvas-topbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 44px; padding: 0 16px; color: #57544d; font-size: 12px; font-weight: 700; }
.canvas-topbar span { color: #171713; font-size: 11px; font-weight: 900; letter-spacing: .13em; text-transform: uppercase; }
.canvas-topbar p { margin: 0; }
.live-page { background-attachment: scroll; box-shadow: inset 0 0 0 1px rgb(255 255 255 / 20%); }
.live-page--visitor button { cursor: default; }
.live-page--visitor .inline-edit, .live-page--visitor .link-edit-icon { display: none; }
.live-page--clean { width: 100%; max-width: 680px; margin: 0 auto; }
.edit-badge { position: absolute; display: grid; width: 27px; height: 27px; place-items: center; right: 1px; bottom: 1px; border: 2px solid currentColor; border-radius: 999px; background: #b9ff66; color: #171713; font-size: 18px; line-height: 1; opacity: 0; transition: opacity .18s ease; }
.group:hover .edit-badge, .group:focus-visible .edit-badge { opacity: 1; }
.inline-edit { display: inline-block; margin-left: 9px; color: inherit; font-size: 11px; font-weight: 900; letter-spacing: .02em; opacity: 0; text-decoration: underline; text-underline-offset: 3px; transition: opacity .18s ease; vertical-align: middle; }
.group:hover .inline-edit, .group:focus-visible .inline-edit { opacity: .72; }
.empty-copy { border-bottom: 2px dashed currentColor; padding-bottom: 3px; }
.brand-icon { display: inline-block; width: 1em; height: 1em; flex: 0 0 auto; background: currentColor; mask: var(--brand-icon) center / contain no-repeat; -webkit-mask: var(--brand-icon) center / contain no-repeat; }
.brand-icon--button { width: 24px; height: 24px; }
.preview-banner { display: flex; flex-direction: column; padding: 8px 8px 14px; gap: 12px; overflow: hidden; }
.preview-banner-image { width: 100%; aspect-ratio: 16 / 9; object-fit: cover; border-radius: 12px; }
.link-button-icon, .link-button-image { position: absolute; left: 20px; width: 24px; height: 24px; }
.link-button-image { border-radius: 7px; object-fit: cover; }
.social-editor-button, .social-add-button, .social-real-button { display: grid; width: 42px; height: 42px; place-items: center; border: 1.5px solid rgb(255 255 255 / 70%); border-radius: 999px; background: rgb(15 15 15 / 25%); color: inherit; font-size: 17px; font-weight: 900; line-height: 1; transition: transform .18s ease, background-color .18s ease; }
.social-editor-button:hover:not(:disabled), .social-add-button:hover, .social-real-button:hover { transform: translateY(-2px); background: rgb(15 15 15 / 46%); }
.social-editor-button:disabled { cursor: default; }
.social-add-button { border-style: dashed; background: rgb(255 255 255 / 16%); }
.social-real-button { text-decoration: none; }
.link-edit-icon { position: absolute; right: 15px; font-size: 14px; opacity: 0; transition: opacity .18s ease; }
.group:hover .link-edit-icon, .group:focus-visible .link-edit-icon { opacity: .78; }
.add-link-card { color: inherit; }
.status-card { border: 2px solid #171713; border-radius: 1.5rem; background: #171713; box-shadow: 5px 5px 0 #6e35ff; color: #fff; }
.status-card h2 { color: #fff; }
.status-card p { color: rgb(255 255 255 / 68%); }
.control-card { border: 2px solid #171713; border-radius: 1.5rem; background: #fff; box-shadow: 5px 5px 0 #171713; }
.control-action { display: grid; width: 100%; grid-template-columns: 40px minmax(0, 1fr) auto; gap: 10px; align-items: center; border-radius: 1rem; padding: 12px; text-align: left; transition: background-color .18s ease, transform .18s ease; }
.control-action:hover { background: #f0edff; transform: translateX(2px); }
.control-icon { display: grid; width: 36px; height: 36px; place-items: center; border-radius: 12px; background: #b9ff66; color: #171713; font-weight: 900; }
.control-action strong, .control-action small { display: block; }
.control-action strong { font-size: 14px; font-weight: 900; color: #171713; }
.control-action small { margin-top: 2px; overflow: hidden; color: rgb(23 23 19 / 58%); font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
.control-action b { color: #6e35ff; font-size: 18px; }
.studio-shell :deep(.studio-modal-frame) { border: 2px solid #171713; border-radius: 28px; background: #e9e2ff; box-shadow: 10px 10px 0 #171713; }
.modal-panel { position: relative; overflow: hidden; color: #171713; background: linear-gradient(135deg, #ffffff 0%, #f7f4ff 66%, #e6ffdf 145%); }
.modal-panel::before { position: absolute; top: 0; left: 0; width: 100%; height: 7px; background: linear-gradient(90deg, #6e35ff 0 68%, #b9ff66 68%); content: ''; }
.modal-eyebrow { color: #5730c7; font-size: 11px; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }
.modal-title { margin-top: 3px; color: #171713; font-size: 27px; font-weight: 900; letter-spacing: -.045em; }
.modal-subtitle { margin-top: 5px; max-width: 48ch; color: #5f5a70; font-size: 14px; line-height: 1.45; }
.modal-close { display: grid; width: 36px; height: 36px; place-items: center; border: 2px solid #171713; border-radius: 999px; background: #fff; color: #171713; font-size: 25px; line-height: 1; transition: background-color .18s ease, transform .18s ease; }
.modal-close:hover { transform: rotate(6deg); background: #b9ff66; }
.field-label { display: grid; gap: 7px; color: #312f2a; font-size: 13px; font-weight: 850; }
.field-label legend { padding: 0; }
.field-label input, .field-label textarea, .field-label select { width: 100%; border: 1.5px solid #cfc5ea; border-radius: 12px; background: rgb(255 255 255 / 92%); box-shadow: 0 2px 0 rgb(23 23 19 / 5%); color: #171713; font: inherit; font-size: 14px; font-weight: 500; }
.field-label input, .field-label select { min-height: 45px; padding: 0 12px; }
.field-label textarea { padding: 11px 12px; resize: vertical; }
.field-label input:focus, .field-label textarea:focus, .field-label select:focus { border-color: #6e35ff; box-shadow: 0 0 0 4px rgb(110 53 255 / 16%); outline: none; }
.field-hint { color: #6f687d; font-size: 11px; font-weight: 650; }
.icon-library { display: grid; grid-template-columns: repeat(auto-fill, minmax(76px, 1fr)); gap: 8px; max-height: 214px; overflow-y: auto; padding: 3px; }
.icon-library--links { max-height: 272px; }
.icon-choice { display: grid; min-height: 68px; place-items: center; gap: 4px; border: 1.5px solid #d7cfed; border-radius: 12px; background: rgb(255 255 255 / 80%); color: #393242; padding: 8px 4px; transition: border-color .16s ease, background-color .16s ease, transform .16s ease; }
.icon-choice .brand-icon { width: 22px; height: 22px; }
.icon-choice:hover { transform: translateY(-2px); border-color: #6e35ff; background: #fff; }
.icon-choice--selected { border-color: #171713; background: #b9ff66; box-shadow: 2px 2px 0 #171713; color: #171713; }
.icon-choice-label { overflow: hidden; max-width: 66px; font-size: 10px; font-weight: 850; line-height: 1.1; text-align: center; text-overflow: ellipsis; white-space: nowrap; }
.custom-icon-upload { display: flex; align-items: center; gap: 12px; min-height: 54px; border: 1.5px solid #d7cfed; border-radius: 14px; background: rgb(255 255 255 / 80%); padding: 9px; }
.custom-icon-preview, .custom-icon-placeholder { display: grid; width: 38px; height: 38px; flex: 0 0 auto; place-items: center; border: 1.5px solid #171713; border-radius: 9px; background: #f6f4ee; color: #6e35ff; font-size: 21px; object-fit: cover; }
.primary-modal-button, .secondary-modal-button { border: 2px solid #171713; border-radius: 999px; padding: 10px 17px; font-size: 14px; font-weight: 900; transition: transform .18s ease, background-color .18s ease; }
.primary-modal-button { background: #6e35ff; color: #fff; box-shadow: 3px 3px 0 #171713; }
.primary-modal-button:hover:not(:disabled), .secondary-modal-button:hover { transform: translateY(-2px); }
.primary-modal-button:disabled { cursor: not-allowed; opacity: .55; }
.secondary-modal-button { background: #f1edff; color: #171713; }
.theme-swatch { min-height: 95px; border: 2px solid; border-radius: 15px; padding: 10px; transition: transform .18s ease; }
.theme-swatch:hover { transform: translateY(-3px); }
.color-field { padding: 3px !important; }
.appearance-preview-shell { align-self: start; overflow: hidden; border: 2px solid #171713; border-radius: 1.5rem; background: #171713; padding: 14px; }
.appearance-preview-shell > p { color: #b9ff66; }
@media (max-width: 639px) {
    .studio-toolbar { align-items: flex-start; }
    .toolbar-preview-button { font-size: 12px; padding: 8px 10px; }
    .canvas-workspace { margin-left: -1rem; margin-right: -1rem; border-left: 0; border-right: 0; border-radius: 0; box-shadow: none; }
    .canvas-topbar { padding: 0 1rem; }
    .canvas-topbar p { display: none; }
    .live-page { min-height: 580px; padding-left: 1.25rem; padding-right: 1.25rem; }
    .link-edit-icon, .inline-edit, .edit-badge { opacity: .74; }
}
</style>
