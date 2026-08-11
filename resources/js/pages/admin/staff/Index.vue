<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/shop/Pagination.vue';
import { Badge } from '@/components/ui/badge';
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
import AdminLayout from '@/layouts/AdminLayout.vue';
import { destroy as destroyStaff, edit as editStaff, store as storeStaff } from '@/routes/admin/staff';
import type { User } from '@/types/auth';
import type { Paginated } from '@/types/shop';

const props = defineProps<{
    staff: Paginated<User>;
    filters: { search: string };
    roles: { value: string; label: string }[];
}>();

const search = ref(props.filters.search ?? '');
const createOpen = ref(false);

const inputClass =
    'h-10 w-full rounded-lg border bg-background px-3 text-sm outline-none transition-colors focus:border-primary';

const form = useForm({
    name: '',
    email: '',
    role: props.roles[0]?.value ?? 'staff',
    contact: '',
    password: '',
    password_confirmation: '',
});

function applyFilters(): void {
    router.get(
        '/admin/staff',
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function submit(): void {
    form.post(storeStaff.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            createOpen.value = false;
        },
    });
}

function openCreate(): void {
    form.reset();
    form.clearErrors();
    createOpen.value = true;
}

function destroy(member: User): void {
    if (confirm(`Remove ${member.name}?`)) {
        router.delete(destroyStaff.url(member.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Staff" />
    <AdminLayout title="Staff">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative flex-1 sm:max-w-xs">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search staff..."
                    class="h-10 w-full rounded-lg border bg-card pr-3 pl-9 text-sm outline-none focus:border-primary"
                    @keyup.enter="applyFilters"
                />
            </div>
            <Button class="gap-1.5" @click="openCreate">
                <Plus class="size-4" /> Add staff
            </Button>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-[11px] font-semibold tracking-[0.1em] text-muted-foreground uppercase">
                            <th class="px-5 py-3.5">Name</th>
                            <th class="px-5 py-3.5">Email</th>
                            <th class="px-5 py-3.5">Role</th>
                            <th class="px-5 py-3.5">Contact</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="member in staff.data" :key="member.id" class="transition-colors hover:bg-muted/40">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 items-center justify-center rounded-full bg-primary/10 font-display text-sm font-semibold text-primary">
                                        {{ member.name.charAt(0) }}
                                    </span>
                                    <span class="font-medium">{{ member.name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-muted-foreground">{{ member.email }}</td>
                            <td class="px-5 py-3.5">
                                <Badge :variant="member.role === 'admin' ? 'default' : 'secondary'" class="capitalize">
                                    {{ member.role }}
                                </Badge>
                            </td>
                            <td class="px-5 py-3.5 text-muted-foreground">{{ member.contact ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <Button as-child size="icon" variant="ghost">
                                        <Link :href="editStaff(member.id)"><Pencil class="size-4" /></Link>
                                    </Button>
                                    <Button size="icon" variant="ghost" class="text-red-600 hover:text-red-600" @click="destroy(member)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!staff.data.length">
                            <td colspan="5" class="px-5 py-10 text-center text-muted-foreground">No staff found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <Pagination :links="staff.links" />
        </div>

        <!-- Add staff modal -->
        <Dialog v-model:open="createOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="font-display text-xl">Add a staff member</DialogTitle>
                    <DialogDescription>They'll be able to sign in to the admin panel right away.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="name">Name</Label>
                        <input id="name" v-model="form.name" :class="inputClass" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="email">Email</Label>
                        <input id="email" v-model="form.email" type="email" :class="inputClass" required />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="role">Role</Label>
                        <select id="role" v-model="form.role" :class="inputClass">
                            <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                        </select>
                        <InputError :message="form.errors.role" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="contact">Contact</Label>
                        <input id="contact" v-model="form.contact" :class="inputClass" />
                        <InputError :message="form.errors.contact" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="password">Password</Label>
                        <input id="password" v-model="form.password" type="password" :class="inputClass" required autocomplete="new-password" />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="password_confirmation">Confirm password</Label>
                        <input id="password_confirmation" v-model="form.password_confirmation" type="password" :class="inputClass" autocomplete="new-password" />
                    </div>

                    <DialogFooter class="sm:col-span-2">
                        <Button type="button" variant="ghost" @click="createOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">Add staff</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
