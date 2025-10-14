<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

const form = useForm({
    title: '',
    description: '',
});

const submit = () => {
    form.post('/feature-requests', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Submit Feature Request" />

        <div class="mx-auto max-w-2xl space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/feature-requests">
                    <Button size="icon" variant="ghost">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Submit Feature Request</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Tell us about a feature you'd like to see
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6 rounded-lg border bg-card p-6">
                <!-- Title -->
                <div class="space-y-2">
                    <Label for="title">
                        Feature Title <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="title"
                        v-model="form.title"
                        type="text"
                        placeholder="e.g., Add message scheduling feature"
                        required
                    />
                    <p v-if="form.errors.title" class="text-sm text-destructive">
                        {{ form.errors.title }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        A short, descriptive title for your feature request
                    </p>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">
                        Description <span class="text-destructive">*</span>
                    </Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="8"
                        placeholder="Describe the feature you'd like to see, why it would be useful, and how it should work..."
                        required
                    />
                    <p v-if="form.errors.description" class="text-sm text-destructive">
                        {{ form.errors.description }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ form.description.length }} / 5000 characters
                    </p>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 border-t pt-6">
                    <Link href="/feature-requests">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                    </Button>
                </div>
            </form>

            <!-- Info Card -->
            <div class="rounded-lg border bg-muted/50 p-4">
                <h3 class="mb-2 font-semibold">Guidelines for Feature Requests:</h3>
                <ul class="space-y-1 text-sm text-muted-foreground">
                    <li>• Be specific and clear about what you want</li>
                    <li>• Explain why this feature would be valuable</li>
                    <li>• Provide examples or use cases if possible</li>
                    <li>• Check if a similar request already exists</li>
                    <li>• We review all requests and update their status regularly</li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
