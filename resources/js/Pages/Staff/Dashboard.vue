<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Staff Dashboard" />

    <div class="min-h-screen bg-gray-100">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Staff Dashboard</h1>
                <Link
                    :href="route('staff.logout')"
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
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Welcome to Staff Dashboard</h2>
                <p class="text-gray-600">This is the staff dashboard for managing orders and tables.</p>
                
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                        <h3 class="text-lg font-medium text-blue-900">Take Order</h3>
                        <p class="mt-2 text-blue-700">Create new customer orders</p>
                    </div>
                    
                    <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                        <h3 class="text-lg font-medium text-green-900">View Orders</h3>
                        <p class="mt-2 text-green-700">View and manage active orders</p>
                    </div>
                    
                    <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                        <h3 class="text-lg font-medium text-purple-900">Table Status</h3>
                        <p class="mt-2 text-purple-700">Check table availability</p>
                    </div>
                    
                    <div class="bg-orange-50 p-6 rounded-lg border border-orange-200">
                        <h3 class="text-lg font-medium text-orange-900">Menu</h3>
                        <p class="mt-2 text-orange-700">View menu items</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
