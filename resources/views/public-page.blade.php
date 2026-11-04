@php
    $profile = $publication->payload;
    $theme = $profile['theme'];
    $radius = match ($theme['radius']) {
        'square' => '0px',
        'soft' => '12px',
        default => '22px',
    };
    $buttonStyle = $theme['button_style'] ?? 'solid';
    $automaticButtonTextColor = (function (string $color): string {
        $hex = ltrim($color, '#');
        if (!preg_match('/^[a-f0-9]{6}$/i', $hex)) return '#171713';

        $channels = array_map(
            fn (int $offset) => hexdec(substr($hex, $offset, 2)) / 255,
            [0, 2, 4],
        );
        $linearChannels = array_map(
            fn (float $channel) => $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4,
            $channels,
        );
        $luminance = ($linearChannels[0] * 0.2126) + ($linearChannels[1] * 0.7152) + ($linearChannels[2] * 0.0722);

        return (1.05 / ($luminance + 0.05)) >= (($luminance + 0.05) / 0.059) ? '#ffffff' : '#171713';
    })($theme['primary']);
    $linkTextColor = $theme['button_text'] ?? (in_array($buttonStyle, ['solid', 'shadow'], true) ? $automaticButtonTextColor : $theme['primary']);
    $backgroundImageUrl = ! empty($theme['background_image']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($theme['background_image']) : null;
    $avatarUrl = $profile['avatar_path'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($profile['avatar_path']) : null;
    $description = $profile['bio'] ?: "Los enlaces de {$profile['display_name']}";
    $iconAssets = ['link' => 'link', 'google' => 'google', 'linkedin' => 'linkedin', 'maps' => 'googlemaps', 'instagram' => 'instagram', 'facebook' => 'facebook', 'whatsapp' => 'whatsapp', 'youtube' => 'youtube', 'tiktok' => 'tiktok', 'spotify' => 'spotify', 'gmail' => 'gmail', 'email' => 'gmail', 'calendar' => 'googlecalendar', 'telegram' => 'telegram', 'x' => 'x', 'threads' => 'threads', 'twitch' => 'twitch', 'discord' => 'discord', 'pinterest' => 'pinterest', 'snapchat' => 'snapchat', 'calendly' => 'calendly'];
    $socialLinks = is_array($profile['social_links'] ?? null) ? $profile['social_links'] : [];
    $socialLabels = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'whatsapp' => 'WhatsApp', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'spotify' => 'Spotify', 'linkedin' => 'LinkedIn', 'gmail' => 'Gmail', 'email' => 'Correo', 'telegram' => 'Telegram', 'x' => 'X', 'threads' => 'Threads', 'twitch' => 'Twitch', 'discord' => 'Discord', 'pinterest' => 'Pinterest', 'snapchat' => 'Snapchat'];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $profile['display_name'] }} · Enlink</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url('/'.$publication->username) }}">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $profile['display_name'] }} · Enlink">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url('/'.$publication->username) }}">
    @if ($avatarUrl)<meta property="og:image" content="{{ $avatarUrl }}">@endif
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background-color: {{ $theme['background'] }}; color: {{ $theme['text'] }}; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .page { width: min(100%, 650px); min-height: 100vh; margin: 0 auto; padding: 72px 32px 40px; text-align: center; background-color: {{ $theme['background'] }}; @if ($backgroundImageUrl) background-image: linear-gradient(rgb(4 7 19 / 28%), rgb(4 7 19 / 28%)), url('{{ $backgroundImageUrl }}'); background-position: center top; background-size: cover; @endif }
        @media (min-width: 700px) {
            body { background-image: linear-gradient(rgb(0 0 0 / 25%), rgb(0 0 0 / 25%)); }
            .page { min-height: calc(100vh - 44px); margin-top: 44px; border-radius: 24px 24px 0 0; }
        }
        @media (max-width: 699px) {
            .page { padding: 48px 16px 40px; }
        }
        .avatar, .avatar-placeholder { width: 96px; height: 96px; margin: 0 auto 18px; border-radius: 999px; object-fit: cover; box-shadow: 0 12px 30px rgb(15 23 42 / 18%); }
        .avatar-placeholder { display: grid; place-items: center; background: rgb(255 255 255 / 25%); color: inherit; font-size: 34px; font-weight: 800; }
        h1 { margin: 0; font-size: clamp(26px, 5vw, 34px); letter-spacing: -0.04em; }
        .bio { max-width: 540px; margin: 12px auto 34px; font-size: 16px; line-height: 1.5; opacity: .78; white-space: pre-line; }
        .social-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin: -12px 0 30px; }
        .social-link { display: grid; width: 42px; height: 42px; place-items: center; border: 1.5px solid currentColor; border-radius: 999px; color: inherit; font-size: 16px; font-weight: 900; line-height: 1; text-decoration: none; transition: transform .18s ease, background-color .18s ease; }
        .social-link:hover, .social-link:focus-visible { transform: translateY(-2px); background: rgb(255 255 255 / 18%); }
        .brand-mark { display: inline-block; width: 19px; height: 19px; background: currentColor; mask: var(--icon-image) center / contain no-repeat; -webkit-mask: var(--icon-image) center / contain no-repeat; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .links { display: grid; gap: 14px; }
        .link-card { position: relative; }
        .link-share { position: absolute; z-index: 1; right: 15px; bottom: 11px; width: 40px; height: 40px; display: grid; place-items: center; padding: 0; border: 0; border-radius: 50%; color: {{ $linkTextColor }}; background: transparent; cursor: pointer; }
        .link-share:hover, .link-share:focus-visible { background: rgb(127 127 127 / 25%); }
        .link-share svg { width: 20px; height: 20px; }
        .link-banner { display: flex; flex-direction: column; gap: 12px; padding: 8px 8px 20px; overflow: hidden; }
        .link-banner img { width: 100%; aspect-ratio: 16 / 9; object-fit: cover; border-radius: max(0px, calc({{ $radius }} - 8px)); }
        .link-banner-title { padding: 0 54px; }
        .share-status { position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%); background: #171713; color: white; border-radius: 10px; padding: 10px 16px; font-size: 14px; z-index: 2; }
        .link { position: relative; display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; min-height: 62px; padding: 17px 64px; border-radius: {{ $radius }}; color: {{ $linkTextColor }}; font: inherit; font-size: 16px; font-weight: 750; text-decoration: none; cursor: pointer; transition: transform .18s ease, filter .18s ease; }
        .link-icon { position: absolute; left: 22px; display: inline-grid; place-items: center; min-width: 26px; height: 26px; padding: 0 4px; border: 1px solid currentColor; border-radius: 7px; font-size: 12px; font-weight: 900; line-height: 1; opacity: .9; }
        .link-icon--brand { min-width: 22px; width: 22px; height: 22px; padding: 0; border: 0; border-radius: 0; background: currentColor; mask: var(--icon-image) center / contain no-repeat; -webkit-mask: var(--icon-image) center / contain no-repeat; }
        .link-icon--image { width: 28px; height: 28px; padding: 0; border: 0; border-radius: 7px; object-fit: cover; }
        .link-solid { border: 2px solid transparent; background: {{ $theme['primary'] }}; box-shadow: 0 8px 20px rgb(15 23 42 / 14%); }
        .link-outline { border: 2px solid {{ $theme['primary'] }}; background: transparent; box-shadow: none; }
        .link-shadow { border: 2px solid {{ $theme['text'] }}; background: {{ $theme['primary'] }}; box-shadow: 5px 5px 0 {{ $theme['text'] }}; }
        .link-transparent { border: 2px solid transparent; background: transparent; box-shadow: none; text-decoration: underline; text-underline-offset: 6px; }
        .link:hover, .link:focus-visible { transform: translateY(-2px); filter: brightness(1.08); }
        dialog { width: min(calc(100% - 32px), 440px); padding: 0; border: 2px solid #171713; border-radius: 24px; background: #f6f4ee; color: #171713; box-shadow: 8px 8px 0 #171713; }
        dialog::backdrop { background: rgb(15 15 12 / 65%); backdrop-filter: blur(3px); }
        .reservation { padding: 28px; text-align: left; }
        .reservation h2 { margin: 0; font-size: 25px; letter-spacing: -.035em; }
        .reservation p { margin: 8px 0 22px; color: #5d5b55; line-height: 1.45; }
        .reservation label { display: grid; gap: 7px; margin-top: 14px; font-size: 14px; font-weight: 750; }
        .reservation input, .reservation select { width: 100%; border: 2px solid #171713; border-radius: 12px; background: #fff; padding: 13px 14px; color: #171713; font: inherit; }
        .reservation-actions { display: grid; grid-template-columns: 1fr 1.5fr; gap: 10px; margin-top: 22px; }
        .reservation-actions button { border: 2px solid #171713; border-radius: 999px; padding: 13px 16px; font-weight: 800; cursor: pointer; }
        .cancel { background: transparent; color: #171713; }
        .send { background: #b9ff66; color: #171713; }
        .brand { margin: 42px 0 0; font-size: 13px; font-weight: 700; opacity: .55; }
        .brand a { color: inherit; text-decoration: none; }
    </style>
</head>
<body>
    <main class="page">
        @if ($avatarUrl)
            <img class="avatar" src="{{ $avatarUrl }}" alt="Foto de {{ $profile['display_name'] }}">
        @else
            <div class="avatar-placeholder" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($profile['display_name'], 0, 1)) }}</div>
        @endif
        <h1>{{ $profile['display_name'] }}</h1>
        @if ($profile['bio'])<p class="bio">{{ $profile['bio'] }}</p>@endif
        @if (count($socialLinks))
            <nav class="social-links" aria-label="Redes sociales de {{ $profile['display_name'] }}">
                @foreach ($socialLinks as $socialLink)
                    @php($network = $socialLink['network'] ?? '')
                    @if (isset($iconAssets[$network], $socialLabels[$network]) && ! empty($socialLink['url']))
                        <a class="social-link" href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $socialLabels[$network] }}"><span class="brand-mark" style="--icon-image: url('{{ asset('social-icons/'.$iconAssets[$network].'.svg') }}')" aria-hidden="true"></span><span class="sr-only">{{ $socialLabels[$network] }}</span></a>
                    @endif
                @endforeach
            </nav>
        @endif
        <section class="links" aria-label="Enlaces de {{ $profile['display_name'] }}">
            @foreach ($profile['links'] as $link)
                @php
                    $token = \App\Support\PublishedLinkToken::encode($publication->id, $link['id']);
                    $trackedUrl = route('public.link', ['token' => $token]);
                    $reservationUrl = route('public.reservation', ['token' => $token]);
                    $linkIcon = $link['icon'] ?? 'link';
                    $iconAsset = $iconAssets[$linkIcon] ?? 'link';
                    $isBanner = ($link['settings']['layout'] ?? 'button') === 'banner' && ! empty($link['icon_image_path']);
                    $shareUrl = ($link['type'] ?? 'link') === 'reservation' ? url('/'.$publication->username).'#link-'.$link['id'] : $link['url'];
                    $customIconUrl = ! empty($link['icon_image_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($link['icon_image_path']) : null;
                @endphp
                <div class="link-card" id="link-{{ $link['id'] }}">
                    @if (($link['type'] ?? 'link') === 'reservation')
                        <button class="link link-{{ $buttonStyle }} reservation-trigger {{ $isBanner ? 'link-banner' : '' }}" type="button" data-action="{{ $reservationUrl }}" data-title="{{ $link['title'] }}">
                    @else
                        <a class="link link-{{ $buttonStyle }} {{ $isBanner ? 'link-banner' : '' }}" href="{{ $trackedUrl }}" target="_blank" rel="noopener noreferrer">
                    @endif
                        @if ($isBanner)<img src="{{ $customIconUrl }}" alt="">@elseif ($customIconUrl)<img class="link-icon link-icon--image" src="{{ $customIconUrl }}" alt="">@else<span class="link-icon link-icon--brand" style="--icon-image: url('{{ asset('social-icons/'.$iconAsset.'.svg') }}')" aria-hidden="true"></span>@endif
                        <span class="{{ $isBanner ? 'link-banner-title' : '' }}">{{ $link['title'] }}</span>
                    @if (($link['type'] ?? 'link') === 'reservation')</button>@else</a>@endif
                    <button class="link-share" type="button" data-url="{{ $shareUrl }}" data-title="{{ $link['title'] }}" aria-label="Compartir {{ $link['title'] }}" title="Compartir {{ $link['title'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V3m-4 4 4-4 4 4M5 13v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6"/></svg></button>
                </div>
            @endforeach
        </section>
        <p class="brand"><a href="{{ url('/') }}">Hecho con Enlink</a></p>
    </main>
    <div class="share-status" role="status" hidden></div>
    <dialog id="reservation-dialog">
        <form class="reservation" method="post" target="_blank" id="reservation-form">
            @csrf
            <h2 id="reservation-title">Tomar reserva</h2>
            <p>Completá estos datos y enviaremos el pedido por WhatsApp.</p>
            <label>¿A nombre de quién?<input name="name" required maxlength="80" autocomplete="name" placeholder="Tu nombre"></label>
            <label>Cantidad de personas<select name="guests" required>@foreach (range(1, 20) as $guests)<option value="{{ $guests }}">{{ $guests }} {{ $guests === 1 ? 'persona' : 'personas' }}</option>@endforeach</select></label>
            <div class="reservation-actions"><button class="cancel" type="button" id="reservation-cancel">Cancelar</button><button class="send" type="submit">Enviar a WhatsApp →</button></div>
        </form>
    </dialog>
    <script>
        const shareStatus = document.querySelector('.share-status');
        document.querySelectorAll('.link-share').forEach((button) => button.addEventListener('click', async () => {
            const data = { title: button.dataset.title, url: button.dataset.url };
            try {
                if (navigator.share) { await navigator.share(data); return; }
                if (!navigator.clipboard) throw new Error('Clipboard unavailable');
                await navigator.clipboard.writeText(data.url);
                shareStatus.textContent = 'Enlace copiado';
                shareStatus.hidden = false;
                setTimeout(() => { shareStatus.hidden = true; }, 2500);
            } catch (error) {
                if (error.name !== 'AbortError') window.prompt('Copiá este enlace:', data.url);
            }
        }));
        const dialog = document.getElementById('reservation-dialog');
        const form = document.getElementById('reservation-form');
        const title = document.getElementById('reservation-title');
        document.querySelectorAll('.reservation-trigger').forEach((button) => button.addEventListener('click', () => {
            form.action = button.dataset.action;
            title.textContent = button.dataset.title;
            dialog.showModal();
        }));
        if (/^#link-\d+$/.test(location.hash)) {
            document.getElementById(location.hash.slice(1))?.querySelector('.reservation-trigger')?.click();
        }
        document.getElementById('reservation-cancel').addEventListener('click', () => dialog.close());
        form.addEventListener('submit', () => setTimeout(() => dialog.close(), 100));
    </script>
</body>
</html>
