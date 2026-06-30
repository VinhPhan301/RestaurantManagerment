<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <AdminLayout title="Admin Dashboard">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Welcome to Admin Dashboard</h2>
            <p class="text-gray-600">This is the admin dashboard where you can manage the restaurant system.</p>
            
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <Link
                    :href="route('admin.branches.index')"
                    class="bg-blue-50 p-6 rounded-lg border border-blue-200 hover:bg-blue-100 transition cursor-pointer"
                >
                    <h3 class="text-lg font-medium text-blue-900">Chi nhánh</h3>
                    <p class="mt-2 text-blue-700">Quản lý các chi nhánh</p>
                </Link>
                
                <Link
                    :href="route('admin.menus.index')"
                    class="bg-green-50 p-6 rounded-lg border border-green-200 hover:bg-green-100 transition cursor-pointer"
                >
                    <h3 class="text-lg font-medium text-green-900">Thực đơn</h3>
                    <p class="mt-2 text-green-700">Quản lý món ăn</p>
                </Link>
                
                <Link
                    :href="route('admin.users.index')"
                    class="bg-purple-50 p-6 rounded-lg border border-purple-200 hover:bg-purple-100 transition cursor-pointer"
                >
                    <h3 class="text-lg font-medium text-purple-900">Nhân sự</h3>
                    <p class="mt-2 text-purple-700">Quản lý nhân viên</p>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
