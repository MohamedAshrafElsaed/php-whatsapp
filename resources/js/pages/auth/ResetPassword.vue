<script lang="ts" setup>
import NewPasswordController from '@/actions/App/Http/Controllers/Auth/NewPasswordController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <AuthLayout
        description="Please enter your new password below"
        title="Reset password"
    >
        <Head title="Reset password" />

        <Form
            v-slot="{ errors, processing }"
            :reset-on-success="['password', 'password_confirmation']"
            :transform="(data) => ({ ...data, token, email })"
            v-bind="NewPasswordController.store.form()"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="inputEmail"
                        autocomplete="email"
                        class="mt-1 block w-full"
                        name="email"
                        readonly
                        type="email"
                    />
                    <InputError :message="errors.email" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        autocomplete="new-password"
                        autofocus
                        class="mt-1 block w-full"
                        name="password"
                        placeholder="Password"
                        type="password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">
                        Confirm Password
                    </Label>
                    <Input
                        id="password_confirmation"
                        autocomplete="new-password"
                        class="mt-1 block w-full"
                        name="password_confirmation"
                        placeholder="Confirm password"
                        type="password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    :disabled="processing"
                    class="mt-4 w-full"
                    data-test="reset-password-button"
                    type="submit"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin"
                    />
                    Reset password
                </Button>
            </div>
        </Form>
    </AuthLayout>
</template>
