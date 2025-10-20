<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Send, Users, CheckCircle2, XCircle, FileSpreadsheet } from 'lucide-vue-next';

const { t } = useTranslation();

interface Recipient {
    id: number;
    phone_raw: string;
    phone_e164: string | null;
    first_name: string | null;
    last_name: string | null;
    email: string | null;
    is_valid: boolean;
    validation_errors_json: string[] | null;
}

interface ImportData {
    id: number;
    filename: string;
    total_rows: number;
    valid_rows: number;
    invalid_rows: number;
    status: string;
    created_at: string;
}

const props = defineProps<{
    importData: ImportData;
    recipients: Recipient[];
}>();

const createCampaign = () => {
    router.visit(`/campaigns/create?import_id=${props.importData.id}`);
};
</script>

<template>
    <AppLayout>
        <Head :title="`${t('imports.import')}: ${importData.filename}`" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/contacts/imports">
                        <Button size="icon" variant="ghost">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ importData.filename }}</h1>
                        <p class="text-muted-foreground mt-1">
                            {{ t('imports.uploaded') }} {{ new Date(importData.created_at).toLocaleDateString() }}
                        </p>
                    </div>
                </div>
                <Button
                    v-if="importData.status === 'ready' && importData.valid_rows > 0"
                    variant="default"
                    @click="createCampaign"
                >
                    <Send class="mr-2 h-4 w-4" />
                    {{ t('imports.use_in_campaign') }}
                </Button>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border-l-4 border-l-blue-500 bg-card p-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-950">
                            <FileSpreadsheet class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-blue-700 dark:text-blue-400">{{ importData.total_rows }}</div>
                            <div class="text-sm text-muted-foreground mt-1">{{ t('imports.total_rows') }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border-l-4 border-l-green-500 bg-card p-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-green-100 p-2 dark:bg-green-950">
                            <CheckCircle2 class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-green-700 dark:text-green-400">{{ importData.valid_rows }}</div>
                            <div class="text-sm text-muted-foreground mt-1">{{ t('imports.valid_contacts') }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border-l-4 border-l-red-500 bg-card p-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-red-100 p-2 dark:bg-red-950">
                            <XCircle class="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-red-700 dark:text-red-400">{{ importData.invalid_rows }}</div>
                            <div class="text-sm text-muted-foreground mt-1">{{ t('imports.invalid_contacts') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-card overflow-hidden">
                <div class="border-b bg-muted/30 p-4">
                    <div class="flex items-center gap-2">
                        <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                            <Users class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h2 class="text-lg font-semibold">{{ t('imports.recipients_preview') }}</h2>
                    </div>
                </div>

                <div v-if="recipients.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/30">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.status') }}</th>
                            <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.phone') }}</th>
                            <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.name') }}</th>
                            <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.email') }}</th>
                            <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.errors') }}</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        <tr v-for="recipient in recipients" :key="recipient.id" class="hover:bg-muted/30">
                            <td class="px-6 py-4">
                                <Badge :variant="recipient.is_valid ? 'default' : 'destructive'">
                                    {{ recipient.is_valid ? t('imports.valid') : t('imports.invalid') }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm">{{ recipient.phone_e164 || recipient.phone_raw }}</td>
                            <td class="px-6 py-4">
                                {{ [recipient.first_name, recipient.last_name].filter(Boolean).join(' ') || '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">{{ recipient.email || '-' }}</td>
                            <td class="px-6 py-4">
                                    <span v-if="recipient.validation_errors_json" class="text-sm text-red-600 dark:text-red-400">
                                        {{ recipient.validation_errors_json.join(', ') }}
                                    </span>
                                <span v-else class="text-sm text-muted-foreground">-</span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="flex flex-col items-center justify-center p-12">
                    <div class="rounded-full bg-muted p-4 mb-4">
                        <Users class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <p class="text-lg font-semibold">{{ t('imports.no_recipients') }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
