<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ReservationRequest;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReservationRequestController extends Controller
{
    private const STATUSES = ['pending', 'confirmed', 'rejected'];

    public function index(Request $request): Response
    {
        $branchId = $this->scopeBranchId($request);
        $status = $request->query('status');
        $branches = $this->availableBranches($request);
        $visibleBranchIds = $branches->pluck('id');
        $availableTables = Table::query()
            ->whereIn('branch_id', $visibleBranchIds)
            ->where('status', 'empty')
            ->orderBy('branch_id')
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name', 'capacity']);
        $conflictKeys = [];

        ReservationRequest::query()
            ->where('status', 'confirmed')
            ->whereNotNull('table_id')
            ->whereIn('branch_id', $visibleBranchIds)
            ->get(['table_id', 'reservation_date', 'reservation_time'])
            ->each(function (ReservationRequest $reservation) use (&$conflictKeys) {
                $conflictKeys[$this->reservationKey(
                    $reservation->table_id,
                    $reservation->reservation_date?->format('Y-m-d'),
                    $reservation->reservation_time,
                )] = true;
            });

        $query = $this->scopedRequests($request)
            ->with(['branch', 'table'])
            ->latest();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        } else {
            $status = '';
        }

        $requests = $query->get()->map(fn (ReservationRequest $reservation) => [
            'id' => $reservation->id,
            'branch_id' => $reservation->branch_id,
            'customer_name' => $reservation->customer_name,
            'phone' => $reservation->phone,
            'reservation_date' => $reservation->reservation_date?->format('d/m/Y'),
            'reservation_time' => substr((string) $reservation->reservation_time, 0, 5),
            'guests' => $reservation->guests,
            'note' => $reservation->note,
            'status' => $reservation->status,
            'created_at' => $reservation->created_at?->format('H:i d/m/Y'),
            'branch' => $reservation->branch,
            'table' => $reservation->table ? [
                'id' => $reservation->table->id,
                'name' => $reservation->table->name,
                'capacity' => $reservation->table->capacity,
            ] : null,
            'available_tables' => $reservation->status === 'pending'
                ? $availableTables
                    ->where('branch_id', $reservation->branch_id)
                    ->reject(fn (Table $table) => isset($conflictKeys[$this->reservationKey(
                        $table->id,
                        $reservation->reservation_date?->format('Y-m-d'),
                        $reservation->reservation_time,
                    )]))
                    ->map(fn (Table $table) => [
                        'id' => $table->id,
                        'name' => $table->name,
                        'capacity' => $table->capacity,
                    ])
                    ->values()
                : [],
        ])->values();

        return Inertia::render('Admin/Reservations/Index', [
            'requests' => $requests,
            'branches' => $branches,
            'filters' => [
                'branch_id' => $branchId,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'digits:10'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        ReservationRequest::create($validated + ['status' => 'pending']);

        return redirect()->back()->with('success', 'Yêu cầu đặt bàn đã được ghi nhận. Nhà hàng sẽ liên hệ để xác nhận.');
    }

    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,rejected'],
            'table_id' => ['nullable', 'integer', 'exists:tables,id'],
        ]);

        return DB::transaction(function () use ($request, $id, $validated): RedirectResponse {
            $reservation = $this->scopedRequests($request)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($reservation->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ yêu cầu đang chờ xử lý mới có thể được cập nhật.',
                ]);
            }

            if ($validated['status'] === 'rejected') {
                $reservation->update([
                    'status' => 'rejected',
                    'table_id' => null,
                ]);

                return redirect()->back()->with('success', 'Đã từ chối yêu cầu đặt bàn.');
            }

            if (empty($validated['table_id'])) {
                throw ValidationException::withMessages([
                    'table_id' => 'Vui lòng chọn bàn trước khi xác nhận.',
                ]);
            }

            $table = Table::query()
                ->where('branch_id', $reservation->branch_id)
                ->whereKey($validated['table_id'])
                ->lockForUpdate()
                ->first();

            if (! $table) {
                throw ValidationException::withMessages([
                    'table_id' => 'Bàn không thuộc chi nhánh của yêu cầu này.',
                ]);
            }

            if ($table->status !== 'empty') {
                throw ValidationException::withMessages([
                    'table_id' => 'Bàn này không còn trống.',
                ]);
            }

            $reservationDate = $reservation->reservation_date?->format('Y-m-d');
            $reservationTime = substr((string) $reservation->reservation_time, 0, 5);
            $hasConflict = ReservationRequest::query()
                ->where('status', 'confirmed')
                ->where('table_id', $table->id)
                ->where('id', '<>', $reservation->id)
                ->whereDate('reservation_date', $reservationDate)
                ->whereTime('reservation_time', $reservationTime)
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'table_id' => 'Bàn này đã có yêu cầu được xác nhận vào thời gian này.',
                ]);
            }

            $reservation->update([
                'status' => 'confirmed',
                'table_id' => $table->id,
            ]);

            $table->update([
                'status' => 'reserved',
                'reservation_customer_name' => $reservation->customer_name,
                'reservation_phone' => $reservation->phone,
                'reservation_time' => $reservationDate . ' ' . $reservationTime,
                'reservation_note' => $reservation->note,
            ]);

            return redirect()->back()->with('success', 'Đã xác nhận yêu cầu và giữ bàn thành công.');
        });
    }

    private function scopedRequests(Request $request)
    {
        $query = ReservationRequest::query();

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
            return Branch::where('id', $request->user()->branch_id)->get(['id', 'name']);
        }

        return Branch::orderBy('name')->get(['id', 'name']);
    }

    private function reservationKey(?int $tableId, ?string $date, $time): string
    {
        return implode('|', [
            (string) $tableId,
            (string) $date,
            substr((string) $time, 0, 5),
        ]);
    }
}
