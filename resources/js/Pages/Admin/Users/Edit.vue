<script setup>
/**
 * Админка: создание и редактирование пользователя (п. 5 ТЗ),
 * включая генерацию и смену паролей.
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: Object, // null — режим создания
});

const isEdit = props.user !== null;

const form = useForm({
    name: props.user?.name ?? '',
    last_name: props.user?.last_name ?? '',
    email: props.user?.email ?? '',
    phone: props.user?.phone ?? '',
    city: props.user?.city ?? '',
    role: props.user?.role ?? 'user',
});

const submit = () => {
    if (isEdit) {
        form.put(route('admin.users.update', props.user.id));
    } else {
        form.post(route('admin.users.store'));
    }
};

// Смена пароля: либо админ вводит вручную, либо система генерирует.
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
    generate: false,
});

const resetPassword = () => {
    passwordForm.post(route('admin.users.reset-password', props.user.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="isEdit ? 'Редактирование пользователя' : 'Новый пользователь'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold">{{ isEdit ? 'Редактирование пользователя' : 'Новый пользователь' }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div v-if="$page.props.flash.status" class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ $page.props.flash.status }}</div>

                <form @submit.prevent="submit" class="space-y-4 rounded-2xl bg-white p-6 shadow">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm text-slate-600">Имя</label>
                            <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border-slate-300" required />
                            <p v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-slate-600">Фамилия</label>
                            <input v-model="form.last_name" type="text" class="mt-1 w-full rounded-lg border-slate-300" />
                        </div>
                        <div>
                            <label class="text-sm text-slate-600">Email</label>
                            <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-slate-300" required />
                            <p v-if="form.errors.email" class="text-xs text-red-600">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-slate-600">Телефон</label>
                            <input v-model="form.phone" type="tel" class="mt-1 w-full rounded-lg border-slate-300" />
                        </div>
                        <div>
                            <label class="text-sm text-slate-600">Город</label>
                            <input v-model="form.city" type="text" class="mt-1 w-full rounded-lg border-slate-300" />
                        </div>
                        <div>
                            <label class="text-sm text-slate-600">Роль</label>
                            <select v-model="form.role" class="mt-1 w-full rounded-lg border-slate-300">
                                <option value="user">Пользователь</option>
                                <option value="admin">Администратор</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                            {{ isEdit ? 'Сохранить' : 'Создать пользователя' }}
                        </button>
                        <Link :href="route('admin.users.index')" class="text-sm text-slate-500 underline">Отмена</Link>
                    </div>
                </form>

                <!-- Смена пароля — только для существующего пользователя (п. 5 ТЗ). -->
                <form v-if="isEdit" @submit.prevent="resetPassword" class="mt-6 space-y-4 rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <h3 class="font-semibold">Смена пароля</h3>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="passwordForm.generate" type="checkbox" class="rounded border-slate-300" />
                        Сгенерировать случайный пароль
                    </label>
                    <template v-if="!passwordForm.generate">
                        <input v-model="passwordForm.password" type="password" placeholder="Новый пароль" class="w-full rounded-lg border-slate-300" />
                        <input v-model="passwordForm.password_confirmation" type="password" placeholder="Повторите пароль" class="w-full rounded-lg border-slate-300" />
                    </template>
                    <button type="submit" :disabled="passwordForm.processing" class="rounded-lg bg-amber-600 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-50">
                        Сменить пароль
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
