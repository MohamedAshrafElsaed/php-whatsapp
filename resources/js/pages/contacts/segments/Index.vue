<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Plus, Eye, Edit, Trash2, MoreVertical,
    Users, Tag, TrendingUp, AlertCircle
} from 'lucide-vue-next';
import { useTranslation } from '@/composables/useTranslation';

interface Segment {
    id: number;
    name: string;
    description: string | null;
    total_contacts: number;
    valid_contacts: number;
    invalid_contacts: number;
    campaigns_count: number;
    created_at: string;
}

interface PaginatedSegments {
    data: Segment[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    meta: any;
}

defineProps<{
    segments: PaginatedSegments;
}>();

const { t } = useTranslation();

/**
 * Navigate to segment details page
 */
const viewSegment = (segmentId: number): void => {
    router.visit(`/contacts/segments/${segmentId}`);
};

/**
 * Navigate to edit segment page
 */
const editSegment = (segmentId: number): void => {
    router.visit(`/contacts/segments/${segmentId}/edit`);
};

/**
 * Delete segment with confirmation
 */
const deleteSegment = (segmentId: number): void => {
    if (confirm(t('segments.delete_confirm'))) {
        router.delete(`/contacts/segments/${segmentId}`);
    }
};

/**
 * Get health status badge variant based on contact validity
 */
const getHealthVariant = (segment: Segment): 'default' | 'secondary' | 'destructive' => {
    if (segment.total_contacts === 0) return 'secondary';
    const validPercentage = (segment.valid_contacts / segment.total_contacts) * 100;
    if (validPercentage >= 80) return 'default';
    if (validPercentage >= 50) return 'secondary';
    return 'destructive';
};

/**
 * Calculate segment health percentage
 */
const getHealthPercentage = (segment: Segment): number => {
    if (segment.total_contacts === 0) return 0;
    return Math.round((segment.valid_contacts / segment.total_contacts) * 100);
};
</script>

<template>
    <AppLayout>
        <Head :title="t('segments.title')" />

        <div class="min-h-screen bg-background">
            <div class="mx-auto max-w-7xl space-y-4 p-4 sm:space-y-6 sm:p-6 lg:p-8">
                <!-- Header Section -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ t('segments.title') }}
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            {{ t('segments.description') }}
                        </p>
                    </div>
                    <Link href="/contacts/segments/create">
                        <Button class="w-full sm:w-auto">
                            <Plus class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('segments.create') }}
                        </Button>
                    </Link>
                </div>

                <!-- Empty State -->
                <div
                    v-if="segments.data.length === 0"
                    class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed bg-muted/30 p-8 shadow-sm sm:p-12"
                >
                    <div class="mb-4 rounded-full bg-muted p-4">
                        <Tag class="h-10 w-10 text-muted-foreground sm:h-12 sm:w-12" />
                    </div>
                    <h3 class="mb-2 text-center text-lg font-semibold">
                        {{ t('segments.no_segments') }}
                    </h3>
                    <p class="mb-4 text-center text-sm text-muted-foreground">
                        {{ t('segments.get_started') }}
                    </p>
                    <Link href="/contacts/segments/create">
                        <Button>
                            <Plus class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('segments.create_first') }}
                        </Button>
                    </Link>
                </div>

                <!-- Segments Grid -->
                <div v-else class="grid gap-4 sm:gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="segment in segments.data"
                        :key="segment.id"
                        class="group relative overflow-hidden rounded-lg border bg-card shadow-sm transition-all hover:shadow-md"
                    >
                        <!-- Header -->
                        <div class="flex items-start justify-between border-b p-4">
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="`/contacts/segments/${segment.id}`"
                                    class="block"
                                >
                                    <h3 class="truncate font-semibold hover:underline" :title="segment.name">
                                        {{ segment.name }}
                                    </h3>
                                </Link>
                                <p
                                    v-if="segment.description"
                                    class="mt-1 line-clamp-2 text-xs text-muted-foreground"
                                    :title="segment.description"
                                >
                                    {{ segment.description }}
                                </p>
                            </div>

                            <!-- Actions Dropdown -->
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button size="icon" variant="ghost" class="h-8 w-8 shrink-0">
                                        <MoreVertical class="h-4 w-4" />
                                        <span class="sr-only">{{ t('segments.actions') }}</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="viewSegment(segment.id)">
                                        <Eye class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                        {{ t('common.view') }}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="editSegment(segment.id)">
                                        <Edit class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                        {{ t('common.edit') }}
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="deleteSegment(segment.id)"
                                    >
                                        <Trash2 class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                        {{ t('common.delete') }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <!-- Statistics -->
                        <div class="space-y-4 p-4">
                            <!-- Contacts Count -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Users class="h-4 w-4" />
                                    <span>{{ t('segments.total_contacts') }}</span>
                                </div>
                                <span class="text-lg font-bold">{{ segment.total_contacts }}</span>
                            </div>

                            <!-- Health Status -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-muted-foreground">{{ t('segments.health') }}</span>
                                    <Badge :variant="getHealthVariant(segment)">
                                        {{ getHealthPercentage(segment) }}%
                                    </Badge>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-primary transition-all"
                                        :style="{ width: `${getHealthPercentage(segment)}%` }"
                                    />
                                </div>
                                <div class="flex justify-between text-xs text-muted-foreground">
                                    <span>{{ segment.valid_contacts }} {{ t('segments.valid') }}</span>
                                    <span>{{ segment.invalid_contacts }} {{ t('segments.invalid') }}</span>
                                </div>
                            </div>

                            <!-- Campaigns Count -->
                            <div class="flex items-center justify-between rounded-lg bg-muted/50 p-3">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <TrendingUp class="h-4 w-4" />
                                    <span>{{ t('segments.campaigns_using') }}</span>
                                </div>
                                <span class="font-semibold">{{ segment.campaigns_count }}</span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="border-t bg-muted/30 px-4 py-3">
                            <p class="text-xs text-muted-foreground">
                                {{ t('segments.created') }}: {{ segment.created_at }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="segments.data.length > 0 && segments.links"
                    class="flex flex-wrap justify-center gap-2"
                >
                    <Link
                        v-for="(link, index) in segments.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'inline-flex min-w-[2.5rem] items-center justify-center rounded-md border px-3 py-2 text-sm transition-colors',
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-muted',
                            !link.url && 'pointer-events-none opacity-50'
                        ]"
                        v-html="link.label"
                    />
                </div>
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

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
