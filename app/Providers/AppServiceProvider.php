<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\PendingMember;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layouts.app', function (\Illuminate\View\View $view): void {
            $view->with('navCounts', $this->sidebarCounts());
        });
    }

    /**
     * Pending-work counts shown as badges next to the sidebar links (staff and admins only).
     *
     * @return array<string, int>
     */
    private function sidebarCounts(): array
    {
        $user = auth()->user();

        if (! $user?->canManageMembers()) {
            return [];
        }

        return [
            'payments.review' => Payment::where('status', 'pending')->whereNotNull('proof_path')->count(),
            'members.index' => PendingMember::where('expires_at', '>', now())->count(),
        ];
    }
}
