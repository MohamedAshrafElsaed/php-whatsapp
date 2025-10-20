<script lang="ts" setup>
import PasswordResetLinkController from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const { t } = useTranslation();
</script>

<template>
    <AuthLayout
        :description="t('auth.forgot_password_description')"
        :title="t('auth.forgot_password_title')"
    >
        <Head :title="t('auth.forgot_password_title')" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <div class="space-y-6">
            <Form
                v-slot="{ errors, processing }"
                v-bind="PasswordResetLinkController.store.form()"
            >
                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.email_required') }}</Label>
                    <Input
                        id="email"
                        autocomplete="off"
                        autofocus
                        name="email"
                        :placeholder="t('auth.email_placeholder')"
                        type="email"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="my-6 flex items-center justify-start">
                    <Button
                        :disabled="processing"
                        class="w-full"
                        data-test="email-password-reset-link-button"
                        variant="default"
                    >
                        <LoaderCircle
                            v-if="processing"
                            class="h-4 w-4 animate-spin"
                        />
                        {{ processing ? t('auth.sending') : t('auth.send_reset_link') }}
                    </Button>
                </div>
            </Form>

            <div class="space-x-1 text-center text-sm text-muted-foreground">
                <span>{{ t('auth.return_to_login') }}</span>
                <TextLink :href="login()">{{ t('auth.log_in') }}</TextLink>
            </div>
        </div>
    </AuthLayout>
</template>
