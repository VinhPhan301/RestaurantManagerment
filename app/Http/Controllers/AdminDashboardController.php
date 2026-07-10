<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $branchId = $this->scopeBranchId($request);
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $lastSevenDaysStart = Carbon::today()->subDays(6);

        return Inertia::render('Admin/Dashboard', [
            'branches' => $this->availableBranches($request),
            'filters' => [
                'branch_id' => $branchId,
            ],
            'stats' => [
                'today' => $this->todayStats($branchId, $today),
                'revenueByDay' => $this->revenueByDay($branchId, $lastSevenDaysStart, $today),
                'topMenus' => $this->topMenus($branchId, $monthStart, $monthEnd),
                'orderStatus' => $this->orderStatus($branchId),
                'recentOrders' => $this->recentOrders($branchId),
            ],
        ]);
    }

    private function todayStats(?string $branchId, Carbon $today): array
    {
        $todayOrders = $this->scopedOrders($branchId)
            ->whereDate('created_at', $today);

        return [
            'revenue' => (float) (clone $todayOrders)->where('status', 'paid')->sum('total_price'),
            'orders' => (clone $todayOrders)->count(),
            'occupiedTables' => $this->scopedTables($branchId)->where('status', 'occupied')->count(),
            'pendingItems' => $this->scopedOrderItems($branchId)
                ->whereIn('order_items.status', ['ordered', 'cooking', 'ready'])
                ->count(),
        ];
    }

    private function revenueByDay(?string $branchId, Carbon $start, Carbon $end): array
    {
        $rows = $this->scopedOrders($branchId)
            ->selectRaw('DATE(created_at) as order_date, SUM(total_price) as revenue')
            ->where('status', 'paid')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->groupBy('order_date')
            ->pluck('revenue', 'order_date');

        return collect(CarbonPeriod::create($start, $end))
            ->map(fn (Carbon $date) => [
                'date' => $date->toDateString(),
                'label' => $date->format('d/m'),
                'revenue' => (float) ($rows[$date->toDateString()] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function topMenus(?string $branchId, Carbon $start, Carbon $end): array
    {
        return $this->scopedOrderItems($branchId)
            ->join('menus', 'order_items.menu_id', '=', 'menus.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select([
                'menus.id',
                'menus.name',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(order_items.quantity * order_items.price) as revenue'),
            ])
            ->whereDate('orders.created_at', '>=', $start)
            ->whereDate('orders.created_at', '<=', $end)
            ->groupBy('menus.id', 'menus.name')
            ->orderByDesc('quantity')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => (int) $item->quantity,
                'revenue' => (float) $item->revenue,
            ])
            ->all();
    }

    private function orderStatus(?string $branchId): array
    {
        $counts = $this->scopedOrders($branchId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(['pending', 'serving', 'paid', 'cancelled'])
            ->map(fn ($status) => [
                'status' => $status,
                'total' => (int) ($counts[$status] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function recentOrders(?string $branchId): array
    {
        return $this->scopedOrders($branchId)
            ->with(['table', 'branch'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'table' => $order->table?->name,
                'branch' => $order->branch?->name,
                'total_price' => (float) $order->total_price,
                'status' => $order->status,
                'created_at' => $order->created_at?->format('H:i d/m/Y'),
            ])
            ->all();
    }

    private function scopedOrders(?string $branchId)
    {
        $query = Order::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function scopedTables(?string $branchId)
    {
        $query = Table::query();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function scopedOrderItems(?string $branchId)
    {
        $query = OrderItem::query();

        if ($branchId) {
            $query->whereHas('order', fn ($orderQuery) => $orderQuery->where('branch_id', $branchId));
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
