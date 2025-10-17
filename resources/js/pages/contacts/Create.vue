<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    extra_fields: {} as Record<string, string>,
});

const submit = () => {
    form.post('/contacts', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Add Contact" />

        <div class="mx-auto max-w-2xl space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/contacts">
                    <Button size="icon" variant="ghost">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        Add Contact
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Manually add a new contact to your list
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form
                class="space-y-6 rounded-lg border bg-card p-4 md:p-6"
                @submit.prevent="submit"
            >
                <!-- First Name -->
                <div class="space-y-2">
                    <Label for="first_name">
                        First Name <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="first_name"
                        v-model="form.first_name"
                        placeholder="John"
                        required
                        type="text"
                    />
                    <p
                        v-if="form.errors.first_name"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.first_name }}
                    </p>
                </div>

                <!-- Last Name -->
                <div class="space-y-2">
                    <Label for="last_name">Last Name</Label>
                    <Input
                        id="last_name"
                        v-model="form.last_name"
                        placeholder="Doe"
                        type="text"
                    />
                    <p
                        v-if="form.errors.last_name"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.last_name }}
                    </p>
                </div>

                <!-- Phone -->
                <div class="space-y-2">
                    <Label for="phone">
                        Phone Number <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        placeholder="+1234567890"
                        required
                        type="tel"
                    />
                    <p class="text-xs text-muted-foreground">
                        Include country code (e.g., +1 for US, +44 for UK)
                    </p>
                    <p
                        v-if="form.errors.phone"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.phone }}
                    </p>
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        placeholder="john.doe@example.com"
                        type="email"
                    />
                    <p
                        v-if="form.errors.email"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Form Actions -->
                <div
                    class="flex flex-col-reverse items-center justify-end gap-3 border-t pt-6 sm:flex-row"
                >
                    <Link class="w-full sm:w-auto" href="/contacts">
                        <Button class="w-full" type="button" variant="outline"
                            >Cancel</Button
                        >
                    </Link>
                    <Button
                        :disabled="form.processing"
                        class="w-full sm:w-auto"
                        type="submit"
                    >
                        {{ form.processing ? 'Creating...' : 'Create Contact' }}
                    </Button>
                </div>
            </form>

            <!-- Info Card -->
            <div class="rounded-lg border bg-muted/50 p-4">
                <h3 class="mb-2 text-sm font-semibold md:text-base">
                    Tips for adding contacts:
                </h3>
                <ul class="space-y-1 text-sm text-muted-foreground">
                    <li>
                        • Phone number must include country code (e.g., +1, +44,
                        +91)
                    </li>
                    <li>• Phone number will be automatically validated</li>
                    <li>• First name is required, other fields are optional</li>
                    <li>• Duplicate phone numbers are not allowed</li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
