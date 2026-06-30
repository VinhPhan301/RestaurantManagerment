<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    branches: Array,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingBranch = ref(null);

const form = useForm({
    name: '',
    address: '',
    phone: '',
    status: 'active',
});

const openModal = (branch = null) => {
    if (branch) {
        isEditing.value = true;
        editingBranch.value = branch;
        form.name = branch.name;
        form.address = branch.address || '';
        form.phone = branch.phone || '';
        form.status = branch.status;
    } else {
        isEditing.value = false;
        editingBranch.value = null;
        form.reset();
        form.status = 'active';
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingBranch.value = null;
    isEditing.value = false;
};

const submit = () => {
    if (isEditing.value && editingBranch.value) {
        form.put(route('admin.branches.update', editingBranch.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.branches.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteBranch = (branch) => {
    if (confirm('Bạn có chắc chắn muốn xóa chi nhánh này?')) {
        form.delete(route('admin.branches.destroy', branch.id));
    }
};

const toggleStatus = (branch) => {
    const newStatus = branch.status === 'active' ? 'inactive' : 'active';
    form.put(route('admin.branches.update', branch.id), {
        data: {
            name: branch.name,
            address: branch.address || '',
            phone: branch.phone || '',
            status: newStatus,
        },
    });
};
</script>

<template>
    <AdminLayout title="Quản lý Chi nhánh">
        <div class="flex justify-between items-center mb-6">
            <button
                @click="openModal()"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
            >
                Thêm Chi Nhánh
            </button>
        </div>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tên
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Địa chỉ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Điện thoại
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
                        <tr v-for="branch in branches" :key="branch.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ branch.name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">{{ branch.address || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">{{ branch.phone || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    @click="toggleStatus(branch)"
                                    :class="branch.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                >
                                    {{ branch.status === 'active' ? 'Hoạt động' : 'Tạm dừng' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button
                                    @click="openModal(branch)"
                                    class="text-indigo-600 hover:text-indigo-900 mr-4"
                                >
                                    Sửa
                                </button>
                                <button
                                    @click="deleteBranch(branch)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Xóa
                                </button>
                            </td>
                        </tr>
                        <tr v-if="branches.length === 0">
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                Chưa có chi nhánh nào
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
            <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    {{ isEditing ? 'Sửa Chi Nhánh' : 'Thêm Chi Nhánh Mới' }}
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
                            Địa chỉ
                        </label>
                        <input
                            v-model="form.address"
                            type="text"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Điện thoại
                        </label>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
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
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Tạm dừng</option>
                        </select>
                        <div v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</div>
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
