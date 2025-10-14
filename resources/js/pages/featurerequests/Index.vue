<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Plus, MessageSquareText, CheckCircle2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface FeatureRequest {
    id: number;
    title: string;
    description: string;
    status: string;
    created_at: string;
}

defineProps<{
    requests: {
        data: FeatureRequest[];
        links: any;
        meta: any;
    };
}>();

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const statusColors: Record<string, string> = {
    pending: 'secondary',
    under_review: 'default',
    planned: 'default',
    in_progress: 'default',
    completed: 'default',
    rejected: 'destructive',
};

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    under_review: 'Under Review',
    planned: 'Planned',
    in_progress: 'In Progress',
    completed: 'Completed',
    rejected: 'Rejected',
};
</script>

<template>
    <AppLayout>
        <Head title="Feature Requests" />

        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Feature Requests</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Submit feature requests and track their status
                    </p>
                </div>
                <Link href="/feature-requests/create">
                    <Button class="gap-2">
                        <Plus class="h-4 w-4" />
                        New Request
                    </Button>
                </Link>
            </div>

            <!-- Success Alert -->
            <Alert v-if="flashSuccess" variant="default" class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950">
                <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                <AlertDescription class="text-green-700 dark:text-green-300">
                    {{ flashSuccess }}
                </AlertDescription>
            </Alert>

            <!-- Requests List -->
            <div class="rounded-lg border bg-card">
                <div v-if="requests.data.length > 0" class="divide-y">
                    <Link
                        v-for="request in requests.data"
                        :key="request.id"
                        :href="`/feature-requests/${request.id}`"
                        class="block p-6 transition-colors hover:bg-muted/50"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-lg font-semibold">{{ request.title }}</h3>
                                    <Badge :variant="statusColors[request.status]">
                                        {{ statusLabels[request.status] }}
                                    </Badge>
                                </div>
                                <p class="mt-2 line-clamp-2 text-sm text-muted-foreground">
                                    {{ request.description }}
                                </p>
                                <p class="mt-2 text-xs text-muted-foreground">
                                    Submitted {{ new Date(request.created_at).toLocaleDateString() }}
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center p-12 text-center"
                >
                    <MessageSquareText class="mb-4 h-12 w-12 text-muted-foreground" />
                    <h3 class="mb-2 text-lg font-semibold">No feature requests yet</h3>
                    <p class="mb-4 text-sm text-muted-foreground">
                        Have an idea? Submit your first feature request.
                    </p>
                    <Link href="/feature-requests/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Create Request
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
