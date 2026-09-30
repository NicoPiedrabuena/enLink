<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const menuOpen = ref(false);
const navItems = [
    { label: 'Editor', routeName: 'dashboard', active: 'dashboard' },
    { label: 'Créditos', routeName: 'credits.index', active: 'credits.*' },
    { label: 'Analytics', routeName: 'analytics.index', active: 'analytics.*' },
];
</script>

<template>
    <div class="min-h-screen bg-[#f6f4ee] text-[#171713]">
        <nav class="relative z-40 border-b-2 border-[#171713] bg-[#f6f4ee]">
            <div class="mx-auto flex h-[74px] max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <Link :href="route('dashboard')" class="flex items-center gap-2.5" aria-label="Enlink Studio">
                        <span class="grid size-10 place-items-center rounded-[14px] bg-[#171713] p-1.5 text-[#b9ff66]"><ApplicationLogo class="size-full" /></span>
                        <span class="hidden text-xl font-black tracking-[-0.04em] xs:block sm:block">enlink</span>
                    </Link>
                    <div class="hidden items-center gap-1 md:flex">
                        <Link v-for="item in navItems" :key="item.routeName" :href="route(item.routeName)" class="rounded-full px-4 py-2 text-sm font-extrabold transition" :class="route().current(item.active) ? 'bg-[#171713] text-white' : 'hover:bg-[#b9ff66]'">{{ item.label }}</Link>
                        <Link v-if="$page.props.auth.user.role === 'admin'" :href="route('admin.index')" class="rounded-full px-4 py-2 text-sm font-extrabold transition" :class="route().current('admin.*') ? 'bg-[#171713] text-white' : 'hover:bg-[#b9ff66]'">Admin</Link>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a v-if="$page.props.auth.publicUrl" :href="$page.props.auth.publicUrl" target="_blank" rel="noopener noreferrer" class="hidden rounded-full border-2 border-[#171713] px-4 py-2 text-sm font-extrabold transition hover:-translate-y-0.5 hover:bg-white sm:block">Ver tu Enlink ↗</a>
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center gap-2 rounded-full bg-[#6e35ff] py-2 pl-2 pr-4 text-sm font-extrabold text-white transition hover:bg-[#5d27e8]">
                                <span class="grid size-8 place-items-center rounded-full bg-[#b9ff66] text-[#171713]">{{ $page.props.auth.user.name.slice(0, 1).toUpperCase() }}</span>
                                <span class="hidden sm:inline">{{ $page.props.auth.user.name }}</span><span>⌄</span>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Mi perfil</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Cerrar sesión</DropdownLink>
                        </template>
                    </Dropdown>
                    <button class="grid size-11 place-items-center rounded-full border-2 border-[#171713] md:hidden" :aria-expanded="menuOpen" aria-label="Abrir menú" @click="menuOpen = !menuOpen">
                        <span class="text-xl font-black">{{ menuOpen ? '×' : '≡' }}</span>
                    </button>
                </div>
            </div>
            <div v-if="menuOpen" class="border-t-2 border-[#171713] bg-[#b9ff66] p-4 md:hidden">
                <div class="mx-auto grid max-w-7xl gap-2">
                    <Link v-for="item in navItems" :key="item.routeName" :href="route(item.routeName)" class="rounded-xl px-4 py-3 font-black" :class="route().current(item.active) ? 'bg-[#171713] text-white' : 'bg-white/60'">{{ item.label }}</Link>
                    <Link v-if="$page.props.auth.user.role === 'admin'" :href="route('admin.index')" class="rounded-xl px-4 py-3 font-black" :class="route().current('admin.*') ? 'bg-[#171713] text-white' : 'bg-white/60'">Administración</Link>
                    <a v-if="$page.props.auth.publicUrl" :href="$page.props.auth.publicUrl" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-white/60 px-4 py-3 font-black">Ver tu Enlink ↗</a>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="relative overflow-hidden border-b-2 border-[#171713] bg-[#b6a7ff]">
            <div class="pointer-events-none absolute -right-12 -top-20 size-52 rounded-full bg-[#b9ff66]/80 blur-2xl"></div>
            <div class="relative mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"><slot name="header" /></div>
        </header>

        <main><slot /></main>
    </div>
</template>
