<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';

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

const getMessageStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        queued: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        sent: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    };
    return colors[status] || '';
};

const startCampaign = () => {
    if (confirm('Start this campaign and begin sending messages?')) {
        router.post(`/campaigns/${props.campaign.id}/start`);
    }
};

const pauseCampaign = () => {
    if (confirm('Pause this campaign?')) {
        router.post(`/campaigns/${props.campaign.id}/pause`);
    }
};

const cancelCampaign = () => {
    if (confirm('Cancel this campaign? This action cannot be undone.')) {
        router.post(`/campaigns/${props.campaign.id}/cancel`);
    }
};

const deleteCampaign = () => {
    if (confirm('Delete this campaign? This action cannot be undone.')) {
        router.delete(`/campaigns/${props.campaign.id}`);
    }
};

const backToCampaigns = () => {
    router.visit('/campaigns');
};

const refreshPage = () => {
    router.reload();
};
</script>

<template>
    <AppLayout>
        <Head :title="campaign.name" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ campaign.name }}
                        </h1>
                        <span
                            :class="getStatusColor(campaign.status)"
                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                        >
                            {{ campaign.status }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Created {{ campaign.created_at }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button variant="outline" @click="backToCampaigns">
                        Back to Campaigns
                    </Button>
                    <Button
                        v-if="campaign.status === 'running'"
                        variant="outline"
                        @click="refreshPage"
                    >
                        Refresh
                    </Button>
                    <Button
                        v-if="campaign.status === 'draft' || campaign.status === 'paused'"
                        @click="startCampaign"
                    >
                        Start Campaign
                    </Button>
                    <Button
                        v-if="campaign.status === 'running'"
                        variant="outline"
                        @click="pauseCampaign"
                    >
                        Pause
                    </Button>
                    <Button
                        v-if="campaign.status === 'running' || campaign.status === 'paused'"
                        variant="destructive"
                        @click="cancelCampaign"
                    >
                        Cancel
                    </Button>
                    <Button
                        v-if="campaign.status !== 'running'"
                        variant="destructive"
                        @click="deleteCampaign"
                    >
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-950">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Total Messages
                    </div>
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ stats.total }}
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-950">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Sent
                    </div>
                    <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ stats.sent }}
                    </div>
                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                        {{ stats.total > 0 ? Math.round((stats.sent / stats.total) * 100) : 0 }}% complete
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-950">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Failed
                    </div>
                    <div class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">
                        {{ stats.failed }}
                    </div>
                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                        {{ stats.total > 0 ? Math.round((stats.failed / stats.total) * 100) : 0 }}% failed
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-950">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Queued
                    </div>
                    <div class="mt-2 text-3xl font-bold text-gray-600 dark:text-gray-400">
                        {{ stats.queued }}
                    </div>
                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                        Waiting to send
                    </div>
                </div>
            </div>

            <!-- Campaign Details -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Message Template -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-950">
                    <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        Message Template
                    </h3>
                    <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                        <p class="whitespace-pre-wrap text-sm text-gray-900 dark:text-gray-100">
                            {{ campaign.message_template }}
                        </p>
                    </div>
                </div>

                <!-- Campaign Info -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-950">
                    <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        Campaign Information
                    </h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Import File
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ campaign.import.filename }} ({{ campaign.import.valid_rows }} recipients)
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Rate Limiting
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ campaign.throttling.messages_per_minute }} messages/min,
                                {{ campaign.throttling.delay_seconds }}s delay
                            </dd>
                        </div>
                        <div v-if="campaign.started_at">
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Started At
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ campaign.started_at }}
                            </dd>
                        </div>
                        <div v-if="campaign.finished_at">
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Finished At
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ campaign.finished_at }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Messages Table -->
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
                <div class="border-b border-gray-200 p-6 dark:border-gray-800">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Messages (First 50)
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Track the delivery status of individual messages
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Recipient
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Phone
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Sent At
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Error
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        <tr
                            v-for="message in messages"
                            :key="message.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-900"
                        >
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                {{ message.recipient_name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ message.phone }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        :class="getMessageStatusColor(message.status)"
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                    >
                                        {{ message.status }}
                                    </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ message.sent_at || '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-red-600 dark:text-red-400">
                                {{ message.error_message || '-' }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="messages.length === 0" class="p-12 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        No messages yet. Start the campaign to begin sending.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
