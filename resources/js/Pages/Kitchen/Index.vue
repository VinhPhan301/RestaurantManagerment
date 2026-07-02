<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    branch: Object,
    items: Array,
});

const statusForm = useForm({
    status: '',
});

const orderedItems = computed(() => props.items.filter((item) => item.status === 'ordered'));
const cookingItems = computed(() => props.items.filter((item) => item.status === 'cooking'));
const readyItems = computed(() => props.items.filter((item) => item.status === 'ready'));

const elapsedMinutes = (createdAt) => {
    if (!createdAt) {
        return 0;
    }

    return Math.max(0, Math.floor((Date.now() - new Date(createdAt).getTime()) / 60000));
};

const updateStatus = (item, status) => {
    statusForm.status = status;
    statusForm.patch(route('kitchen.items.status', item.id), {
        preserveScroll: true,
        onFinish: () => {
            statusForm.reset();
        },
    });
};

const statusLabel = (status) => {
    if (status === 'ordered') {
        return 'Mới gọi';
    }

    if (status === 'cooking') {
        return 'Đang làm';
    }

    if (status === 'ready') {
        return 'Chờ phục vụ';
    }

    return status;
};

const itemCardClass = (status) => {
    if (status === 'ordered') {
        return 'border-amber-300 bg-white';
    }

    if (status === 'cooking') {
        return 'border-sky-300 bg-white';
    }

    if (status === 'ready') {
        return 'border-rose-300 bg-rose-100 text-rose-950';
    }

    return 'border-zinc-200 bg-white';
};

const statusBadgeClass = (status) => {
    if (status === 'ordered') {
        return 'bg-amber-100 text-amber-800';
    }

    if (status === 'cooking') {
        return 'bg-sky-100 text-sky-800';
    }

    if (status === 'ready') {
        return 'bg-white text-rose-700';
    }

    return 'bg-zinc-100 text-zinc-800';
};
</script>

<template>
    <Head title="Màn hình bếp" />

    <div class="min-h-screen bg-zinc-100">
        <header class="sticky top-0 z-20 border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-[1600px] items-center justify-between px-4 py-3 sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase text-zinc-500">Kitchen Display System</p>
                    <h1 class="text-xl font-bold text-zinc-950">{{ branch?.name || 'Nhà bếp' }}</h1>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('staff.dashboard')"
                        class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50"
                    >
                        POS
                    </Link>
                    <Link
                        :href="route('staff.logout')"
                        method="post"
                        as="button"
                        class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-zinc-700"
                    >
                        Đăng xuất
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1600px] px-4 py-4 sm:px-6">
            <div class="mb-4 grid gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <div class="text-sm font-semibold text-zinc-500">Đang chờ</div>
                    <div class="mt-1 text-3xl font-bold text-amber-700">{{ orderedItems.length }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <div class="text-sm font-semibold text-zinc-500">Đang làm</div>
                    <div class="mt-1 text-3xl font-bold text-sky-700">{{ cookingItems.length }}</div>
                </div>
                <div class="rounded-lg border border-rose-200 bg-rose-50 p-4">
                    <div class="text-sm font-semibold text-rose-700">Chờ phục vụ</div>
                    <div class="mt-1 text-3xl font-bold text-rose-700">{{ readyItems.length }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <div class="text-sm font-semibold text-zinc-500">Tổng món trên bếp</div>
                    <div class="mt-1 text-3xl font-bold text-zinc-950">{{ items.length }}</div>
                </div>
            </div>

            <div v-if="statusForm.errors.status" class="mb-4 rounded-md bg-rose-50 p-3 text-sm font-semibold text-rose-700">
                {{ statusForm.errors.status }}
            </div>

            <div v-if="items.length === 0" class="flex min-h-[calc(100vh-240px)] items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-white p-8 text-center">
                <div>
                    <h2 class="text-2xl font-bold text-zinc-950">Bếp đang trống</h2>
                    <p class="mt-2 text-zinc-500">Các món mới gửi bếp sẽ xuất hiện tại đây.</p>
                </div>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                <article
                    v-for="item in items"
                    :key="item.id"
                    class="flex min-h-64 flex-col justify-between rounded-lg border p-4 shadow-sm"
                    :class="itemCardClass(item.status)"
                >
                    <div>
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-bold text-zinc-500">{{ item.order_code }}</div>
                                <h2 class="mt-1 text-3xl font-black leading-tight" :class="item.status === 'ready' ? 'text-rose-950' : 'text-zinc-950'">{{ item.menu_name }}</h2>
                            </div>
                            <span
                                class="rounded-md px-2.5 py-1 text-xs font-bold"
                                :class="statusBadgeClass(item.status)"
                            >
                                {{ statusLabel(item.status) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-[1.2fr_1fr_0.8fr] gap-2 text-center">
                            <div class="rounded-md p-3 text-white" :class="item.status === 'ready' ? 'bg-rose-800' : 'bg-zinc-900'">
                                <div class="text-xs font-bold uppercase text-zinc-300">Bàn</div>
                                <div class="mt-1 text-3xl font-black leading-none">{{ item.table_name }}</div>
                            </div>
                            <div class="rounded-md p-3 text-white" :class="item.status === 'ready' ? 'bg-rose-800' : 'bg-zinc-900'">
                                <div class="text-xs font-bold uppercase text-zinc-300">SL</div>
                                <div class="mt-1 text-3xl font-black leading-none">x{{ item.quantity }}</div>
                            </div>
                            <div class="rounded-md p-3" :class="item.status === 'ready' ? 'bg-white text-rose-800' : 'bg-zinc-100'">
                                <div class="text-xs font-bold uppercase" :class="item.status === 'ready' ? 'text-rose-600' : 'text-zinc-500'">Phút</div>
                                <div class="mt-1 text-2xl font-black" :class="item.status === 'ready' ? 'text-rose-950' : 'text-zinc-950'">{{ elapsedMinutes(item.created_at) }}</div>
                            </div>
                        </div>

                        <div class="mt-3 rounded-md border p-3" :class="item.status === 'ready' ? 'border-rose-300 bg-white' : 'border-zinc-200'">
                            <div class="text-xs font-bold uppercase" :class="item.status === 'ready' ? 'text-rose-600' : 'text-zinc-500'">Ghi chú</div>
                            <p class="mt-1 min-h-10 text-base font-semibold" :class="item.status === 'ready' ? 'text-rose-950' : 'text-zinc-900'">
                                {{ item.notes || 'Không có ghi chú' }}
                            </p>
                        </div>

                        <div class="mt-3 text-sm font-semibold" :class="item.status === 'ready' ? 'text-rose-700' : 'text-zinc-500'">
                            Gọi lúc {{ item.created_at_label }}
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            @click="updateStatus(item, 'cooking')"
                            :disabled="item.status !== 'ordered' || statusForm.processing"
                            class="rounded-md bg-sky-600 px-4 py-3 font-bold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-500"
                        >
                            Đang làm
                        </button>
                        <button
                            type="button"
                            @click="updateStatus(item, 'ready')"
                            :disabled="item.status !== 'cooking' || statusForm.processing"
                            class="rounded-md bg-emerald-600 px-4 py-3 font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-500"
                        >
                            Hoàn thành
                        </button>
                        <button
                            type="button"
                            @click="updateStatus(item, 'served')"
                            :disabled="item.status !== 'ready' || statusForm.processing"
                            class="rounded-md bg-rose-800 px-4 py-3 font-bold text-white transition hover:bg-rose-950 disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-500"
                        >
                            Đã phục vụ
                        </button>
                    </div>
                </article>
            </div>
        </main>
    </div>
</template>
