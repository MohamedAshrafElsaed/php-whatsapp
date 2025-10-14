<script lang="ts" setup>
import PhoneVerificationBanner from '@/components/PhoneVerificationBanner.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, MessageSquare, Upload, Users } from 'lucide-vue-next';

defineProps<{
    stats: {
        total_imports: number;
        total_contacts: number;
        total_campaigns: number;
        messages_sent: number;
    };
    recentImports: Array<any>;
    recentCampaigns: Array<any>;
    hasWhatsApp: boolean;
    phoneVerified: boolean;
    userPhone: {
        country_code: string;
        phone: string;
        full_phone: string;
    };
}>();
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold">Dashboard</h1>
                <p class="text-muted-foreground">
                    Welcome back! Here's an overview of your account.
                </p>
            </div>

            <!-- Phone Verification Banner -->
            <PhoneVerificationBanner
                :phone-verified="phoneVerified"
                :user-phone="userPhone"
            />

            <!-- WhatsApp Connection Warning -->
            <div
                v-if="!hasWhatsApp"
                class="rounded-lg border border-orange-200 bg-orange-50 p-4"
            >
                <div class="flex items-center gap-2">
                    <MessageSquare class="h-5 w-5 text-orange-600" />
                    <p class="text-sm text-orange-900">
                        <span class="font-medium">WhatsApp Not Connected:</span>
                        Connect your WhatsApp to start sending messages.
                        <Link
                            class="underline hover:text-orange-700"
                            href="/wa/connect"
                        >
                            Connect Now
                        </Link>
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Total Imports
                        </CardTitle>
                        <Upload class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.total_imports }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Valid Contacts
                        </CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.total_contacts }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Total Campaigns
                        </CardTitle>
                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.total_campaigns }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            Messages Sent
                        </CardTitle>
                        <CheckCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.messages_sent }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Recent Imports -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Imports</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="recentImports.length === 0"
                            class="py-4 text-center text-muted-foreground"
                        >
                            No imports yet
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="import_ in recentImports"
                                :key="import_.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">
                                        {{ import_.filename }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ import_.valid_rows }} valid contacts
                                    </p>
                                </div>
                                <Link
                                    :href="`/contacts/imports/${import_.id}`"
                                    class="text-sm text-primary hover:underline"
                                >
                                    View
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Campaigns -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Campaigns</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="recentCampaigns.length === 0"
                            class="py-4 text-center text-muted-foreground"
                        >
                            No campaigns yet
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="campaign in recentCampaigns"
                                :key="campaign.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">
                                        {{ campaign.name }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        Status: {{ campaign.status }}
                                    </p>
                                </div>
                                <Link
                                    :href="`/campaigns/${campaign.id}`"
                                    class="text-sm text-primary hover:underline"
                                >
                                    View
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
