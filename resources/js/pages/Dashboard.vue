<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircle,
    Clock,
    FileText,
    MessageSquare,
    Smartphone,
    Users,
    XCircle,
} from 'lucide-vue-next';

interface Stats {
    total_contacts: number;
    total_imports: number;
    total_campaigns: number;
    messages_sent: number;
    messages_failed: number;
    messages_queued: number;
    whatsapp_connected: boolean;
}

interface Import {
    id: number;
    filename: string;
    valid_rows: number;
    status: string;
    created_at: string;
}

interface Campaign {
    id: number;
    name: string;
    status: string;
    created_at: string;
}

const props = defineProps<{
    stats: Stats;
    recentImports: Import[];
    recentCampaigns: Campaign[];
}>();

const navigateToWhatsApp = () => {
    router.visit('/wa/connect');
};

const navigateToImports = () => {
    router.visit('/contacts/imports');
};

const statusColor: Record<string, string> = {
    draft: 'secondary',
    running: 'warning',
    paused: 'warning',
    finished: 'success',
    canceled: 'destructive',
    ready: 'success',
};
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold">Dashboard</h1>
                <p class="text-muted-foreground">
                    Overview of your WhatsApp bulk messaging system
                </p>
            </div>

            <!-- WhatsApp Status Alert -->
            <div
                v-if="!stats.whatsapp_connected"
                class="rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-900 dark:bg-orange-950"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Smartphone
                            class="h-5 w-5 text-orange-600 dark:text-orange-400"
                        />
                        <div>
                            <h3
                                class="font-semibold text-orange-900 dark:text-orange-100"
                            >
                                WhatsApp Not Connected
                            </h3>
                            <p
                                class="text-sm text-orange-800 dark:text-orange-200"
                            >
                                Connect your WhatsApp account to start sending
                                messages
                            </p>
                        </div>
                    </div>
                    <Button variant="default" @click="navigateToWhatsApp">
                        Connect Now
                    </Button>
                </div>
            </div>

            <div
                v-else
                class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950"
            >
                <div class="flex items-center gap-3">
                    <CheckCircle
                        class="h-5 w-5 text-green-600 dark:text-green-400"
                    />
                    <div>
                        <h3
                            class="font-semibold text-green-900 dark:text-green-100"
                        >
                            WhatsApp Connected
                        </h3>
                        <p class="text-sm text-green-800 dark:text-green-200">
                            Your WhatsApp account is active and ready to send
                            messages
                        </p>
                    </div>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Contacts -->
                <div class="rounded-lg border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Total Contacts
                            </p>
                            <h3 class="mt-2 text-3xl font-bold">
                                {{ stats.total_contacts.toLocaleString() }}
                            </h3>
                        </div>
                        <div
                            class="rounded-full bg-blue-100 p-3 dark:bg-blue-950"
                        >
                            <Users
                                class="h-6 w-6 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        Valid contacts ready to receive messages
                    </p>
                </div>

                <!-- Total Imports -->
                <div class="rounded-lg border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Total Imports
                            </p>
                            <h3 class="mt-2 text-3xl font-bold">
                                {{ stats.total_imports }}
                            </h3>
                        </div>
                        <div
                            class="rounded-full bg-purple-100 p-3 dark:bg-purple-950"
                        >
                            <FileText
                                class="h-6 w-6 text-purple-600 dark:text-purple-400"
                            />
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        Contact lists ready for campaigns
                    </p>
                </div>

                <!-- Messages Sent -->
                <div class="rounded-lg border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Messages Sent
                            </p>
                            <h3 class="mt-2 text-3xl font-bold">
                                {{ stats.messages_sent.toLocaleString() }}
                            </h3>
                        </div>
                        <div
                            class="rounded-full bg-green-100 p-3 dark:bg-green-950"
                        >
                            <CheckCircle
                                class="h-6 w-6 text-green-600 dark:text-green-400"
                            />
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        Successfully delivered messages
                    </p>
                </div>

                <!-- Total Campaigns -->
                <div class="rounded-lg border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Total Campaigns
                            </p>
                            <h3 class="mt-2 text-3xl font-bold">
                                {{ stats.total_campaigns }}
                            </h3>
                        </div>
                        <div
                            class="rounded-full bg-orange-100 p-3 dark:bg-orange-950"
                        >
                            <MessageSquare
                                class="h-6 w-6 text-orange-600 dark:text-orange-400"
                            />
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        Bulk messaging campaigns created
                    </p>
                </div>
            </div>

            <!-- Message Status Grid -->
            <div class="grid gap-4 md:grid-cols-3">
                <!-- Messages Queued -->
                <div class="rounded-lg border p-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-950"
                        >
                            <Clock
                                class="h-4 w-4 text-yellow-600 dark:text-yellow-400"
                            />
                        </div>
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Queued
                            </p>
                            <p class="text-xl font-bold">
                                {{ stats.messages_queued }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Messages Sent -->
                <div class="rounded-lg border p-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-full bg-green-100 p-2 dark:bg-green-950"
                        >
                            <CheckCircle
                                class="h-4 w-4 text-green-600 dark:text-green-400"
                            />
                        </div>
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Sent
                            </p>
                            <p class="text-xl font-bold">
                                {{ stats.messages_sent }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Messages Failed -->
                <div class="rounded-lg border p-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-full bg-red-100 p-2 dark:bg-red-950"
                        >
                            <XCircle
                                class="h-4 w-4 text-red-600 dark:text-red-400"
                            />
                        </div>
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Failed
                            </p>
                            <p class="text-xl font-bold">
                                {{ stats.messages_failed }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Imports -->
                <div class="rounded-lg border">
                    <div class="flex items-center justify-between border-b p-4">
                        <h2 class="text-lg font-semibold">Recent Imports</h2>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="navigateToImports"
                        >
                            View All
                        </Button>
                    </div>
                    <div class="divide-y">
                        <div
                            v-for="item in recentImports"
                            :key="item.id"
                            class="flex items-center justify-between p-4 hover:bg-muted/30"
                        >
                            <div>
                                <p class="font-medium">{{ item.filename }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ item.valid_rows }} contacts
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <Badge :variant="statusColor[item.status]">
                                    {{ item.status }}
                                </Badge>
                                <span class="text-xs text-muted-foreground">
                                    {{
                                        new Date(
                                            item.created_at,
                                        ).toLocaleDateString()
                                    }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="recentImports.length === 0"
                            class="flex flex-col items-center justify-center p-8 text-center"
                        >
                            <FileText
                                class="mb-2 h-8 w-8 text-muted-foreground"
                            />
                            <p class="text-sm text-muted-foreground">
                                No imports yet
                            </p>
                            <Button
                                class="mt-2"
                                size="sm"
                                variant="link"
                                @click="navigateToImports"
                            >
                                Upload contacts
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Recent Campaigns -->
                <div class="rounded-lg border">
                    <div class="flex items-center justify-between border-b p-4">
                        <h2 class="text-lg font-semibold">Recent Campaigns</h2>
                        <Button disabled size="sm" variant="outline">
                            View All
                        </Button>
                    </div>
                    <div class="divide-y">
                        <div
                            v-for="campaign in recentCampaigns"
                            :key="campaign.id"
                            class="flex items-center justify-between p-4 hover:bg-muted/30"
                        >
                            <div>
                                <p class="font-medium">{{ campaign.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{
                                        new Date(
                                            campaign.created_at,
                                        ).toLocaleDateString()
                                    }}
                                </p>
                            </div>
                            <Badge :variant="statusColor[campaign.status]">
                                {{ campaign.status }}
                            </Badge>
                        </div>

                        <div
                            v-if="recentCampaigns.length === 0"
                            class="flex flex-col items-center justify-center p-8 text-center"
                        >
                            <MessageSquare
                                class="mb-2 h-8 w-8 text-muted-foreground"
                            />
                            <p class="text-sm text-muted-foreground">
                                No campaigns yet
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Create a campaign to start sending messages
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-lg border p-6">
                <h2 class="mb-4 text-lg font-semibold">Quick Actions</h2>
                <div class="flex flex-wrap gap-3">
                    <Button
                        v-if="!stats.whatsapp_connected"
                        variant="default"
                        @click="navigateToWhatsApp"
                    >
                        <Smartphone class="mr-2 h-4 w-4" />
                        Connect WhatsApp
                    </Button>
                    <Button variant="outline" @click="navigateToImports">
                        <Users class="mr-2 h-4 w-4" />
                        Import Contacts
                    </Button>
                    <Button disabled variant="outline">
                        <MessageSquare class="mr-2 h-4 w-4" />
                        Create Campaign
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
