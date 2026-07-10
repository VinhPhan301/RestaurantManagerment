<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StaffPosController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $branchId = $this->staffBranchId($request);

        $tables = Table::query()
            ->where('branch_id', $branchId)
            ->with(['orders' => function ($query) {
                $query
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->with(['items.menu'])
                    ->latest();
            }])
            ->orderBy('name')
            ->get()
            ->map(function (Table $table) {
                $activeOrder = $table->orders->first();

                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'capacity' => $table->capacity,
                    'status' => $table->status,
                    'active_order' => $activeOrder ? [
                        'id' => $activeOrder->id,
                        'order_code' => $activeOrder->order_code,
                        'status' => $activeOrder->status,
                        'total_price' => $activeOrder->items->sum(fn ($item) => $item->quantity * $item->price),
                        'items' => $activeOrder->items->map(fn ($item) => [
                            'id' => $item->id,
                            'menu_id' => $item->menu_id,
                            'menu_name' => $item->menu?->name,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'status' => $item->status,
                            'notes' => $item->notes,
                        ])->values(),
                    ] : null,
                ];
            })
            ->values();

        $menus = Menu::query()
            ->where('is_available', true)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->with('category')
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Staff/Dashboard', [
            'branch' => $request->user()->branch,
            'tables' => $tables,
            'menus' => $menus,
            'categories' => $categories,
        ]);
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $branchId = $this->staffBranchId($request);

        $validated = $request->validate([
            'table_id' => ['required', 'exists:tables,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'exists:menus,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $request, $branchId) {
            $table = Table::query()
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->findOrFail($validated['table_id']);

            $menuIds = collect($validated['items'])->pluck('menu_id')->unique()->values();
            $menus = Menu::query()
                ->whereIn('id', $menuIds)
                ->where('is_available', true)
                ->where(function ($query) use ($branchId) {
                    $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
                })
                ->get()
                ->keyBy('id');

            if ($menus->count() !== $menuIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Một số món không còn khả dụng tại chi nhánh này.',
                ]);
            }

            $order = Order::query()
                ->where('table_id', $table->id)
                ->where('branch_id', $branchId)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->lockForUpdate()
                ->first();

            if ($table->status === 'empty' && ! $order) {
                $order = Order::create([
                    'order_code' => $this->makeOrderCode(),
                    'table_id' => $table->id,
                    'branch_id' => $branchId,
                    'user_id' => $request->user()->id,
                    'total_price' => 0,
                    'status' => 'pending',
                ]);

                $table->update(['status' => 'occupied']);
            }

            if (! $order) {
                throw ValidationException::withMessages([
                    'table_id' => 'Không tìm thấy hóa đơn đang hoạt động cho bàn này.',
                ]);
            }

            foreach ($validated['items'] as $item) {
                $menu = $menus->get($item['menu_id']);

                $order->items()->create([
                    'menu_id' => $menu->id,
                    'quantity' => $item['quantity'],
                    'price' => $menu->price,
                    'status' => 'ordered',
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $order->update([
                'total_price' => $order->items()->sum(DB::raw('quantity * price')),
            ]);
        });

        return redirect()->route('staff.dashboard')->with('success', 'Đã gửi order xuống bếp.');
    }

    public function moveTable(Request $request): RedirectResponse
    {
        $branchId = $this->staffBranchId($request);

        $validated = $request->validate([
            'current_table_id' => ['required', 'exists:tables,id', 'different:target_table_id'],
            'target_table_id' => ['required', 'exists:tables,id'],
        ]);

        DB::transaction(function () use ($validated, $branchId) {
            $currentTable = Table::query()
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->findOrFail($validated['current_table_id']);

            $targetTable = Table::query()
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->findOrFail($validated['target_table_id']);

            if ($targetTable->status !== 'empty') {
                throw ValidationException::withMessages([
                    'target_table_id' => 'Bàn đích phải đang trống.',
                ]);
            }

            $order = Order::query()
                ->where('table_id', $currentTable->id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->latest()
                ->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'current_table_id' => 'Không tìm thấy hóa đơn đang hoạt động ở bàn hiện tại.',
                ]);
            }

            $order->update(['table_id' => $targetTable->id]);
            $currentTable->update(['status' => 'empty']);
            $targetTable->update(['status' => 'occupied']);
        });

        return redirect()->route('staff.dashboard')->with('success', 'Đã chuyển bàn thành công.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $branchId = $this->staffBranchId($request);

        $validated = $request->validate([
            'table_id' => ['required', 'exists:tables,id'],
        ]);

        DB::transaction(function () use ($validated, $branchId) {
            $table = Table::query()
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->findOrFail($validated['table_id']);

            $order = Order::query()
                ->where('table_id', $table->id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->with('items')
                ->latest()
                ->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'table_id' => 'Bàn này không có hóa đơn đang hoạt động.',
                ]);
            }

            $total = $order->items->sum(fn ($item) => $item->quantity * $item->price);

            $order->update([
                'total_price' => $total,
                'status' => 'paid',
            ]);

            $table->update(['status' => 'empty']);
        });

        return redirect()->route('staff.dashboard')->with('success', 'Đã thanh toán và giải phóng bàn.');
    }

    private function staffBranchId(Request $request): int
    {
        $branchId = $request->user()->branch_id;

        if (! $branchId) {
            abort(403, 'Tài khoản nhân viên chưa được gán chi nhánh.');
        }

        return $branchId;
    }

    private function makeOrderCode(): string
    {
        do {
            $code = 'ORD-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }
}
