<script setup>
/**
 * Профиль пользователя (п. 4 ТЗ) — целиком:
 * контакты, семья и работа, текстовые блоки, шкала активного дохода,
 * индикатор заполненности, «Скачать мои данные» и «Удалить аккаунт» (152-ФЗ).
 */
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    profileCompletion: Number, // процент заполненности (0–100)
    profileBlocks: Array,      // детализация по блокам
    messengers: Array,         // MAX, Telegram, WhatsApp
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name ?? '',
    last_name: user.last_name ?? '',
    email: user.email ?? '',
    phone: user.phone ?? '',
    city: user.city ?? '',
    birth_date: user.birth_date?.slice(0, 10) ?? '', // date input принимает YYYY-MM-DD
    messengers: user.messengers ?? [],
    family_status: user.family_status ?? '',
    children: user.children ?? '',
    employment: user.employment ?? '',
    profession: user.profession ?? '',
    risk_attitude: user.risk_attitude ?? '',
    planning_horizon: user.planning_horizon ?? '',
    about_situation: user.about_situation ?? '',
    investment_strategy: user.investment_strategy ?? '',
    active_income_until: user.active_income_until ?? 60,
    photo: null,
});

// Индикатор заполненности: доля заполненных блоков × 100 (см. сервис на бэкенде).
const completionColor = computed(() =>
    props.profileCompletion >= 80 ? 'bg-emerald-500'
        : props.profileCompletion >= 40 ? 'bg-amber-500'
        : 'bg-red-400',
);

const submit = () => {
    // Фото передаётся multipart/form-data, Inertia делает это автоматически.
    form.transform((data) => ({ ...data, photo: form.photo })).post(route('profile.update'), {
        _method: 'patch',
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Профиль" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Мой профиль</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

                <!-- Индикатор «Профиль заполнен на N%» (п. 4 ТЗ). -->
                <div class="rounded-2xl bg-white p-6 shadow">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold">Профиль заполнен на {{ profileCompletion }}%</h3>
                        <Link :href="route('profile.export')" class="text-sm text-emerald-700 underline">
                            Скачать мои данные
                        </Link>
                    </div>
                    <div class="mt-3 h-2.5 rounded bg-slate-200">
                        <div class="h-2.5 rounded transition-all" :class="completionColor" :style="{ width: profileCompletion + '%' }" />
                    </div>
                    <p class="mt-2 text-sm text-slate-500">
                        Заполните блоки ниже — платформа точнее рассчитает путь к финансовой независимости.
                    </p>
                </div>

                <div v-if="status" class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800">{{ status }}</div>

                <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
                    <!-- БЛОК 1. Контакты (п. 4 ТЗ). -->
                    <section class="rounded-2xl bg-white p-6 shadow">
                        <h3 class="mb-4 font-semibold">Контакты</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm text-slate-600">Имя</label>
                                <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border-slate-300" required />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Фамилия</label>
                                <input v-model="form.last_name" type="text" class="mt-1 w-full rounded-lg border-slate-300" required />
                                <InputError :message="form.errors.last_name" />
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Email</label>
                                <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-slate-300" required />
                                <InputError :message="form.errors.email" />
                                <p v-if="mustVerifyEmail && !user.email_verified_at" class="mt-1 text-xs text-amber-600">
                                    Email не подтверждён — проверьте почту.
                                </p>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Телефон</label>
                                <input v-model="form.phone" type="tel" class="mt-1 w-full rounded-lg border-slate-300" />
                                <InputError :message="form.errors.phone" />
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Город</label>
                                <input v-model="form.city" type="text" class="mt-1 w-full rounded-lg border-slate-300" />
                                <InputError :message="form.errors.city" />
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Дата рождения</label>
                                <input v-model="form.birth_date" type="date" class="mt-1 w-full rounded-lg border-slate-300" />
                                <InputError :message="form.errors.birth_date" />
                            </div>
                        </div>

                        <!-- Предпочитаемые мессенджеры: выбор нескольких. -->
                        <div class="mt-4">
                            <label class="text-sm text-slate-600">Предпочитаемые мессенджеры</label>
                            <div class="mt-2 flex flex-wrap gap-3">
                                <label v-for="m in messengers" :key="m" class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <input v-model="form.messengers" type="checkbox" :value="m" class="rounded border-slate-300" />
                                    {{ m }}
                                </label>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="text-sm text-slate-600">Фото</label>
                            <input type="file" accept="image/*" @input="form.photo = $event.target.files[0]" class="mt-1 block w-full text-sm" />
                            <InputError :message="form.errors.photo" />
                        </div>
                    </section>

                    <!-- БЛОК 2. Семья и работа (п. 4 ТЗ). -->
                    <section class="rounded-2xl bg-white p-6 shadow">
                        <h3 class="mb-4 font-semibold">Семья и работа</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm text-slate-600">Семейное положение</label>
                                <select v-model="form.family_status" class="mt-1 w-full rounded-lg border-slate-300">
                                    <option value="">—</option>
                                    <option>Холост/не замужем</option>
                                    <option>В браке</option>
                                    <option>Разведён(а)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Дети</label>
                                <input v-model="form.children" type="number" min="0" max="30" class="mt-1 w-full rounded-lg border-slate-300" />
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Занятость</label>
                                <select v-model="form.employment" class="mt-1 w-full rounded-lg border-slate-300">
                                    <option value="">—</option>
                                    <option>Найм</option>
                                    <option>Своё дело</option>
                                    <option>Фриланс</option>
                                    <option>Не работаю</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Профессия / бизнес</label>
                                <input v-model="form.profession" type="text" class="mt-1 w-full rounded-lg border-slate-300" />
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Отношение к риску</label>
                                <select v-model="form.risk_attitude" class="mt-1 w-full rounded-lg border-slate-300">
                                    <option value="">—</option>
                                    <option>Консервативное</option>
                                    <option>Умеренное</option>
                                    <option>Агрессивное</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Горизонт планирования</label>
                                <select v-model="form.planning_horizon" class="mt-1 w-full rounded-lg border-slate-300">
                                    <option value="">—</option>
                                    <option>До 3 лет</option>
                                    <option>3–10 лет</option>
                                    <option>10+ лет</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- БЛОК 3. Текстовые блоки (п. 4 ТЗ). -->
                    <section class="rounded-2xl bg-white p-6 shadow">
                        <h3 class="mb-4 font-semibold">Моя ситуация</h3>
                        <textarea v-model="form.about_situation" rows="5" class="w-full rounded-lg border-slate-300"
                            placeholder="Опишите вашу текущую финансовую ситуацию..."></textarea>
                    </section>

                    <section class="rounded-2xl bg-white p-6 shadow">
                        <h3 class="mb-4 font-semibold">Моя инвестиционная стратегия</h3>
                        <textarea v-model="form.investment_strategy" rows="5" class="w-full rounded-lg border-slate-300"
                            placeholder="Как вы планируете распоряжаться капиталом..."></textarea>
                    </section>

                    <!-- БЛОК 4. Шкала активного дохода: ползунок 0–100 лет (п. 4 ТЗ). -->
                    <section class="rounded-2xl bg-white p-6 shadow">
                        <h3 class="mb-1 font-semibold">Активный доход</h3>
                        <p class="text-sm text-slate-500">До какого возраста планируете активно зарабатывать?</p>
                        <div class="mt-4 flex items-center gap-4">
                            <input v-model.number="form.active_income_until" type="range" min="0" max="100" class="flex-1" />
                            <span class="w-16 rounded-lg bg-slate-100 py-1 text-center font-semibold">{{ form.active_income_until }} лет</span>
                        </div>
                    </section>

                    <button type="submit" :disabled="form.processing" class="w-full rounded-xl bg-emerald-600 py-3 font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                        Сохранить профиль
                    </button>
                </form>

                <!-- Удаление аккаунта — отдельная форма с подтверждением паролем (152-ФЗ). -->
                <DeleteUserForm />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
