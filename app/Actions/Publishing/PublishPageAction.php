<?php

namespace App\Actions\Publishing;

use App\Actions\Credits\CreateCreditTransactionAction;
use App\Models\LinkPage;
use App\Models\PagePublication;
use App\Models\User;
use App\Support\PublicPageCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishPageAction
{
    public function __construct(private readonly CreateCreditTransactionAction $createCreditTransaction) {}

    public function execute(User $user): PagePublication
    {
        return DB::transaction(function () use ($user): PagePublication {
            $page = LinkPage::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $links = $page->links()
                ->where('is_active', true)
                ->orderBy('position')
                ->get(['id', 'title', 'icon', 'url', 'type', 'settings', 'position']);

            if ($links->isEmpty()) {
                throw ValidationException::withMessages([
                    'publish' => 'Agregá al menos un enlace activo antes de publicar.',
                ]);
            }

            $payload = [
                'display_name' => $page->display_name,
                'bio' => $page->bio,
                'avatar_path' => $page->avatar_path,
                'theme' => $page->theme,
                'links' => $links->map(fn ($link): array => [
                    'id' => $link->id,
                    'title' => $link->title,
                    'icon' => $link->icon,
                    'url' => $link->url,
                    'type' => $link->type,
                    'settings' => $link->settings,
                    'position' => $link->position,
                ])->all(),
            ];
            $activePublication = $page->activePublication;

            if ($activePublication && $activePublication->username === $page->username && $activePublication->payload === $payload) {
                throw ValidationException::withMessages([
                    'publish' => 'No hay cambios nuevos para publicar.',
                ]);
            }

            $version = ((int) $page->publications()->max('version')) + 1;
            $publication = $page->publications()->create([
                'version' => $version,
                'username' => $page->username,
                'payload' => $payload,
                'published_at' => now(),
            ]);

            $cost = (int) config('enlink.publication_credit_cost');
            $this->createCreditTransaction->execute(
                $user,
                'publication',
                -$cost,
                "Publicación de {$page->username}, versión {$version}",
                "publication:{$publication->id}",
                ['publication_id' => $publication->id, 'version' => $version],
            );

            $page->update(['active_publication_id' => $publication->id]);

            DB::afterCommit(function () use ($activePublication, $page, $publication): void {
                Cache::forget(PublicPageCache::key($page->username));

                if ($activePublication) {
                    Cache::forget(PublicPageCache::key($activePublication->username));
                }

                Cache::forget(PublicPageCache::key($publication->username));
            });

            return $publication;
        });
    }
}
