<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $branchId = $this->scopeBranchId($request);
        $search = trim((string) $request->query('search', ''));

        $query = User::whereIn('role', ['manager', 'staff']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->with('branch')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        $branches = $this->availableBranches($request);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'branches' => $branches,
            'filters' => [
                'branch_id' => $branchId,
                'search' => $search,
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
        $this->applyManagerBranchInput($request);
        $this->forceManagerStaffRole($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:manager,staff',
            'branch_id' => 'required|exists:branches,id',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'],
        ]);

        return redirect()->back()->with('success', 'Nhân viên đã được tạo thành công.');
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
        $user = $this->scopedUsers($request)->findOrFail($id);

        $this->applyManagerBranchInput($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:manager,staff',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Nhân viên đã được cập nhật thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = $this->scopedUsers(request())->findOrFail($id);
        $user->delete();

        return redirect()->back()->with('success', 'Nhân viên đã được xóa thành công.');
    }

    private function scopedUsers(Request $request)
    {
        $query = User::whereIn('role', ['manager', 'staff']);

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

    private function applyManagerBranchInput(Request $request): void
    {
        if ($request->user()->role === 'manager') {
            $request->merge([
                'branch_id' => $request->user()->branch_id,
            ]);
        }
    }

    private function forceManagerStaffRole(Request $request): void
    {
        if ($request->user()->role === 'manager') {
            $request->merge(['role' => 'staff']);
        }
    }
}
