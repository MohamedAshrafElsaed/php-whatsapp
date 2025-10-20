<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import {
    ArrowLeft, Play, Pause, XCircle, Trash2, RefreshCw,
    MessageSquare, Users, Send, XOctagon, Clock,
    FileText, Settings, CheckCircle2
} from 'lucide-vue-next';

interface Campaign {
    id: number;
    name: string;
    status: 'draft' | 'running' | 'paused' | 'canceled' | 'finished';
    message_template: string;
    throttling: {
        messages_per_minute: number;
        delay_seconds: number;
    };
    started_at: string | null;
    finished_at: string | null;
    created_at: string;
    import: {
        id: number;
        filename: string;
        valid_rows: number;
    };
}

interface Stats {
    total: number;
    sent: number;
    failed: number;
    queued: number;
}

interface Message {
    id: number;
    recipient_name: string;
    phone: string;
    status: 'queued' | 'sent' | 'failed';
    sent_at: string | null;
    error_message: string | null;
}

const props = defineProps<{
    campaign: Campaign;
    stats: Stats;
    messages: Message[];
}>();

const { t, isRTL } = useTranslation();

const getStatusVariant = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        draft: 'secondary',
        running: 'default',
        paused: 'outline',
        canceled: 'destructive',
        finished: 'secondary',
    };
    return variants[status] || 'default';
};

const getMessageStatusVariant = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        queued: 'secondary',
        sent: 'default',
        failed: 'destructive',
    };
    return variants[status] || 'secondary';
};

const getStatusIcon = (status: string) => {
    const icons: Record<string, any> = {
        draft: Clock,
        running: Send,
        paused: Pause,
        canceled: XCircle,
        finished: CheckCircle2,
    };
    return icons[status] || Clock;
};

const startCampaign = () => {
    if (confirm(t('campaigns.start_confirm'))) {
        router.post(`/campaigns/${props.campaign.id}/start`);
    }
};

const pauseCampaign = () => {
    if (confirm(t('campaigns.pause_confirm'))) {
        router.post(`/campaigns/${props.campaign.id}/pause`);
    }
};

const cancelCampaign = () => {
    if (confirm(t('campaigns.cancel_confirm'))) {
        router.post(`/campaigns/${props.campaign.id}/cancel`);
    }
};

const deleteCampaign = () => {
    if (confirm(t('campaigns.delete_confirm'))) {
        router.delete(`/campaigns/${props.campaign.id}`);
    }
};

const refreshPage = () => {
    router.reload();
};

const getProgressPercentage = () => {
    if (props.stats.total === 0) return 0;
    return Math.round((props.stats.sent / props.stats.total) * 100);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString(isRTL ? 'ar-EG' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="campaign.name" />

        <div class="min-h-screen bg-background">
            <div class="mx-auto max-w-7xl space-y-4 p-4 sm:space-y-6 sm:p-6 lg:p-8">
                <!-- Header -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <Link href="/campaigns">
                            <Button size="icon" variant="ghost" class="h-9 w-9 shrink-0 sm:h-10 sm:w-10">
                                <ArrowLeft class="h-4 w-4 sm:h-5 sm:w-5" />
                                <span class="sr-only">{{ t('common.back') }}</span>
                            </Button>
                        </Link>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                <h1 class="break-words text-2xl font-bold tracking-tight sm:text-3xl">
                                    {{ campaign.name }}
                                </h1>
                                <Badge :variant="getStatusVariant(campaign.status)" class="inline-flex w-fit items-center gap-1">
                                    <component :is="getStatusIcon(campaign.status)" class="h-3 w-3" />
                                    {{ t(`campaigns.status_${campaign.status}`) }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ t('campaigns.created') }}: {{ formatDate(campaign.created_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="campaign.status === 'running'"
                            variant="outline"
                            size="sm"
                            @click="refreshPage"
                        >
                            <RefreshCw class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            <span class="hidden sm:inline">{{ t('campaigns.refresh') }}</span>
                        </Button>
                        <Button
                            v-if="campaign.status === 'draft' || campaign.status === 'paused'"
                            size="sm"
                            @click="startCampaign"
                        >
                            <Play class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('campaigns.start') }}
                        </Button>
                        <Button
                            v-if="campaign.status === 'running'"
                            variant="outline"
                            size="sm"
                            @click="pauseCampaign"
                        >
                            <Pause class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('campaigns.pause') }}
                        </Button>
                        <Button
                            v-if="campaign.status === 'running' || campaign.status === 'paused'"
                            variant="destructive"
                            size="sm"
                            @click="cancelCampaign"
                        >
                            <XCircle class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            <span class="hidden sm:inline">{{ t('campaigns.cancel') }}</span>
                        </Button>
                        <Button
                            v-if="campaign.status !== 'running'"
                            variant="destructive"
                            size="sm"
                            @click="deleteCampaign"
                        >
                            <Trash2 class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            <span class="hidden sm:inline">{{ t('campaigns.delete') }}</span>
                        </Button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border bg-gradient-to-br from-blue-50 to-blue-100 p-4 shadow-sm dark:from-blue-950/50 dark:to-blue-900/50 sm:p-6">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 rounded-lg bg-blue-200 p-2 dark:bg-blue-900">
                                <Users class="h-5 w-5 text-blue-700 dark:text-blue-300" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                    {{ t('campaigns.total_messages') }}
                                </div>
                                <div class="mt-1 text-2xl font-bold text-blue-700 sm:text-3xl dark:text-blue-200">
                                    {{ stats.total }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border bg-gradient-to-br from-green-50 to-green-100 p-4 shadow-sm dark:from-green-950/50 dark:to-green-900/50 sm:p-6">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 rounded-lg bg-green-200 p-2 dark:bg-green-900">
                                <CheckCircle2 class="h-5 w-5 text-green-700 dark:text-green-300" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-green-900 dark:text-green-100">
                                    {{ t('campaigns.sent_count') }}
                                </div>
                                <div class="mt-1 text-2xl font-bold text-green-700 sm:text-3xl dark:text-green-200">
                                    {{ stats.sent }}
                                </div>
                                <div class="mt-1 text-xs text-green-700 dark:text-green-300">
                                    {{ getProgressPercentage() }}% {{ t('campaigns.complete') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border bg-gradient-to-br from-red-50 to-red-100 p-4 shadow-sm dark:from-red-950/50 dark:to-red-900/50 sm:p-6">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 rounded-lg bg-red-200 p-2 dark:bg-red-900">
                                <XOctagon class="h-5 w-5 text-red-700 dark:text-red-300" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-red-900 dark:text-red-100">
                                    {{ t('campaigns.failed_count') }}
                                </div>
                                <div class="mt-1 text-2xl font-bold text-red-700 sm:text-3xl dark:text-red-200">
                                    {{ stats.failed }}
                                </div>
                                <div class="mt-1 text-xs text-red-700 dark:text-red-300">
                                    {{ stats.total > 0 ? Math.round((stats.failed / stats.total) * 100) : 0 }}% {{ t('campaigns.failed_percent') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border bg-gradient-to-br from-orange-50 to-orange-100 p-4 shadow-sm dark:from-orange-950/50 dark:to-orange-900/50 sm:p-6">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 rounded-lg bg-orange-200 p-2 dark:bg-orange-900">
                                <Clock class="h-5 w-5 text-orange-700 dark:text-orange-300" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-orange-900 dark:text-orange-100">
                                    {{ t('campaigns.queued_count') }}
                                </div>
                                <div class="mt-1 text-2xl font-bold text-orange-700 sm:text-3xl dark:text-orange-200">
                                    {{ stats.queued }}
                                </div>
                                <div class="mt-1 text-xs text-orange-700 dark:text-orange-300">
                                    {{ t('campaigns.waiting_to_send') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div v-if="campaign.status === 'running'" class="rounded-lg border bg-card p-4 shadow-sm">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium">{{ t('campaigns.campaign_progress') }}</span>
                            <span class="text-muted-foreground">{{ getProgressPercentage() }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full bg-primary transition-all duration-500"
                                :style="{ width: `${getProgressPercentage()}%` }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Campaign Details -->
                <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
                    <!-- Message Template -->
                    <div class="rounded-lg border bg-card shadow-sm">
                        <div class="space-y-4 p-4 sm:p-6">
                            <div class="flex items-center gap-3 border-b pb-4">
                                <div class="shrink-0 rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                                    <MessageSquare class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                </div>
                                <h3 class="text-lg font-semibold">{{ t('campaigns.message_template') }}</h3>
                            </div>
                            <div class="rounded-lg bg-muted p-4">
                                <p class="whitespace-pre-wrap break-words text-sm">
                                    {{ campaign.message_template }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Campaign Info -->
                    <div class="rounded-lg border bg-card shadow-sm">
                        <div class="space-y-4 p-4 sm:p-6">
                            <div class="flex items-center gap-3 border-b pb-4">
                                <div class="shrink-0 rounded-lg bg-blue-100 p-2 dark:bg-blue-950">
                                    <FileText class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <h3 class="text-lg font-semibold">{{ t('campaigns.campaign_info') }}</h3>
                            </div>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-muted-foreground">
                                        {{ t('campaigns.import_file') }}
                                    </dt>
                                    <dd class="mt-1 break-words font-medium">
                                        {{ campaign.import.filename }}
                                    </dd>
                                    <dd class="mt-0.5 text-sm text-muted-foreground">
                                        {{ campaign.import.valid_rows }} {{ t('campaigns.recipients') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                        <Settings class="h-4 w-4" />
                                        {{ t('campaigns.rate_limiting') }}
                                    </dt>
                                    <dd class="mt-1 font-medium">
                                        {{ campaign.throttling.messages_per_minute }} {{ t('campaigns.messages_per_minute') }}
                                    </dd>
                                    <dd class="mt-0.5 text-sm text-muted-foreground">
                                        {{ campaign.throttling.delay_seconds }}s {{ t('campaigns.delay_between_messages') }}
                                    </dd>
                                </div>
                                <div v-if="campaign.started_at">
                                    <dt class="text-sm font-medium text-muted-foreground">
                                        {{ t('campaigns.started_at') }}
                                    </dt>
                                    <dd class="mt-1 font-medium">
                                        {{ formatDate(campaign.started_at) }}
                                    </dd>
                                </div>
                                <div v-if="campaign.finished_at">
                                    <dt class="text-sm font-medium text-muted-foreground">
                                        {{ t('campaigns.finished_at') }}
                                    </dt>
                                    <dd class="mt-1 font-medium">
                                        {{ formatDate(campaign.finished_at) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Messages Table -->
                <div class="rounded-lg border bg-card shadow-sm">
                    <div class="border-b p-4 sm:p-6">
                        <h3 class="text-lg font-semibold">{{ t('campaigns.messages_first_50') }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ t('campaigns.track_delivery') }}
                        </p>
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="w-full">
                            <thead class="border-b bg-muted/30">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.recipient') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.phone') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.status') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.sent_at') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.error') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y">
                            <tr
                                v-for="message in messages"
                                :key="message.id"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="px-4 py-4 font-medium xl:px-6">
                                    {{ message.recipient_name }}
                                </td>
                                <td class="px-4 py-4 font-mono text-sm text-muted-foreground xl:px-6">
                                    {{ message.phone }}
                                </td>
                                <td class="px-4 py-4 xl:px-6">
                                    <Badge :variant="getMessageStatusVariant(message.status)">
                                        {{ t(`campaigns.status_${message.status}`) }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 text-sm text-muted-foreground xl:px-6">
                                    {{ message.sent_at ? formatDate(message.sent_at) : '-' }}
                                </td>
                                <td class="px-4 py-4 xl:px-6">
                                    <div class="max-w-xs truncate text-sm text-red-600 dark:text-red-400">
                                        {{ message.error_message || '-' }}
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile/Tablet Card View -->
                    <div class="divide-y lg:hidden">
                        <div
                            v-for="message in messages"
                            :key="message.id"
                            class="p-4 transition-colors hover:bg-muted/50"
                        >
                            <div class="space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium">{{ message.recipient_name }}</p>
                                        <p class="truncate font-mono text-sm text-muted-foreground">{{ message.phone }}</p>
                                    </div>
                                    <Badge :variant="getMessageStatusVariant(message.status)" class="shrink-0">
                                        {{ t(`campaigns.status_${message.status}`) }}
                                    </Badge>
                                </div>
                                <div v-if="message.sent_at" class="text-xs text-muted-foreground">
                                    {{ formatDate(message.sent_at) }}
                                </div>
                                <div v-if="message.error_message" class="rounded bg-red-50 p-2 text-xs text-red-600 dark:bg-red-950/50 dark:text-red-400">
                                    {{ message.error_message }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="messages.length === 0" class="flex flex-col items-center justify-center p-8 sm:p-12">
                        <div class="mb-4 rounded-full bg-muted p-4">
                            <MessageSquare class="h-10 w-10 text-muted-foreground sm:h-12 sm:w-12" />
                        </div>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ t('campaigns.no_messages') }}
                        </p>
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
</style>
