<script setup>
/**
 * Журнал действий администраторов (п. 5 ТЗ):
 * кто, когда и что менял у пользователей.
 */
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');

let timeout;
watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.logs.index'), { search: search.value }, { preserveState: true });
    }, 300);
});

// Человекочитаемые названия действий из журнала.
const actionLabels = {
    'user.create': 'Создание пользователя',
    'user.update': 'Редактирование пользователя',
    'user.block': 'Блокировка',
    'user.unblock': 'Разблокировка',
    'user.delete': 'Удаление пользователя',
    'user.reset-password': 'Смена пароля',
};
</script>

<template>
    <Head title="Журнал действий — админка" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold">Журнал действий</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="mb-4 rounded-2xl bg-white p-4 shadow">
                    <input v-model="search" type="text" placeholder="Поиск по email или имени пользователя"
                        class="w-80 rounded-lg border-slate-300" />
                </div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="p-3">Когда</th>
                                <th class="p-3">Кто (админ)</th>
                                <th class="p-3">Действие</th>
                                <th class="p-3">Над кем / чем</th>
                                <th class="p-3">Детали</th>
                                <th class="p-3">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="log in logs.data" :key="log.id">
                                <td class="p-3 whitespace-nowrap">{{ new Date(log.created_at).toLocaleString('ru-RU') }}</td>
                                <td class="p-3">{{ log.admin?.last_name }} {{ log.admin?.name }}</td>
                                <td class="p-3">
                                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs">{{ actionLabels[log.action] ?? log.action }}</span>
                                </td>
                                <td class="p-3">{{ log.target_user?.email ?? '—' }}</td>
                                <td class="p-3 max-w-xs truncate text-slate-500">{{ log.details ? JSON.stringify(log.details) : '—' }}</td>
                                <td class="p-3 text-slate-500">{{ log.ip ?? '—' }}</td>
                            </tr>
                            <tr v-if="!logs.data.length">
                                <td colspan="6" class="p-6 text-center text-slate-400">Записей нет.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-center gap-2">
                    <Link v-for="link in logs.links" :key="link.label" :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="[link.active ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600', !link.url && 'opacity-40 pointer-events-none']"
                        class="rounded-lg px-3 py-1.5 text-sm shadow-sm" preserve-scroll />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
