<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Table;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tạo 2 chi nhánh mẫu
        $branchCauGiay = Branch::create([
            'name' => 'Cơ sở Cầu Giấy',
            'address' => '123 Đường Cầu Giấy, Hà Nội',
            'phone' => '0901234567',
            'status' => 'active',
        ]);

        Branch::create([
            'name' => 'Cơ sở Đống Đa',
            'address' => '456 Đường Đống Đa, Hà Nội',
            'phone' => '0907654321',
            'status' => 'active',
        ]);

        // Tạo tài khoản Admin tổng
        User::create([
            'name' => 'Admin',
            'email' => 'admin@restaurant.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'branch_id' => null,
        ]);

        // Tạo 2 tài khoản Staff mẫu thuộc Cơ sở Cầu Giấy
        User::create([
            'name' => 'Staff 1',
            'email' => 'staff1@restaurant.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'branch_id' => $branchCauGiay->id,
        ]);

        User::create([
            'name' => 'Manager 1',
            'email' => 'manager1@restaurant.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'branch_id' => $branchCauGiay->id,
        ]);

        // Tạo 6 Danh mục mẫu
        $categories = [
            ['name' => 'Best Seller', 'sort_order' => 1],
            ['name' => 'Must Try', 'sort_order' => 2],
            ['name' => 'Khai vị', 'sort_order' => 3],
            ['name' => 'Món chính', 'sort_order' => 4],
            ['name' => 'Tráng miệng', 'sort_order' => 5],
            ['name' => 'Đồ uống', 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Tạo khoảng 10 món ăn mẫu phân bổ đều vào các danh mục
        $menuItems = [
            ['category_id' => 1, 'name' => 'Phở Bò Đặc Biệt', 'description' => 'Phở bò tươi với nước dùng đậm đà', 'price' => 85000, 'is_available' => true, 'is_best_seller' => true, 'is_must_try' => false],
            ['category_id' => 1, 'name' => 'Bún Chả Hà Nội', 'description' => 'Bún chả thịt nướng than hoa', 'price' => 75000, 'is_available' => true, 'is_best_seller' => true, 'is_must_try' => false],
            ['category_id' => 2, 'name' => 'Gỏi Cuốn Tôm Thịt', 'description' => 'Gỏi cuốn tươi ngon', 'price' => 45000, 'is_available' => true, 'is_best_seller' => false, 'is_must_try' => true],
            ['category_id' => 2, 'name' => 'Chả Giò Rế', 'description' => 'Chả giò rế giòn rụm', 'price' => 40000, 'is_available' => true, 'is_best_seller' => false, 'is_must_try' => true],
            ['category_id' => 3, 'name' => 'Nem Rán', 'description' => 'Nem rán truyền thống', 'price' => 35000, 'is_available' => true, 'is_best_seller' => false, 'is_must_try' => false],
            ['category_id' => 3, 'name' => 'Salad Bò', 'description' => 'Salad bò tươi mát', 'price' => 55000, 'is_available' => true, 'is_best_seller' => false, 'is_must_try' => false],
            ['category_id' => 4, 'name' => 'Cơm Tấm Sườn Bì', 'description' => 'Cơm tấm sườn bì chả', 'price' => 65000, 'is_available' => true, 'is_best_seller' => false, 'is_must_try' => false],
            ['category_id' => 4, 'name' => 'Bún Đậu Mắm Tôm', 'description' => 'Bún đậu mắm tôm truyền thống', 'price' => 50000, 'is_available' => true, 'is_best_seller' => false, 'is_must_try' => false],
            ['category_id' => 5, 'name' => 'Trà Sen Vàng', 'description' => 'Trà sen vàng thơm ngon', 'price' => 25000, 'is_available' => true, 'is_best_seller' => false, 'is_must_try' => false],
            ['category_id' => 5, 'name' => 'Sinh Tố Bơ', 'description' => 'Sinh tố bơ creamy', 'price' => 35000, 'is_available' => true, 'is_best_seller' => false, 'is_must_try' => false],
        ];

        foreach ($menuItems as $item) {
            Menu::create($item);
        }

        // Tạo 5 bàn ăn mẫu cho Cơ sở Cầu Giấy
        for ($i = 1; $i <= 5; $i++) {
            Table::create([
                'branch_id' => $branchCauGiay->id,
                'name' => 'Bàn ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'capacity' => 4,
                'status' => 'empty',
            ]);
        }
    }
}
