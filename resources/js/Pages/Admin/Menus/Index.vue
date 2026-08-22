<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    menus: Array,
    categories: Array,
    branches: Array,
    filters: Object,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingMenu = ref(null);
const imagePreview = ref(null);
const selectedBranchId = ref(props.filters.branch_id || '');
const selectedCategoryId = ref(props.filters.category_id || '');
const searchQuery = ref(props.filters.search || '');
const page = usePage();
const isManager = page.props.auth.user.role === 'manager';

const form = useForm({
    category_id: '',
    branch_id: '',
    name: '',
    description: '',
    price: '',
    image: null,
    is_available: true,
    is_best_seller: false,
    is_must_try: false,
});

const applyFilters = () => {
    const params = {};

    if (!isManager && selectedBranchId.value) {
        params.branch_id = selectedBranchId.value;
    }
    if (selectedCategoryId.value) {
        params.category_id = selectedCategoryId.value;
    }
    if (searchQuery.value.trim()) {
        params.search = searchQuery.value.trim();
    }

    router.get(route('admin.menus.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(selectedBranchId, (value, oldValue) => {
    if (!isManager && value !== oldValue) {
        applyFilters();
    }
});

const openModal = (menu = null) => {
    if (menu) {
        isEditing.value = true;
        editingMenu.value = menu;
        form.category_id = menu.category_id;
        form.branch_id = menu.branch_id || selectedBranchId.value || '';
        form.name = menu.name;
        form.description = menu.description || '';
        form.price = menu.price;
        form.image = null;
        form.is_available = menu.is_available;
        form.is_best_seller = menu.is_best_seller;
        form.is_must_try = menu.is_must_try;
        if (menu.image) {
            imagePreview.value = `/storage/${menu.image}`;
        } else {
            imagePreview.value = null;
        }
    } else {
        isEditing.value = false;
        editingMenu.value = null;
        form.reset();
        form.is_available = true;
        form.is_best_seller = false;
        form.is_must_try = false;
        form.branch_id = selectedBranchId.value || '';
        imagePreview.value = null;
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingMenu.value = null;
    isEditing.value = false;
    imagePreview.value = null;
};

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    const formData = new FormData();
    formData.append('category_id', form.category_id);
    formData.append('branch_id', form.branch_id || '');
    formData.append('name', form.name);
    formData.append('description', form.description);
    formData.append('price', form.price);
    formData.append('is_available', form.is_available ? '1' : '0');
    formData.append('is_best_seller', form.is_best_seller ? '1' : '0');
    formData.append('is_must_try', form.is_must_try ? '1' : '0');
    if (form.image) {
        formData.append('image', form.image);
    }

    if (isEditing.value && editingMenu.value) {
        form.post(route('admin.menus.update', editingMenu.value.id), {
            data: formData,
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.menus.store'), {
            data: formData,
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteMenu = (menu) => {
    if (confirm('Bạn có chắc chắn muốn xóa món ăn này?')) {
        form.delete(route('admin.menus.destroy', menu.id));
    }
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
};
</script>

<template>
    <AdminLayout title="Quản lý Thực đơn">
        <div class="flex justify-between items-center mb-6">
            <button
                @click="openModal()"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
            >
                Thêm Món Ăn
            </button>
        </div>

        <!-- Filter -->
        <form @submit.prevent="applyFilters" class="bg-white shadow rounded-lg p-4 mb-6">
            <div
                class="grid gap-4 md:items-end"
                :class="isManager ? 'md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]' : 'md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto]'"
            >
                <div>
                    <label for="menu-search" class="block text-sm font-medium text-gray-700 mb-1">
                        Tìm theo tên món ăn
                    </label>
                    <input
                        id="menu-search"
                        v-model="searchQuery"
                        type="search"
                        placeholder="Nhập tên món ăn..."
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label for="menu-category" class="block text-sm font-medium text-gray-700 mb-1">
                        Lọc theo danh mục
                    </label>
                    <select
                        id="menu-category"
                        v-model="selectedCategoryId"
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Tất cả danh mục</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <div v-if="!isManager">
                    <label for="menu-branch" class="block text-sm font-medium text-gray-700 mb-1">
                        Lọc theo chi nhánh
                    </label>
                    <select
                        id="menu-branch"
                        v-model="selectedBranchId"
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                    Lọc
                </button>
            </div>
        </form>

        <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="admin-list-table">
                    <colgroup>
                        <col style="width: 10%;" />
                        <col style="width: 26%;" />
                        <col style="width: 13%;" />
                        <col style="width: 10%;" />
                        <col style="width: 12%;" />
                        <col style="width: 15%;" />
                        <col style="width: 14%;" />
                    </colgroup>
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Hình ảnh
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tên
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Danh mục
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Giá
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Trạng thái
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nổi bật
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Hành động
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="menu in menus" :key="menu.id">
                            <td class="px-6 py-4">
                                <img
                                    v-if="menu.image"
                                    :src="`/storage/${menu.image}`"
                                    :alt="menu.name"
                                    class="h-12 w-12 object-cover rounded"
                                />
                                <div v-else class="h-12 w-12 bg-gray-200 rounded flex items-center justify-center">
                                    <span class="text-gray-400 text-xs">No img</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ menu.name }}</div>
                                <div class="text-sm text-gray-500 text-xs">{{ menu.description || '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">{{ menu.category ? menu.category.name : '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ formatPrice(menu.price) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="menu.is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                >
                                    {{ menu.is_available ? 'Có sẵn' : 'Hết hàng' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-if="menu.is_best_seller"
                                        class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800"
                                    >
                                        Best Seller
                                    </span>
                                    <span
                                        v-if="menu.is_must_try"
                                        class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800"
                                    >
                                        Must Try
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <button
                                    @click="openModal(menu)"
                                    class="text-indigo-600 hover:text-indigo-900 mr-4"
                                >
                                    Sửa
                                </button>
                                <button
                                    @click="deleteMenu(menu)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Xóa
                                </button>
                            </td>
                        </tr>
                        <tr v-if="menus.length === 0">
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                Chưa có món ăn nào
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
            <div class="relative p-5 border w-[500px] shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    {{ isEditing ? 'Sửa Món Ăn' : 'Thêm Món Ăn Mới' }}
                </h3>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Danh mục *
                        </label>
                        <select
                            v-model="form.category_id"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="">Chọn danh mục</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="text-red-500 text-xs mt-1">{{ form.errors.category_id }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Chi nhánh
                        </label>
                        <select
                            v-model="form.branch_id"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :disabled="isManager"
                        >
                            <option value="">Tất cả chi nhánh</option>
                            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                                {{ branch.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.branch_id" class="text-red-500 text-xs mt-1">{{ form.errors.branch_id }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Tên món ăn *
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
                            Mô tả
                        </label>
                        <textarea
                            v-model="form.description"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            rows="3"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Giá *
                        </label>
                        <input
                            v-model="form.price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                        <div v-if="form.errors.price" class="text-red-500 text-xs mt-1">{{ form.errors.price }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Hình ảnh
                        </label>
                        <input
                            type="file"
                            @change="handleImageChange"
                            accept="image/*"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <div v-if="imagePreview" class="mt-2">
                            <img :src="imagePreview" alt="Preview" class="h-32 w-32 object-cover rounded" />
                        </div>
                        <div v-if="form.errors.image" class="text-red-500 text-xs mt-1">{{ form.errors.image }}</div>
                    </div>

                    <div class="mb-4 flex gap-4">
                        <label class="flex items-center">
                            <input
                                v-model="form.is_available"
                                type="checkbox"
                                class="mr-2"
                            />
                            <span class="text-sm text-gray-700">Có sẵn</span>
                        </label>

                        <label class="flex items-center">
                            <input
                                v-model="form.is_best_seller"
                                type="checkbox"
                                class="mr-2"
                            />
                            <span class="text-sm text-gray-700">Best Seller</span>
                        </label>

                        <label class="flex items-center">
                            <input
                                v-model="form.is_must_try"
                                type="checkbox"
                                class="mr-2"
                            />
                            <span class="text-sm text-gray-700">Must Try</span>
                        </label>
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
