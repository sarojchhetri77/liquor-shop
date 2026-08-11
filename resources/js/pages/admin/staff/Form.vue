<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, Copy, Eye, EyeOff } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { index as adminStaff, store as storeStaff, update as updateStaff } from '@/routes/admin/staff';

const props = defineProps<{
    staff?: {
        id: number;
        name: string;
        email: string;
        role: string;
        contact: string | null;
    };
    roles: { value: string; label: string }[];
}>();

const isEdit = computed(() => !!props.staff);

const form = useForm({
    name: props.staff?.name ?? '',
    email: props.staff?.email ?? '',
    role: props.staff?.role ?? props.roles[0]?.value ?? 'staff',
    contact: props.staff?.contact ?? '',
    password: '',
    password_confirmation: '',
});

const inputClass =
    'h-10 w-full rounded-md border bg-background px-3 text-sm outline-none focus:border-primary';

const showPassword = ref(false);
const copied = ref(false);

async function copyPassword(): Promise<void> {
    if (!form.password) {
        return;
    }

    await navigator.clipboard.writeText(form.password);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

function submit(): void {
    if (isEdit.value) {
        form.put(updateStaff.url(props.staff!.id));
    } else {
        form.post(storeStaff.url());
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit staff' : 'Add staff'" />
    <AdminLayout :title="isEdit ? 'Edit staff' : 'Add staff'">
        <Link
            :href="adminStaff()"
            class="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to staff
        </Link>

        <form class="max-w-2xl space-y-4" @submit.prevent="submit">
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="name">Name</Label>
                        <input
                            id="name"
                            v-model="form.name"
                            :class="inputClass"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="email">Email</Label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            :class="inputClass"
                            required
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="role">Role</Label>
                        <select
                            id="role"
                            v-model="form.role"
                            :class="inputClass"
                        >
                            <option
                                v-for="role in roles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.role" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="contact">Contact</Label>
                        <input
                            id="contact"
                            v-model="form.contact"
                            :class="inputClass"
                        />
                        <InputError :message="form.errors.contact" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="password">{{
                            isEdit ? 'New password' : 'Password'
                        }}</Label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                :class="[inputClass, 'pr-16']"
                                :required="!isEdit"
                                autocomplete="new-password"
                            />
                            <div class="absolute inset-y-0 right-2 flex items-center gap-0.5">
                                <button
                                    type="button"
                                    class="flex size-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeOff v-if="showPassword" class="size-4" />
                                    <Eye v-else class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    class="flex size-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                                    :disabled="!form.password"
                                    :aria-label="copied ? 'Password copied' : 'Copy password'"
                                    @click="copyPassword"
                                >
                                    <Check v-if="copied" class="size-4 text-emerald-600" />
                                    <Copy v-else class="size-4" />
                                </button>
                            </div>
                        </div>
                        <p v-if="copied" class="text-xs text-emerald-600">Password copied to clipboard.</p>
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="password_confirmation"
                            >Confirm password</Label
                        >
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showPassword ? 'text' : 'password'"
                            :class="inputClass"
                            autocomplete="new-password"
                        />
                    </div>
                </div>
                <p v-if="isEdit" class="mt-3 text-xs text-muted-foreground">
                    Leave password blank to keep the current one.
                </p>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">{{
                    isEdit ? 'Save changes' : 'Add staff'
                }}</Button>
                <Button as-child variant="ghost"
                    ><Link :href="adminStaff()">Cancel</Link></Button
                >
            </div>
        </form>
    </AdminLayout>
</template>
