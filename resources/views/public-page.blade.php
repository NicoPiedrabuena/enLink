@php
    $profile = $publication->payload;
    $theme = $profile['theme'];
    $radius = match ($theme['radius']) {
        'square' => '0px',
        'soft' => '12px',
        default => '22px',
    };
    $buttonStyle = $theme['button_style'] ?? 'solid';
    $avatarUrl = $profile['avatar_path'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($profile['avatar_path']) : null;
    $description = $profile['bio'] ?: "Los enlaces de {$profile['display_name']}";
    $iconGlyphs = ['link' => '↗', 'google' => 'G', 'linkedin' => 'in', 'maps' => '⌖', 'instagram' => '◎', 'facebook' => 'f', 'whatsapp' => '◉', 'youtube' => '▶', 'tiktok' => '♪', 'spotify' => '≋', 'email' => '@', 'calendar' => '31'];
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
        body { margin: 0; min-height: 100vh; background: {{ $theme['background'] }}; color: {{ $theme['text'] }}; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .page { width: min(100% - 32px, 680px); margin: 0 auto; padding: 72px 0 40px; text-align: center; }
        .avatar, .avatar-placeholder { width: 96px; height: 96px; margin: 0 auto 18px; border-radius: 999px; object-fit: cover; box-shadow: 0 12px 30px rgb(15 23 42 / 18%); }
        .avatar-placeholder { display: grid; place-items: center; background: rgb(255 255 255 / 25%); color: inherit; font-size: 34px; font-weight: 800; }
        h1 { margin: 0; font-size: clamp(26px, 5vw, 34px); letter-spacing: -0.04em; }
        .bio { max-width: 540px; margin: 12px auto 34px; font-size: 16px; line-height: 1.5; opacity: .78; white-space: pre-line; }
        .links { display: grid; gap: 14px; }
        .link { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 17px 24px; border-radius: {{ $radius }}; color: {{ $buttonStyle === 'solid' ? '#fff' : $theme['primary'] }}; font: inherit; font-size: 16px; font-weight: 750; text-decoration: none; cursor: pointer; transition: transform .18s ease, filter .18s ease; }
        .link-icon { display: inline-grid; place-items: center; min-width: 26px; height: 26px; padding: 0 4px; border: 1px solid currentColor; border-radius: 7px; font-size: 12px; font-weight: 900; line-height: 1; opacity: .9; }
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
        <section class="links" aria-label="Enlaces de {{ $profile['display_name'] }}">
            @foreach ($profile['links'] as $link)
                @php
                    $token = \App\Support\PublishedLinkToken::encode($publication->id, $link['id']);
                    $trackedUrl = route('public.link', ['token' => $token]);
                    $reservationUrl = route('public.reservation', ['token' => $token]);
                @endphp
                @if (($link['type'] ?? 'link') === 'reservation')
                    <button class="link link-{{ $buttonStyle }} reservation-trigger" type="button" data-action="{{ $reservationUrl }}" data-title="{{ $link['title'] }}"><span class="link-icon" aria-hidden="true">{{ $iconGlyphs[$link['icon'] ?? 'calendar'] ?? '↗' }}</span><span>{{ $link['title'] }}</span></button>
                @else
                    <a class="link link-{{ $buttonStyle }}" href="{{ $trackedUrl }}" target="_blank" rel="noopener noreferrer"><span class="link-icon" aria-hidden="true">{{ $iconGlyphs[$link['icon'] ?? 'link'] ?? '↗' }}</span><span>{{ $link['title'] }}</span></a>
                @endif
            @endforeach
        </section>
        <p class="brand"><a href="{{ url('/') }}">Hecho con Enlink</a></p>
    </main>
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
        const dialog = document.getElementById('reservation-dialog');
        const form = document.getElementById('reservation-form');
        const title = document.getElementById('reservation-title');
        document.querySelectorAll('.reservation-trigger').forEach((button) => button.addEventListener('click', () => {
            form.action = button.dataset.action;
            title.textContent = button.dataset.title;
            dialog.showModal();
        }));
        document.getElementById('reservation-cancel').addEventListener('click', () => dialog.close());
        form.addEventListener('submit', () => setTimeout(() => dialog.close(), 100));
    </script>
</body>
</html>
