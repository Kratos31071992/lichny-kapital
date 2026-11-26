<script setup>
/**
 * Форма входа (п. 2 ТЗ): то же модальное окно, вкладка «Вход».
 * Восстановление пароля — отдельная страница со ссылкой из формы.
 */
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const emit = defineEmits(['switch-to-register', 'close']);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onSuccess: () => emit('close'),
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <h2 class="text-lg font-bold">Вход</h2>

        <div>
            <input v-model="form.email" type="email" placeholder="Email" class="w-full rounded-lg border-slate-300" required autofocus />
            <InputError :message="form.errors.email" />
        </div>

        <div>
            <input v-model="form.password" type="password" placeholder="Пароль" class="w-full rounded-lg border-slate-300" required />
            <InputError :message="form.errors.password" />
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-slate-600">
                <input v-model="form.remember" type="checkbox" class="rounded border-slate-300" />
                Запомнить меня
            </label>
            <a :href="route('password.request')" class="text-emerald-700 underline">Забыли пароль?</a>
        </div>

        <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-emerald-600 py-2.5 font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
            Войти
        </button>

        <p class="text-center text-sm text-slate-500">
            Нет аккаунта?
            <button type="button" @click="emit('switch-to-register')" class="text-emerald-700 underline">Зарегистрироваться</button>
        </p>
    </form>
</template>
