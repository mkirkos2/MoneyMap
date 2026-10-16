<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = $request->user()->transactions()->with('category');

        // Filter by type if provided
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // Filter by category if provided
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // Filter by date range if provided
        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->get('end_date'));
        }

        // Order by transaction date descending
        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(15)->withQueryString();

        // Get user's categories for filter dropdown
        $categories = $request->user()->categories()->orderBy('name')->get();

        return Inertia::render('transactions/Index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => $request->only(['type', 'category_id', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $categories = $request->user()->categories()->orderBy('name')->get();

        return Inertia::render('transactions/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionStoreRequest $request): RedirectResponse
    {
        $request->user()->transactions()->create([
            'category_id' => $request->category_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
            'notes' => $request->notes,
        ]);

        return to_route('transactions.index')
            ->with('toast', ['type' => 'success', 'message' => __('Transaction created successfully.')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        $categories = $request->user()->categories()->orderBy('name')->get();

        return Inertia::render('transactions/Edit', [
            'transaction' => $transaction,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionUpdateRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $transaction->update([
            'category_id' => $request->category_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
            'notes' => $request->notes,
        ]);

        return to_route('transactions.index')
            ->with('toast', ['type' => 'success', 'message' => __('Transaction updated successfully.')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return to_route('transactions.index')
            ->with('toast', ['type' => 'success', 'message' => __('Transaction deleted successfully.')]);
    }
}
