<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    title: String,
});

const sidebarOpen = ref(true);
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-gray-100 flex">
        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'w-64' : 'w-0'"
            class="bg-gray-800 text-white transition-all duration-300 overflow-hidden flex-shrink-0"
        >
            <div class="p-4">
                <h2 class="text-xl font-bold mb-6">Admin Menu</h2>
                <nav class="space-y-2">
                    <Link
                        :href="route('admin.dashboard')"
                        class="block px-4 py-2 rounded hover:bg-gray-700 transition flex items-center gap-3"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </Link>
                    <Link
                        :href="route('admin.branches.index')"
                        class="block px-4 py-2 rounded hover:bg-gray-700 transition flex items-center gap-3"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Chi nhánh
                    </Link>
                    <Link
                        :href="route('admin.users.index')"
                        class="block px-4 py-2 rounded hover:bg-gray-700 transition flex items-center gap-3"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Nhân sự
                    </Link>
                    <Link
                        :href="route('admin.menus.index')"
                        class="block px-4 py-2 rounded hover:bg-gray-700 transition flex items-center gap-3"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        Thực đơn
                    </Link>
                    <Link
                        :href="route('admin.orders.index')"
                        class="block px-4 py-2 rounded hover:bg-gray-700 transition flex items-center gap-3"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        Đơn hàng
                    </Link>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <button
                            @click="sidebarOpen = !sidebarOpen"
                            class="p-2 rounded hover:bg-gray-100 transition"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
                    </div>
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
            <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <slot />
            </main>
        </div>
    </div>
</template>
