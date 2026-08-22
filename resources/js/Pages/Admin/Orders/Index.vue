<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    orders: Array,
    branches: Array,
    filters: Object,
});

const selectedBranchId = ref(props.filters.branch_id || '');
const page = usePage();
const isManager = page.props.auth.user.role === 'manager';
const form = useForm({});

watch(selectedBranchId, (value) => {
    if (isManager) {
        return;
    }

    const params = new URLSearchParams();
    if (value) {
        params.set('branch_id', value);
    }
    window.location.href = route('admin.orders.index') + (value ? '?' + params.toString() : '');
});

const updateStatus = (order, status) => {
    form.put(route('admin.orders.update', order.id), {
        data: { status },
    });
};

const deleteOrder = (order) => {
    if (confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')) {
        form.delete(route('admin.orders.destroy', order.id));
    }
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        serving: 'bg-blue-100 text-blue-800',
        paid: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
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
</script>

<template>
    <AdminLayout title="Quản lý Đơn hàng">
        <!-- Filter -->
        <div v-if="!isManager" class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="flex items-center gap-4">
                <label class="text-gray-700 font-medium">Lọc theo chi nhánh:</label>
                <select
                    v-model="selectedBranchId"
                    class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Tất cả chi nhánh</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                        {{ branch.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="admin-list-table">
                <colgroup>
                    <col style="width: 14%;" />
                    <col style="width: 11%;" />
                    <col style="width: 14%;" />
                    <col style="width: 14%;" />
                    <col style="width: 14%;" />
                    <col style="width: 17%;" />
                    <col style="width: 16%;" />
                </colgroup>
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mã đơn
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Bàn
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Chi nhánh
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nhân viên
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tổng tiền
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Trạng thái
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Hành động
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template v-for="order in orders" :key="order.id">
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ order.order_code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">{{ order.table ? order.table.name : '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">{{ order.branch ? order.branch.name : '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">{{ order.user ? order.user.name : '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ formatPrice(order.total_price) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <select
                                    :value="order.status"
                                    @change="updateStatus(order, $event.target.value)"
                                    :class="getStatusClass(order.status)"
                                    class="max-w-full px-2 py-1 text-xs leading-5 font-semibold rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="pending">Chờ bếp</option>
                                    <option value="serving">Đang phục vụ</option>
                                    <option value="paid">Đã thanh toán</option>
                                    <option value="cancelled">Đã hủy</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <button
                                    @click="deleteOrder(order)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Xóa
                                </button>
                            </td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td colspan="7" class="px-6 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="item in order.items"
                                        :key="item.id"
                                        class="rounded-md border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700"
                                    >
                                        <span class="font-semibold">{{ item.menu ? item.menu.name : 'Món đã xóa' }}</span>
                                        <span> x{{ item.quantity }}</span>
                                        <span class="text-gray-500"> · {{ item.status }}</span>
                                    </span>
                                    <span v-if="order.items.length === 0" class="text-xs text-gray-500">Chưa có món</span>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="orders.length === 0">
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            Chưa có đơn hàng nào
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
