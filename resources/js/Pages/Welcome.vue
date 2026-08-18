<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    menus: {
        type: Array,
        default: () => [],
    },
});

const tabs = [
    { key: 'home', label: 'Trang chủ' },
    { key: 'branches', label: 'Cơ sở' },
    { key: 'menu', label: 'Menu' },
];

const validTabKeys = tabs.map((tab) => tab.key);
const tabFromHash = () => window.location.hash.replace('#', '');
const activeTab = ref(validTabKeys.includes(tabFromHash()) ? tabFromHash() : 'home');
const activeCategory = ref('Tất cả');
const bookingToastVisible = ref(false);
let bookingToastTimer;

const reviews = [
    {
        name: 'Minh Anh',
        visit: 'Khách hàng thân thiết',
        quote: 'Món ăn vừa miệng, không gian ấm cúng. Cả nhà mình ai cũng tìm được món yêu thích.',
    },
    {
        name: 'Hoàng Nam',
        visit: 'Bữa tối cuối tuần',
        quote: 'Nhân viên nhiệt tình, món lên nhanh và hương vị rất tròn. Chắc chắn sẽ quay lại.',
    },
    {
        name: 'Thu Hà',
        visit: 'Tiệc gia đình',
        quote: 'Đặt bàn rất thuận tiện, nhà hàng hỗ trợ chu đáo từ lúc gọi điện đến khi dùng bữa.',
    },
];

const categories = computed(() => [
    'Tất cả',
    ...new Set(props.menus.map((menu) => menu.category?.name).filter(Boolean)),
]);

const filteredMenus = computed(() => {
    if (activeCategory.value === 'Tất cả') {
        return props.menus;
    }

    return props.menus.filter((menu) => menu.category?.name === activeCategory.value);
});

const bookingForm = useForm({
    branch_id: props.branches[0]?.id ?? '',
    customer_name: '',
    phone: '',
    reservation_date: '',
    reservation_time: '',
    guests: 2,
    note: '',
});

const minBookingDate = new Date().toISOString().slice(0, 10);
const formatPrice = (price) => `${new Intl.NumberFormat('vi-VN').format(Number(price))}đ`;
const phoneHref = (phone) => `tel:${String(phone || '').replace(/[^\d+]/g, '')}`;
const mapHref = (address) => `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address || '')}`;
const handleBookingPhoneInput = (event) => {
    bookingForm.phone = event.target.value.replace(/\D/g, '').slice(0, 10);
};

const selectTab = (tab) => {
    if (!validTabKeys.includes(tab)) {
        return;
    }

    activeTab.value = tab;
    activeCategory.value = 'Tất cả';
    window.history.replaceState({}, '', tab === 'home' ? '/' : `/#${tab}`);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const syncTabFromHash = () => {
    const hashTab = tabFromHash();
    if (validTabKeys.includes(hashTab)) {
        activeTab.value = hashTab;
    }
};

const submitBooking = () => {
    bookingForm.post(route('reservations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            bookingForm.reset();
            bookingToastVisible.value = true;
            clearTimeout(bookingToastTimer);
            bookingToastTimer = setTimeout(() => {
                bookingToastVisible.value = false;
            }, 4500);
            selectTab('branches');
        },
        onError: () => selectTab('branches'),
    });
};

onMounted(() => window.addEventListener('hashchange', syncTabFromHash));
onUnmounted(() => {
    window.removeEventListener('hashchange', syncTabFromHash);
    clearTimeout(bookingToastTimer);
});
</script>

<template>
    <Head title="Nhà hàng Bếp Việt">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet" />
    </Head>

    <div class="landing-page min-h-screen overflow-hidden bg-[#fbf8f2] text-[#20332d]">
        <header class="sticky top-0 z-50 border-b border-[#20332d]/10 bg-[#fbf8f2]/90 backdrop-blur-lg">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-5 py-4 lg:px-8">
                <a href="#home" class="flex shrink-0 items-center gap-3" aria-label="Bếp Việt - Trang chủ" @click.prevent="selectTab('home')">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#e36c3d] text-white shadow-lg shadow-[#e36c3d]/20">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 12.5c0-3.59 3.58-6.5 8-6.5s8 2.91 8 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            <path d="M3 12.5h18M5.5 12.5l1.2 5.14A2.99 2.99 0 0 0 9.62 20h4.76a2.99 2.99 0 0 0 2.92-2.36l1.2-5.14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M12 3v2M8.5 4l.7 1.35M15.5 4l-.7 1.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-[10px] font-semibold uppercase tracking-[0.32em] text-[#e36c3d]">Nhà hàng</span>
                        <span class="block text-xl font-bold tracking-tight">Bếp Việt</span>
                    </span>
                </a>

                <nav class="flex items-center gap-1 rounded-full bg-white/70 p-1 shadow-sm" aria-label="Điều hướng chính">
                    <a
                        v-for="tab in tabs"
                        :key="tab.key"
                        :href="`#${tab.key}`"
                        class="rounded-full px-3 py-2 text-xs font-bold transition sm:px-4 sm:text-sm"
                        :class="activeTab === tab.key ? 'bg-[#20332d] text-white shadow-sm' : 'text-[#20332d]/60 hover:text-[#20332d]'"
                        :aria-current="activeTab === tab.key ? 'page' : undefined"
                        @click.prevent="selectTab(tab.key)"
                    >
                        {{ tab.label }}
                    </a>
                </nav>

                <button type="button" class="hidden shrink-0 items-center gap-2 rounded-full bg-[#e36c3d] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#c9572b] sm:inline-flex" @click="selectTab('branches')">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 4h3l1.5 4-2 1.5a15.5 15.5 0 0 0 7 7l1.5-2 4 1.5v3a1 1 0 0 1-1 1C11.38 20 4 12.62 4 3a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                    </svg>
                    Đặt bàn
                </button>
            </div>
        </header>

        <div v-if="bookingToastVisible" class="pointer-events-none fixed right-5 top-5 z-[100] flex w-[min(22rem,calc(100vw-2.5rem))] items-start gap-3 rounded-2xl border border-[#8ca98d]/40 bg-white p-4 text-[#20332d] shadow-2xl shadow-[#20332d]/20" role="status" aria-live="polite">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e2eee3] text-lg font-bold text-[#52765d]">✓</span>
            <div>
                <p class="font-bold">Đặt bàn thành công</p>
                <p class="mt-1 text-sm text-[#20332d]/60">Yêu cầu đã được gửi. Nhà hàng sẽ sớm liên hệ để xác nhận.</p>
            </div>
        </div>

        <main>
            <section v-if="activeTab === 'home'" class="relative isolate">
                <div class="pointer-events-none absolute -left-32 top-20 -z-10 h-72 w-72 rounded-full bg-[#e36c3d]/10 blur-3xl"></div>
                <div class="pointer-events-none absolute -right-24 top-0 -z-10 h-96 w-96 rounded-full bg-[#8ca98d]/20 blur-3xl"></div>

                <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 pb-16 pt-16 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:pb-24 lg:pt-24">
                    <div>
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-[#e36c3d]/20 bg-white/70 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-[#e36c3d]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#e36c3d]"></span>
                            Vị ngon Việt, trọn khoảnh khắc
                        </div>
                        <h1 class="max-w-3xl text-5xl font-bold leading-[1.05] tracking-tight text-[#20332d] sm:text-6xl lg:text-7xl">
                            Mâm cơm thân quen,
                            <span class="block text-[#e36c3d]">vị ngon đáng nhớ.</span>
                        </h1>
                        <p class="mt-7 max-w-xl text-lg leading-8 text-[#20332d]/65">
                            Bếp Việt mang những món ăn gần gũi của mâm cơm Việt đến không gian ấm cúng, nơi mọi cuộc gặp gỡ đều trở nên trọn vẹn hơn.
                        </p>
                        <div class="mt-9 flex flex-wrap items-center gap-3">
                            <button type="button" class="rounded-full bg-[#e36c3d] px-6 py-3.5 text-sm font-bold text-white shadow-xl shadow-[#e36c3d]/20 transition hover:-translate-y-0.5 hover:bg-[#c9572b]" @click="selectTab('branches')">Tìm cơ sở gần bạn</button>
                            <button type="button" class="rounded-full border border-[#20332d]/15 bg-white/60 px-6 py-3.5 text-sm font-bold text-[#20332d] transition hover:border-[#20332d]/30 hover:bg-white" @click="selectTab('menu')">Xem thực đơn</button>
                        </div>
                        <div class="mt-12 flex flex-wrap gap-x-10 gap-y-4 border-t border-[#20332d]/10 pt-6">
                            <div><p class="text-2xl font-bold text-[#20332d]">{{ menus.length }}+</p><p class="mt-1 text-xs font-semibold uppercase tracking-wider text-[#20332d]/50">Món ngon mỗi ngày</p></div>
                            <div><p class="text-2xl font-bold text-[#20332d]">{{ branches.length }}</p><p class="mt-1 text-xs font-semibold uppercase tracking-wider text-[#20332d]/50">Cơ sở phục vụ</p></div>
                            <div><p class="text-2xl font-bold text-[#20332d]">100%</p><p class="mt-1 text-xs font-semibold uppercase tracking-wider text-[#20332d]/50">Tươi ngon mỗi ngày</p></div>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-[540px]">
                        <div class="absolute -right-3 top-10 h-28 w-28 rounded-full border border-[#e36c3d]/25"></div>
                        <div class="absolute -bottom-5 -left-5 h-24 w-24 rounded-full border border-[#20332d]/15"></div>
                        <div class="relative overflow-hidden rounded-[2.5rem] bg-[#20332d] p-5 shadow-2xl shadow-[#20332d]/20 sm:p-7">
                            <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-[#8ca98d]/20 blur-2xl"></div>
                            <div class="relative flex items-center justify-between text-white/70"><span class="text-xs font-semibold uppercase tracking-[0.25em]">Bếp Việt / 01</span><span class="rounded-full border border-white/20 px-3 py-1 text-[10px] font-bold uppercase tracking-wider">Since 2024</span></div>
                            <div class="relative mx-auto my-10 flex aspect-square max-w-[330px] items-center justify-center rounded-full bg-[#e6d6bb] shadow-inner shadow-black/10">
                                <div class="absolute inset-5 rounded-full border border-[#20332d]/10"></div>
                                <div class="relative flex h-48 w-48 items-center justify-center rounded-full bg-[#f5eee3] shadow-[0_20px_35px_rgba(32,51,45,0.16)]">
                                    <div class="absolute left-12 top-14 h-10 w-16 rotate-12 rounded-[50%] bg-[#d8663b] shadow-[18px_22px_0_#e7a15b,-17px_27px_0_#75906d]"></div>
                                    <div class="absolute bottom-12 right-10 h-8 w-8 rounded-full bg-[#e7a15b] shadow-[-40px_6px_0_#75906d,25px_12px_0_#d8663b]"></div>
                                    <span class="relative z-10 text-3xl font-bold text-[#20332d]">Bếp Việt</span>
                                </div>
                            </div>
                            <div class="relative flex items-end justify-between"><div><p class="text-2xl font-bold text-white">Ăn ngon, sống vui</p><p class="mt-1 text-sm text-white/55">Một chút thân quen trong mỗi món ăn</p></div><span class="mb-1 text-3xl text-[#e7a15b]">✦</span></div>
                        </div>
                    </div>
                </div>

                <div class="border-y border-[#20332d]/10 bg-white/60">
                    <div class="mx-auto grid max-w-7xl gap-6 px-5 py-8 sm:grid-cols-3 lg:px-8">
                        <div class="flex items-start gap-4"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#f8e4d8] text-[#e36c3d]"><span class="text-xl">✦</span></span><div><p class="font-bold">Nguyên liệu tươi</p><p class="mt-1 text-sm text-[#20332d]/55">Chọn lọc mỗi ngày</p></div></div>
                        <div class="flex items-start gap-4"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#e2eee3] text-[#52765d]"><span class="text-xl">✓</span></span><div><p class="font-bold">Phục vụ tận tâm</p><p class="mt-1 text-sm text-[#20332d]/55">Đón tiếp như người nhà</p></div></div>
                        <div class="flex items-start gap-4"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#f4edcf] text-[#b58a27]"><span class="text-xl">★</span></span><div><p class="font-bold">Không gian ấm cúng</p><p class="mt-1 text-sm text-[#20332d]/55">Gặp gỡ thêm đáng nhớ</p></div></div>
                    </div>
                </div>

                <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-24">
                    <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-end">
                        <div><p class="text-xs font-bold uppercase tracking-[0.25em] text-[#e36c3d]">Câu chuyện Bếp Việt</p><h2 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">Một nơi để trở về.</h2></div>
                        <p class="max-w-2xl text-base leading-7 text-[#20332d]/65">Chúng tôi tin rằng một bữa ăn ngon bắt đầu từ nguyên liệu tử tế và kết thúc bằng những câu chuyện vui. Bếp Việt giữ lại hương vị thân quen, thêm một không gian chỉn chu để mỗi lần gặp nhau đều đáng nhớ.</p>
                    </div>
                </div>

                <div class="bg-[#f2e8d9] px-5 py-20 lg:py-24">
                    <div class="mx-auto max-w-7xl lg:px-8">
                        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="text-xs font-bold uppercase tracking-[0.25em] text-[#e36c3d]">Khách hàng nói gì</p><h2 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">Bữa ngon, lời thật.</h2></div><p class="max-w-sm text-sm leading-6 text-[#20332d]/60">Niềm vui của khách hàng là phần đặc biệt nhất trong mỗi ngày phục vụ của Bếp.</p></div>
                        <div class="mt-10 grid gap-5 md:grid-cols-3">
                            <article v-for="review in reviews" :key="review.name" class="rounded-3xl bg-white p-6 shadow-sm sm:p-7"><div class="flex gap-1 text-[#e7a15b]" aria-label="5 trên 5 sao">★★★★★</div><p class="mt-5 leading-7 text-[#20332d]/70">“{{ review.quote }}”</p><div class="mt-7 border-t border-[#20332d]/10 pt-4"><p class="font-bold">{{ review.name }}</p><p class="mt-1 text-xs font-semibold uppercase tracking-wider text-[#20332d]/45">{{ review.visit }}</p></div></article>
                        </div>
                    </div>
                </div>
            </section>

            <section v-else-if="activeTab === 'branches'" class="mx-auto max-w-7xl px-5 py-16 lg:px-8 lg:py-24">
                <div class="max-w-2xl"><p class="text-xs font-bold uppercase tracking-[0.25em] text-[#e36c3d]">Ghé thăm chúng tôi</p><h1 class="mt-3 text-5xl font-bold tracking-tight sm:text-6xl">Cơ sở & đặt bàn</h1><p class="mt-6 text-lg leading-8 text-[#20332d]/65">Chọn cơ sở thuận tiện nhất, xem vị trí và gửi thông tin đặt bàn. Nhà hàng sẽ gọi lại để xác nhận.</p></div>

                <div class="mt-12 grid gap-5 lg:grid-cols-2">
                    <article v-for="(branch, index) in branches" :key="branch.id" class="group relative overflow-hidden rounded-3xl border border-[#20332d]/10 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-[#20332d]/10 sm:p-8">
                        <div class="absolute right-0 top-0 h-32 w-32 rounded-bl-[5rem] bg-[#e2eee3] transition group-hover:bg-[#f8e4d8]"></div>
                        <div class="relative"><div class="flex items-start justify-between"><span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#20332d] text-xl font-bold text-white">0{{ index + 1 }}</span><span class="rounded-full bg-[#e2eee3] px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-[#52765d]">Đang phục vụ</span></div><h2 class="mt-8 text-2xl font-bold">{{ branch.name }}</h2>
                            <div class="mt-5 space-y-4 text-sm text-[#20332d]/65"><p class="flex items-start gap-3"><span class="mt-0.5 text-lg text-[#e36c3d]">⌖</span><span>{{ branch.address || 'Thông tin địa chỉ đang cập nhật' }}</span></p><p v-if="branch.phone" class="flex items-center gap-3"><span class="text-lg text-[#e36c3d]">☎</span><a :href="phoneHref(branch.phone)" class="font-bold text-[#20332d] hover:text-[#e36c3d]">{{ branch.phone }}</a></p></div>
                            <div class="mt-8 flex flex-wrap gap-3"><a v-if="branch.address" :href="mapHref(branch.address)" target="_blank" rel="noopener noreferrer" class="rounded-full border border-[#20332d]/15 px-4 py-2 text-sm font-bold text-[#20332d] transition hover:border-[#e36c3d] hover:text-[#e36c3d]">Xem vị trí ↗</a><a v-if="branch.phone" :href="phoneHref(branch.phone)" class="rounded-full bg-[#e36c3d] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#c9572b]">Gọi đặt bàn</a></div>
                        </div>
                    </article>
                    <div v-if="!branches.length" class="rounded-3xl border border-dashed border-[#20332d]/20 p-8 text-center text-[#20332d]/60 lg:col-span-2">Thông tin cơ sở đang được cập nhật.</div>
                </div>

                <div class="mt-14 rounded-[2rem] bg-[#20332d] p-6 text-white shadow-2xl shadow-[#20332d]/15 sm:p-10">
                    <div class="grid gap-10 lg:grid-cols-[0.7fr_1.3fr] lg:items-start"><div><p class="text-xs font-bold uppercase tracking-[0.25em] text-[#e7a15b]">Giữ chỗ cho bạn</p><h2 class="mt-3 text-4xl font-bold tracking-tight">Gửi thông tin đặt bàn</h2><p class="mt-5 text-sm leading-6 text-white/60">Điền thông tin, chúng tôi sẽ liên hệ lại để xác nhận bàn và thời gian phù hợp.</p></div>
                        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitBooking">
                            <div class="sm:col-span-2"><label for="booking-branch" class="mb-2 block text-sm font-semibold text-white/80">Cơ sở *</label><select id="booking-branch" v-model="bookingForm.branch_id" class="w-full rounded-xl border-white/10 bg-white/10 px-4 py-3 text-sm text-white focus:border-[#e7a15b] focus:ring-[#e7a15b]" required><option v-for="branch in branches" :key="branch.id" :value="branch.id" class="text-[#20332d]">{{ branch.name }}</option></select><p v-if="bookingForm.errors.branch_id" class="mt-1 text-xs text-rose-300">{{ bookingForm.errors.branch_id }}</p></div>
                            <div><label for="booking-name" class="mb-2 block text-sm font-semibold text-white/80">Họ và tên *</label><input id="booking-name" v-model="bookingForm.customer_name" type="text" autocomplete="name" class="w-full rounded-xl border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-white/35 focus:border-[#e7a15b] focus:ring-[#e7a15b]" placeholder="Nguyễn Văn A" required /><p v-if="bookingForm.errors.customer_name" class="mt-1 text-xs text-rose-300">{{ bookingForm.errors.customer_name }}</p></div>
                            <div><label for="booking-phone" class="mb-2 block text-sm font-semibold text-white/80">Số điện thoại *</label><input id="booking-phone" v-model="bookingForm.phone" @input="handleBookingPhoneInput" type="tel" inputmode="numeric" maxlength="10" autocomplete="tel" class="w-full rounded-xl border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-white/35 focus:border-[#e7a15b] focus:ring-[#e7a15b]" placeholder="0901234567" required /><p v-if="bookingForm.errors.phone" class="mt-1 text-xs text-rose-300">{{ bookingForm.errors.phone }}</p></div>
                            <div><label for="booking-date" class="mb-2 block text-sm font-semibold text-white/80">Ngày đặt *</label><input id="booking-date" v-model="bookingForm.reservation_date" :min="minBookingDate" type="date" class="w-full rounded-xl border-white/10 bg-white/10 px-4 py-3 text-sm text-white focus:border-[#e7a15b] focus:ring-[#e7a15b]" required /><p v-if="bookingForm.errors.reservation_date" class="mt-1 text-xs text-rose-300">{{ bookingForm.errors.reservation_date }}</p></div>
                            <div><label for="booking-time" class="mb-2 block text-sm font-semibold text-white/80">Giờ dùng bữa *</label><input id="booking-time" v-model="bookingForm.reservation_time" type="time" class="w-full rounded-xl border-white/10 bg-white/10 px-4 py-3 text-sm text-white focus:border-[#e7a15b] focus:ring-[#e7a15b]" required /><p v-if="bookingForm.errors.reservation_time" class="mt-1 text-xs text-rose-300">{{ bookingForm.errors.reservation_time }}</p></div>
                            <div><label for="booking-guests" class="mb-2 block text-sm font-semibold text-white/80">Số khách *</label><input id="booking-guests" v-model="bookingForm.guests" min="1" max="30" type="number" class="w-full rounded-xl border-white/10 bg-white/10 px-4 py-3 text-sm text-white focus:border-[#e7a15b] focus:ring-[#e7a15b]" required /><p v-if="bookingForm.errors.guests" class="mt-1 text-xs text-rose-300">{{ bookingForm.errors.guests }}</p></div>
                            <div class="sm:col-span-2"><label for="booking-note" class="mb-2 block text-sm font-semibold text-white/80">Ghi chú</label><textarea id="booking-note" v-model="bookingForm.note" rows="3" class="w-full rounded-xl border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-white/35 focus:border-[#e7a15b] focus:ring-[#e7a15b]" placeholder="Ví dụ: bàn gần cửa sổ, sinh nhật..."></textarea><p v-if="bookingForm.errors.note" class="mt-1 text-xs text-rose-300">{{ bookingForm.errors.note }}</p></div>
                            <div class="flex items-center justify-between gap-4 sm:col-span-2"><p class="text-xs text-white/40">Thông tin dùng để xác nhận đặt bàn.</p><button type="submit" :disabled="bookingForm.processing || !branches.length" class="rounded-full bg-[#e36c3d] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#c9572b] disabled:cursor-not-allowed disabled:opacity-50">{{ bookingForm.processing ? 'Đang gửi...' : 'Gửi yêu cầu' }}</button></div>
                        </form>
                    </div>
                </div>
            </section>

            <section v-else class="bg-[#20332d] px-5 py-16 text-white lg:py-24">
                <div class="mx-auto max-w-7xl lg:px-8"><div class="flex flex-col justify-between gap-7 sm:flex-row sm:items-end"><div><p class="text-xs font-bold uppercase tracking-[0.25em] text-[#e7a15b]">Món ngon mỗi ngày</p><h1 class="mt-3 text-5xl font-bold tracking-tight sm:text-6xl">Menu của Bếp</h1></div><p class="max-w-sm text-sm leading-6 text-white/60">Từ món khai vị nhẹ nhàng đến những món chính đậm đà hương vị quê nhà.</p></div>
                    <div class="mt-10 flex gap-2 overflow-x-auto pb-2" aria-label="Lọc danh mục món ăn"><button v-for="category in categories" :key="category" type="button" class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition" :class="activeCategory === category ? 'bg-[#e36c3d] text-white' : 'bg-white/10 text-white/65 hover:bg-white/15 hover:text-white'" @click="activeCategory = category">{{ category }}</button></div>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><article v-for="menu in filteredMenus" :key="menu.id" class="group rounded-2xl border border-white/10 bg-white/[0.06] p-5 transition hover:border-[#e7a15b]/40 hover:bg-white/[0.1]"><div class="flex items-start justify-between gap-4"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e7a15b]/15 text-xl font-bold text-[#e7a15b]">{{ menu.name?.charAt(0) }}</div><div class="flex flex-wrap justify-end gap-1.5"><span v-if="menu.is_best_seller" class="rounded-full bg-[#e36c3d] px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-white">Bán chạy</span><span v-if="menu.is_must_try" class="rounded-full bg-[#e7a15b] px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-[#20332d]">Nên thử</span></div></div><p class="mt-5 text-[10px] font-bold uppercase tracking-[0.2em] text-white/45">{{ menu.category?.name || 'Món ăn' }}</p><h2 class="mt-2 text-xl font-bold">{{ menu.name }}</h2><p class="mt-2 min-h-10 text-sm leading-5 text-white/55">{{ menu.description || 'Hương vị đặc trưng được chuẩn bị mỗi ngày.' }}</p><p class="mt-5 text-lg font-bold text-[#e7a15b]">{{ formatPrice(menu.price) }}</p></article><div v-if="!filteredMenus.length" class="rounded-2xl border border-dashed border-white/20 p-8 text-center text-white/60 sm:col-span-2 lg:col-span-3">Thực đơn đang được cập nhật.</div></div>
                </div>
            </section>
        </main>

        <footer class="border-t border-[#20332d]/10 bg-white/50"><div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm text-[#20332d]/55 sm:flex-row sm:items-center sm:justify-between lg:px-8"><button type="button" class="flex items-center gap-2 text-lg font-bold text-[#20332d]" @click="selectTab('home')"><span class="h-2 w-2 rounded-full bg-[#e36c3d]"></span>Bếp Việt</button><p>© {{ new Date().getFullYear() }} Bếp Việt. Hẹn gặp bạn bên mâm cơm thân quen.</p></div></footer>
    </div>
</template>

<style scoped>
.landing-page {
    font-family: 'Roboto', sans-serif;
}
</style>
