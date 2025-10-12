<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
</script>

<template>
    <AuthLayout
        description="This is a secure area of the application. Please confirm your password before continuing."
        title="Confirm your password"
    >
        <Head title="Confirm password" />

        <Form
            v-slot="{ errors, processing }"
            reset-on-success
            v-bind="store.form()"
        >
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label htmlFor="password">Password</Label>
                    <Input
                        id="password"
                        autocomplete="current-password"
                        autofocus
                        class="mt-1 block w-full"
                        name="password"
                        required
                        type="password"
                    />

                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center">
                    <Button
                        :disabled="processing"
                        class="w-full"
                        data-test="confirm-password-button"
                    >
                        <LoaderCircle
                            v-if="processing"
                            class="h-4 w-4 animate-spin"
                        />
                        Confirm Password
                    </Button>
                </div>
            </div>
        </Form>
    </AuthLayout>
</template>
