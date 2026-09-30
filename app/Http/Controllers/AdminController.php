<?php

namespace App\Http\Controllers;

use App\Actions\Credits\CreateCreditTransactionAction;
use App\Actions\Credits\InsufficientCreditsException;
use App\Models\AuditLog;
use App\Models\CreditPackage;
use App\Models\LinkPage;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\User;
use App\Support\PublicPageCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin', [
            'summary' => [
                'users' => User::query()->count(),
                'publishedPages' => LinkPage::query()->whereNotNull('active_publication_id')->where('status', 'active')->count(),
                'credits' => (int) DB::table('credit_balances')->sum('balance'),
                'approvedRevenue' => (string) Payment::query()->where('status', 'approved')->sum('amount'),
            ],
            'users' => User::query()
                ->with(['creditBalance:id,user_id,balance', 'linkPage:id,user_id,username,status'])
                ->latest()
                ->limit(50)
                ->get(['id', 'name', 'email', 'role', 'status', 'created_at']),
            'pages' => LinkPage::query()
                ->with('user:id,name,email')
                ->latest()
                ->limit(50)
                ->get(['id', 'user_id', 'username', 'display_name', 'status', 'suspension_reason', 'active_publication_id']),
            'packages' => CreditPackage::query()->orderBy('sort_order')->get(),
            'orders' => PaymentOrder::query()
                ->with('user:id,name,email')
                ->latest()
                ->limit(30)
                ->get(['id', 'user_id', 'package_name', 'credits', 'amount', 'currency', 'status', 'created_at']),
            'auditLogs' => AuditLog::query()
                ->with('actor:id,name,email')
                ->latest('id')
                ->limit(30)
                ->get(),
        ]);
    }

    public function updateUserStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'No podés suspender tu propia cuenta.');
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'reason' => ['required_if:status,suspended', 'nullable', 'string', 'max:500'],
        ]);
        $before = ['status' => $user->status];

        DB::transaction(function () use ($before, $data, $request, $user): void {
            $user->update(['status' => $data['status']]);
            $this->audit($request->user(), 'user.status_changed', $user, $data['reason'] ?? null, $before, ['status' => $data['status']]);
        });

        if ($user->linkPage?->activePublication) {
            Cache::forget(PublicPageCache::key($user->linkPage->activePublication->username));
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Estado del usuario actualizado.']);
    }

    public function updatePageStatus(Request $request, LinkPage $page): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'reason' => ['required_if:status,suspended', 'nullable', 'string', 'max:500'],
        ]);
        $before = ['status' => $page->status, 'suspension_reason' => $page->suspension_reason];

        DB::transaction(function () use ($before, $data, $page, $request): void {
            $page->update([
                'status' => $data['status'],
                'suspension_reason' => $data['status'] === 'suspended' ? $data['reason'] : null,
            ]);
            $this->audit($request->user(), 'page.status_changed', $page, $data['reason'] ?? null, $before, [
                'status' => $page->status,
                'suspension_reason' => $page->suspension_reason,
            ]);
        });

        if ($page->activePublication) {
            Cache::forget(PublicPageCache::key($page->activePublication->username));
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Estado de la página actualizado.']);
    }

    public function adjustCredits(Request $request, User $user, CreateCreditTransactionAction $createTransaction): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'between:-10000,10000', 'not_in:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($createTransaction, $data, $request, $user): void {
                $transaction = $createTransaction->execute(
                    $user,
                    'admin_adjustment',
                    (int) $data['amount'],
                    $data['reason'],
                    'admin-adjustment:'.Str::uuid(),
                    ['actor_id' => $request->user()->id],
                );
                $this->audit($request->user(), 'credits.adjusted', $user, $data['reason'], null, [
                    'amount' => $transaction->amount,
                    'balance_after' => $transaction->balance_after,
                    'transaction_id' => $transaction->id,
                ]);
            });
        } catch (InsufficientCreditsException) {
            throw ValidationException::withMessages(['amount' => 'El ajuste dejaría un saldo negativo.']);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Saldo de créditos actualizado.']);
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $data = $this->packageData($request);
        $package = CreditPackage::query()->create($data);
        $this->audit($request->user(), 'credit_package.created', $package, null, null, $package->only(['name', 'credits', 'price', 'currency', 'is_active']));

        return back()->with('toast', ['type' => 'success', 'message' => 'Paquete creado correctamente.']);
    }

    public function updatePackage(Request $request, CreditPackage $creditPackage): RedirectResponse
    {
        $before = $creditPackage->only(['name', 'credits', 'price', 'currency', 'is_active']);
        $creditPackage->update($this->packageData($request, $creditPackage));
        $this->audit($request->user(), 'credit_package.updated', $creditPackage, null, $before, $creditPackage->only(['name', 'credits', 'price', 'currency', 'is_active']));

        return back()->with('toast', ['type' => 'success', 'message' => 'Paquete y precio actualizados.']);
    }

    /** @return array<string, mixed> */
    private function packageData(Request $request, ?CreditPackage $package = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('credit_packages')->ignore($package)],
            'credits' => ['required', 'integer', 'min:1', 'max:100000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'currency' => ['required', Rule::in(['ARS'])],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);
    }

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    private function audit(User $actor, string $action, object $subject, ?string $reason, ?array $before, ?array $after): void
    {
        AuditLog::query()->create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->id,
            'reason' => $reason,
            'before' => $before,
            'after' => $after,
            'created_at' => now(),
        ]);
    }
}
