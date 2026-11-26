<script setup>
/**
 * Страница регистрации (отдельная, помимо модального окна посадочной).
 * Использует ту же форму, что и модальное окно на Welcome.
 */
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import RegisterForm from './Partials/RegisterForm.vue';

defineProps({
    captcha: Object,
});
</script>

<template>
    <Head title="Регистрация" />

    <GuestLayout>
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow">
            <RegisterForm @switch-to-login="() => $inertia.get(route('login'))" />
        </div>
    </GuestLayout>
</template>
