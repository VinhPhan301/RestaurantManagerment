<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    branches: Array,
    filters: Object,
    stats: Object,
});

const page = usePage();
const isManager = page.props.auth.user.role === 'manager';
const selectedBranchId = ref(props.filters.branch_id || '');

const maxRevenue = computed(() => {
    const values = props.stats.revenueByDay.map((day) => Number(day.revenue));
    return Math.max(...values, 1);
});

const maxStatusTotal = computed(() => {
    const values = props.stats.orderStatus.map((item) => Number(item.total));
    return Math.max(...values, 1);
});

watch(selectedBranchId, (value) => {
    if (isManager) {
        return;
    }

    const params = new URLSearchParams();
    if (value) {
        params.set('branch_id', value);
    }

    window.location.href = route('admin.dashboard') + (value ? '?' + params.toString() : '');
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price || 0);
};

const getStatusText = (status) => {
    const texts = {
        pending: 'Chờ bếp',
        serving: 'Đang phục vụ',
        paid: 'Đã thanh toán',
        cancelled: 'Đã hủy',
    };
    return texts[status] || status;
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-800',
        serving: 'bg-sky-100 text-sky-800',
        paid: 'bg-emerald-100 text-emerald-800',
        cancelled: 'bg-rose-100 text-rose-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const kpis = computed(() => [
    {
        label: 'Doanh thu hôm nay',
        value: formatPrice(props.stats.today.revenue),
        tone: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    },
    {
        label: 'Đơn hôm nay',
        value: props.stats.today.orders,
        tone: 'border-sky-200 bg-sky-50 text-sky-700',
    },
    {
        label: 'Bàn đang phục vụ',
        value: props.stats.today.occupiedTables,
        tone: 'border-violet-200 bg-violet-50 text-violet-700',
    },
    {
        label: 'Món chờ xử lý',
        value: props.stats.today.pendingItems,
        tone: 'border-amber-200 bg-amber-50 text-amber-700',
    },
]);
</script>

<template>
    <AdminLayout title="Dashboard quản trị">
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">Tổng quan vận hành</h2>
                    <p class="mt-1 text-sm text-gray-500">Theo dõi doanh thu, đơn hàng và món bán chạy trong hệ thống.</p>
                </div>

                <div v-if="!isManager" class="flex items-center gap-3 rounded-lg bg-white px-4 py-3 shadow">
                    <label class="text-sm font-medium text-gray-700">Lọc theo chi nhánh</label>
                    <select
                        v-model="selectedBranchId"
                        class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">Tất cả chi nhánh</option>
                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                            {{ branch.name }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    class="rounded-lg border bg-white p-5 shadow-sm"
                >
                    <div :class="kpi.tone" class="mb-4 inline-flex rounded-md border px-3 py-1 text-xs font-semibold">
                        {{ kpi.label }}
                    </div>
                    <div class="text-2xl font-bold text-gray-900">{{ kpi.value }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <section class="rounded-lg bg-white p-6 shadow xl:col-span-2">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Doanh thu 7 ngày gần nhất</h3>
                        <span class="text-sm text-gray-500">Chỉ tính đơn đã thanh toán</span>
                    </div>
                    <div class="flex h-72 items-end gap-3">
                        <div
                            v-for="day in stats.revenueByDay"
                            :key="day.date"
                            class="flex min-w-0 flex-1 flex-col items-center gap-3"
                        >
                            <div class="flex h-52 w-full items-end rounded-md bg-gray-100 px-2">
                                <div
                                    class="w-full rounded-t-md bg-emerald-500 transition-all"
                                    :style="{ height: `${Math.max((day.revenue / maxRevenue) * 100, day.revenue > 0 ? 8 : 0)}%` }"
                                ></div>
                            </div>
                            <div class="text-center">
                                <div class="text-xs font-semibold text-gray-700">{{ day.label }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ formatPrice(day.revenue) }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-6 text-lg font-semibold text-gray-900">Trạng thái đơn</h3>
                    <div class="space-y-4">
                        <div v-for="item in stats.orderStatus" :key="item.status">
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-700">{{ getStatusText(item.status) }}</span>
                                <span class="text-gray-500">{{ item.total }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-gray-100">
                                <div
                                    class="h-3 rounded-full bg-sky-500"
                                    :style="{ width: `${(item.total / maxStatusTotal) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900">Món được đặt nhiều trong tháng</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Món</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Số lượng</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="item in stats.topMenus" :key="item.id">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ item.name }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-600">{{ item.quantity }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ formatPrice(item.revenue) }}</td>
                                </tr>
                                <tr v-if="stats.topMenus.length === 0">
                                    <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">Chưa có dữ liệu món trong tháng</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900">Đơn gần đây</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="order in stats.recentOrders"
                            :key="order.id"
                            class="flex items-center justify-between gap-4 px-6 py-4"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ order.order_code }}</span>
                                    <span :class="getStatusClass(order.status)" class="rounded-full px-2 py-1 text-xs font-semibold">
                                        {{ getStatusText(order.status) }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-gray-500">
                                    {{ order.table || 'Không có bàn' }} · {{ order.branch || 'Không có chi nhánh' }} · {{ order.created_at }}
                                </div>
                            </div>
                            <div class="shrink-0 text-sm font-semibold text-gray-900">{{ formatPrice(order.total_price) }}</div>
                        </div>
                        <div v-if="stats.recentOrders.length === 0" class="px-6 py-8 text-center text-sm text-gray-500">
                            Chưa có đơn hàng gần đây
                        </div>
                    </div>
                </section>
            </div>

            <section class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Quản trị nhanh</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Link
                        v-if="page.props.auth.user.role === 'admin'"
                        :href="route('admin.branches.index')"
                        class="rounded-lg border border-blue-200 bg-blue-50 p-4 transition hover:bg-blue-100"
                    >
                        <div class="font-semibold text-blue-900">Chi nhánh</div>
                        <div class="mt-1 text-sm text-blue-700">Quản lý chi nhánh</div>
                    </Link>
                    <Link :href="route('admin.menus.index')" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 transition hover:bg-emerald-100">
                        <div class="font-semibold text-emerald-900">Thực đơn</div>
                        <div class="mt-1 text-sm text-emerald-700">Quản lý món ăn</div>
                    </Link>
                    <Link :href="route('admin.users.index')" class="rounded-lg border border-violet-200 bg-violet-50 p-4 transition hover:bg-violet-100">
                        <div class="font-semibold text-violet-900">Nhân sự</div>
                        <div class="mt-1 text-sm text-violet-700">Quản lý nhân viên</div>
                    </Link>
                    <Link :href="route('admin.orders.index')" class="rounded-lg border border-amber-200 bg-amber-50 p-4 transition hover:bg-amber-100">
                        <div class="font-semibold text-amber-900">Đơn hàng</div>
                        <div class="mt-1 text-sm text-amber-700">Theo dõi đơn</div>
                    </Link>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
