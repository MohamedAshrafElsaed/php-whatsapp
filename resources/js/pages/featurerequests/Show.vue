<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, User } from 'lucide-vue-next';

interface FeatureRequest {
    id: number;
    title: string;
    description: string;
    status: string;
    admin_notes: string | null;
    created_at: string;
    updated_at: string;
    user: {
        name: string;
        email: string;
    };
}

defineProps<{
    request: FeatureRequest;
}>();

const statusColors: Record<string, string> = {
    pending: 'secondary',
    under_review: 'default',
    planned: 'default',
    in_progress: 'default',
    completed: 'default',
    rejected: 'destructive',
};

const statusLabels: Record<string, string> = {
    pending: 'Pending Review',
    under_review: 'Under Review',
    planned: 'Planned',
    in_progress: 'In Progress',
    completed: 'Completed',
    rejected: 'Rejected',
};
</script>

<template>
    <AppLayout>
        <Head :title="request.title" />

        <div class="mx-auto max-w-4xl space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/feature-requests">
                    <Button size="icon" variant="ghost">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ request.title }}
                    </h1>
                </div>
                <Badge :variant="statusColors[request.status]" class="text-sm">
                    {{ statusLabels[request.status] }}
                </Badge>
            </div>

            <!-- Request Details -->
            <div class="rounded-lg border bg-card p-6">
                <div
                    class="mb-6 flex items-center gap-6 text-sm text-muted-foreground"
                >
                    <div class="flex items-center gap-2">
                        <User class="h-4 w-4" />
                        <span>{{ request.user.name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Calendar class="h-4 w-4" />
                        <span
                            >Submitted
                            {{
                                new Date(
                                    request.created_at,
                                ).toLocaleDateString()
                            }}</span
                        >
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <h2 class="mb-3 text-lg font-semibold">Description</h2>
                        <div
                            class="text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground"
                        >
                            {{ request.description }}
                        </div>
                    </div>

                    <!-- Admin Notes (if any) -->
                    <div
                        v-if="request.admin_notes"
                        class="rounded-lg border bg-muted/50 p-4"
                    >
                        <h3 class="mb-2 font-semibold">Admin Response</h3>
                        <p
                            class="text-sm whitespace-pre-wrap text-muted-foreground"
                        >
                            {{ request.admin_notes }}
                        </p>
                    </div>

                    <!-- Status Timeline (placeholder for future enhancement) -->
                    <div class="border-t pt-4">
                        <h3 class="mb-3 font-semibold">Timeline</h3>
                        <div class="space-y-2">
                            <div class="flex items-start gap-3">
                                <div
                                    class="mt-1 h-2 w-2 rounded-full bg-primary"
                                ></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium">
                                        Request Submitted
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            new Date(
                                                request.created_at,
                                            ).toLocaleString()
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div
                                v-if="request.status !== 'pending'"
                                class="flex items-start gap-3"
                            >
                                <div
                                    class="mt-1 h-2 w-2 rounded-full bg-primary"
                                ></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium">
                                        Status:
                                        {{ statusLabels[request.status] }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            new Date(
                                                request.updated_at,
                                            ).toLocaleString()
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="rounded-lg border bg-muted/50 p-4">
                <p class="text-sm text-muted-foreground">
                    <strong>Note:</strong> We review all feature requests
                    regularly. You'll see status updates here as we progress
                    through the review process.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
