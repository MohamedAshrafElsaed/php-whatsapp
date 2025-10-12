<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Eye, Play, Pause, XCircle, Trash2 } from 'lucide-vue-next';

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

defineProps<{
    campaigns: {
        data: Campaign[];
        links: any;
        meta: any;
    };
}>();

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

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        running: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        paused: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        canceled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        finished: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    };
    return colors[status] || '';
};

// Truncate campaign name to first 2 words
const truncateName = (name: string) => {
    const words = name.trim().split(/\s+/);
    if (words.length <= 2) {
        return name;
    }
    return words.slice(0, 2).join(' ') + '...';
};

const viewCampaign = (campaignId: number) => {
    router.visit(`/campaigns/${campaignId}`);
};

const startCampaign = (campaignId: number) => {
    if (confirm('Start this campaign and begin sending messages?')) {
        router.post(`/campaigns/${campaignId}/start`);
    }
};

const pauseCampaign = (campaignId: number) => {
    if (confirm('Pause this campaign?')) {
        router.post(`/campaigns/${campaignId}/pause`);
    }
};

const cancelCampaign = (campaignId: number) => {
    if (confirm('Cancel this campaign? This action cannot be undone.')) {
        router.post(`/campaigns/${campaignId}/cancel`);
    }
};

const deleteCampaign = (campaignId: number) => {
    if (confirm('Delete this campaign and all its messages? This action cannot be undone.')) {
        router.delete(`/campaigns/${campaignId}`);
    }
};

const createCampaign = () => {
    router.visit('/campaigns/create');
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Campaigns" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        Campaigns
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Create and manage your WhatsApp bulk messaging campaigns
                    </p>
                </div>
                <Button @click="createCampaign">
                    Create Campaign
                </Button>
            </div>

            <!-- Empty State -->
            <div
                v-if="campaigns.data.length === 0"
                class="rounded-lg border border-dashed border-gray-300 p-12 text-center dark:border-gray-700"
            >
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    No campaigns yet
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Get started by creating your first campaign
                </p>
                <Button class="mt-4" @click="createCampaign">
                    Create Your First Campaign
                </Button>
            </div>

            <!-- Campaigns Table -->
            <div
                v-else
                class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800"
            >
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Name
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Import
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Status
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Recipients
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Progress
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Created
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-950"
                    >
                    <tr
                        v-for="campaign in campaigns.data"
                        :key="campaign.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-900"
                    >
                        <td class="whitespace-nowrap px-6 py-4">
                            <div
                                :title="campaign.name"
                                class="group relative cursor-help text-sm font-medium text-gray-900 dark:text-gray-100"
                            >
                                {{ truncateName(campaign.name) }}
                                <!-- Tooltip on hover -->
                                <div
                                    class="pointer-events-none absolute left-0 top-full z-10 mt-1 hidden w-max max-w-xs rounded-md bg-gray-900 px-3 py-2 text-sm text-white shadow-lg group-hover:block dark:bg-gray-700"
                                >
                                    {{ campaign.name }}
                                    <div
                                        class="absolute -top-1 left-4 h-2 w-2 rotate-45 bg-gray-900 dark:bg-gray-700"
                                    ></div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ campaign.import.filename }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    :class="getStatusColor(campaign.status)"
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                >
                                    {{ campaign.status }}
                                </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                {{ campaign.total_messages }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="text-green-600 dark:text-green-400"
                                    >{{ campaign.sent_count }} sent</span
                                    >
                                /
                                <span class="text-red-600 dark:text-red-400"
                                >{{ campaign.failed_count }} failed</span
                                >
                                /
                                <span class="text-gray-500"
                                >{{ campaign.queued_count }} queued</span
                                >
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ formatDate(campaign.created_at) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <!-- View Button -->
                                <button
                                    type="button"
                                    title="View Details"
                                    class="group relative inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:outline-hidden focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 dark:border-input dark:bg-input/30 dark:hover:bg-input/50"
                                    @click="viewCampaign(campaign.id)"
                                >
                                    <Eye class="h-4 w-4" />
                                </button>

                                <!-- Start Button -->
                                <button
                                    v-if="campaign.status === 'draft' || campaign.status === 'paused'"
                                    type="button"
                                    title="Start Campaign"
                                    class="group relative inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent bg-primary text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 focus-visible:border-ring focus-visible:outline-hidden focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50"
                                    @click="startCampaign(campaign.id)"
                                >
                                    <Play class="h-4 w-4" />
                                </button>

                                <!-- Pause Button -->
                                <button
                                    v-if="campaign.status === 'running'"
                                    type="button"
                                    title="Pause Campaign"
                                    class="group relative inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:outline-hidden focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 dark:border-input dark:bg-input/30 dark:hover:bg-input/50"
                                    @click="pauseCampaign(campaign.id)"
                                >
                                    <Pause class="h-4 w-4" />
                                </button>

                                <!-- Cancel Button -->
                                <button
                                    v-if="campaign.status === 'running' || campaign.status === 'paused'"
                                    type="button"
                                    title="Cancel Campaign"
                                    class="group relative inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent bg-destructive text-sm font-medium text-white shadow-xs transition-all hover:bg-destructive/90 focus-visible:border-ring focus-visible:outline-hidden focus-visible:ring-[3px] focus-visible:ring-destructive/20 disabled:pointer-events-none disabled:opacity-50 dark:bg-destructive/60 dark:focus-visible:ring-destructive/40"
                                    @click="cancelCampaign(campaign.id)"
                                >
                                    <XCircle class="h-4 w-4" />
                                </button>

                                <!-- Delete Button -->
                                <button
                                    v-if="campaign.status !== 'running'"
                                    type="button"
                                    title="Delete Campaign"
                                    class="group relative inline-flex h-8 w-8 items-center justify-center rounded-md border border-transparent bg-destructive text-sm font-medium text-white shadow-xs transition-all hover:bg-destructive/90 focus-visible:border-ring focus-visible:outline-hidden focus-visible:ring-[3px] focus-visible:ring-destructive/20 disabled:pointer-events-none disabled:opacity-50 dark:bg-destructive/60 dark:focus-visible:ring-destructive/40"
                                    @click="deleteCampaign(campaign.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="campaigns.data.length > 0 && campaigns.links"
                class="flex justify-center gap-2"
            >
                <Button
                    v-for="(link, index) in campaigns.links"
                    :key="index"
                    :disabled="!link.url || link.active"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    @click="link.url && router.visit(link.url)"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
