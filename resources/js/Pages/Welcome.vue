<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ canLogin?: boolean; canRegister?: boolean }>();

const steps = [
    ['01', 'Armá tu perfil', 'Elegí tu nombre, contá quién sos y reuní todos tus enlaces en minutos.'],
    ['02', 'Dejala perfecta', 'Editá, guardá y previsualizá todo lo que quieras antes de pagar.'],
    ['03', 'Medí y mejorá', 'Entendé cuántas visitas y clics recibe cada enlace desde tu panel.'],
];

const plans = [
    { name: 'Inicial', price: '$5.000', credits: 3, color: 'bg-[#b9ff66]', featured: false },
    { name: 'Medio', price: '$10.000', credits: 10, color: 'bg-[#b6a7ff]', featured: true },
    { name: 'Pro', price: '$30.000', credits: 50, color: 'bg-[#ff9eca]', featured: false },
];
</script>

<template>
    <Head title="Tu mundo en un solo enlace">
        <meta head-key="description" name="description" content="Creá y editá gratis una página atractiva, sin publicidad y con todos tus enlaces. Elegí el pack de créditos que mejor se adapte a vos." />
    </Head>

    <div class="min-h-screen overflow-hidden bg-[#f6f4ee] text-[#171713]">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[760px] overflow-hidden">
            <div class="glow absolute -right-36 -top-40 size-[520px] rounded-full bg-[#b9ff66]/60 blur-3xl"></div>
            <div class="glow-delayed absolute -left-36 top-72 size-[420px] rounded-full bg-[#b6a7ff]/45 blur-3xl"></div>
        </div>

        <header class="relative z-20 mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-12">
            <Link href="/" class="flex items-center gap-2.5" aria-label="Enlink, inicio">
                <span class="grid size-10 place-items-center rounded-[14px] bg-[#171713] p-1.5 text-[#b9ff66]"><ApplicationLogo class="size-full" /></span>
                <span class="text-xl font-black tracking-[-0.04em]">enlink</span>
            </Link>
            <nav v-if="canLogin" class="flex items-center gap-2 sm:gap-3" aria-label="Navegación principal">
                <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="rounded-full bg-[#171713] px-5 py-2.5 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#34342d]">Ir a mi panel</Link>
                <template v-else>
                    <Link :href="route('login')" class="hidden px-4 py-2 text-sm font-bold transition hover:opacity-60 sm:block">Ingresar</Link>
                    <Link v-if="canRegister" :href="route('register')" class="rounded-full bg-[#171713] px-5 py-2.5 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#34342d]">Crear mi Enlink</Link>
                </template>
            </nav>
        </header>

        <main>
            <section class="relative mx-auto grid max-w-7xl items-center gap-10 px-5 pb-20 pt-7 sm:px-8 sm:pt-10 lg:grid-cols-[1.05fr_.95fr] lg:gap-14 lg:px-12 lg:pb-32 lg:pt-20">
                <div class="relative z-10 hidden text-center lg:block lg:text-left">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-black/10 bg-white/70 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] backdrop-blur">
                        <span class="size-2 rounded-full bg-[#6e35ff]"></span> Hecho para crecer
                    </div>
                    <h1 class="mx-auto max-w-3xl text-[clamp(3.2rem,9vw,7.2rem)] font-black leading-[0.86] tracking-[-0.075em] lg:mx-0">
                        Todo lo tuyo.
                        <span class="relative mt-2 block text-[#6e35ff]">Un solo link.
                            <svg class="absolute -bottom-4 left-1/2 w-[82%] -translate-x-1/2 lg:left-0 lg:translate-x-0" viewBox="0 0 500 22" fill="none" aria-hidden="true"><path d="M4 14C116 2 303 2 496 12" stroke="#b9ff66" stroke-width="10" stroke-linecap="round" /></svg>
                        </span>
                    </h1>
                    <p class="mx-auto mt-10 max-w-xl text-lg leading-relaxed text-black/65 sm:text-xl lg:mx-0">Convertí visitas en oportunidades con una página simple, hermosa y lista para compartir en cualquier lugar.</p>
                    <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center lg:justify-start">
                        <Link :href="$page.props.auth.user ? route('dashboard') : route('register')" class="group flex w-full items-center justify-center gap-3 rounded-full bg-[#6e35ff] px-7 py-4 text-base font-extrabold text-white shadow-[0_12px_30px_rgba(110,53,255,.28)] transition hover:-translate-y-1 hover:bg-[#5d27e8] sm:w-auto">
                            {{ $page.props.auth.user ? 'Editar mi página' : 'Empezar gratis' }} <span class="transition group-hover:translate-x-1">→</span>
                        </Link>
                        <a href="#como-funciona" class="w-full rounded-full border border-black/15 bg-white/60 px-7 py-4 text-center text-base font-bold backdrop-blur transition hover:bg-white sm:w-auto">Ver cómo funciona</a>
                    </div>
                    <p class="mt-5 text-sm font-semibold text-black/50">Editá gratis · Sin publicidad · Tu marca primero</p>
                </div>

                <div class="relative mx-auto w-full max-w-[560px] lg:ml-auto">
                    <div class="absolute -left-4 top-12 z-10 -rotate-6 rounded-2xl bg-[#b9ff66] px-4 py-3 text-sm font-black shadow-xl sm:-left-12">TU MARCA,<br />TU ESTILO ✦</div>
                    <div class="absolute -right-2 bottom-20 z-10 rotate-6 rounded-2xl bg-[#ff9eca] px-4 py-3 text-sm font-black shadow-xl sm:-right-8">TODO EN<br />UN LUGAR ↗</div>
                    <div class="phone-float relative mx-auto w-[min(82vw,340px)] rounded-[3rem] border-[10px] border-[#171713] bg-[#d6ccff] p-4 shadow-[0_35px_80px_rgba(32,20,76,.28)]">
                        <div class="mx-auto mb-5 h-5 w-24 rounded-full bg-[#171713]"></div>
                        <div class="rounded-[2.15rem] bg-[#f7f5ef] px-5 pb-7 pt-9 text-center">
                            <div class="mx-auto grid size-20 place-items-center rounded-full bg-[#171713] text-2xl font-black text-[#b9ff66] shadow-lg">RC</div>
                            <h2 class="mt-4 text-2xl font-black tracking-tight">RAÍZ CERVECERÍA</h2>
                            <p class="mt-1 text-sm text-black/55">Cerveza, amigos y buenos momentos.</p>
                            <div class="mt-6 space-y-3">
                                <div v-for="(link, i) in ['Nuestra carta', 'Instagram', 'Reservá tu mesa']" :key="link" class="link-card flex items-center justify-between rounded-2xl px-5 py-4 font-extrabold shadow-[4px_4px_0_#171713]" :class="[i === 0 ? 'bg-[#b9ff66]' : i === 1 ? 'bg-[#ff9eca]' : 'bg-[#ffd568]']">{{ link }} <span>↗</span></div>
                            </div>
                            <div class="mt-7 text-xs font-black tracking-wide text-black/40">ENLINK</div>
                        </div>
                    </div>

                    <div class="relative z-20 mx-auto mt-10 max-w-sm lg:hidden">
                        <div class="flex flex-col gap-3">
                            <Link :href="$page.props.auth.user ? route('dashboard') : route('register')" class="group flex w-full items-center justify-center gap-3 rounded-full bg-[#6e35ff] px-7 py-4 text-base font-extrabold text-white shadow-[0_12px_30px_rgba(110,53,255,.28)] transition active:scale-[.98]">
                                {{ $page.props.auth.user ? 'Editar mi página' : 'Empezar gratis' }} <span>→</span>
                            </Link>
                            <a href="#como-funciona" class="w-full rounded-full border-2 border-[#171713] bg-white/75 px-7 py-4 text-center text-base font-black shadow-[3px_3px_0_#171713] backdrop-blur transition active:translate-y-0.5">Ver cómo funciona</a>
                        </div>
                        <p class="mt-5 text-center text-sm font-semibold text-black/50">Editá gratis · Sin publicidad · Tu marca primero</p>
                    </div>
                </div>
            </section>

            <section class="border-y border-black/10 bg-[#171713] py-5 text-white" aria-label="Ideal para">
                <div class="marquee flex min-w-max items-center gap-10 text-sm font-black uppercase tracking-[0.18em]"><span>Emprendedores</span><span class="text-[#b9ff66]">✦</span><span>Creadores</span><span class="text-[#ff9eca]">✦</span><span>Artistas</span><span class="text-[#ffd568]">✦</span><span>Negocios</span><span class="text-[#b6a7ff]">✦</span><span>Profesionales</span><span class="text-[#b9ff66]">✦</span><span>Emprendedores</span><span class="text-[#ff9eca]">✦</span><span>Creadores</span></div>
            </section>

            <section id="como-funciona" class="mx-auto max-w-7xl px-5 py-24 sm:px-8 lg:px-12 lg:py-32">
                <p class="text-sm font-black uppercase tracking-[0.2em] text-[#6e35ff]">Simple de verdad</p>
                <h2 class="mt-4 text-4xl font-black leading-none tracking-[-0.05em] sm:text-6xl">De cero a publicado<br />en tres pasos.</h2>
                <div class="mt-14 grid gap-5 md:grid-cols-3">
                    <article v-for="(step, index) in steps" :key="step[0]" class="group rounded-[2rem] border border-black/10 p-7 transition duration-300 hover:-translate-y-2 hover:shadow-xl" :class="[index === 0 ? 'bg-[#b9ff66]' : index === 1 ? 'bg-[#b6a7ff]' : 'bg-[#ff9eca]']">
                        <div class="flex items-center justify-between"><span class="text-sm font-black">{{ step[0] }}</span><span class="grid size-11 place-items-center rounded-full bg-[#171713] text-white transition group-hover:rotate-12">↗</span></div>
                        <h3 class="mt-16 text-2xl font-black tracking-tight">{{ step[1] }}</h3>
                        <p class="mt-3 leading-relaxed text-black/65">{{ step[2] }}</p>
                    </article>
                </div>
            </section>

            <section class="mx-4 rounded-[2.5rem] bg-[#6e35ff] px-5 py-20 text-center text-white sm:mx-8 sm:px-10 lg:mx-auto lg:max-w-[calc(100%-6rem)] lg:py-28">
                <div class="mx-auto max-w-4xl">
                    <span class="inline-block rounded-full bg-white/15 px-4 py-2 text-xs font-black uppercase tracking-[0.18em]">100% tuya</span>
                    <h2 class="mt-7 text-4xl font-black leading-[0.95] tracking-[-0.055em] sm:text-6xl lg:text-7xl">Tu página.<br />Sin publicidad.</h2>
                    <p class="mx-auto mt-6 max-w-2xl text-lg text-white/75">Sin banners ajenos ni distracciones. Cada visita se concentra en tu contenido, tus enlaces y tu marca.</p>
                    <Link :href="$page.props.auth.user ? route('dashboard') : route('register')" class="mt-9 inline-flex items-center gap-3 rounded-full bg-[#b9ff66] px-8 py-4 text-base font-black text-[#171713] transition hover:-translate-y-1 hover:bg-white">{{ $page.props.auth.user ? 'Volver a mi panel' : 'Crear mi página ahora' }} <span>→</span></Link>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-5 py-24 sm:px-8 lg:px-12 lg:py-32">
                <div class="max-w-3xl">
                        <p class="text-sm font-black uppercase tracking-[0.2em] text-[#6e35ff]">Créditos Enlink</p>
                        <h2 class="mt-4 text-4xl font-black leading-none tracking-[-0.05em] sm:text-6xl">Pagás cuando<br />querés publicar.</h2>
                        <p class="mt-6 max-w-xl text-lg leading-relaxed text-black/60">Diseñar, editar, guardar y previsualizar no consume créditos. Cada vez que publicás una versión, usás uno.</p>
                </div>
                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    <article v-for="plan in plans" :key="plan.name" class="relative rounded-[2rem] border-2 border-[#171713] p-7 shadow-[8px_8px_0_#171713] sm:p-8" :class="plan.color">
                        <span v-if="plan.featured" class="absolute -top-4 right-6 rotate-2 rounded-full bg-[#171713] px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white">Más elegido</span>
                        <div class="flex items-center justify-between gap-4">
                            <span class="rounded-full bg-[#171713] px-4 py-2 text-xs font-black uppercase tracking-[0.15em] text-white">Pack {{ plan.name }}</span>
                            <span class="text-sm font-black">ARS</span>
                        </div>
                        <p class="mt-10 text-5xl font-black tracking-[-0.06em]">{{ plan.price }}</p>
                        <p class="mt-2 text-2xl font-black">{{ plan.credits }} créditos</p>
                        <div class="my-7 h-0.5 bg-[#171713]/20"></div>
                        <ul class="space-y-3 text-sm font-bold">
                            <li>✓ Sin vencimiento</li>
                            <li>✓ Sin suscripción mensual</li>
                            <li>✓ 1 publicación = 1 crédito</li>
                        </ul>
                        <Link :href="$page.props.auth.user ? route('credits.index') : route('register')" class="mt-8 flex w-full items-center justify-center rounded-full bg-[#171713] px-6 py-4 font-black text-white transition hover:-translate-y-1 hover:bg-[#6e35ff]">{{ $page.props.auth.user ? 'Comprar créditos' : 'Empezar a crear' }} →</Link>
                    </article>
                </div>
            </section>
        </main>

        <footer class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-5 px-5 py-10 text-center sm:flex-row sm:px-8 sm:text-left lg:px-12">
            <div class="flex items-center gap-2 font-black"><span class="grid size-8 place-items-center rounded-xl bg-[#171713] p-1 text-[#b9ff66]"><ApplicationLogo class="size-full" /></span> enlink</div>
            <p class="text-sm text-black/50">Un enlace. Todas tus posibilidades.</p>
            <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')" class="text-sm font-bold underline decoration-[#6e35ff] decoration-2 underline-offset-4">Ya tengo cuenta</Link>
        </footer>
    </div>
</template>

<style scoped>
@keyframes float { 0%, 100% { transform: translateY(0) rotate(1deg); } 50% { transform: translateY(-12px) rotate(-1deg); } }
@keyframes drift { 0%, 100% { transform: translate3d(0, 0, 0); } 50% { transform: translate3d(-24px, 18px, 0); } }
@keyframes marquee { to { transform: translateX(-35%); } }
.phone-float { animation: float 6s ease-in-out infinite; }
.glow { animation: drift 9s ease-in-out infinite; }
.glow-delayed { animation: drift 11s ease-in-out infinite reverse; }
.marquee { animation: marquee 20s linear infinite; }
.link-card { transition: transform 180ms ease, box-shadow 180ms ease; }
.link-card:hover { transform: translate(-2px, -2px); box-shadow: 7px 7px 0 #171713; }
@media (prefers-reduced-motion: reduce) { .phone-float, .glow, .glow-delayed, .marquee { animation: none; } }
</style>
