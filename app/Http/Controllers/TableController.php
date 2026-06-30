<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Table;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $branchId = $request->query('branch_id');

        $query = Table::with('branch');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $tables = $query->get();
        $branches = Branch::all();

        return Inertia::render('Admin/Tables/Index', [
            'tables' => $tables,
            'branches' => $branches,
            'filters' => [
                'branch_id' => $branchId,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not needed for modal form
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:empty,occupied,reserved',
        ]);

        Table::create($validated);

        return redirect()->back()->with('success', 'Bàn đã được tạo thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Not needed
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Not needed for modal form
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $table = Table::findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:empty,occupied,reserved',
        ]);

        $table->update($validated);

        return redirect()->back()->with('success', 'Bàn đã được cập nhật thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $table = Table::findOrFail($id);
        $table->delete();

        return redirect()->back()->with('success', 'Bàn đã được xóa thành công.');
    }
}
