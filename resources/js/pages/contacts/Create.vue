<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, User, Phone, Mail, Info, CheckCircle2 } from 'lucide-vue-next';

const { t, isRTL } = useTranslation();

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
});

const submit = () => {
    form.post('/contacts', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="t('contacts.add')" />

        <div class="min-h-screen bg-background">
            <div class="mx-auto max-w-3xl space-y-4 p-4 sm:space-y-6 sm:p-6 lg:p-8">
                <!-- Header Section -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <Link href="/contacts">
                        <Button size="icon" variant="ghost" class="h-9 w-9 shrink-0 sm:h-10 sm:w-10">
                            <ArrowLeft class="h-4 w-4 sm:h-5 sm:w-5" />
                            <span class="sr-only">{{ t('common.back') }}</span>
                        </Button>
                    </Link>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ t('contacts.add') }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground sm:text-base">
                            {{ t('contacts.add_description') }}
                        </p>
                    </div>
                </div>

                <!-- Form Card -->
                <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
                    <div class="rounded-lg border bg-card shadow-sm">
                        <div class="space-y-6 p-4 sm:p-6">
                            <!-- Section Header -->
                            <div class="flex items-center gap-3 border-b pb-4">
                                <div class="shrink-0 rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                                    <User class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                </div>
                                <h2 class="text-lg font-semibold">{{ t('contacts.personal_info') }}</h2>
                            </div>

                            <!-- Name Fields -->
                            <div class="grid gap-4 sm:gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="first_name">
                                        {{ t('auth.first_name') }}
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="first_name"
                                        v-model="form.first_name"
                                        :placeholder="t('auth.placeholder.first_name')"
                                        required
                                        type="text"
                                        class="h-10 sm:h-11"
                                        autocomplete="given-name"
                                    />
                                    <p v-if="form.errors.first_name" class="text-sm text-destructive">
                                        {{ form.errors.first_name }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="last_name">
                                        {{ t('auth.last_name') }}
                                        <span class="text-xs text-muted-foreground">
                                            ({{ t('common.optional') }})
                                        </span>
                                    </Label>
                                    <Input
                                        id="last_name"
                                        v-model="form.last_name"
                                        :placeholder="t('auth.placeholder.last_name')"
                                        type="text"
                                        class="h-10 sm:h-11"
                                        autocomplete="family-name"
                                    />
                                    <p v-if="form.errors.last_name" class="text-sm text-destructive">
                                        {{ form.errors.last_name }}
                                    </p>
                                </div>
                            </div>

                            <!-- Phone Field -->
                            <div class="space-y-2">
                                <Label for="phone">
                                    {{ t('auth.phone') }}
                                    <span class="text-destructive">*</span>
                                </Label>
                                <div class="relative">
                                    <Phone
                                        :class="[
                                            'pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground',
                                            isRTL ? 'right-3' : 'left-3'
                                        ]"
                                    />
                                    <Input
                                        id="phone"
                                        v-model="form.phone"
                                        :class="[
                                            'h-10 sm:h-11',
                                            isRTL ? 'pr-10' : 'pl-10'
                                        ]"
                                        placeholder="+1234567890"
                                        required
                                        type="tel"
                                        autocomplete="tel"
                                    />
                                </div>
                                <div class="flex items-start gap-1.5 text-xs text-muted-foreground">
                                    <Info class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                    <span>{{ t('contacts.phone_note') }}</span>
                                </div>
                                <p v-if="form.errors.phone" class="text-sm text-destructive">
                                    {{ form.errors.phone }}
                                </p>
                            </div>

                            <!-- Email Field -->
                            <div class="space-y-2">
                                <Label for="email">
                                    {{ t('auth.email') }}
                                    <span class="text-xs text-muted-foreground">
                                        ({{ t('common.optional') }})
                                    </span>
                                </Label>
                                <div class="relative">
                                    <Mail
                                        :class="[
                                            'pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground',
                                            isRTL ? 'right-3' : 'left-3'
                                        ]"
                                    />
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        :class="[
                                            'h-10 sm:h-11',
                                            isRTL ? 'pr-10' : 'pl-10'
                                        ]"
                                        :placeholder="t('auth.placeholder.email')"
                                        type="email"
                                        autocomplete="email"
                                    />
                                </div>
                                <p v-if="form.errors.email" class="text-sm text-destructive">
                                    {{ form.errors.email }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tips Card -->
                    <div class="rounded-lg border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 p-4 shadow-sm dark:border-blue-800 dark:from-blue-950/50 dark:to-blue-900/50 sm:p-5">
                        <div class="flex gap-3">
                            <Info class="mt-0.5 h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" />
                            <div class="min-w-0 flex-1 space-y-2">
                                <p class="font-semibold text-blue-900 dark:text-blue-100">
                                    {{ t('contacts.tips_title') }}
                                </p>
                                <ul class="space-y-1.5 text-sm text-blue-800 dark:text-blue-200">
                                    <li class="flex items-start gap-2">
                                        <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>{{ t('contacts.tip_phone_format') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>{{ t('contacts.tip_validation') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>{{ t('contacts.tip_required') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>{{ t('contacts.tip_duplicates') }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col-reverse gap-3 border-t pt-4 sm:flex-row sm:justify-end sm:border-t-0 sm:pt-0">
                        <Link href="/contacts" class="flex-1 sm:flex-none">
                            <Button type="button" variant="outline" class="w-full">
                                {{ t('common.cancel') }}
                            </Button>
                        </Link>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            variant="default"
                            class="flex-1 sm:flex-none"
                        >
                            <User class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ form.processing ? t('common.creating') : t('contacts.create_contact') }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Cairo font for Arabic */
:root[dir="rtl"] {
    font-family: 'Cairo', sans-serif;
}

/* Inter font for English */
:root[dir="ltr"] {
    font-family: 'Inter', sans-serif;
}

/* Ensure proper text alignment in RTL */
[dir="rtl"] .text-start {
    text-align: right;
}

[dir="ltr"] .text-start {
    text-align: left;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Form focus improvements */
input:focus-visible,
textarea:focus-visible {
    outline: 2px solid hsl(var(--ring));
    outline-offset: 2px;
}
</style>
