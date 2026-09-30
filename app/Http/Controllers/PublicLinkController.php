<?php

namespace App\Http\Controllers;

use App\Jobs\RecordLinkClickJob;
use App\Models\LinkPage;
use App\Models\PagePublication;
use App\Support\PublishedLinkToken;
use App\Support\TrafficContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicLinkController extends Controller
{
    public function show(Request $request, string $token): RedirectResponse
    {
        [$publication, $link, $linkId] = $this->publishedLink($token);
        abort_if(($link['type'] ?? 'link') === 'reservation', 404);

        $this->recordClick($request, $publication, $linkId, $link);

        return redirect()->away($link['url']);
    }

    public function reserve(Request $request, string $token): RedirectResponse
    {
        [$publication, $link, $linkId] = $this->publishedLink($token);
        abort_unless(($link['type'] ?? 'link') === 'reservation', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'guests' => ['required', 'integer', 'min:1', 'max:99'],
        ]);
        $number = $link['settings']['whatsapp_number'] ?? null;
        abort_unless(is_string($number) && preg_match('/^[1-9][0-9]{7,14}$/', $number), 404);

        $this->recordClick($request, $publication, $linkId, $link);

        $message = rawurlencode("Hola, quiero hacer una reserva para {$data['guests']} personas. A nombre de {$data['name']}.");

        return redirect()->away("https://wa.me/{$number}?text={$message}");
    }

    /** @return array{0: PagePublication, 1: array<string, mixed>, 2: string} */
    private function publishedLink(string $token): array
    {
        $decoded = PublishedLinkToken::decode($token);
        abort_unless($decoded, 404);

        $publication = PagePublication::query()->findOrFail($decoded['publication_id']);
        $isActive = LinkPage::query()
            ->where('active_publication_id', $publication->id)
            ->where('status', 'active')
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->exists();
        abort_unless($isActive && $publication->status === 'published', 404);

        $link = collect($publication->payload['links'] ?? [])->first(
            fn (array $link): bool => (int) ($link['id'] ?? 0) === $decoded['link_id'],
        );
        abort_unless(is_array($link) && filter_var($link['url'] ?? null, FILTER_VALIDATE_URL), 404);

        return [$publication, $link, (string) $decoded['link_id']];
    }

    /** @param array<string, mixed> $link */
    private function recordClick(Request $request, PagePublication $publication, string $linkId, array $link): void
    {
        $userAgent = $request->userAgent();

        if (! TrafficContext::isBot($userAgent)) {
            RecordLinkClickJob::dispatch(
                $publication->id,
                $linkId,
                (string) $link['title'],
                TrafficContext::referrerDomain($request->headers->get('referer')),
                TrafficContext::userAgentFamily($userAgent),
            );
        }
    }
}
