<script setup>
/**
 * Дашборд после входа. В спринте 1 все разделы продукта — заглушки
 * с пометкой «появится после заполнения профиля и таблиц» (п. 10 ТЗ).
 * Реальные расчёты капитала и планов появятся в следующих спринтах.
 */
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const user = usePage().props.auth.user;
const isAdmin = computed(() => user?.role === 'admin');

// Будущие разделы платформы — сейчас заглушки (п. 10 ТЗ).
const sections = [
    { title: 'Доходы', note: 'Таблица доходов появится в следующих спринтах.' },
    { title: 'Расходы', note: 'Таблица расходов появится в следующих спринтах.' },
    { title: 'Активы', note: 'Таблица активов появится в следующих спринтах.' },
    { title: 'Пассивы', note: 'Таблица пассивов появится в следующих спринтах.' },
    { title: 'План к финансовой независимости', note: 'Появится после заполнения профиля и таблиц.' },
];
</script>

<template>
    <Head title="Дашборд" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Здравствуйте, {{ user.name }}!
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

                <div class="rounded-2xl bg-white p-6 shadow">
                    <h3 class="font-semibold">Начните с профиля</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Платформа рассчитает капитал и пассивный доход на основе вашего профиля
                        и таблиц доходов и расходов.
                    </p>
                    <Link :href="route('profile.edit')" class="mt-3 inline-block rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Заполнить профиль
                    </Link>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="s in sections" :key="s.title" class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-6 text-center">
                        <h4 class="font-semibold text-slate-700">{{ s.title }}</h4>
                        <p class="mt-2 text-sm text-slate-400">Заглушка — появится после заполнения профиля и таблиц.</p>
                    </div>
                </div>

                <div v-if="isAdmin" class="rounded-2xl bg-slate-800 p-6 text-white">
                    <h3 class="font-semibold">Администрирование</h3>
                    <div class="mt-3 flex flex-wrap gap-3 text-sm">
                        <Link :href="route('admin.users.index')" class="rounded-lg bg-white/10 px-4 py-2 hover:bg-white/20">Пользователи</Link>
                        <Link :href="route('admin.logs.index')" class="rounded-lg bg-white/10 px-4 py-2 hover:bg-white/20">Журнал действий</Link>
                        <Link :href="route('admin.integrations.index')" class="rounded-lg bg-white/10 px-4 py-2 hover:bg-white/20">Настройки интеграций</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
