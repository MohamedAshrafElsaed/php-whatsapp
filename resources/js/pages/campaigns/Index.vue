<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Eye, Pause, Play, Trash2, XCircle, Plus,
    MessageSquare, Send, Clock, AlertCircle, CheckCircle2,
    MoreVertical
} from 'lucide-vue-next';
import { useTranslation } from '@/composables/useTranslation';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Campaign {
    id: number;
    name: string;
    status: 'draft' | 'running' | 'paused' | 'canceled' | 'finished';
    created_at: string;
    import: {
        filename: string;
    };
    total_messages: number;
    sent_count: number;
    failed_count: number;
    queued_count: number;
}

interface PaginatedCampaigns {
    data: Campaign[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    meta: any;
}

defineProps<{
    campaigns: PaginatedCampaigns;
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

const viewCampaign = (campaignId: number) => {
    router.visit(`/campaigns/${campaignId}`);
};

const startCampaign = (campaignId: number) => {
    if (confirm(t('campaigns.start_confirm'))) {
        router.post(`/campaigns/${campaignId}/start`);
    }
};

const pauseCampaign = (campaignId: number) => {
    if (confirm(t('campaigns.pause_confirm'))) {
        router.post(`/campaigns/${campaignId}/pause`);
    }
};

const cancelCampaign = (campaignId: number) => {
    if (confirm(t('campaigns.cancel_confirm'))) {
        router.post(`/campaigns/${campaignId}/cancel`);
    }
};

const deleteCampaign = (campaignId: number) => {
    if (confirm(t('campaigns.delete_confirm'))) {
        router.delete(`/campaigns/${campaignId}`);
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(isRTL ? 'ar-EG' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getProgressPercentage = (campaign: Campaign) => {
    if (campaign.total_messages === 0) return 0;
    return Math.round((campaign.sent_count / campaign.total_messages) * 100);
};
</script>

<template>
    <AppLayout>
        <Head :title="t('campaigns.title')" />

        <div class="min-h-screen bg-background">
            <div class="mx-auto max-w-7xl space-y-4 p-4 sm:space-y-6 sm:p-6 lg:p-8">
                <!-- Header Section -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ t('campaigns.title') }}
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            {{ t('campaigns.description') }}
                        </p>
                    </div>
                    <Link href="/campaigns/create">
                        <Button class="w-full sm:w-auto">
                            <Plus class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('campaigns.create') }}
                        </Button>
                    </Link>
                </div>

                <!-- Empty State -->
                <div
                    v-if="campaigns.data.length === 0"
                    class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed bg-muted/30 p-8 shadow-sm sm:p-12"
                >
                    <div class="mb-4 rounded-full bg-muted p-4">
                        <MessageSquare class="h-10 w-10 text-muted-foreground sm:h-12 sm:w-12" />
                    </div>
                    <h3 class="mb-2 text-center text-lg font-semibold">
                        {{ t('campaigns.no_campaigns') }}
                    </h3>
                    <p class="mb-4 text-center text-sm text-muted-foreground">
                        {{ t('campaigns.get_started') }}
                    </p>
                    <Link href="/campaigns/create">
                        <Button>
                            <Plus class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ t('campaigns.create_first') }}
                        </Button>
                    </Link>
                </div>

                <!-- Campaigns List -->
                <div v-else class="rounded-lg border bg-card shadow-sm">
                    <!-- Desktop Table View -->
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="w-full">
                            <thead class="border-b bg-muted/30">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.name') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.import') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.status') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.progress') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.created') }}
                                </th>
                                <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:px-6">
                                    {{ t('campaigns.actions') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y">
                            <tr
                                v-for="campaign in campaigns.data"
                                :key="campaign.id"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="px-4 py-4 xl:px-6">
                                    <div class="max-w-[200px] truncate font-semibold" :title="campaign.name">
                                        {{ campaign.name }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 xl:px-6">
                                    <div class="max-w-[180px] truncate text-sm text-muted-foreground">
                                        {{ campaign.import.filename }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 xl:px-6">
                                    <Badge :variant="getStatusVariant(campaign.status)" class="inline-flex items-center gap-1">
                                        <component :is="getStatusIcon(campaign.status)" class="h-3 w-3" />
                                        {{ t(`campaigns.status_${campaign.status}`) }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 xl:px-6">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-xs">
                                                <span class="font-medium text-green-600 dark:text-green-400">
                                                    {{ campaign.sent_count }}
                                                </span>
                                            <span class="text-muted-foreground">/</span>
                                            <span class="font-medium text-red-600 dark:text-red-400">
                                                    {{ campaign.failed_count }}
                                                </span>
                                            <span class="text-muted-foreground">/</span>
                                            <span class="text-muted-foreground">
                                                    {{ campaign.queued_count }}
                                                </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    class="h-full bg-primary transition-all"
                                                    :style="{ width: `${getProgressPercentage(campaign)}%` }"
                                                />
                                            </div>
                                            <span class="text-xs text-muted-foreground">
                                                    {{ getProgressPercentage(campaign) }}%
                                                </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-muted-foreground xl:px-6">
                                    {{ formatDate(campaign.created_at) }}
                                </td>
                                <td class="px-4 py-4 xl:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="h-8 w-8"
                                            @click="viewCampaign(campaign.id)"
                                        >
                                            <Eye class="h-4 w-4" />
                                            <span class="sr-only">{{ t('common.view') }}</span>
                                        </Button>

                                        <Button
                                            v-if="campaign.status === 'draft' || campaign.status === 'paused'"
                                            size="icon"
                                            variant="ghost"
                                            class="h-8 w-8 text-green-600 hover:bg-green-100 hover:text-green-700 dark:text-green-400 dark:hover:bg-green-950"
                                            @click="startCampaign(campaign.id)"
                                        >
                                            <Play class="h-4 w-4" />
                                            <span class="sr-only">{{ t('campaigns.start') }}</span>
                                        </Button>

                                        <Button
                                            v-if="campaign.status === 'running'"
                                            size="icon"
                                            variant="ghost"
                                            class="h-8 w-8 text-orange-600 hover:bg-orange-100 hover:text-orange-700 dark:text-orange-400 dark:hover:bg-orange-950"
                                            @click="pauseCampaign(campaign.id)"
                                        >
                                            <Pause class="h-4 w-4" />
                                            <span class="sr-only">{{ t('campaigns.pause') }}</span>
                                        </Button>

                                        <Button
                                            v-if="campaign.status === 'running' || campaign.status === 'paused'"
                                            size="icon"
                                            variant="ghost"
                                            class="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            @click="cancelCampaign(campaign.id)"
                                        >
                                            <XCircle class="h-4 w-4" />
                                            <span class="sr-only">{{ t('campaigns.cancel') }}</span>
                                        </Button>

                                        <Button
                                            v-if="campaign.status !== 'running'"
                                            size="icon"
                                            variant="ghost"
                                            class="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            @click="deleteCampaign(campaign.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            <span class="sr-only">{{ t('common.delete') }}</span>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile/Tablet Card View -->
                    <div class="divide-y lg:hidden">
                        <div
                            v-for="campaign in campaigns.data"
                            :key="campaign.id"
                            class="p-4 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1 space-y-2">
                                    <div class="flex items-start gap-2">
                                        <Link
                                            :href="`/campaigns/${campaign.id}`"
                                            class="flex-1"
                                        >
                                            <h3 class="truncate font-semibold hover:underline" :title="campaign.name">
                                                {{ campaign.name }}
                                            </h3>
                                        </Link>
                                        <Badge :variant="getStatusVariant(campaign.status)" class="shrink-0">
                                            {{ t(`campaigns.status_${campaign.status}`) }}
                                        </Badge>
                                    </div>

                                    <p class="truncate text-sm text-muted-foreground">
                                        {{ campaign.import.filename }}
                                    </p>

                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="font-medium text-green-600 dark:text-green-400">
                                            {{ campaign.sent_count }} {{ t('campaigns.sent') }}
                                        </span>
                                        <span class="font-medium text-red-600 dark:text-red-400">
                                            {{ campaign.failed_count }} {{ t('campaigns.failed') }}
                                        </span>
                                        <span class="text-muted-foreground">
                                            {{ campaign.queued_count }} {{ t('campaigns.queued') }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                            <div
                                                class="h-full bg-primary transition-all"
                                                :style="{ width: `${getProgressPercentage(campaign)}%` }"
                                            />
                                        </div>
                                        <span class="text-xs text-muted-foreground">
                                            {{ getProgressPercentage(campaign) }}%
                                        </span>
                                    </div>

                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(campaign.created_at) }}
                                    </p>
                                </div>

                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button size="icon" variant="ghost" class="h-8 w-8 shrink-0">
                                            <MoreVertical class="h-4 w-4" />
                                            <span class="sr-only">{{ t('campaigns.actions') }}</span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="viewCampaign(campaign.id)">
                                            <Eye class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                            {{ t('common.view') }}
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="campaign.status === 'draft' || campaign.status === 'paused'"
                                            @click="startCampaign(campaign.id)"
                                        >
                                            <Play class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                            {{ t('campaigns.start') }}
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="campaign.status === 'running'"
                                            @click="pauseCampaign(campaign.id)"
                                        >
                                            <Pause class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                            {{ t('campaigns.pause') }}
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator v-if="campaign.status === 'running' || campaign.status === 'paused' || campaign.status !== 'running'" />

                                        <DropdownMenuItem
                                            v-if="campaign.status === 'running' || campaign.status === 'paused'"
                                            class="text-destructive focus:text-destructive"
                                            @click="cancelCampaign(campaign.id)"
                                        >
                                            <XCircle class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                            {{ t('campaigns.cancel') }}
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="campaign.status !== 'running'"
                                            class="text-destructive focus:text-destructive"
                                            @click="deleteCampaign(campaign.id)"
                                        >
                                            <Trash2 class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                            {{ t('common.delete') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="campaigns.data.length > 0 && campaigns.links"
                    class="flex flex-wrap justify-center gap-2"
                >
                    <Link
                        v-for="(link, index) in campaigns.links"
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

/* Ensure proper text alignment in RTL */
[dir="rtl"] .text-start {
    text-align: right;
}

[dir="ltr"] .text-start {
    text-align: left;
}

[dir="rtl"] .text-end {
    text-align: left;
}

[dir="ltr"] .text-end {
    text-align: right;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
