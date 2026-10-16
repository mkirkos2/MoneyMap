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
        title: 'Create your account',
        description: 'Set up your account to start tracking your finances.',
        variant: 'split',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5 [&_p.text-finance-negative]:text-finance-negative"
    >
        <div class="grid gap-4">
            <div class="grid gap-2">
                <Label for="name" class="text-sm font-medium text-finance-body">Full name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    v-focus
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                    :aria-invalid="Boolean(errors.name)"
                    class="h-11 rounded-md border-finance-border-strong bg-finance-input px-3.5 text-sm text-finance-text shadow-none placeholder:text-finance-faint focus-visible:border-finance-accent focus-visible:ring-finance-accent/20"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email" class="text-sm font-medium text-finance-body">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                    :aria-invalid="Boolean(errors.email)"
                    class="h-11 rounded-md border-finance-border-strong bg-finance-input px-3.5 text-sm text-finance-text shadow-none placeholder:text-finance-faint focus-visible:border-finance-accent focus-visible:ring-finance-accent/20"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password" class="text-sm font-medium text-finance-body">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                    :aria-invalid="Boolean(errors.password)"
                    class="h-11 rounded-md border-finance-border-strong bg-finance-input px-3.5 text-sm text-finance-text shadow-none placeholder:text-finance-faint focus-visible:border-finance-accent focus-visible:ring-finance-accent/20"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation" class="text-sm font-medium text-finance-body">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                    :aria-invalid="Boolean(errors.password_confirmation)"
                    class="h-11 rounded-md border-finance-border-strong bg-finance-input px-3.5 text-sm text-finance-text shadow-none placeholder:text-finance-faint focus-visible:border-finance-accent focus-visible:ring-finance-accent/20"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 h-11 w-full rounded-md bg-finance-action text-sm font-semibold text-finance-action-text shadow-none transition-colors hover:bg-finance-action-hover focus-visible:ring-finance-accent/40"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                {{ processing ? 'Creating your account…' : 'Create account' }}
            </Button>
        </div>

        <div class="border-t border-finance-border pt-5 text-center text-sm text-finance-subtle">
            Already have an account?
            <TextLink
                :href="login()"
                class="ml-1 font-semibold text-finance-accent decoration-finance-accent/50 hover:text-finance-accent"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
