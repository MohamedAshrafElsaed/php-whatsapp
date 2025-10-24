<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Lightbulb } from 'lucide-vue-next';
import type { Component } from 'vue';

interface Props {
    title: string;
    description: string;
    icon: Component;
    features?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    features: () => [],
});

const { t } = useTranslation();
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="[{ label: title, url: null }]">
        <div class="container mx-auto max-w-4xl px-4 py-8">
            <!-- Back Button -->
            <Link
                href="/dashboard"
                class="mb-6 inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
                {{ t('common.back_to_dashboard') }}
            </Link>

            <!-- Main Card -->
            <Card class="w-full max-w-2xl">
                <CardHeader class="text-center">
                    <!-- Icon -->
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-primary/10">
                        <component :is="icon" class="size-8 text-primary" />
                    </div>

                    <CardTitle class="text-3xl">{{ title }}</CardTitle>
                    <CardDescription class="text-base">
                        {{ description }}
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Coming Soon Badge -->
                    <div class="flex items-center justify-center">
                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                        >
                            <Lightbulb class="size-4" />
                            {{ t('coming_soon.notify_message') }}
                        </span>
                    </div>

                    <!-- Planned Features -->
                    <div v-if="features.length > 0" class="space-y-4">
                        <h3 class="text-lg font-semibold">
                            {{ t('coming_soon.planned_features') }}
                        </h3>
                        <ul class="space-y-3">
                            <li
                                v-for="(feature, index) in features"
                                :key="index"
                                class="flex items-start gap-3 text-sm text-muted-foreground"
                            >
                                <span
                                    class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-medium text-primary"
                                >
                                    {{ index + 1 }}
                                </span>
                                <span>{{ feature }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-3 pt-4 sm:flex-row">
                        <Button as-child variant="outline" size="lg">
                            <Link href="/dashboard">
                                <ArrowLeft class="size-4" />
                                {{ t('common.back_to_dashboard') }}
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
