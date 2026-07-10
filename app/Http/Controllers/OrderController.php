<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $branchId = $this->scopeBranchId($request);

        $query = Order::with(['table', 'branch', 'user', 'items.menu']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();
        $branches = $this->availableBranches($request);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
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
        // Not needed
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Not needed for admin
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
        // Not needed
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = $this->scopedOrders($request)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,serving,paid,cancelled',
        ]);

        $order->update($validated);

        return redirect()->back()->with('success', 'Trạng thái đơn hàng đã được cập nhật.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = $this->scopedOrders(request())->findOrFail($id);
        $order->delete();

        return redirect()->back()->with('success', 'Đơn hàng đã được xóa thành công.');
    }

    private function scopedOrders(Request $request)
    {
        $query = Order::query();

        if ($request->user()->role === 'manager') {
            $query->where('branch_id', $request->user()->branch_id);
        }

        return $query;
    }

    private function scopeBranchId(Request $request): ?string
    {
        if ($request->user()->role === 'manager') {
            return (string) $request->user()->branch_id;
        }

        return $request->query('branch_id');
    }

    private function availableBranches(Request $request)
    {
        if ($request->user()->role === 'manager') {
            return Branch::where('id', $request->user()->branch_id)->get();
        }

        return Branch::all();
    }
}
