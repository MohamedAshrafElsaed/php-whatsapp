<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft, Edit, Trash2, Users, TrendingUp,
    CheckCircle2, XCircle, BarChart3, Send, Eye
} from 'lucide-vue-next';
import { useTranslation } from '@/composables/useTranslation';

interface Segment {
    id: number;
    name: string;
    description: string | null;
    total_contacts: number;
    valid_contacts: number;
    invalid_contacts: number;
    created_at: string;
}

interface Statistics {
    total_contacts: number;
    valid_contacts: number;
    invalid_contacts: number;
    total_campaigns: number;
    active_campaigns: number;
    completed_campaigns: number;
    last_campaign_date: string | null;
    total_messages_sent: number;
    total_messages_failed: number;
    success_rate: number;
}

interface Contact {
    id: number;
    full_name: string;
    phone_e164: string;
    email: string | null;
    is_valid: boolean;
}

interface Campaign {
    id: number;
    name: string;
    status: string;
    sent_count: number;
    failed_count: number;
    total_recipients: number;
    created_at: string;
}

const props = defineProps<{
    segment: Segment;
    statistics: Statistics;
    contacts: Contact[];
    campaigns: Campaign[];
}>();

const { t } = useTranslation();

/**
 * Navigate to edit segment page
 */
const editSegment = (): void => {
    router.visit(`/contacts/segments/${props.segment.id}/edit`);
};

/**
 * Delete segment with confirmation
 */
const deleteSegment = (): void => {
    if (confirm(t('segments.delete_confirm'))) {
        router.delete(`/contacts/segments/${props.segment.id}`);
    }
};

/**
 * Navigate to campaign details
 */
const viewCampaign = (campaignId: number): void => {
    router.visit(`/campaigns/${campaignId}`);
};

/**
 * Get campaign status variant
 */
const getStatusVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        draft: 'secondary',
        running: 'default',
        paused: 'outline',
        canceled: 'destructive',
        finished: 'secondary',
    };
    return variants[status] || 'default';
};

/**
 * Calculate campaign progress percentage
 */
const getCampaignProgress = (campaign: Campaign): number => {
    if (campaign.total_recipients === 0) return 0;
    return Math.round(((campaign.sent_count + campaign.failed_count) / campaign.total_recipients) * 100);
};
</script>

<template>
    <AppLayout>
        <Head :title="segment.name" />

        <div class="min-h-screen bg-background">
            <div class="mx-auto max-w-7xl space-y-4 p-4 sm:space-y-6 sm:p-6 lg:p-8">
                <!-- Header -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <Link href="/contacts/segments">
                            <Button size="icon" variant="ghost" class="h-9 w-9 shrink-0 sm:h-10 sm:w-10">
                                <ArrowLeft class="h-4 w-4 sm:h-5 sm:w-5" />
                                <span class="sr-only">{{ t('common.back') }}</span>
                            </Button>
                        </Link>
                        <div class="min-w-0 flex-1">
                            <h1 class="break-words text-2xl font-bold tracking-tight sm:text-3xl">
                                {{ segment.name }}
                            </h1>
                            <p v-if="segment.description" class="mt-1 text-sm text-muted-foreground">
                                {{ segment.description }}
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ t('segments.created') }}: {{ segment.created_at }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" @click="editSegment">
                            <Edit class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('common.edit') }}
                        </Button>
                        <Button variant="destructive" size="sm" @click="deleteSegment">
                            <Trash2 class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('common.delete') }}
                        </Button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid gap-4 sm:gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Contacts -->
                    <div class="rounded-lg border bg-gradient-to-br from-blue-50 to-blue-100 p-4 shadow-sm dark:from-blue-950/50 dark:to-blue-900/50 sm:p-6">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 rounded-lg bg-blue-200 p-2 dark:bg-blue-900">
                                <Users class="h-5 w-5 text-blue-700 dark:text-blue-300" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                    {{ t('segments.total_contacts') }}
                                </div>
                                <div class="mt-1 text-2xl font-bold text-blue-700 sm:text-3xl dark:text-blue-200">
                                    {{ statistics.total_contacts }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Valid Contacts -->
                    <div class="rounded-lg border bg-gradient-to-br from-green-50 to-green-100 p-4 shadow-sm dark:from-green-950/50 dark:to-green-900/50 sm:p-6">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 rounded-lg bg-green-200 p-2 dark:bg-green-900">
                                <CheckCircle2 class="h-5 w-5 text-green-700 dark:text-green-300" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-green-900 dark:text-green-100">
                                    {{ t('segments.valid_contacts') }}
                                </div>
                                <div class="mt-1 text-2xl font-bold text-green-700 sm:text-3xl dark:text-green-200">
                                    {{ statistics.valid_contacts }}
                                </div>
                                <div class="mt-1 text-xs text-green-700 dark:text-green-300">
                                    {{ statistics.total_contacts > 0
                                    ? Math.round((statistics.valid_contacts / statistics.total_contacts) * 100)
                                    : 0
                                    }}% {{ t('segments.valid') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Campaigns -->
                    <div class="rounded-lg border bg-gradient-to-br from-purple-50 to-purple-100 p-4 shadow-sm dark:from-purple-950/50 dark:to-purple-900/50 sm:p-6">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 rounded-lg bg-purple-200 p-2 dark:bg-purple-900">
                                <TrendingUp class="h-5 w-5 text-purple-700 dark:text-purple-300" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-purple-900 dark:text-purple-100">
                                    {{ t('segments.campaigns') }}
                                </div>
                                <div class="mt-1 text-2xl font-bold text-purple-700 sm:text-3xl dark:text-purple-200">
                                    {{ statistics.total_campaigns }}
                                </div>
                                <div class="mt-1 text-xs text-purple-700 dark:text-purple-300">
                                    {{ statistics.active_campaigns }} {{ t('segments.active') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success Rate -->
                    <div class="rounded-lg border bg-gradient-to-br from-orange-50 to-orange-100 p-4 shadow-sm dark:from-orange-950/50 dark:to-orange-900/50 sm:p-6">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 rounded-lg bg-orange-200 p-2 dark:bg-orange-900">
                                <BarChart3 class="h-5 w-5 text-orange-700 dark:text-orange-300" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-orange-900 dark:text-orange-100">
                                    {{ t('segments.success_rate') }}
                                </div>
                                <div class="mt-1 text-2xl font-bold text-orange-700 sm:text-3xl dark:text-orange-200">
                                    {{ statistics.success_rate }}%
                                </div>
                                <div class="mt-1 text-xs text-orange-700 dark:text-orange-300">
                                    {{ statistics.total_messages_sent }} {{ t('segments.sent') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
                    <!-- Contacts List -->
                    <div class="rounded-lg border bg-card shadow-sm">
                        <div class="border-b p-4 sm:p-6">
                            <h3 class="text-lg font-semibold">{{ t('segments.contacts_list') }}</h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ t('segments.showing_first_100') }}
                            </p>
                        </div>

                        <div class="divide-y">
                            <div
                                v-for="contact in contacts"
                                :key="contact.id"
                                class="flex items-center justify-between p-4 transition-colors hover:bg-muted/50"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">{{ contact.full_name }}</p>
                                    <p class="truncate text-sm text-muted-foreground">{{ contact.phone_e164 }}</p>
                                    <p v-if="contact.email" class="truncate text-xs text-muted-foreground">
                                        {{ contact.email }}
                                    </p>
                                </div>
                                <Badge :variant="contact.is_valid ? 'default' : 'destructive'" class="shrink-0">
                                    {{ contact.is_valid ? t('segments.valid') : t('segments.invalid') }}
                                </Badge>
                            </div>

                            <div v-if="contacts.length === 0" class="p-8 text-center">
                                <Users class="mx-auto h-12 w-12 text-muted-foreground" />
                                <p class="mt-2 text-sm text-muted-foreground">
                                    {{ t('segments.no_contacts') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Campaigns Using This Segment -->
                    <div class="rounded-lg border bg-card shadow-sm">
                        <div class="border-b p-4 sm:p-6">
                            <h3 class="text-lg font-semibold">{{ t('segments.recent_campaigns') }}</h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ t('segments.campaigns_using_segment') }}
                            </p>
                        </div>

                        <div class="divide-y">
                            <div
                                v-for="campaign in campaigns"
                                :key="campaign.id"
                                class="p-4 transition-colors hover:bg-muted/50"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <Link
                                                :href="`/campaigns/${campaign.id}`"
                                                class="truncate font-medium hover:underline"
                                            >
                                                {{ campaign.name }}
                                            </Link>
                                            <Badge :variant="getStatusVariant(campaign.status)" class="shrink-0">
                                                {{ t(`campaigns.status_${campaign.status}`) }}
                                            </Badge>
                                        </div>

                                        <div class="mt-2 space-y-1">
                                            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                                <span class="flex items-center gap-1">
                                                    <CheckCircle2 class="h-3 w-3 text-green-600 dark:text-green-400" />
                                                    {{ campaign.sent_count }} {{ t('segments.sent') }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <XCircle class="h-3 w-3 text-red-600 dark:text-red-400" />
                                                    {{ campaign.failed_count }} {{ t('segments.failed') }}
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        class="h-full bg-primary transition-all"
                                                        :style="{ width: `${getCampaignProgress(campaign)}%` }"
                                                    />
                                                </div>
                                                <span class="text-xs text-muted-foreground">
                                                    {{ getCampaignProgress(campaign) }}%
                                                </span>
                                            </div>

                                            <p class="text-xs text-muted-foreground">
                                                {{ campaign.created_at }}
                                            </p>
                                        </div>
                                    </div>

                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="h-8 w-8 shrink-0"
                                        @click="viewCampaign(campaign.id)"
                                    >
                                        <Eye class="h-4 w-4" />
                                        <span class="sr-only">{{ t('common.view') }}</span>
                                    </Button>
                                </div>
                            </div>

                            <div v-if="campaigns.length === 0" class="p-8 text-center">
                                <Send class="mx-auto h-12 w-12 text-muted-foreground" />
                                <p class="mt-2 text-sm text-muted-foreground">
                                    {{ t('segments.no_campaigns') }}
                                </p>
                                <Link href="/campaigns/create" class="mt-4 inline-block">
                                    <Button size="sm">
                                        {{ t('segments.create_campaign') }}
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>
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
