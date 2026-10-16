<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = $request->user()->categories()->withCount('transactions');

        // Filter by type if provided
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        $categories = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['type']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('categories/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        $category = $request->user()->categories()->create([
            'name' => $request->name,
            'type' => $request->type,
            'color' => $request->color,
            'icon' => $request->icon,
        ]);

        return to_route('categories.index')
            ->with('toast', ['type' => 'success', 'message' => __('Category created successfully.')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): Response
    {
        $this->authorize('update', $category);

        return Inertia::render('categories/Edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryUpdateRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->update([
            'name' => $request->name,
            'type' => $request->type,
            'color' => $request->color,
            'icon' => $request->icon,
        ]);

        return to_route('categories.index')
            ->with('toast', ['type' => 'success', 'message' => __('Category updated successfully.')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return to_route('categories.index')
            ->with('toast', ['type' => 'success', 'message' => __('Category deleted successfully.')]);
    }
}
