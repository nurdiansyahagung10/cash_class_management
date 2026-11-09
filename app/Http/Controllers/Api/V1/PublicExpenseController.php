<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

/**
 * Read-only public list of what the class cash was spent on.
 */
class PublicExpenseController extends Controller
{
    /**
     * Newest first by spend date; pass `month=YYYY-MM` to fetch a single month.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $expenses = Expense::query()
            ->when(isset($validated['month']), function ($query) use ($validated) {
                $month = Carbon::parse($validated['month'].'-01');

                $query->whereBetween('spent_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
            })
            ->latest('spent_at')
            ->latest('id')
            ->paginate($validated['per_page'] ?? 50)
            ->withQueryString();

        return PublicExpenseResource::collection($expenses);
    }
}
