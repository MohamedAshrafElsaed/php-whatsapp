<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Send, Trash2, MessageSquare, Mail, Phone, Calendar, FileText } from 'lucide-vue-next';
import { ref } from 'vue';

interface Contact {
    id: number;
    full_name: string;
    first_name: string;
    last_name: string;
    phone_e164: string;
    phone_raw: string;
    email: string | null;
    extra_json: Record<string, any> | null;
    import_source: string;
    created_at: string;
}

interface Message {
    id: number;
    campaign_name: string;
    body: string;
    status: 'queued' | 'sent' | 'failed';
    sent_at: string | null;
    created_at: string;
}

const props = defineProps<{
    contact: Contact;
    messages: Message[];
}>();

const showSendForm = ref(false);

const messageForm = useForm({
    message: '',
});

const sendMessage = () => {
    messageForm.post(`/contacts/${props.contact.id}/send`, {
        preserveScroll: true,
        onSuccess: () => {
            messageForm.reset();
            showSendForm.value = false;
        },
    });
};

const deleteContact = () => {
    if (confirm('Are you sure you want to delete this contact? This action cannot be undone.')) {
        router.delete(`/contacts/${props.contact.id}`);
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'sent':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'failed':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        case 'queued':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    }
};
</script>

<template>
    <AppLayout>
        <Head :title="contact.full_name" />

        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/contacts">
                        <Button size="icon" variant="ghost">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ contact.full_name }}</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Contact Details</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button @click="showSendForm = !showSendForm">
                        <Send class="mr-2 h-4 w-4" />
                        Send Message
                    </Button>
                    <Button variant="destructive" @click="deleteContact">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Send Message Form (Inline) -->
            <div v-if="showSendForm" class="rounded-lg border bg-card p-6">
                <h2 class="mb-4 text-lg font-semibold">Send Message</h2>
                <form @submit.prevent="sendMessage" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="message">Message</Label>
                        <Textarea
                            id="message"
                            v-model="messageForm.message"
                            placeholder="Type your message here..."
                            rows="6"
                            required
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ messageForm.message.length }} characters
                        </p>
                        <p v-if="messageForm.errors.message" class="text-sm text-destructive">
                            {{ messageForm.errors.message }}
                        </p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="outline" @click="showSendForm = false">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="messageForm.processing">
                            <Send class="mr-2 h-4 w-4" />
                            {{ messageForm.processing ? 'Sending...' : 'Send Message' }}
                        </Button>
                    </div>
                </form>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Contact Information Card -->
                <div class="space-y-6 lg:col-span-1">
                    <div class="rounded-lg border bg-card p-6">
                        <h2 class="mb-4 text-lg font-semibold">Contact Information</h2>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <Phone class="mt-0.5 h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">Phone</p>
                                    <p class="text-sm text-muted-foreground">{{ contact.phone_e164 }}</p>
                                    <p v-if="contact.phone_raw !== contact.phone_e164" class="text-xs text-muted-foreground">
                                        Original: {{ contact.phone_raw }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="contact.email" class="flex items-start gap-3">
                                <Mail class="mt-0.5 h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">Email</p>
                                    <p class="text-sm text-muted-foreground">{{ contact.email }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <FileText class="mt-0.5 h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">Import Source</p>
                                    <p class="text-sm text-muted-foreground">{{ contact.import_source }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <Calendar class="mt-0.5 h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">Added On</p>
                                    <p class="text-sm text-muted-foreground">{{ contact.created_at }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Extra Fields -->
                        <div v-if="contact.extra_json && Object.keys(contact.extra_json).length > 0" class="mt-6 border-t pt-4">
                            <h3 class="mb-3 text-sm font-semibold">Additional Fields</h3>
                            <div class="space-y-2">
                                <div v-for="(value, key) in contact.extra_json" :key="key" class="flex justify-between text-sm">
                                    <span class="font-medium capitalize">{{ key }}:</span>
                                    <span class="text-muted-foreground">{{ value }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message History -->
                <div class="lg:col-span-2">
                    <div class="rounded-lg border bg-card p-6">
                        <h2 class="mb-4 text-lg font-semibold">Message History</h2>

                        <div v-if="messages.length > 0" class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Campaign</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Message</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Date</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y">
                                <tr v-for="message in messages" :key="message.id" class="hover:bg-muted/30">
                                    <td class="px-4 py-3 text-sm">
                                        {{ message.campaign_name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="max-w-md truncate">
                                            {{ message.body }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge :class="getStatusColor(message.status)">
                                            {{ message.status }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ message.sent_at || message.created_at }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <p v-if="messages.length === 50" class="mt-4 text-center text-sm text-muted-foreground">
                                Showing last 50 messages
                            </p>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="flex flex-col items-center justify-center py-12">
                            <MessageSquare class="mb-4 h-12 w-12 text-muted-foreground" />
                            <h3 class="mb-2 text-lg font-semibold">No messages sent yet</h3>
                            <p class="mb-4 text-sm text-muted-foreground">
                                Send your first message to this contact
                            </p>
                            <Button @click="showSendForm = true">
                                <Send class="mr-2 h-4 w-4" />
                                Send Message
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
