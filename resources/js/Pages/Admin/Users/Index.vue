<script setup>
/**
 * Админка: список пользователей (п. 5 ТЗ).
 * Поиск, фильтр по статусу, сортировка, пагинация, массовая блокировка
 * и удаление спама, смена пароля, переход к редактированию.
 */
import { ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: Object,   // пагинатор Laravel
    filters: Object, // текущие фильтры
});

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');
const selected = ref([]);

// Поиск с задержкой 300 мс, чтобы не дёргать сервер на каждую букву.
let timeout;
watch([search, status], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.users.index'), { search: search.value, status: status.value }, { preserveState: true });
    }, 300);
});

const blockForm = useForm({ blocked: true });

const toggleBlock = (user) => {
    blockForm.blocked = !user.is_blocked;
    blockForm.post(route('admin.users.block', user.id), { preserveScroll: true });
};

const bulkForm = useForm({ ids: [], action: 'block' });

const bulk = (action) => {
    if (!selected.value.length) return;
    bulkForm.ids = selected.value;
    bulkForm.action = action;
    bulkForm.post(route('admin.users.bulk'), {
        preserveScroll: true,
        onSuccess: () => (selected.value = []),
    });
};

const destroyForm = useForm({});
const destroy = (user) => {
    if (!confirm(`Удалить пользователя ${user.email}? Действие необратимо.`)) return;
    destroyForm.delete(route('admin.users.destroy', user.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Пользователи — админка" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Пользователи</h2>
                <Link :href="route('admin.users.create')" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    + Создать пользователя
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="$page.props.flash.status" class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ $page.props.flash.status }}</div>

                <!-- Поиск и фильтр по статусу (п. 5 ТЗ). -->
                <div class="mb-4 flex flex-wrap gap-3 rounded-2xl bg-white p-4 shadow">
                    <input v-model="search" type="text" placeholder="Поиск: имя, email, телефон, город"
                        class="w-80 rounded-lg border-slate-300" />
                    <select v-model="status" class="rounded-lg border-slate-300">
                        <option value="">Все статусы</option>
                        <option value="unconfirmed">Email не подтверждён</option>
                        <option value="active">Активен</option>
                    </select>
                    <div class="ml-auto space-x-2">
                        <button @click="bulk('block')" class="rounded-lg bg-amber-100 px-4 py-2 text-sm text-amber-800 hover:bg-amber-200">
                            Заблокировать выбранных
                        </button>
                        <button @click="bulk('delete')" class="rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700 hover:bg-red-200">
                            Удалить выбранных
                        </button>
                    </div>
                </div>

                <!-- Таблица пользователей (п. 5 ТЗ: имя, город, роль, email, телефон, статус, кто пригласил). -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="p-3"><input type="checkbox" @change="selected = $event.target.checked ? users.data.map(u => u.id) : []" /></th>
                                <th class="p-3">Имя</th>
                                <th class="p-3">Email</th>
                                <th class="p-3">Телефон</th>
                                <th class="p-3">Город</th>
                                <th class="p-3">Роль</th>
                                <th class="p-3">Статус</th>
                                <th class="p-3">Кто пригласил</th>
                                <th class="p-3">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in users.data" :key="user.id" :class="{ 'bg-red-50': user.is_blocked }">
                                <td class="p-3"><input v-model="selected" type="checkbox" :value="user.id" /></td>
                                <td class="p-3 font-medium">{{ user.last_name }} {{ user.name }}</td>
                                <td class="p-3">{{ user.email }}</td>
                                <td class="p-3">{{ user.phone ?? '—' }}</td>
                                <td class="p-3">{{ user.city ?? '—' }}</td>
                                <td class="p-3">{{ user.role === 'admin' ? 'Админ' : 'Пользователь' }}</td>
                                <td class="p-3">
                                    <span v-if="user.is_blocked" class="rounded bg-red-100 px-2 py-0.5 text-xs text-red-700">Заблокирован</span>
                                    <span v-else-if="!user.email_verified_at" class="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-700">Email не подтверждён</span>
                                    <span v-else class="rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Активен</span>
                                </td>
                                <td class="p-3">{{ user.inviter ? `${user.inviter.last_name} ${user.inviter.name}` : '—' }}</td>
                                <td class="p-3 space-x-2 whitespace-nowrap">
                                    <Link :href="route('admin.users.edit', user.id)" class="text-emerald-700 underline">Редактировать</Link>
                                    <button @click="toggleBlock(user)" class="text-amber-700 underline">
                                        {{ user.is_blocked ? 'Разблокировать' : 'Блокировать' }}
                                    </button>
                                    <button @click="destroy(user)" class="text-red-600 underline">Удалить</button>
                                </td>
                            </tr>
                            <tr v-if="!users.data.length">
                                <td colspan="9" class="p-6 text-center text-slate-400">Пользователей не найдено.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Пагинация. -->
                <div class="mt-4 flex justify-center gap-2">
                    <Link v-for="link in users.links" :key="link.label" :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="[link.active ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600', !link.url && 'opacity-40 pointer-events-none']"
                        class="rounded-lg px-3 py-1.5 text-sm shadow-sm" preserve-scroll />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
