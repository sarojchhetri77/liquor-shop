<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { cn } from '@/lib/utils';
import {
    destroy as destroyUser,
    index as adminUsers,
    store as storeUser,
    update as updateUser,
} from '@/routes/admin/users';
import type { User } from '@/types/auth';
import type { Paginated } from '@/types/shop';

/** How long typing has to pause before the table reloads. */
const SEARCH_DEBOUNCE_MS = 300;

type RoleOption = { value: string; label: string };

const props = defineProps<{
    users: Paginated<User>;
    filters: { search: string; role: string };
    roles: RoleOption[];
    counts: Record<string, number>;
}>();

const search = ref(props.filters.search ?? '');
const role = ref(props.filters.role ?? '');
const dialogOpen = ref(false);
const editing = ref<User | null>(null);
const isFiltering = ref(false);
let searchTimer: ReturnType<typeof setTimeout> | undefined;

const isEdit = computed(() => editing.value !== null);
const showPassword = ref(false);

const inputClass =
    'h-11 w-full rounded-lg border bg-background px-3.5 text-sm outline-none transition-colors focus:border-primary';

const roleStyles: Record<string, string> = {
    admin: 'bg-primary/10 text-primary',
    staff: 'bg-amber-500/15 text-amber-700 dark:text-amber-500',
    customer: 'bg-muted text-muted-foreground',
};

const form = useForm({
    name: '',
    email: '',
    role: 'customer',
    contact: '',
    password: '',
    password_confirmation: '',
});

function applyFilters(): void {
    clearTimeout(searchTimer);

    router.get(
        adminUsers.url(),
        {
            search: search.value || undefined,
            role: role.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters', 'counts'],
            onStart: () => {
                isFiltering.value = true;
            },
            onFinish: () => {
                isFiltering.value = false;
            },
        },
    );
}

// Filter the table as the admin types, rather than waiting for Enter.
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, SEARCH_DEBOUNCE_MS);
});

function selectRole(value: string): void {
    role.value = value;
    applyFilters();
}

onBeforeUnmount(() => clearTimeout(searchTimer));

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.role = 'customer';
    dialogOpen.value = true;
}

function openEdit(user: User): void {
    editing.value = user;
    form.reset();
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.role = user.role ?? 'customer';
    form.contact = user.contact ?? '';
    dialogOpen.value = true;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    };

    if (isEdit.value) {
        form.put(updateUser.url(editing.value!.id), options);

        return;
    }

    form.post(storeUser.url(), options);
}

function destroy(user: User): void {
    if (confirm(`Delete "${user.name}" (${user.email})? This cannot be undone.`)) {
        router.delete(destroyUser.url(user.id), { preserveScroll: true });
    }
}

function formatDate(value: string | undefined): string {
    return value
        ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(value))
        : '—';
}
</script>

<template>
    <Head title="Users" />
    <AdminLayout title="Users">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative flex-1 sm:max-w-xs">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search by name or email…"
                    class="h-11 w-full rounded-lg border bg-card pr-9 pl-9 text-sm outline-none transition-colors focus:border-primary"
                />
                <Spinner
                    v-if="isFiltering"
                    class="absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
            </div>
            <Button class="h-11 gap-1.5" @click="openCreate">
                <Plus class="size-4" /> New user
            </Button>
        </div>

        <!-- Role filter -->
        <div class="mt-3 flex flex-wrap gap-1.5">
            <button
                :class="[
                    'rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                    role === '' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/70',
                ]"
                @click="selectRole('')"
            >
                All ({{ counts.all ?? 0 }})
            </button>
            <button
                v-for="option in roles"
                :key="option.value"
                :class="[
                    'rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                    role === option.value ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/70',
                ]"
                @click="selectRole(option.value)"
            >
                {{ option.label }} ({{ counts[option.value] ?? 0 }})
            </button>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-[11px] font-semibold tracking-[0.1em] text-muted-foreground uppercase">
                            <th class="px-5 py-3.5">User</th>
                            <th class="px-5 py-3.5">Role</th>
                            <th class="hidden px-5 py-3.5 sm:table-cell">Contact</th>
                            <th class="hidden px-5 py-3.5 md:table-cell">Orders</th>
                            <th class="hidden px-5 py-3.5 lg:table-cell">Joined</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="user in users.data" :key="user.id" class="transition-colors hover:bg-muted/40">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary uppercase">
                                        {{ user.name.slice(0, 1) }}
                                    </span>
                                    <div class="min-w-0">
                                        <button class="block truncate font-medium transition-colors hover:text-primary" @click="openEdit(user)">
                                            {{ user.name }}
                                        </button>
                                        <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize', roleStyles[user.role ?? 'customer']]">
                                    {{ user.role }}
                                </span>
                            </td>
                            <td class="hidden px-5 py-3.5 text-muted-foreground sm:table-cell">{{ user.contact || '—' }}</td>
                            <td class="hidden px-5 py-3.5 text-muted-foreground md:table-cell">{{ user.orders_count ?? 0 }}</td>
                            <td class="hidden px-5 py-3.5 text-muted-foreground lg:table-cell">{{ formatDate(user.created_at) }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <Button size="icon" variant="ghost" @click="openEdit(user)">
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button size="icon" variant="ghost" class="text-red-600 hover:text-red-600" @click="destroy(user)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!users.data.length">
                            <td colspan="6" class="px-5 py-12 text-center text-muted-foreground">No users found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <Pagination :links="users.links" />
        </div>

        <!-- Create / edit modal -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="font-display text-xl">{{ isEdit ? 'Edit user' : 'New user' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEdit ? 'Leave the password blank to keep the current one.' : 'Accounts created here are verified straight away.' }}
                    </DialogDescription>
                </DialogHeader>

                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-1.5">
                        <Label for="user-name">Name</Label>
                        <input id="user-name" v-model="form.name" :class="inputClass" placeholder="Full name" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="user-email">Email</Label>
                            <input id="user-email" v-model="form.email" type="email" :class="inputClass" placeholder="name@example.com" required />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="user-role">Role</Label>
                            <select id="user-role" v-model="form.role" :class="inputClass">
                                <option v-for="option in roles" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <InputError :message="form.errors.role" />
                        </div>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="user-contact">Contact</Label>
                        <input id="user-contact" v-model="form.contact" :class="inputClass" placeholder="Phone number (optional)" />
                        <InputError :message="form.errors.contact" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="user-password">{{ isEdit ? 'New password' : 'Password' }}</Label>
                            <div class="relative">
                                <input
                                    id="user-password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    :required="!isEdit"
                                    autocomplete="new-password"
                                    :class="cn(inputClass, 'pr-10')"
                                    :placeholder="isEdit ? 'Leave blank to keep' : 'Password'"
                                />
                                <button
                                    type="button"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeOff v-if="showPassword" class="size-4" />
                                    <Eye v-else class="size-4" />
                                </button>
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="user-password-confirm">Confirm password</Label>
                            <input
                                id="user-password-confirm"
                                v-model="form.password_confirmation"
                                :type="showPassword ? 'text' : 'password'"
                                :required="!isEdit && form.password !== ''"
                                autocomplete="new-password"
                                :class="inputClass"
                                placeholder="Repeat password"
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="dialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">{{ isEdit ? 'Save changes' : 'Create user' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
