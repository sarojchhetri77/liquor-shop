<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { Promotion } from '@/types/shop';

const props = defineProps<{
    promotion?: Promotion;
}>();

const isEdit = computed(() => !!props.promotion);
const preview = ref<string | null>(props.promotion?.image_url ?? null);

const form = useForm({
    _method: isEdit.value ? 'put' : 'post',
    title: props.promotion?.title ?? '',
    link: props.promotion?.link ?? '',
    is_active: props.promotion?.is_active ?? true,
    sort_order: props.promotion?.sort_order ?? 0,
    image: null as File | null,
});

const inputClass =
    'h-10 w-full rounded-md border bg-background px-3 text-sm outline-none focus:border-primary';

function onFile(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.image = file;
    preview.value = file ? URL.createObjectURL(file) : (props.promotion?.image_url ?? null);
}

function submit(): void {
    const url = isEdit.value
        ? `/admin/promotions/${props.promotion!.id}`
        : '/admin/promotions';
    form.post(url, { forceFormData: true });
}
</script>

<template>
    <Head :title="isEdit ? 'Edit promotion' : 'New promotion'" />
    <AdminLayout :title="isEdit ? 'Edit promotion' : 'New promotion'">
        <Link
            href="/admin/promotions"
            class="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to promotions
        </Link>

        <form class="grid max-w-4xl gap-6 lg:grid-cols-2" @submit.prevent="submit">
            <div class="space-y-4">
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <div class="grid gap-4">
                        <div class="grid gap-1.5">
                            <Label for="title">Title</Label>
                            <input id="title" v-model="form.title" :class="inputClass" required />
                            <InputError :message="form.errors.title" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="link">Link (optional)</Label>
                            <input id="link" v-model="form.link" :class="inputClass" placeholder="/products" />
                            <InputError :message="form.errors.link" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="sort_order">Priority (lower shows first)</Label>
                            <input id="sort_order" v-model.number="form.sort_order" type="number" min="0" :class="inputClass" />
                            <InputError :message="form.errors.sort_order" />
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-muted-foreground/40" />
                            Active (show popup on homepage)
                        </label>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button type="submit" :disabled="form.processing">
                        {{ isEdit ? 'Save changes' : 'Create promotion' }}
                    </Button>
                    <Button as-child variant="ghost"><Link href="/admin/promotions">Cancel</Link></Button>
                </div>
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <Label for="image">Popup image</Label>
                <p class="mb-3 text-xs text-muted-foreground">
                    Landscape banner works best (e.g. 900×600). This is what customers see in the popup.
                </p>
                <div
                    class="flex aspect-[3/2] items-center justify-center overflow-hidden rounded-lg border border-dashed bg-muted"
                >
                    <img v-if="preview" :src="preview" class="h-full w-full object-contain" />
                    <span v-else class="text-sm text-muted-foreground">No image selected</span>
                </div>
                <input id="image" type="file" accept="image/*" class="mt-3 text-sm" @change="onFile" />
                <InputError :message="form.errors.image" />
            </div>
        </form>
    </AdminLayout>
</template>
