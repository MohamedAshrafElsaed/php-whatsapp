<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';

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

const goBack = () => {
    router.visit('/contacts/imports');
};
</script>

<template>
    <AppLayout>
        <Head :title="`Import: ${importData.filename}`" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button
                        class="mb-2"
                        size="sm"
                        variant="ghost"
                        @click="goBack"
                    >
                        ← Back to Imports
                    </Button>
                    <h1 class="text-2xl font-bold">
                        {{ importData.filename }}
                    </h1>
                    <p class="text-muted-foreground">
                        Uploaded
                        {{
                            new Date(importData.created_at).toLocaleDateString()
                        }}
                    </p>
                </div>

                <Button
                    v-if="
                        importData.status === 'ready' &&
                        importData.valid_rows > 0
                    "
                    size="lg"
                    @click="createCampaign"
                >
                    Use in Campaign
                </Button>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border p-6">
                    <div class="text-2xl font-bold">
                        {{ importData.total_rows }}
                    </div>
                    <div class="text-sm text-muted-foreground">Total Rows</div>
                </div>

                <div class="rounded-lg border p-6">
                    <div class="text-2xl font-bold text-green-600">
                        {{ importData.valid_rows }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        Valid Contacts
                    </div>
                </div>

                <div class="rounded-lg border p-6">
                    <div class="text-2xl font-bold text-red-600">
                        {{ importData.invalid_rows }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        Invalid Contacts
                    </div>
                </div>
            </div>

            <!-- Recipients Preview -->
            <div class="rounded-lg border">
                <div class="border-b p-4">
                    <h2 class="text-lg font-semibold">
                        Recipients Preview (First 50)
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="p-4 text-left font-medium">
                                    Status
                                </th>
                                <th class="p-4 text-left font-medium">Phone</th>
                                <th class="p-4 text-left font-medium">Name</th>
                                <th class="p-4 text-left font-medium">Email</th>
                                <th class="p-4 text-left font-medium">
                                    Errors
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="recipient in recipients"
                                :key="recipient.id"
                                class="border-b hover:bg-muted/30"
                            >
                                <td class="p-4">
                                    <Badge
                                        :variant="
                                            recipient.is_valid
                                                ? 'default'
                                                : 'destructive'
                                        "
                                    >
                                        {{
                                            recipient.is_valid
                                                ? 'Valid'
                                                : 'Invalid'
                                        }}
                                    </Badge>
                                </td>
                                <td class="p-4 font-mono text-sm">
                                    {{
                                        recipient.phone_e164 ||
                                        recipient.phone_raw
                                    }}
                                </td>
                                <td class="p-4">
                                    {{
                                        [
                                            recipient.first_name,
                                            recipient.last_name,
                                        ]
                                            .filter(Boolean)
                                            .join(' ') || '-'
                                    }}
                                </td>
                                <td class="p-4 text-sm text-muted-foreground">
                                    {{ recipient.email || '-' }}
                                </td>
                                <td class="p-4">
                                    <span
                                        v-if="recipient.validation_errors_json"
                                        class="text-sm text-red-600"
                                    >
                                        {{
                                            recipient.validation_errors_json.join(
                                                ', ',
                                            )
                                        }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-sm text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="recipients.length === 0"
                    class="flex flex-col items-center justify-center p-12 text-center"
                >
                    <p class="text-lg font-medium">No recipients found</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
