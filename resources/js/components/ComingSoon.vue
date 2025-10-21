<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Lightbulb, Rocket } from 'lucide-vue-next';

interface Props {
    title: string;
    description: string;
    icon?: any;
    features?: string[];
}

const props = defineProps<Props>();
const { t } = useTranslation();
</script>

<template>
    <AppLayout>
        <Head :title="title" />

        <div class="flex min-h-[calc(100vh-4rem)] items-center justify-center p-4">
            <Card class="w-full max-w-2xl">
                <CardHeader class="text-center">
                    <div class="mb-6 flex justify-center">
                        <div class="rounded-full bg-primary/10 p-6">
                            <component
                                :is="icon || Rocket"
                                class="h-16 w-16 text-primary"
                            />
                        </div>
                    </div>
                    <CardTitle class="text-3xl font-bold">
                        {{ title }}
                    </CardTitle>
                    <CardDescription class="mt-3 text-lg">
                        {{ description }}
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div
                        v-if="features && features.length > 0"
                        class="rounded-lg border bg-muted/30 p-6"
                    >
                        <div class="mb-4 flex items-center gap-2 text-sm font-semibold">
                            <Lightbulb class="h-5 w-5 text-yellow-500" />
                            <span>{{ t('coming_soon.planned_features') }}</span>
                        </div>
                        <ul class="space-y-2">
                            <li
                                v-for="(feature, index) in features"
                                :key="index"
                                class="flex items-start gap-2 text-sm text-muted-foreground"
                            >
                                <span class="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-primary" />
                                <span>{{ feature }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-lg border border-primary/20 bg-primary/5 p-4">
                        <p class="text-center text-sm text-muted-foreground">
                            {{ t('coming_soon.notify_message') }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <Button as-child variant="outline" size="lg">
                            <Link :href="route('dashboard')">
                                <ArrowLeft class="h-4 w-4" />
                                <span>{{ t('common.back_to_dashboard') }}</span>
                            </Link>
                        </Button>
                        <Button as-child size="lg">
                            <Link :href="route('feature-requests.create')">
                                <Lightbulb class="h-4 w-4" />
                                <span>{{ t('feature_requests.suggest_feature') }}</span>
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
