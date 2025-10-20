<script lang="ts" setup>
import PhoneVerificationBanner from '@/components/PhoneVerificationBanner.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, CheckCircle, MessageSquare, TrendingUp, Upload, Users } from 'lucide-vue-next';

const { t, isRTL } = useTranslation();

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
        <Head :title="t('dashboard.title')" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ t('dashboard.title') }}</h1>
                    <p class="text-muted-foreground mt-1">{{ t('dashboard.welcome') }}</p>
                </div>
            </div>

            <PhoneVerificationBanner :phone-verified="phoneVerified" :user-phone="userPhone" />

            <div v-if="!hasWhatsApp" class="rounded-lg border-2 border-orange-200 bg-gradient-to-r from-orange-50 to-orange-100 p-4 dark:border-orange-800 dark:from-orange-950 dark:to-orange-900">
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-orange-200 p-2 dark:bg-orange-800">
                        <MessageSquare class="h-5 w-5 text-orange-700 dark:text-orange-300" />
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-orange-900 dark:text-orange-100">{{ t('dashboard.whatsapp_not_connected') }}</p>
                        <p class="text-sm text-orange-800 dark:text-orange-200">{{ t('dashboard.whatsapp_not_connected_desc') }}</p>
                    </div>
                    <Link href="/w/connect" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 dark:bg-orange-700 dark:hover:bg-orange-600">
                        {{ t('dashboard.connect_now') }}
                    </Link>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card class="overflow-hidden border-l-4 border-l-blue-500 dark:border-l-blue-400">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('dashboard.total_imports') }}</CardTitle>
                        <div class="rounded-full bg-blue-100 p-2.5 dark:bg-blue-950">
                            <Upload class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-1">
                        <div class="text-3xl font-bold text-blue-700 dark:text-blue-400">{{ stats.total_imports }}</div>
                        <p class="text-xs text-muted-foreground flex items-center gap-1">
                            <TrendingUp class="h-3 w-3" />
                            <span>{{ t('dashboard.from_csv_excel') }}</span>
                        </p>
                    </CardContent>
                </Card>

                <Card class="overflow-hidden border-l-4 border-l-purple-500 dark:border-l-purple-400">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('dashboard.valid_contacts') }}</CardTitle>
                        <div class="rounded-full bg-purple-100 p-2.5 dark:bg-purple-950">
                            <Users class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-1">
                        <div class="text-3xl font-bold text-purple-700 dark:text-purple-400">{{ stats.total_contacts }}</div>
                        <p class="text-xs text-muted-foreground flex items-center gap-1">
                            <CheckCircle class="h-3 w-3" />
                            <span>{{ t('dashboard.ready_to_message') }}</span>
                        </p>
                    </CardContent>
                </Card>

                <Card class="overflow-hidden border-l-4 border-l-green-500 dark:border-l-green-400">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('dashboard.total_campaigns') }}</CardTitle>
                        <div class="rounded-full bg-green-100 p-2.5 dark:bg-green-950">
                            <MessageSquare class="h-4 w-4 text-green-600 dark:text-green-400" />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-1">
                        <div class="text-3xl font-bold text-green-700 dark:text-green-400">{{ stats.total_campaigns }}</div>
                        <p class="text-xs text-muted-foreground flex items-center gap-1">
                            <TrendingUp class="h-3 w-3" />
                            <span>{{ t('dashboard.active_and_completed') }}</span>
                        </p>
                    </CardContent>
                </Card>

                <Card class="overflow-hidden border-l-4 border-l-emerald-500 dark:border-l-emerald-400">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('dashboard.messages_sent') }}</CardTitle>
                        <div class="rounded-full bg-emerald-100 p-2.5 dark:bg-emerald-950">
                            <CheckCircle class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-1">
                        <div class="text-3xl font-bold text-emerald-700 dark:text-emerald-400">{{ stats.messages_sent }}</div>
                        <p class="text-xs text-muted-foreground flex items-center gap-1">
                            <CheckCircle class="h-3 w-3" />
                            <span>{{ t('dashboard.successfully_delivered') }}</span>
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between border-b bg-muted/30">
                        <div class="flex items-center gap-2">
                            <div class="rounded-md bg-blue-100 p-1.5 dark:bg-blue-950">
                                <Upload class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            <CardTitle class="text-base">{{ t('dashboard.recent_imports') }}</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="recentImports.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="rounded-full bg-muted p-4 mb-3">
                                <Upload class="h-8 w-8 text-muted-foreground" />
                            </div>
                            <p class="text-sm font-medium text-muted-foreground">{{ t('dashboard.no_imports') }}</p>
                        </div>
                        <div v-else class="divide-y">
                            <Link v-for="import_ in recentImports" :key="import_.id" :href="`/contacts/imports/${import_.id}`" class="flex items-center justify-between p-4 transition-colors hover:bg-muted/50">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="rounded-lg bg-blue-50 p-2 dark:bg-blue-950/50">
                                        <Upload class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-sm truncate">{{ import_.filename }}</p>
                                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
                                            <Users class="h-3 w-3" />
                                            <span>{{ import_.valid_rows }} {{ t('dashboard.valid_contacts_suffix') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <ArrowUpRight class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between border-b bg-muted/30">
                        <div class="flex items-center gap-2">
                            <div class="rounded-md bg-green-100 p-1.5 dark:bg-green-950">
                                <MessageSquare class="h-4 w-4 text-green-600 dark:text-green-400" />
                            </div>
                            <CardTitle class="text-base">{{ t('dashboard.recent_campaigns') }}</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="recentCampaigns.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="rounded-full bg-muted p-4 mb-3">
                                <MessageSquare class="h-8 w-8 text-muted-foreground" />
                            </div>
                            <p class="text-sm font-medium text-muted-foreground">{{ t('dashboard.no_campaigns') }}</p>
                        </div>
                        <div v-else class="divide-y">
                            <Link v-for="campaign in recentCampaigns" :key="campaign.id" :href="`/campaigns/${campaign.id}`" class="flex items-center justify-between p-4 transition-colors hover:bg-muted/50">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="rounded-lg bg-green-50 p-2 dark:bg-green-950/50">
                                        <MessageSquare class="h-4 w-4 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-sm truncate">{{ campaign.name }}</p>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <Badge :variant="campaign.status === 'completed' ? 'default' : campaign.status === 'running' ? 'secondary' : 'outline'" class="text-xs">{{ campaign.status }}</Badge>
                                        </div>
                                    </div>
                                </div>
                                <ArrowUpRight class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
