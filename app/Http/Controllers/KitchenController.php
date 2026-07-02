<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KitchenController extends Controller
{
    public function index(Request $request): Response
    {
        $branchId = $this->staffBranchId($request);

        $items = OrderItem::query()
            ->whereIn('status', ['ordered', 'cooking'])
            ->whereHas('order', function ($query) use ($branchId) {
                $query
                    ->where('branch_id', $branchId)
                    ->whereNotIn('status', ['paid', 'cancelled']);
            })
            ->with(['menu', 'order.table'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'menu_name' => $item->menu?->name,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
                'status' => $item->status,
                'created_at' => $item->created_at?->toISOString(),
                'created_at_label' => $item->created_at?->format('H:i'),
                'order_code' => $item->order?->order_code,
                'table_name' => $item->order?->table?->name,
            ])
            ->values();

        return Inertia::render('Kitchen/Index', [
            'branch' => $request->user()->branch,
            'items' => $items,
        ]);
    }

    public function updateItemStatus(Request $request, OrderItem $orderItem): RedirectResponse
    {
        $branchId = $this->staffBranchId($request);

        $validated = $request->validate([
            'status' => ['required', 'in:cooking,ready'],
        ]);

        $orderItem->load('order');

        if ($orderItem->order?->branch_id !== $branchId) {
            abort(404);
        }

        $this->assertValidTransition($orderItem, $validated['status']);

        $orderItem->update([
            'status' => $validated['status'],
        ]);

        return redirect()->route('kitchen.index')->with('success', 'Đã cập nhật trạng thái món.');
    }

    private function assertValidTransition(OrderItem $orderItem, string $nextStatus): void
    {
        $allowed = [
            'ordered' => ['cooking'],
            'cooking' => ['ready'],
        ];

        if (! in_array($nextStatus, $allowed[$orderItem->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Trạng thái món không hợp lệ cho thao tác này.',
            ]);
        }
    }

    private function staffBranchId(Request $request): int
    {
        $branchId = $request->user()->branch_id;

        if (! $branchId) {
            abort(403, 'Tài khoản này chưa được gán chi nhánh.');
        }

        return $branchId;
    }
}
