<?php

namespace App\Http\Controllers;

use App\Actions\Credits\InsufficientCreditsException;
use App\Actions\Payments\CheckoutUnavailableException;
use App\Actions\Payments\CreateMercadoPagoCheckoutAction;
use App\Actions\Publishing\PublishPageAction;
use App\Models\CreditPackage;
use App\Models\Link;
use App\Models\LinkPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DashboardController extends Controller
{
    private const RESERVED = ['admin', 'api', 'login', 'register', 'dashboard', 'settings', 'pricing', 'support', 'terms', 'privacy', 'help', 'assets', 'profile', 'logout'];

    private const LINK_ICONS = ['link', 'google', 'linkedin', 'maps', 'instagram', 'facebook', 'whatsapp', 'youtube', 'tiktok', 'spotify', 'gmail', 'email', 'calendar', 'telegram', 'x', 'threads', 'twitch', 'discord', 'pinterest', 'snapchat', 'calendly'];

    private const SOCIAL_NETWORKS = ['instagram', 'facebook', 'whatsapp', 'youtube', 'tiktok', 'spotify', 'linkedin', 'gmail', 'telegram', 'x', 'threads', 'twitch', 'discord', 'pinterest', 'snapchat'];

    public function show(Request $request): Response
    {
        $page = $request->user()->linkPage()->firstOrCreate([], [
            'username' => $this->suggestedUsername(),
            'display_name' => $request->user()->name,
            'theme' => ['background' => '#f8fafc', 'primary' => '#111827', 'text' => '#111827', 'button_text' => '#ffffff', 'radius' => 'rounded', 'button_style' => 'solid'],
        ]);

        $balance = $request->user()->creditBalance()->firstOrCreate([], ['balance' => 0]);

        return Inertia::render('Dashboard', [
            'page' => $page->load(['links', 'activePublication:id,link_page_id,version,username,published_at']),
            'credits' => ['balance' => $balance->balance],
            'publicUrl' => $page->activePublication ? route('public.page', ['username' => $page->activePublication->username]) : null,
        ]);
    }

    private function suggestedUsername(): string
    {
        do {
            $suffix = collect(range('a', 'z'))->random(6)->implode('');
            $username = "mi-enlink-{$suffix}";
        } while (LinkPage::where('username', $username)->exists());

        return $username;
    }

    public function updatePage(Request $request): RedirectResponse
    {
        $page = $request->user()->linkPage;
        $request->merge([
            'username' => strtolower(trim((string) $request->input('username'))),
        ]);

        $data = $request->validate([
            'username' => ['required', 'regex:/^[a-z]+(?:-[a-z]+)*$/', 'min:3', 'max:30', Rule::unique('link_pages', 'username')->ignore($page), Rule::notIn(self::RESERVED)],
            'display_name' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:180'],
            'theme' => ['required', 'array'],
            'theme.background' => ['required', 'string', 'max:20'],
            'theme.primary' => ['required', 'string', 'max:20'],
            'theme.text' => ['required', 'string', 'max:20'],
            'theme.button_text' => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme.radius' => ['required', Rule::in(['rounded', 'soft', 'square'])],
            'theme.button_style' => ['sometimes', Rule::in(['solid', 'outline', 'shadow', 'transparent'])],
            'social_links' => ['sometimes', 'array', 'max:8'],
            'social_links.*.network' => ['required_with:social_links', 'distinct', Rule::in(self::SOCIAL_NETWORKS)],
            'social_links.*.url' => ['required_with:social_links', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
        ]);
        $data['theme']['button_style'] ??= $page->theme['button_style'] ?? 'solid';
        $data['theme']['button_text'] ??= $page->theme['button_text'] ?? '#ffffff';
        $data['theme']['background_image'] = $page->theme['background_image'] ?? null;
        $data['username'] = strtolower($data['username']);
        $data['social_links'] = array_values($data['social_links'] ?? $page->social_links ?? []);
        $page->update($data);

        return back()->with('toast', ['type' => 'success', 'message' => 'Datos y apariencia guardados.']);
    }

    public function storeLink(Request $request): RedirectResponse
    {
        $page = $request->user()->linkPage;
        $data = $this->validatedLink($request);
        $link = $page->links()->create($data + ['position' => ($page->links()->max('position') ?? -1) + 1]);
        $this->replaceLinkIconImage($request, $link);

        return back()->with('toast', ['type' => 'success', 'message' => 'Enlace agregado.']);
    }

    public function updateLink(Request $request, Link $link): RedirectResponse
    {
        abort_unless($link->linkPage->user_id === $request->user()->id, 403);
        $data = $this->validatedLink($request, true);
        $link->update($data);
        $this->replaceLinkIconImage($request, $link);

        return back()->with('toast', ['type' => 'success', 'message' => 'Enlace actualizado.']);
    }

    /** @return array<string, mixed> */
    private function validatedLink(Request $request, bool $updating = false): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'icon' => ['required', Rule::in(self::LINK_ICONS)],
            'type' => ['required', Rule::in(['link', 'reservation'])],
            'url' => [Rule::requiredIf($request->input('type') === 'link'), 'nullable', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'whatsapp_number' => [Rule::requiredIf($request->input('type') === 'reservation'), 'nullable', 'string', 'regex:/^[1-9][0-9]{7,14}$/'],
            'is_active' => [$updating ? 'required' : 'sometimes', 'boolean'],
            'icon_image' => ['nullable', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:1024', 'dimensions:min_width=32,min_height=32,max_width=2000,max_height=2000'],
            'remove_icon_image' => ['sometimes', 'boolean'],
            'layout' => ['sometimes', Rule::in(['button', 'banner'])],
        ], [
            'icon_image.uploaded' => 'No se pudo subir la imagen. Verificá que pese hasta 1 MB e intentá de nuevo.',
            'icon_image.file' => 'Seleccioná un archivo de imagen válido.',
            'icon_image.image' => 'Seleccioná una imagen válida en formato JPG, PNG o WEBP.',
            'icon_image.mimetypes' => 'La imagen debe estar en formato JPG, PNG o WEBP.',
            'icon_image.max' => 'La imagen debe pesar como máximo 1 MB.',
            'icon_image.dimensions' => 'La imagen debe medir entre 32 × 32 y 2000 × 2000 píxeles.',
        ]);

        $layout = $data['layout'] ?? 'button';
        if ($data['type'] === 'reservation') {
            $number = $data['whatsapp_number'];
            $data['url'] = "https://wa.me/{$number}";
            $data['settings'] = ['whatsapp_number' => $number, 'layout' => $layout];
        } else {
            $data['settings'] = ['layout' => $layout];
        }

        unset($data['whatsapp_number'], $data['icon_image'], $data['remove_icon_image'], $data['layout']);

        return $data;
    }

    private function replaceLinkIconImage(Request $request, Link $link): void
    {
        if ($request->hasFile('icon_image')) {
            $path = $request->file('icon_image')->store("link-icons/{$link->link_page_id}", 'public');

            if ($link->icon_image_path) {
                $this->deleteDraftLinkImage($link);
            }

            $link->update(['icon_image_path' => $path]);

            return;
        }

        if ($request->boolean('remove_icon_image') && $link->icon_image_path) {
            $this->deleteDraftLinkImage($link);
            $link->update(['icon_image_path' => null]);
        }
    }

    public function destroyLink(Request $request, Link $link): RedirectResponse
    {
        abort_unless($link->linkPage->user_id === $request->user()->id, 403);

        if ($link->icon_image_path) {
            $this->deleteDraftLinkImage($link);
        }

        $link->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Enlace eliminado.']);
    }

    private function deleteDraftLinkImage(Link $link): void
    {
        $publishedLinks = $link->linkPage->activePublication?->payload['links'] ?? [];
        $inUse = collect($publishedLinks)->contains(fn ($published) => ($published['icon_image_path'] ?? null) === $link->icon_image_path);
        if (! $inUse) {
            Storage::disk('public')->delete($link->icon_image_path);
        }
    }

    public function reorderLinks(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'links' => ['required', 'array', 'min:1'],
            'links.*' => ['required', 'integer', 'distinct'],
        ]);

        $page = $request->user()->linkPage;
        $linkIds = $data['links'];
        $ownedIds = $page->links()->pluck('id')->all();

        abort_unless(count($linkIds) === count($ownedIds) && empty(array_diff($linkIds, $ownedIds)), 403);

        DB::transaction(function () use ($linkIds): void {
            Link::query()->whereKey($linkIds)->lockForUpdate()->get();

            foreach ($linkIds as $position => $id) {
                Link::query()->whereKey($id)->update(['position' => $position]);
            }
        });

        return back()->with('toast', ['type' => 'success', 'message' => 'Orden de enlaces actualizado.']);
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'avatar' => [
                'required',
                'file',
                'image',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
                'dimensions:min_width=64,min_height=64,max_width=4000,max_height=4000',
            ],
        ]);

        $page = $request->user()->linkPage;
        $path = $data['avatar']->store("avatars/{$page->id}", 'public');

        if ($page->avatar_path) {
            Storage::disk('public')->delete($page->avatar_path);
        }

        $page->update(['avatar_path' => $path]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Foto de perfil actualizada.']);
    }

    public function updateBackgroundImage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'background_image' => [
                'required',
                'file',
                'image',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:5120',
                'dimensions:min_width=320,min_height=320,max_width=6000,max_height=6000',
            ],
        ]);

        $page = $request->user()->linkPage;
        $theme = $page->theme;
        $path = $data['background_image']->store("backgrounds/{$page->id}", 'public');

        if (! empty($theme['background_image'])) {
            Storage::disk('public')->delete($theme['background_image']);
        }

        $theme['background_image'] = $path;
        $page->update(['theme' => $theme]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Imagen de fondo actualizada.']);
    }

    public function publish(Request $request, PublishPageAction $publishPage, CreateMercadoPagoCheckoutAction $createCheckout): SymfonyResponse
    {
        try {
            $publishPage->execute($request->user());
        } catch (InsufficientCreditsException) {
            $package = CreditPackage::query()
                ->where('name', config('enlink.starter_package_name'))
                ->where('is_active', true)
                ->first();

            if (! $package) {
                return redirect()->route('credits.index')->withErrors([
                    'checkout' => 'El pack inicial todavía no está disponible.',
                ]);
            }

            try {
                $order = $createCheckout->execute($request->user(), $package);
            } catch (CheckoutUnavailableException $exception) {
                return redirect()->route('credits.index')->withErrors([
                    'checkout' => $exception->getMessage(),
                ]);
            }

            return Inertia::location($order->checkout_url);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Tu Enlink fue publicado correctamente.']);
    }
}
