<script setup>
/**
 * Настройки интеграций (п. 6 ТЗ): поля для будущих ключей внешних сервисов
 * и кнопка «Тестовая отправка» по каждому каналу уведомлений.
 * SMS подключается заменой драйвера — здесь только интерфейс управления.
 */
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    channels: Object,  // список каналов { mail: '...', sms: '...' }
    settings: Object,  // текущие значения настроек
});

const testForm = useForm({ channel: 'mail' });

const testSend = (channel) => {
    testForm.channel = channel;
    testForm.post(route('admin.integrations.test'), { preserveScroll: true });
};

const backupForm = useForm({});
const backupNow = () => backupForm.post(route('admin.integrations.backup'), { preserveScroll: true });
</script>

<template>
    <Head title="Настройки интеграций — админка" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold">Настройки интеграций</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="$page.props.flash.status" class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ $page.props.flash.status }}</div>

                <div class="rounded-2xl bg-white p-6 shadow">
                    <h3 class="font-semibold">Каналы уведомлений</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Сейчас письма идут через почтовый драйвер, SMS — демо-драйвер (лог).
                        Реальный SMS-сервис подключается заменой драйвера, код платформы не меняется.
                    </p>

                    <div class="mt-4 space-y-3">
                        <div v-for="(label, key) in channels" :key="key" class="flex items-center justify-between rounded-lg border border-slate-200 p-4">
                            <div>
                                <p class="font-medium">{{ label }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ key === 'mail' ? 'Отправитель: ' + settings.mail_from : 'Ключ API: ' + (settings.sms_api_key ?? 'не задан') }}
                                </p>
                            </div>
                            <button @click="testSend(key)" :disabled="testForm.processing"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                                Тестовая отправка
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow">
                    <h3 class="font-semibold">Резервное копирование</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Ежедневный бэкап базы в 03:00 настроен в расписании (scheduler).
                        Копии хранятся 14 дней с автоматической ротацией.
                    </p>
                    <button @click="backupNow" :disabled="backupForm.processing"
                        class="mt-4 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-50">
                        Создать копию сейчас
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
