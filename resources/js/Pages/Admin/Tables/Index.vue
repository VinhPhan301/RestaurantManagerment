<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    tables: Object,
    branches: Array,
    filters: Object,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingTable = ref(null);
const selectedBranchId = ref(props.filters.branch_id || '');
const page = usePage();
const isManager = page.props.auth.user.role === 'manager';
const tableRows = computed(() => props.tables?.data || []);

const form = useForm({
    branch_id: '',
    name: '',
    capacity: 4,
    status: 'empty',
    reservation_customer_name: '',
    reservation_phone: '',
    reservation_time: '',
    reservation_note: '',
});

watch(selectedBranchId, (value) => {
    if (isManager) {
        return;
    }

    const params = new URLSearchParams();
    if (value) {
        params.set('branch_id', value);
    }
    window.location.href = route('admin.tables.index') + (value ? '?' + params.toString() : '');
});

const openModal = (table = null) => {
    if (table) {
        isEditing.value = true;
        editingTable.value = table;
        form.branch_id = table.branch_id;
        form.name = table.name;
        form.capacity = table.capacity;
        form.status = table.status;
        form.reservation_customer_name = table.reservation_customer_name || '';
        form.reservation_phone = table.reservation_phone || '';
        form.reservation_time = table.reservation_time ? table.reservation_time.slice(0, 16) : '';
        form.reservation_note = table.reservation_note || '';
    } else {
        isEditing.value = false;
        editingTable.value = null;
        form.reset();
        form.capacity = 4;
        form.status = 'empty';
        form.branch_id = selectedBranchId.value || '';
        form.reservation_customer_name = '';
        form.reservation_phone = '';
        form.reservation_time = '';
        form.reservation_note = '';
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingTable.value = null;
    isEditing.value = false;
};

const submit = () => {
    if (isEditing.value && editingTable.value) {
        form.put(route('admin.tables.update', editingTable.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.tables.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteTable = (table) => {
    if (confirm('Bạn có chắc chắn muốn xóa bàn này?')) {
        form.delete(route('admin.tables.destroy', table.id));
    }
};

const toggleStatus = (table) => {
    const newStatus = table.status === 'empty' ? 'occupied' : 'empty';
    form.put(route('admin.tables.update', table.id), {
        data: {
            branch_id: table.branch_id,
            name: table.name,
            capacity: table.capacity,
            status: newStatus,
            reservation_customer_name: table.reservation_customer_name,
            reservation_phone: table.reservation_phone,
            reservation_time: table.reservation_time,
            reservation_note: table.reservation_note,
        },
    });
};

const visitPage = (url) => {
    if (!url) {
        return;
    }

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusClass = (status) => {
    const classes = {
        empty: 'bg-green-100 text-green-800',
        occupied: 'bg-red-100 text-red-800',
        reserved: 'bg-amber-100 text-amber-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    const texts = {
        empty: 'Trống',
        occupied: 'Có khách',
        reserved: 'Đặt trước',
    };
    return texts[status] || status;
};
</script>

<template>
    <AdminLayout title="Quản lý Bàn">
        <div class="flex justify-between items-center mb-6">
            <button
                @click="openModal()"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
            >
                Thêm Bàn
            </button>
        </div>

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

        <!-- Tables Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="admin-list-table">
                <colgroup>
                    <col style="width: 14%;" />
                    <col style="width: 18%;" />
                    <col style="width: 12%;" />
                    <col style="width: 16%;" />
                    <col style="width: 26%;" />
                    <col style="width: 14%;" />
                </colgroup>
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tên bàn
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Chi nhánh
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sức chứa
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Trạng thái
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Khách đặt
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Hành động
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="table in tableRows" :key="table.id">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ table.name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">{{ table.branch ? table.branch.name : '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">{{ table.capacity }} người</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button
                                @click="toggleStatus(table)"
                                :class="getStatusClass(table.status)"
                                class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                            >
                                {{ getStatusText(table.status) }}
                            </button>
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="table.status === 'reserved'" class="text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ table.reservation_customer_name }}</div>
                                <div class="text-gray-500">{{ table.reservation_phone }}</div>
                                <div v-if="table.reservation_time_display" class="mt-1 text-xs font-semibold text-amber-700">{{ table.reservation_time_display }}</div>
                                <div v-if="table.reservation_note" class="mt-1 text-xs text-gray-500">{{ table.reservation_note }}</div>
                            </div>
                            <div v-else class="text-sm text-gray-400">-</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <button
                                @click="openModal(table)"
                                class="text-indigo-600 hover:text-indigo-900 mr-4"
                            >
                                Sửa
                            </button>
                            <button
                                @click="deleteTable(table)"
                                class="text-red-600 hover:text-red-900"
                            >
                                Xóa
                            </button>
                        </td>
                    </tr>
                    <tr v-if="tableRows.length === 0">
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Chưa có bàn nào
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="tables && tables.last_page > 1" class="mt-6 flex flex-wrap justify-center gap-1">
            <button
                v-for="(link, index) in tables.links"
                :key="`${index}-${link.label}`"
                type="button"
                :disabled="!link.url"
                @click="visitPage(link.url)"
                class="px-3 py-2 text-sm border rounded-md transition"
                :class="[
                    link.active
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                    !link.url ? 'opacity-50 cursor-not-allowed' : '',
                ]"
                v-html="link.label"
            ></button>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
            <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    {{ isEditing ? 'Sửa Bàn' : 'Thêm Bàn Mới' }}
                </h3>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Chi nhánh *
                        </label>
                        <select
                            v-model="form.branch_id"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                            :disabled="isManager"
                        >
                            <option value="">Chọn chi nhánh</option>
                            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                                {{ branch.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.branch_id" class="text-red-500 text-xs mt-1">{{ form.errors.branch_id }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Tên bàn *
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Sức chứa *
                        </label>
                        <input
                            v-model="form.capacity"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                        <div v-if="form.errors.capacity" class="text-red-500 text-xs mt-1">{{ form.errors.capacity }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Trạng thái *
                        </label>
                        <select
                            v-model="form.status"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="empty">Trống</option>
                            <option value="occupied">Có khách</option>
                            <option value="reserved">Đặt trước</option>
                        </select>
                        <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
                    </div>

                    <div v-if="form.status === 'reserved'" class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-3">
                        <div class="mb-3">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Tên khách *</label>
                            <input
                                v-model="form.reservation_customer_name"
                                type="text"
                                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            />
                            <div v-if="form.errors.reservation_customer_name" class="text-red-500 text-xs mt-1">{{ form.errors.reservation_customer_name }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Số điện thoại *</label>
                            <input
                                v-model="form.reservation_phone"
                                type="text"
                                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            />
                            <div v-if="form.errors.reservation_phone" class="text-red-500 text-xs mt-1">{{ form.errors.reservation_phone }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Thời gian đặt *</label>
                            <input
                                v-model="form.reservation_time"
                                type="datetime-local"
                                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            />
                            <div v-if="form.errors.reservation_time" class="text-red-500 text-xs mt-1">{{ form.errors.reservation_time }}</div>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Ghi chú</label>
                            <textarea
                                v-model="form.reservation_note"
                                rows="2"
                                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                            <div v-if="form.errors.reservation_note" class="text-red-500 text-xs mt-1">{{ form.errors.reservation_note }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition"
                        >
                            Hủy
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition disabled:opacity-50"
                        >
                            {{ isEditing ? 'Cập Nhật' : 'Thêm' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
