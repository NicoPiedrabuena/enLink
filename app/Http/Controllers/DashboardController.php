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

    private const LINK_ICONS = ['link', 'google', 'linkedin', 'maps', 'instagram', 'facebook', 'whatsapp', 'youtube', 'tiktok', 'spotify', 'email', 'calendar'];

    public function show(Request $request): Response
    {
        $page = $request->user()->linkPage()->firstOrCreate([], [
            'username' => $this->suggestedUsername(),
            'display_name' => $request->user()->name,
            'theme' => ['background' => '#f8fafc', 'primary' => '#111827', 'text' => '#111827', 'radius' => 'rounded', 'button_style' => 'solid'],
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

        $data = $request->validate(['username' => ['required', 'regex:/^[a-z]+(?:-[a-z]+)*$/', 'min:3', 'max:30', Rule::unique('link_pages', 'username')->ignore($page), Rule::notIn(self::RESERVED)], 'display_name' => ['required', 'string', 'max:80'], 'bio' => ['nullable', 'string', 'max:180'], 'theme' => ['required', 'array'], 'theme.background' => ['required', 'string', 'max:20'], 'theme.primary' => ['required', 'string', 'max:20'], 'theme.text' => ['required', 'string', 'max:20'], 'theme.radius' => ['required', Rule::in(['rounded', 'soft', 'square'])], 'theme.button_style' => ['sometimes', Rule::in(['solid', 'outline', 'shadow', 'transparent'])]]);
        $data['theme']['button_style'] ??= $page->theme['button_style'] ?? 'solid';
        $data['username'] = strtolower($data['username']);
        $page->update($data);

        return back()->with('toast', ['type' => 'success', 'message' => 'Datos y apariencia guardados.']);
    }

    public function storeLink(Request $request): RedirectResponse
    {
        $page = $request->user()->linkPage;
        $data = $this->validatedLink($request);
        $page->links()->create($data + ['position' => ($page->links()->max('position') ?? -1) + 1]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Enlace agregado.']);
    }

    public function updateLink(Request $request, Link $link): RedirectResponse
    {
        abort_unless($link->linkPage->user_id === $request->user()->id, 403);
        $data = $this->validatedLink($request, true);
        $link->update($data);

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
        ]);

        if ($data['type'] === 'reservation') {
            $number = $data['whatsapp_number'];
            $data['url'] = "https://wa.me/{$number}";
            $data['settings'] = ['whatsapp_number' => $number];
        } else {
            $data['settings'] = null;
        }

        unset($data['whatsapp_number']);

        return $data;
    }

    public function destroyLink(Request $request, Link $link): RedirectResponse
    {
        abort_unless($link->linkPage->user_id === $request->user()->id, 403);
        $link->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Enlace eliminado.']);
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
