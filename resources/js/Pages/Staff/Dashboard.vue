<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    branch: Object,
    tables: Array,
    menus: Array,
    categories: Array,
});

const selectedTable = ref(null);
const selectedCategory = ref('all');
const mode = ref('tables');
const cart = ref([]);
const moveTargetId = ref('');
const isAddingItems = ref(false);

const orderForm = useForm({
    table_id: null,
    items: [],
});

const moveForm = useForm({
    current_table_id: null,
    target_table_id: null,
});

const reservationForm = useForm({
    table_id: null,
    reservation_customer_name: '',
    reservation_phone: '',
    reservation_time: '',
    reservation_note: '',
});

const cancelReservationForm = useForm({
    table_id: null,
});

const checkoutForm = useForm({
    table_id: null,
});

const specialCategoryNames = new Set(['best seller', 'must try']);

const visibleCategories = computed(() => props.categories.filter((category) => (
    !specialCategoryNames.has(category.name.trim().toLowerCase())
)));

const tabs = computed(() => [
    { key: 'all', label: 'Tất cả' },
    { key: 'best-seller', label: 'Best Seller' },
    { key: 'must-try', label: 'Must Try' },
    ...visibleCategories.value.map((category) => ({
        key: `category-${category.id}`,
        label: category.name,
    })),
]);

const filteredMenus = computed(() => {
    if (selectedCategory.value === 'best-seller') {
        return props.menus.filter((menu) => menu.is_best_seller);
    }

    if (selectedCategory.value === 'must-try') {
        return props.menus.filter((menu) => menu.is_must_try);
    }

    if (selectedCategory.value.startsWith('category-')) {
        const categoryId = Number(selectedCategory.value.replace('category-', ''));
        return props.menus.filter((menu) => menu.category_id === categoryId);
    }

    return props.menus;
});

const emptyTables = computed(() => props.tables.filter((table) => table.status === 'empty'));

const cartTotal = computed(() => cart.value.reduce((total, item) => total + item.price * item.quantity, 0));

const selectedOrderTotal = computed(() => {
    if (!selectedTable.value?.active_order) {
        return 0;
    }

    return selectedTable.value.active_order.items.reduce((total, item) => total + item.price * item.quantity, 0);
});

const readyItemsCount = computed(() => {
    if (!selectedTable.value?.active_order) {
        return 0;
    }

    return selectedTable.value.active_order.items.filter((item) => item.status === 'ready').length;
});

const formatPrice = (price) => new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
}).format(Number(price || 0));

const selectTable = (table) => {
    selectedTable.value = table;
    moveTargetId.value = '';
    orderForm.clearErrors();
    moveForm.clearErrors();
    reservationForm.clearErrors();
    cancelReservationForm.clearErrors();
    checkoutForm.clearErrors();

    if (table.status === 'empty') {
        mode.value = 'order';
        isAddingItems.value = false;
        cart.value = [];
        orderForm.table_id = table.id;
        reservationForm.table_id = table.id;
        cancelReservationForm.table_id = null;
    } else if (table.status === 'reserved') {
        mode.value = 'reserved';
        isAddingItems.value = false;
        cart.value = [];
        orderForm.table_id = table.id;
        reservationForm.table_id = null;
        cancelReservationForm.table_id = table.id;
    } else {
        mode.value = 'checkout';
        isAddingItems.value = false;
        cart.value = [];
        checkoutForm.table_id = table.id;
        moveForm.current_table_id = table.id;
        orderForm.table_id = table.id;
    }
};

const addMenu = (menu) => {
    if (!selectedTable.value || (selectedTable.value.status !== 'empty' && !isAddingItems.value)) {
        return;
    }

    const existing = cart.value.find((item) => item.menu_id === menu.id);

    if (existing) {
        existing.quantity += 1;
        return;
    }

    cart.value.push({
        menu_id: menu.id,
        name: menu.name,
        price: Number(menu.price),
        quantity: 1,
        notes: '',
    });
};

const increase = (item) => {
    item.quantity += 1;
};

const decrease = (item) => {
    if (item.quantity > 1) {
        item.quantity -= 1;
        return;
    }

    cart.value = cart.value.filter((cartItem) => cartItem.menu_id !== item.menu_id);
};

const removeItem = (item) => {
    cart.value = cart.value.filter((cartItem) => cartItem.menu_id !== item.menu_id);
};

const submitOrder = () => {
    orderForm.table_id = selectedTable.value?.id;
    orderForm.items = cart.value.map((item) => ({
        menu_id: item.menu_id,
        quantity: item.quantity,
        notes: item.notes,
    }));

    orderForm.post(route('staff.orders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            isAddingItems.value = false;
            selectedTable.value = null;
            mode.value = 'tables';
        },
    });
};

const moveTable = () => {
    moveForm.current_table_id = selectedTable.value?.id;
    moveForm.target_table_id = moveTargetId.value;

    moveForm.post(route('staff.tables.move'), {
        preserveScroll: true,
        onSuccess: () => {
            moveTargetId.value = '';
            selectedTable.value = null;
            mode.value = 'tables';
        },
    });
};

const checkout = () => {
    checkoutForm.table_id = selectedTable.value?.id;

    checkoutForm.post(route('staff.checkout'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedTable.value = null;
            mode.value = 'tables';
        },
    });
};

const reserveTable = () => {
    reservationForm.table_id = selectedTable.value?.id;

    reservationForm.post(route('staff.tables.reserve'), {
        preserveScroll: true,
        onSuccess: () => {
            reservationForm.reset();
            selectedTable.value = null;
            mode.value = 'tables';
        },
    });
};

const cancelReservation = () => {
    cancelReservationForm.table_id = selectedTable.value?.id;

    cancelReservationForm.post(route('staff.tables.cancel-reservation'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedTable.value = null;
            mode.value = 'tables';
        },
    });
};

const closePanel = () => {
    selectedTable.value = null;
    cart.value = [];
    moveTargetId.value = '';
    isAddingItems.value = false;
    mode.value = 'tables';
    orderForm.clearErrors();
    moveForm.clearErrors();
    reservationForm.reset();
    reservationForm.clearErrors();
    cancelReservationForm.clearErrors();
    checkoutForm.clearErrors();
};

const startAddItems = () => {
    cart.value = [];
    orderForm.table_id = selectedTable.value?.id;
    orderForm.clearErrors();
    isAddingItems.value = true;
};

const cancelAddItems = () => {
    cart.value = [];
    isAddingItems.value = false;
    orderForm.clearErrors();
};

const statusText = (status) => {
    const labels = {
        ordered: 'Mới gọi',
        cooking: 'Đang làm',
        ready: 'Chờ phục vụ',
        served: 'Đã phục vụ',
    };

    return labels[status] || status;
};

const statusClass = (status) => {
    const classes = {
        ordered: 'bg-amber-100 text-amber-800',
        cooking: 'bg-sky-100 text-sky-800',
        ready: 'bg-rose-100 text-rose-800 ring-1 ring-rose-300',
        served: 'bg-emerald-100 text-emerald-800',
    };

    return classes[status] || 'bg-slate-100 text-slate-700';
};

const tableStatusText = (status) => {
    const labels = {
        empty: 'Trống',
        reserved: 'Đặt trước',
        occupied: 'Đang có khách',
    };

    return labels[status] || status;
};

const tableStatusClass = (table) => {
    if (table.status === 'empty') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-950';
    }

    if (table.status === 'reserved') {
        return 'border-amber-200 bg-amber-50 text-amber-950';
    }

    return 'border-rose-200 bg-rose-50 text-rose-950';
};
</script>

<template>
    <Head title="POS" />

    <div class="min-h-screen bg-slate-100">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-[1600px] items-center justify-between px-4 py-3 sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">POS</p>
                    <h1 class="text-xl font-bold text-slate-950">{{ branch?.name || 'Chi nhánh' }}</h1>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('kitchen.index')"
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Màn bếp
                    </Link>
                    <Link
                        :href="route('staff.logout')"
                        method="post"
                        as="button"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                    >
                        Đăng xuất
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto grid max-w-[1600px] gap-4 px-4 py-4 lg:grid-cols-[minmax(280px,0.95fr)_minmax(420px,1.45fr)_minmax(360px,0.9fr)]">
            <section class="min-h-[calc(100vh-104px)] rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Sơ đồ bàn</h2>
                        <p class="text-sm text-slate-500">{{ tables.length }} bàn đang quản lý</p>
                    </div>
                    <button
                        type="button"
                        @click="closePanel"
                        class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Làm mới chọn
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3">
                    <button
                        v-for="table in tables"
                        :key="table.id"
                        type="button"
                        @click="selectTable(table)"
                        class="aspect-[1.15/1] rounded-lg border p-3 text-left transition hover:-translate-y-0.5 hover:shadow-md"
                        :class="[
                            tableStatusClass(table),
                            selectedTable?.id === table.id ? 'ring-2 ring-slate-900' : ''
                        ]"
                    >
                        <div class="flex h-full flex-col justify-between">
                            <div>
                                <div class="text-lg font-bold">{{ table.name }}</div>
                                <div class="text-xs font-medium opacity-75">{{ table.capacity }} chỗ</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold">
                                    {{ tableStatusText(table.status) }}
                                </div>
                                <div v-if="table.status === 'reserved'" class="mt-1 truncate text-xs opacity-80">
                                    {{ table.reservation_customer_name }} · {{ table.reservation_phone }}
                                </div>
                                <div v-if="table.status === 'reserved' && table.reservation_time_display" class="mt-1 truncate text-xs font-semibold opacity-90">
                                    {{ table.reservation_time_display }}
                                </div>
                                <div v-if="table.active_order" class="mt-1 truncate text-xs opacity-80">
                                    {{ table.active_order.order_code }}
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </section>

            <section class="flex h-[calc(100vh-104px)] flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex shrink-0 flex-wrap gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        @click="selectedCategory = tab.key"
                        class="rounded-md border px-3 py-2 text-sm font-semibold transition"
                        :class="selectedCategory === tab.key ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-700 hover:bg-slate-50'"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div class="grid min-h-0 flex-1 auto-rows-min content-start items-start gap-3 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-3">
                    <button
                        v-for="menu in filteredMenus"
                        :key="menu.id"
                        type="button"
                        @click="addMenu(menu)"
                        class="rounded-lg border border-slate-200 bg-white p-3 text-left shadow-sm transition hover:border-slate-400 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!selectedTable || (selectedTable.status !== 'empty' && !isAddingItems)"
                    >
                        <div class="space-y-2">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="font-bold text-slate-950">{{ menu.name }}</h3>
                                    <span class="whitespace-nowrap text-sm font-bold text-emerald-700">{{ formatPrice(menu.price) }}</span>
                                </div>
                                <p class="mt-1 line-clamp-1 text-sm text-slate-500">{{ menu.description || menu.category?.name || 'Món ăn' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <span v-if="menu.is_best_seller" class="rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Best Seller</span>
                                <span v-if="menu.is_must_try" class="rounded bg-cyan-100 px-2 py-1 text-xs font-semibold text-cyan-800">Must Try</span>
                            </div>
                        </div>
                    </button>

                    <div v-if="filteredMenus.length === 0" class="col-span-full rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
                        Chưa có món phù hợp.
                    </div>
                </div>
            </section>

            <aside class="flex h-[calc(100vh-104px)] flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div v-if="!selectedTable" class="flex h-full items-center justify-center text-center">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Chọn bàn để bắt đầu</h2>
                        <p class="mt-2 text-sm text-slate-500">Bàn trống sẽ mở giỏ gọi món, bàn đang có khách sẽ mở hóa đơn.</p>
                    </div>
                </div>

                <div v-else-if="mode === 'order'" class="flex h-full min-h-0 flex-col">
                    <div class="mb-4 flex shrink-0 items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-emerald-700">Gọi món</p>
                            <h2 class="text-xl font-bold text-slate-950">{{ selectedTable.name }}</h2>
                        </div>
                        <button type="button" @click="closePanel" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Đóng
                        </button>
                    </div>

                    <div class="mb-4 shrink-0 rounded-lg border border-amber-200 bg-amber-50 p-3">
                        <div class="mb-3">
                            <p class="text-sm font-bold text-amber-800">Đặt bàn trước</p>
                            <p class="text-xs text-slate-600">Dùng khi khách gọi điện giữ bàn.</p>
                        </div>
                        <div class="space-y-2">
                            <input
                                v-model="reservationForm.reservation_customer_name"
                                type="text"
                                class="w-full rounded-md border-amber-200 text-sm focus:border-amber-500 focus:ring-amber-500"
                                placeholder="Tên khách"
                            />
                            <input
                                v-model="reservationForm.reservation_phone"
                                type="text"
                                class="w-full rounded-md border-amber-200 text-sm focus:border-amber-500 focus:ring-amber-500"
                                placeholder="Số điện thoại"
                            />
                            <input
                                v-model="reservationForm.reservation_time"
                                type="datetime-local"
                                class="w-full rounded-md border-amber-200 text-sm focus:border-amber-500 focus:ring-amber-500"
                            />
                            <textarea
                                v-model="reservationForm.reservation_note"
                                rows="2"
                                class="w-full rounded-md border-amber-200 text-sm focus:border-amber-500 focus:ring-amber-500"
                                placeholder="Ghi chú"
                            ></textarea>
                            <div v-if="reservationForm.errors.table_id" class="text-xs font-semibold text-rose-700">{{ reservationForm.errors.table_id }}</div>
                            <div v-if="reservationForm.errors.reservation_customer_name" class="text-xs font-semibold text-rose-700">{{ reservationForm.errors.reservation_customer_name }}</div>
                            <div v-if="reservationForm.errors.reservation_phone" class="text-xs font-semibold text-rose-700">{{ reservationForm.errors.reservation_phone }}</div>
                            <div v-if="reservationForm.errors.reservation_time" class="text-xs font-semibold text-rose-700">{{ reservationForm.errors.reservation_time }}</div>
                            <button
                                type="button"
                                @click="reserveTable"
                                :disabled="reservationForm.processing"
                                class="w-full rounded-md bg-amber-500 px-4 py-2.5 font-bold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Lưu đặt bàn
                            </button>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                        <div
                            v-for="item in cart"
                            :key="item.menu_id"
                            class="rounded-lg border border-slate-200 p-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-bold text-slate-950">{{ item.name }}</h3>
                                    <p class="text-sm text-slate-500">{{ formatPrice(item.price) }}</p>
                                </div>
                                <button type="button" @click="removeItem(item)" class="text-sm font-semibold text-rose-600 hover:text-rose-700">
                                    Xóa
                                </button>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                <div class="flex items-center rounded-md border border-slate-300">
                                    <button type="button" @click="decrease(item)" class="h-9 w-9 text-lg font-bold text-slate-700">-</button>
                                    <div class="w-10 text-center font-bold">{{ item.quantity }}</div>
                                    <button type="button" @click="increase(item)" class="h-9 w-9 text-lg font-bold text-slate-700">+</button>
                                </div>
                                <div class="font-bold text-slate-950">{{ formatPrice(item.price * item.quantity) }}</div>
                            </div>

                            <input
                                v-model="item.notes"
                                type="text"
                                class="mt-3 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
                                placeholder="Ghi chú món"
                            />
                        </div>

                        <div v-if="cart.length === 0" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
                            Chọn món từ danh sách bên cạnh.
                        </div>
                    </div>

                    <div class="mt-4 shrink-0 border-t border-slate-200 pt-4">
                        <div class="mb-3 flex items-center justify-between text-lg font-bold">
                            <span>Tổng tạm tính</span>
                            <span>{{ formatPrice(cartTotal) }}</span>
                        </div>
                        <div v-if="orderForm.errors.items" class="mb-3 rounded-md bg-rose-50 p-3 text-sm font-semibold text-rose-700">{{ orderForm.errors.items }}</div>
                        <div v-if="orderForm.errors.table_id" class="mb-3 rounded-md bg-rose-50 p-3 text-sm font-semibold text-rose-700">{{ orderForm.errors.table_id }}</div>
                        <button
                            type="button"
                            @click="submitOrder"
                            :disabled="cart.length === 0 || orderForm.processing"
                            class="w-full rounded-md bg-emerald-600 px-4 py-3 font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Gửi bếp
                        </button>
                    </div>
                </div>

                <div v-else-if="mode === 'reserved'" class="flex h-full min-h-0 flex-col">
                    <div class="mb-4 flex shrink-0 items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-amber-700">Bàn đã đặt trước</p>
                            <h2 class="text-xl font-bold text-slate-950">{{ selectedTable.name }}</h2>
                        </div>
                        <button type="button" @click="closePanel" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Đóng
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div class="text-sm font-semibold text-amber-700">Thông tin khách</div>
                            <div class="mt-2 text-lg font-bold text-slate-950">{{ selectedTable.reservation_customer_name }}</div>
                            <div class="mt-1 text-sm text-slate-700">{{ selectedTable.reservation_phone }}</div>
                            <div v-if="selectedTable.reservation_time_display" class="mt-3 rounded-md bg-white px-3 py-2 text-sm font-bold text-amber-800">
                                {{ selectedTable.reservation_time_display }}
                            </div>
                            <div v-if="selectedTable.reservation_note" class="mt-3 rounded-md bg-white p-3 text-sm text-slate-600">
                                {{ selectedTable.reservation_note }}
                            </div>
                        </div>

                        <div v-if="!isAddingItems" class="space-y-3">
                            <button
                                type="button"
                                @click="startAddItems"
                                class="w-full rounded-md bg-emerald-600 px-4 py-3 font-bold text-white transition hover:bg-emerald-700"
                            >
                                Khách đến - chọn món
                            </button>
                            <button
                                type="button"
                                @click="cancelReservation"
                                :disabled="cancelReservationForm.processing"
                                class="w-full rounded-md border border-rose-300 bg-white px-4 py-3 font-bold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Hủy đặt bàn
                            </button>
                            <div v-if="cancelReservationForm.errors.table_id" class="rounded-md bg-rose-50 p-3 text-sm font-semibold text-rose-700">{{ cancelReservationForm.errors.table_id }}</div>
                        </div>

                        <div v-else class="rounded-lg border border-emerald-200 bg-white p-3">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-emerald-700">Chọn món cho khách</p>
                                    <p class="text-xs text-slate-500">Chọn món ở danh sách giữa</p>
                                </div>
                                <button type="button" @click="cancelAddItems" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
                                    Hủy
                                </button>
                            </div>

                            <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                                <div
                                    v-for="item in cart"
                                    :key="item.menu_id"
                                    class="rounded-md border border-slate-200 p-2"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-bold text-slate-950">{{ item.name }}</div>
                                            <div class="text-xs text-slate-500">{{ formatPrice(item.price) }}</div>
                                        </div>
                                        <div class="flex items-center rounded-md border border-slate-300">
                                            <button type="button" @click="decrease(item)" class="h-8 w-8 font-bold text-slate-700">-</button>
                                            <div class="w-8 text-center text-sm font-bold">{{ item.quantity }}</div>
                                            <button type="button" @click="increase(item)" class="h-8 w-8 font-bold text-slate-700">+</button>
                                        </div>
                                    </div>
                                    <input
                                        v-model="item.notes"
                                        type="text"
                                        class="mt-2 w-full rounded-md border-slate-300 text-xs focus:border-slate-500 focus:ring-slate-500"
                                        placeholder="Ghi chú"
                                    />
                                </div>

                                <div v-if="cart.length === 0" class="rounded-md border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                                    Chưa chọn món.
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between font-bold">
                                <span>Tạm tính</span>
                                <span>{{ formatPrice(cartTotal) }}</span>
                            </div>
                            <div v-if="orderForm.errors.items" class="mt-2 rounded-md bg-rose-50 p-2 text-xs font-semibold text-rose-700">{{ orderForm.errors.items }}</div>
                            <div v-if="orderForm.errors.table_id" class="mt-2 rounded-md bg-rose-50 p-2 text-xs font-semibold text-rose-700">{{ orderForm.errors.table_id }}</div>
                            <button
                                type="button"
                                @click="submitOrder"
                                :disabled="cart.length === 0 || orderForm.processing"
                                class="mt-3 w-full rounded-md bg-emerald-600 px-4 py-2.5 font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Gửi bếp
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="flex h-full min-h-0 flex-col">
                    <div class="mb-4 flex shrink-0 items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-rose-700">Hóa đơn hiện tại</p>
                            <h2 class="text-xl font-bold text-slate-950">{{ selectedTable.name }}</h2>
                            <p class="text-sm text-slate-500">{{ selectedTable.active_order?.order_code }}</p>
                            <p v-if="readyItemsCount > 0" class="mt-1 inline-flex rounded-md bg-rose-100 px-2 py-1 text-xs font-bold text-rose-700">
                                {{ readyItemsCount }} món chờ phục vụ
                            </p>
                        </div>
                        <button type="button" @click="closePanel" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Đóng
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
                        <div v-if="selectedTable.active_order" class="space-y-3">
                            <div
                                v-for="item in selectedTable.active_order.items"
                                :key="item.id"
                                class="rounded-lg border border-slate-200 p-3"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-bold text-slate-950">{{ item.menu_name }}</h3>
                                        <p class="text-sm text-slate-500">x{{ item.quantity }}</p>
                                        <p v-if="item.notes" class="mt-1 text-sm text-slate-500">{{ item.notes }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-slate-950">{{ formatPrice(item.price * item.quantity) }}</div>
                                        <span class="mt-2 inline-flex rounded-full px-2 py-1 text-xs font-bold" :class="statusClass(item.status)">
                                            {{ statusText(item.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-slate-500">
                            Chưa tìm thấy hóa đơn đang hoạt động.
                        </div>
                    </div>

                    <div class="mt-4 shrink-0 rounded-lg bg-slate-50 p-4">
                        <div v-if="!isAddingItems" class="mb-4">
                            <button
                                type="button"
                                @click="startAddItems"
                                class="w-full rounded-md bg-emerald-600 px-4 py-3 font-bold text-white transition hover:bg-emerald-700"
                            >
                                Gọi thêm món
                            </button>
                        </div>

                        <div v-else class="mb-4 rounded-lg border border-emerald-200 bg-white p-3">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-emerald-700">Gọi thêm món</p>
                                    <p class="text-xs text-slate-500">Chọn món ở danh sách giữa</p>
                                </div>
                                <button type="button" @click="cancelAddItems" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
                                    Hủy
                                </button>
                            </div>

                            <div class="max-h-48 space-y-2 overflow-y-auto pr-1">
                                <div
                                    v-for="item in cart"
                                    :key="item.menu_id"
                                    class="rounded-md border border-slate-200 p-2"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-bold text-slate-950">{{ item.name }}</div>
                                            <div class="text-xs text-slate-500">{{ formatPrice(item.price) }}</div>
                                        </div>
                                        <div class="flex items-center rounded-md border border-slate-300">
                                            <button type="button" @click="decrease(item)" class="h-8 w-8 font-bold text-slate-700">-</button>
                                            <div class="w-8 text-center text-sm font-bold">{{ item.quantity }}</div>
                                            <button type="button" @click="increase(item)" class="h-8 w-8 font-bold text-slate-700">+</button>
                                        </div>
                                    </div>
                                    <input
                                        v-model="item.notes"
                                        type="text"
                                        class="mt-2 w-full rounded-md border-slate-300 text-xs focus:border-slate-500 focus:ring-slate-500"
                                        placeholder="Ghi chú"
                                    />
                                </div>

                                <div v-if="cart.length === 0" class="rounded-md border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                                    Chưa chọn món thêm.
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between font-bold">
                                <span>Thêm</span>
                                <span>{{ formatPrice(cartTotal) }}</span>
                            </div>
                            <div v-if="orderForm.errors.items" class="mt-2 rounded-md bg-rose-50 p-2 text-xs font-semibold text-rose-700">{{ orderForm.errors.items }}</div>
                            <div v-if="orderForm.errors.table_id" class="mt-2 rounded-md bg-rose-50 p-2 text-xs font-semibold text-rose-700">{{ orderForm.errors.table_id }}</div>
                            <button
                                type="button"
                                @click="submitOrder"
                                :disabled="cart.length === 0 || orderForm.processing"
                                class="mt-3 w-full rounded-md bg-emerald-600 px-4 py-2.5 font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Gửi bếp món thêm
                            </button>
                        </div>

                        <label class="mb-2 block text-sm font-bold text-slate-700">Chuyển sang bàn trống</label>
                        <div class="flex gap-2">
                            <select
                                v-model="moveTargetId"
                                class="min-w-0 flex-1 rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="">Chọn bàn</option>
                                <option v-for="table in emptyTables" :key="table.id" :value="table.id">
                                    {{ table.name }}
                                </option>
                            </select>
                            <button
                                type="button"
                                @click="moveTable"
                                :disabled="!moveTargetId || moveForm.processing"
                                class="rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Chuyển
                            </button>
                        </div>
                        <div v-if="moveForm.errors.target_table_id" class="mt-2 text-sm font-semibold text-rose-700">{{ moveForm.errors.target_table_id }}</div>
                        <div v-if="moveForm.errors.current_table_id" class="mt-2 text-sm font-semibold text-rose-700">{{ moveForm.errors.current_table_id }}</div>
                    </div>

                    <div class="mt-4 shrink-0 border-t border-slate-200 pt-4">
                        <div class="mb-3 flex items-center justify-between text-lg font-bold">
                            <span>Tổng thanh toán</span>
                            <span>{{ formatPrice(selectedOrderTotal) }}</span>
                        </div>
                        <div v-if="checkoutForm.errors.table_id" class="mb-3 rounded-md bg-rose-50 p-3 text-sm font-semibold text-rose-700">{{ checkoutForm.errors.table_id }}</div>
                        <button
                            type="button"
                            @click="checkout"
                            :disabled="!selectedTable.active_order || checkoutForm.processing"
                            class="w-full rounded-md bg-rose-600 px-4 py-3 font-bold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Thanh toán
                        </button>
                    </div>
                </div>
            </aside>
        </main>
    </div>
</template>
