<script setup>
/**
 * Посадочная страница (п. 3 ТЗ).
 * Видят только НЕавторизованные пользователи; авторизованный
 * перенаправляется сразу на дашборд. Кнопка «Попробовать бесплатно»
 * открывает модальное окно с переключателем «Вход / Регистрация» (п. 2 ТЗ).
 *
 * Внимание: демонстрационная вёрстка. В боевом проекте сюда переносится
 * готовый HTML-прототип заказчика в компоненты Vue без перерисовки.
 */
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import LoginForm from './Auth/Partials/LoginForm.vue';
import RegisterForm from './Auth/Partials/RegisterForm.vue';

// Авторизованный пользователь не должен видеть посадочную — сразу на дашборд.
const page = usePage();
if (page.props.auth.user) {
    router.replace('/dashboard');
}

const showModal = ref(false);
const mode = ref('register'); // 'register' | 'login' — переключатель окна

const openAuth = (tab) => {
    mode.value = tab;
    showModal.value = true;
};

const year = computed(() => new Date().getFullYear());
</script>

<template>
    <Head title="Платформа по управлению личными финансами" />

    <div class="min-h-screen bg-gradient-to-b from-slate-50 to-white text-slate-800">
        <header class="flex items-center justify-between px-6 py-4 max-w-6xl mx-auto">
            <span class="text-xl font-bold">Личный капитал</span>
            <div class="space-x-3">
                <button @click="openAuth('login')" class="text-sm text-slate-600 hover:text-slate-900">Вход</button>
                <button
                    @click="openAuth('register')"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                >Регистрация</button>
            </div>
        </header>

        <main class="max-w-3xl mx-auto text-center mt-16 px-6">
            <h1 class="text-4xl font-extrabold leading-tight">
                Платформа по управлению личными финансами
            </h1>
            <p class="mt-4 text-lg text-slate-600">
                Считает ваш капитал, пассивный доход и путь к финансовой независимости —
                и выдаёт персональный план.
            </p>
            <button
                @click="openAuth('register')"
                class="mt-8 rounded-xl bg-emerald-600 px-8 py-4 text-lg font-semibold text-white shadow-lg hover:bg-emerald-700"
            >Попробовать бесплатно</button>

            <figure class="mt-12">
                <!-- Скриншот дашборда предоставляет заказчик (п. 3 ТЗ). -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="rounded-xl bg-slate-100 aspect-video flex items-center justify-center text-slate-400">
                        Скриншот дашборда платформы
                    </div>
                </div>
                <figcaption class="mt-2 text-sm text-slate-500">Пример платформы</figcaption>
            </figure>
        </main>

        <footer class="mt-20 border-t border-slate-200 py-6 text-center text-sm text-slate-500">
            © {{ year }} Личный капитал ·
            <Link href="/privacy" class="underline hover:text-slate-700">Политика конфиденциальности</Link> ·
            <Link href="/terms" class="underline hover:text-slate-700">Пользовательское соглашение</Link>
        </footer>

        <!-- Модальное окно: Вход / Регистрация с переключателем (п. 2 ТЗ). -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showModal = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-6 flex rounded-lg bg-slate-100 p-1">
                    <button
                        @click="mode = 'login'"
                        :class="mode === 'login' ? 'bg-white shadow' : 'text-slate-500'"
                        class="flex-1 rounded-md py-2 text-sm font-semibold"
                    >Вход</button>
                    <button
                        @click="mode = 'register'"
                        :class="mode === 'register' ? 'bg-white shadow' : 'text-slate-500'"
                        class="flex-1 rounded-md py-2 text-sm font-semibold"
                    >Регистрация</button>
                </div>

                <LoginForm v-if="mode === 'login'" @switch-to-register="mode = 'register'" @close="showModal = false" />
                <RegisterForm v-else @switch-to-login="mode = 'login'" @close="showModal = false" />
            </div>
        </div>
    </div>
</template>
