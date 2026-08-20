<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});

/** Customers must be at least 18 years old, so the picker can't go past this date. */
const maxDob = (() => {
    const date = new Date();
    date.setFullYear(date.getFullYear() - 18);

    return date.toISOString().slice(0, 10);
})();

/** Strip anything but digits (and a leading "+" for a country code) as the user types. */
function sanitizeContactInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const hasLeadingPlus = input.value.startsWith('+');
    const digits = input.value.replace(/[^0-9]/g, '');
    const cleaned = hasLeadingPlus ? `+${digits}` : digits;

    if (input.value !== cleaned) {
        input.value = cleaned;
    }
}
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name <span class="text-red-500">*</span></Label>
                <Input
                    id="name"
                    type="text"
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address <span class="text-red-500">*</span></Label>
                <Input
                    id="email"
                    type="email"
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="contact">Contact number <span class="text-red-500">*</span></Label>
                    <Input
                        id="contact"
                        type="tel"
                        inputmode="tel"
                        pattern="\+?[0-9]*"
                        maxlength="16"
                        placeholder="98XXXXXXXX"
                        :tabindex="3"
                        autocomplete="tel"
                        name="contact"
                        @input="sanitizeContactInput"
                    />
                    <InputError :message="errors.contact" />
                </div>

                <div class="grid gap-2">
                    <Label for="dob">Date of birth <span class="text-red-500">*</span></Label>
                    <Input
                        id="dob"
                        type="date"
                        :max="maxDob"
                        :tabindex="4"
                        name="dob"
                    />
                    <InputError :message="errors.dob" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="password">Password <span class="text-red-500">*</span></Label>
                <PasswordInput
                    id="password"
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password <span class="text-red-500">*</span></Label>
                <PasswordInput
                    id="password_confirmation"
                    :tabindex="6"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="7"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="8"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
