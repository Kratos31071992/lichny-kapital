<script setup>
/**
 * Форма регистрации (п. 2 ТЗ): фамилия, имя, email, телефон, город,
 * пароль с подтверждением и индикатором надёжности, чекбокс согласия,
 * демо-капча, honeypot-поле от ботов.
 */
import { computed, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    captcha: Object, // { question, expected } — демо-капча
});
const emit = defineEmits(['switch-to-login', 'close']);

const form = useForm({
    name: '',
    last_name: '',
    email: '',
    phone: '',
    city: '',
    password: '',
    password_confirmation: '',
    agreement: false,
    captcha_answer: '',
    captcha_expected: props.captcha?.expected, // демо: сверяется на сервере
    website: '', // honeypot: должно остаться пустым
});

// Индикатор надёжности пароля (п. 2 ТЗ): длина, регистры, цифры, символы.
const passwordScore = computed(() => {
    const p = form.password;
    if (!p) return 0;
    let score = 0;
    if (p.length >= 8) score++;
    if (p.length >= 12) score++;
    if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score++;
    if (/\d/.test(p)) score++;
    if (/[^a-zA-Z0-9]/.test(p)) score++;
    return Math.min(score, 4);
});

const strengthLabels = ['', 'Слабый', 'Средний', 'Хороший', 'Надёжный'];
const strengthColors = ['', 'bg-red-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-500'];

const submit = () => {
    form.post(route('register'), {
        onSuccess: () => emit('close'),
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <h2 class="text-lg font-bold">Регистрация</h2>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <input v-model="form.last_name" type="text" placeholder="Фамилия" class="w-full rounded-lg border-slate-300" required />
                <InputError :message="form.errors.last_name" />
            </div>
            <div>
                <input v-model="form.name" type="text" placeholder="Имя" class="w-full rounded-lg border-slate-300" required />
                <InputError :message="form.errors.name" />
            </div>
        </div>

        <div>
            <input v-model="form.email" type="email" placeholder="Email" class="w-full rounded-lg border-slate-300" required />
            <InputError :message="form.errors.email" />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <input v-model="form.phone" type="tel" placeholder="Телефон" class="w-full rounded-lg border-slate-300" required />
                <InputError :message="form.errors.phone" />
            </div>
            <div>
                <input v-model="form.city" type="text" placeholder="Город" class="w-full rounded-lg border-slate-300" required />
                <InputError :message="form.errors.city" />
            </div>
        </div>

        <div>
            <input v-model="form.password" type="password" placeholder="Пароль" class="w-full rounded-lg border-slate-300" required />
            <!-- Индикатор надёжности пароля (п. 2 ТЗ). -->
            <div v-if="form.password" class="mt-1 flex items-center gap-2">
                <div class="h-1.5 flex-1 rounded bg-slate-200">
                    <div class="h-1.5 rounded" :class="strengthColors[passwordScore]" :style="{ width: (passwordScore / 4 * 100) + '%' }" />
                </div>
                <span class="text-xs" :class="passwordScore <= 1 ? 'text-red-600' : 'text-slate-500'">{{ strengthLabels[passwordScore] }}</span>
            </div>
            <InputError :message="form.errors.password" />
        </div>

        <div>
            <input v-model="form.password_confirmation" type="password" placeholder="Повторите пароль" class="w-full rounded-lg border-slate-300" required />
        </div>

        <!-- Демо-капча. В продакшене — Яндекс SmartCaptcha. -->
        <div>
            <label class="block text-sm text-slate-600">Антиспам: {{ captcha?.question ?? '2 + 2 = ?' }}</label>
            <input v-model="form.captcha_answer" type="number" class="mt-1 w-full rounded-lg border-slate-300" required />
            <InputError :message="form.errors.captcha_answer" />
        </div>

        <!-- Honeypot: невидимое поле. Боты заполняют, люди — нет. -->
        <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" />

        <label class="flex items-start gap-2 text-sm text-slate-600">
            <input v-model="form.agreement" type="checkbox" class="mt-0.5 rounded border-slate-300" required />
            <span>Согласен с <a href="/terms" class="underline">Пользовательским соглашением</a>
                и <a href="/privacy" class="underline">Политикой конфиденциальности</a></span>
        </label>
        <InputError :message="form.errors.agreement" />

        <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-emerald-600 py-2.5 font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
            Зарегистрироваться
        </button>

        <p class="text-center text-sm text-slate-500">
            Уже есть аккаунт?
            <button type="button" @click="emit('switch-to-login')" class="text-emerald-700 underline">Войти</button>
        </p>
    </form>
</template>
