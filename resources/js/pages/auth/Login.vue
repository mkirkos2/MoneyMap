<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Welcome back',
        description: 'Continue to your MoneyMap account.',
        variant: 'split',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5 [&_p.text-finance-negative]:text-finance-negative"
    >
        <div
            v-if="status"
            role="status"
            class="border border-finance-accent bg-finance-accent-soft px-4 py-3 text-sm leading-6 text-finance-accent"
        >
            {{ status }}
        </div>

        <div class="grid gap-5">
            <div class="grid gap-2">
                <Label for="email" class="text-sm font-medium text-finance-body">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    v-focus
                    autocomplete="email"
                    placeholder="you@example.com"
                    :aria-invalid="Boolean(errors.email)"
                    class="h-11 rounded-md border-finance-border-strong bg-finance-input px-3.5 text-sm text-finance-text shadow-none placeholder:text-finance-faint focus-visible:border-finance-accent focus-visible:ring-finance-accent/20"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-sm font-medium text-finance-body">Password</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-xs font-medium text-finance-accent decoration-finance-accent/50 hover:text-finance-accent"
                    >
                        Forgot your password?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                    :aria-invalid="Boolean(errors.password)"
                    class="h-11 rounded-md border-finance-border-strong bg-finance-input px-3.5 text-sm text-finance-text shadow-none placeholder:text-finance-faint focus-visible:border-finance-accent focus-visible:ring-finance-accent/20"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <Label for="remember" class="flex cursor-pointer items-center gap-2.5 text-sm font-normal text-finance-muted">
                    <Checkbox id="remember" name="remember" class="size-4 rounded-sm border-finance-border-strong data-[state=checked]:border-finance-accent data-[state=checked]:bg-finance-action data-[state=checked]:text-finance-action-text" />
                    <span>Remember me</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-1 h-11 w-full rounded-md bg-finance-action text-sm font-semibold text-finance-action-text shadow-none transition-colors hover:bg-finance-action-hover focus-visible:ring-finance-accent/40"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                {{ processing ? 'Signing in…' : 'Sign in' }}
            </Button>
        </div>

        <div class="border-t border-finance-border pt-5 text-center text-sm text-finance-subtle">
            New to MoneyMap?
            <TextLink :href="register()" class="ml-1 font-semibold text-finance-accent decoration-finance-accent/50 hover:text-finance-accent">Create an account</TextLink>
        </div>
    </Form>
</template>
