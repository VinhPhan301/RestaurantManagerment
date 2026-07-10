<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $branchId = $this->scopeBranchId($request);

        $query = Menu::with(['category', 'branch']);

        if ($request->user()->role === 'manager') {
            $query->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            });
        } elseif ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $menus = $query->get();
        $categories = Category::all();
        $branches = $this->availableBranches($request);

        return Inertia::render('Admin/Menus/Index', [
            'menus' => $menus,
            'categories' => $categories,
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
        $this->applyManagerBranchInput($request);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_available' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_must_try' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menus', 'public');
            $validated['image'] = $imagePath;
        }

        Menu::create($validated);

        return redirect()->back()->with('success', 'Món ăn đã được tạo thành công.');
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
        $menu = $this->scopedMenus($request)->findOrFail($id);

        $this->applyManagerBranchInput($request);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_available' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_must_try' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($menu->image && Storage::disk('public')->exists($menu->image)) {
                Storage::disk('public')->delete($menu->image);
            }
            $imagePath = $request->file('image')->store('menus', 'public');
            $validated['image'] = $imagePath;
        }

        $menu->update($validated);

        return redirect()->back()->with('success', 'Món ăn đã được cập nhật thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $menu = $this->scopedMenus(request())->findOrFail($id);

        // Delete image if exists
        if ($menu->image && Storage::disk('public')->exists($menu->image)) {
            Storage::disk('public')->delete($menu->image);
        }

        $menu->delete();

        return redirect()->back()->with('success', 'Món ăn đã được xóa thành công.');
    }

    private function scopedMenus(Request $request)
    {
        $query = Menu::query();

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
}
