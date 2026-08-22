<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Object,
    branches: Array,
    filters: Object,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingUser = ref(null);
const selectedBranchId = ref(props.filters.branch_id || '');
const search = ref(props.filters.search || '');
const page = usePage();
const isManager = page.props.auth.user.role === 'manager';

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'staff',
    branch_id: '',
});

const applyFilters = () => {
    const params = {};

    if (selectedBranchId.value) {
        params.branch_id = selectedBranchId.value;
    }

    if (search.value.trim()) {
        params.search = search.value.trim();
    }

    router.get(route('admin.users.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';

    if (isManager) {
        applyFilters();
        return;
    }

    selectedBranchId.value = '';
};

watch(selectedBranchId, () => {
    if (isManager) {
        return;
    }

    applyFilters();
});

const openModal = (user = null) => {
    if (user) {
        isEditing.value = true;
        editingUser.value = user;
        form.name = user.name;
        form.email = user.email;
        form.role = user.role;
        form.branch_id = user.branch_id;
        form.password = '';
    } else {
        isEditing.value = false;
        editingUser.value = null;
        form.reset();
        form.role = 'staff';
        form.branch_id = selectedBranchId.value || '';
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingUser.value = null;
    isEditing.value = false;
};

const submit = () => {
    if (isEditing.value && editingUser.value) {
        const data = {
            name: form.name,
            email: form.email,
            role: form.role,
            branch_id: form.branch_id,
        };
        if (form.password) {
            data.password = form.password;
        }
        form.put(route('admin.users.update', editingUser.value.id), {
            data,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const paginationLabel = (label) => label
    .replace('&laquo;', '«')
    .replace('&raquo;', '»');

const deleteUser = (user) => {
    if (confirm('Bạn có chắc chắn muốn xóa nhân viên này?')) {
        form.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <AdminLayout title="Quản lý Nhân sự">
        <div class="flex justify-between items-center mb-6">
            <button
                @click="openModal()"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
            >
                Thêm Nhân Viên
            </button>
        </div>
            <!-- Filter -->
            <div class="bg-white shadow rounded-lg p-4 mb-6">
                <form class="flex flex-col md:flex-row md:items-end gap-4" @submit.prevent="applyFilters">
                    <div class="flex-1">
                        <label for="user-search" class="block text-gray-700 font-medium mb-2">Tìm theo tên hoặc email:</label>
                        <input
                            id="user-search"
                            v-model="search"
                            type="search"
                            placeholder="Nhập tên hoặc email nhân viên"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div v-if="!isManager">
                        <label for="user-branch" class="block text-gray-700 font-medium mb-2">Lọc theo chi nhánh:</label>
                    <select
                        id="user-branch"
                        v-model="selectedBranchId"
                        class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Tất cả chi nhánh</option>
                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                            {{ branch.name }}
                        </option>
                    </select>
                    </div>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
                    >
                        Tìm kiếm
                    </button>
                    <button
                        v-if="search || (!isManager && selectedBranchId)"
                        type="button"
                        @click="clearFilters"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
                    >
                        Xóa lọc
                    </button>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="admin-list-table">
                    <colgroup>
                        <col style="width: 22%;" />
                        <col style="width: 27%;" />
                        <col style="width: 14%;" />
                        <col style="width: 19%;" />
                        <col style="width: 18%;" />
                    </colgroup>
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tên
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Vai trò
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Chi nhánh
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Hành động
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">{{ user.email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="user.role === 'manager' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'"
                                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                >
                                    {{ user.role === 'manager' ? 'Manager' : 'Staff' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">{{ user.branch ? user.branch.name : '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <button
                                    @click="openModal(user)"
                                    class="text-indigo-600 hover:text-indigo-900 mr-4"
                                >
                                    Sửa
                                </button>
                                <button
                                    @click="deleteUser(user)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Xóa
                                </button>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                Không tìm thấy nhân viên nào
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="users.last_page > 1" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-200 px-6 py-4">
                    <p class="text-sm text-gray-500">
                        Hiển thị {{ users.from }}-{{ users.to }} trong tổng số {{ users.total }} nhân viên
                    </p>
                    <nav class="flex flex-wrap gap-1" aria-label="Phân trang nhân viên">
                        <template v-for="link in users.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="px-3 py-1 rounded border text-sm transition"
                                :class="link.active
                                    ? 'bg-blue-600 border-blue-600 text-white'
                                    : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-100'"
                            >
                                {{ paginationLabel(link.label) }}
                            </Link>
                            <span
                                v-else
                                class="px-3 py-1 rounded border border-gray-200 text-sm text-gray-400"
                            >
                                {{ paginationLabel(link.label) }}
                            </span>
                        </template>
                    </nav>
                </div>
            </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
            <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    {{ isEditing ? 'Sửa Nhân Viên' : 'Thêm Nhân Viên Mới' }}
                </h3>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Tên *
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
                            Email *
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                        <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            {{ isEditing ? 'Mật khẩu (để trống nếu không đổi)' : 'Mật khẩu *' }}
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :required="!isEditing"
                        />
                        <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Vai trò *
                        </label>
                        <select
                            v-model="form.role"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                            :disabled="isManager && !isEditing"
                        >
                            <option value="staff">Staff</option>
                            <option
                                v-if="!isManager || (isEditing && form.role === 'manager')"
                                value="manager"
                            >
                                Manager
                            </option>
                        </select>
                        <div v-if="form.errors.role" class="text-red-500 text-xs mt-1">{{ form.errors.role }}</div>
                    </div>

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
