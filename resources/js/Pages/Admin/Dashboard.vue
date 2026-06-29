<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="min-h-screen bg-gray-100">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
                <Link
                    :href="route('admin.logout')"
                    method="post"
                    as="button"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition"
                >
                    Logout
                </Link>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Welcome to Admin Dashboard</h2>
                <p class="text-gray-600">This is the admin dashboard where you can manage the restaurant system.</p>
                
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                        <h3 class="text-lg font-medium text-blue-900">Orders</h3>
                        <p class="mt-2 text-blue-700">Manage customer orders</p>
                    </div>
                    
                    <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                        <h3 class="text-lg font-medium text-green-900">Menu</h3>
                        <p class="mt-2 text-green-700">Manage menu items</p>
                    </div>
                    
                    <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                        <h3 class="text-lg font-medium text-purple-900">Users</h3>
                        <p class="mt-2 text-purple-700">Manage user accounts</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
