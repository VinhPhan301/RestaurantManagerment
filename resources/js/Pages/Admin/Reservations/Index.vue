<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    requests: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const isManager = page.props.auth.user.role === 'manager';
const selectedBranchId = ref(props.filters.branch_id || '');
const selectedStatus = ref(props.filters.status || '');
const selectedReservation = ref(null);
const selectedTableId = ref(null);
const updatingId = ref(null);
const statusForm = useForm({
    status: '',
    table_id: null,
});

const statusOptions = [
    { value: 'pending', label: 'Chờ xử lý' },
    { value: 'confirmed', label: 'Đã xác nhận' },
    { value: 'rejected', label: 'Từ chối' },
];

const summary = computed(() => ({
    total: props.requests.length,
    pending: props.requests.filter((request) => request.status === 'pending').length,
    confirmed: props.requests.filter((request) => request.status === 'confirmed').length,
    rejected: props.requests.filter((request) => request.status === 'rejected').length,
}));

const selectedAvailableTables = computed(() => selectedReservation.value?.available_tables || []);

const applyFilters = () => {
    const params = new URLSearchParams();

    if (!isManager && selectedBranchId.value) {
        params.set('branch_id', selectedBranchId.value);
    }

    if (selectedStatus.value) {
        params.set('status', selectedStatus.value);
    }

    const query = params.toString();
    window.location.href = route('admin.reservations.index') + (query ? `?${query}` : '');
};

const openConfirmModal = (reservation) => {
    selectedReservation.value = reservation;
    selectedTableId.value = reservation.available_tables?.[0]?.id || null;
    statusForm.clearErrors();
};

const closeConfirmModal = () => {
    selectedReservation.value = null;
    selectedTableId.value = null;
    statusForm.reset();
    statusForm.clearErrors();
};

const confirmReservation = () => {
    if (!selectedReservation.value || !selectedTableId.value) {
        return;
    }

    statusForm.status = 'confirmed';
    statusForm.table_id = selectedTableId.value;
    updatingId.value = selectedReservation.value.id;

    statusForm.patch(route('admin.reservations.status', selectedReservation.value.id), {
        preserveScroll: true,
        onSuccess: () => closeConfirmModal(),
        onFinish: () => {
            updatingId.value = null;
        },
    });
};

const rejectReservation = (reservation) => {
    if (!window.confirm('Bạn có chắc muốn từ chối yêu cầu đặt bàn này?')) {
        return;
    }

    statusForm.status = 'rejected';
    statusForm.table_id = null;
    updatingId.value = reservation.id;

    statusForm.patch(route('admin.reservations.status', reservation.id), {
        preserveScroll: true,
        onFinish: () => {
            updatingId.value = null;
        },
    });
};

const statusText = (status) => statusOptions.find((option) => option.value === status)?.label || status;

const statusClass = (status) => ({
    pending: 'bg-amber-100 text-amber-800',
    confirmed: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-rose-100 text-rose-800',
}[status] || 'bg-gray-100 text-gray-800');
</script>

<template>
    <AdminLayout title="Yêu cầu đặt bàn">
        <div class="mb-6 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-gray-500">Theo dõi và xử lý thông tin khách gửi từ landing page.</p>
            </div>
            <p v-if="isManager && branches[0]" class="text-sm font-medium text-gray-600">
                Chi nhánh: <span class="font-semibold text-gray-900">{{ branches[0].name }}</span>
            </p>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-lg bg-white p-4 shadow">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Tổng yêu cầu</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ summary.total }}</p>
            </div>
            <div class="rounded-lg bg-amber-50 p-4 shadow">
                <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Chờ xử lý</p>
                <p class="mt-2 text-2xl font-bold text-amber-900">{{ summary.pending }}</p>
            </div>
            <div class="rounded-lg bg-emerald-50 p-4 shadow">
                <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Đã xác nhận</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900">{{ summary.confirmed }}</p>
            </div>
            <div class="rounded-lg bg-rose-50 p-4 shadow">
                <p class="text-xs font-medium uppercase tracking-wide text-rose-700">Từ chối</p>
                <p class="mt-2 text-2xl font-bold text-rose-900">{{ summary.rejected }}</p>
            </div>
        </div>

        <div class="mb-6 rounded-lg bg-white p-4 shadow">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div v-if="!isManager" class="flex-1">
                    <label for="branch-filter" class="mb-2 block text-sm font-medium text-gray-700">Lọc theo chi nhánh</label>
                    <select id="branch-filter" v-model="selectedBranchId" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Tất cả chi nhánh</option>
                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label for="status-filter" class="mb-2 block text-sm font-medium text-gray-700">Lọc theo trạng thái</label>
                    <select id="status-filter" v-model="selectedStatus" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Tất cả trạng thái</option>
                        <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                </div>
                <button type="button" class="rounded-md bg-gray-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700" @click="applyFilters">Lọc dữ liệu</button>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="overflow-x-auto">
                <table class="admin-list-table">
                    <colgroup>
                        <col style="width: 18%;" />
                        <col style="width: 16%;" />
                        <col style="width: 14%;" />
                        <col style="width: 8%;" />
                        <col style="width: 10%;" />
                        <col style="width: 14%;" />
                        <col style="width: 20%;" />
                    </colgroup>
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Khách hàng</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Thời gian</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Chi nhánh</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Số khách</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Bàn</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Ghi chú</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Trạng thái / thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="reservation in requests" :key="reservation.id" class="align-top">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ reservation.customer_name }}</p>
                                <a :href="`tel:${reservation.phone}`" class="mt-1 block text-sm text-blue-600 hover:text-blue-800">{{ reservation.phone }}</a>
                                <p class="mt-1 text-xs text-gray-400">Gửi lúc {{ reservation.created_at }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                <p class="font-semibold">{{ reservation.reservation_date }}</p>
                                <p class="mt-1 text-gray-500">{{ reservation.reservation_time }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ reservation.branch?.name || '-' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ reservation.guests }} người</td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                <template v-if="reservation.table">
                                    <p class="font-semibold text-gray-900">{{ reservation.table.name }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ reservation.table.capacity }} chỗ</p>
                                </template>
                                <span v-else>-</span>
                            </td>
                            <td class="max-w-xs px-5 py-4 text-sm text-gray-500">{{ reservation.note || 'Không có ghi chú' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(reservation.status)">{{ statusText(reservation.status) }}</span>
                                <div v-if="reservation.status === 'pending'" class="mt-3 flex flex-col items-start gap-2">
                                    <button type="button" class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="statusForm.processing && updatingId === reservation.id" @click="openConfirmModal(reservation)">
                                        Xác nhận & chọn bàn
                                    </button>
                                    <button type="button" class="rounded-md border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="statusForm.processing && updatingId === reservation.id" @click="rejectReservation(reservation)">
                                        Từ chối
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="requests.length === 0">
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500">Chưa có yêu cầu đặt bàn nào.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="selectedReservation" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="reservation-table-modal-title" @click.self="closeConfirmModal">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="reservation-table-modal-title" class="text-lg font-bold text-gray-900">Chọn bàn xác nhận</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ selectedReservation.customer_name }} · {{ selectedReservation.reservation_date }} lúc {{ selectedReservation.reservation_time }}</p>
                    </div>
                    <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Đóng" @click="closeConfirmModal">&times;</button>
                </div>

                <div v-if="statusForm.errors.table_id" class="mt-4 rounded-md bg-rose-50 p-3 text-sm font-medium text-rose-700">{{ statusForm.errors.table_id }}</div>

                <div v-if="selectedAvailableTables.length" class="mt-5 grid max-h-72 gap-3 overflow-y-auto sm:grid-cols-2">
                    <button v-for="table in selectedAvailableTables" :key="table.id" type="button" class="rounded-lg border p-4 text-left transition" :class="selectedTableId === table.id ? 'border-emerald-600 bg-emerald-50 ring-2 ring-emerald-200' : 'border-gray-200 hover:border-emerald-400 hover:bg-emerald-50/50'" @click="selectedTableId = table.id">
                        <p class="font-bold text-gray-900">{{ table.name }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ table.capacity }} chỗ</p>
                    </button>
                </div>
                <div v-else class="mt-5 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">Hiện không còn bàn trống tại chi nhánh này.</div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="closeConfirmModal">Hủy</button>
                    <button type="button" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!selectedTableId || statusForm.processing" @click="confirmReservation">
                        {{ statusForm.processing ? 'Đang xác nhận...' : 'Xác nhận đặt bàn' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
