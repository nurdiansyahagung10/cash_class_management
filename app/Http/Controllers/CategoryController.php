<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageMembers(), 403);

        $search = trim((string) $request->query('q'));

        return view('categories.index', [
            'search' => $search,
            'categories' => Category::withCount(['billingPeriods', 'payments'])
                ->when($search !== '', fn ($q) => $q->where(
                    fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")
                ))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageMembers(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['name'] = trim($validated['name']);

        Category::create($validated);

        return back()->with('status', "Kategori '{$validated['name']}' berhasil dibuat.");
    }
}
