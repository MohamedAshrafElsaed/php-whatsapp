<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, Calendar, FileText, Mail, MessageSquare, Phone,
    Send, Trash2, User, Image as ImageIcon,
    File, Video, Music, Smartphone, AlertCircle
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const { t, isRTL } = useTranslation();

interface WaSession {
    id: number;
    device_label: string;
    is_primary: boolean;
}

interface Contact {
    id: number;
    full_name: string;
    first_name: string;
    last_name: string;
    phone_e164: string;
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
    sessions: WaSession[];
}>();

const showSendForm = ref(false);
const messageType = ref<'text' | 'media'>('text');
const selectedSession = ref<number | null>(null);

const hasConnectedDevices = computed(() => props.sessions.length > 0);
const defaultSession = computed(() => props.sessions.find(s => s.is_primary)?.id || props.sessions[0]?.id || null);

const textForm = useForm({
    message: '',
    wa_session_id: null as number | null,
});

const mediaForm = useForm({
    media_type: 'image',
    media: null as File | null,
    caption: '',
    wa_session_id: null as number | null,
});

const sendTextMessage = () => {
    textForm.wa_session_id = selectedSession.value || defaultSession.value;
    textForm.post(`/contacts/${props.contact.id}/send`, {
        preserveScroll: true,
        onSuccess: () => {
            textForm.reset();
            showSendForm.value = false;
        },
    });
};

const sendMedia = () => {
    if (!mediaForm.media) {
        alert(t('contacts.select_file_error'));
        return;
    }
    mediaForm.wa_session_id = selectedSession.value || defaultSession.value;
    mediaForm.post(`/contacts/${props.contact.id}/send-media`, {
        preserveScroll: true,
        onSuccess: () => {
            mediaForm.reset();
            showSendForm.value = false;
        },
    });
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        mediaForm.media = target.files[0];
    }
};

const deleteContact = () => {
    if (confirm(t('contacts.confirm_delete'))) {
        router.delete(`/contacts/${props.contact.id}`);
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'sent': return 'default';
        case 'failed': return 'destructive';
        case 'queued': return 'secondary';
        default: return 'outline';
    }
};

const getMediaIcon = (type: string) => {
    switch (type) {
        case 'image': return ImageIcon;
        case 'video': return Video;
        case 'audio': return Music;
        case 'file': return File;
        default: return File;
    }
};
</script>

<template>
    <AppLayout>
        <Head :title="contact.full_name" />

        <div class="min-h-screen bg-background">
            <div class="mx-auto max-w-7xl space-y-4 p-4 sm:space-y-6 sm:p-6 lg:p-8">
                <!-- Header Section -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <Link href="/contacts">
                            <Button size="icon" variant="ghost" class="h-9 w-9 shrink-0 sm:h-10 sm:w-10">
                                <ArrowLeft class="h-4 w-4 sm:h-5 sm:w-5" />
                            </Button>
                        </Link>
                        <div class="min-w-0 flex-1">
                            <h1 class="truncate text-2xl font-bold tracking-tight sm:text-3xl">
                                {{ contact.full_name }}
                            </h1>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ t('contacts.contact_details') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="hasConnectedDevices"
                            variant="default"
                            class="flex-1 sm:flex-none"
                            @click="showSendForm = !showSendForm"
                        >
                            <Send class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            <span class="hidden sm:inline">{{ t('contacts.send_message') }}</span>
                            <span class="sm:hidden">{{ t('common.send') }}</span>
                        </Button>
                        <Link v-else href="/w/connect" class="flex-1 sm:flex-none">
                            <Button variant="outline" class="w-full">
                                <Smartphone class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                <span class="hidden sm:inline">{{ t('contacts.connect_whatsapp_first') }}</span>
                                <span class="sm:hidden">{{ t('contacts.connect_device') }}</span>
                            </Button>
                        </Link>
                        <Button
                            variant="destructive"
                            size="icon"
                            class="shrink-0"
                            @click="deleteContact"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- No Device Warning -->
                <div
                    v-if="!hasConnectedDevices"
                    class="rounded-lg border-2 border-orange-200 bg-gradient-to-r from-orange-50 to-orange-100 p-4 dark:border-orange-800 dark:from-orange-950/50 dark:to-orange-900/50"
                >
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 rounded-full bg-orange-200 p-2 dark:bg-orange-800">
                            <Smartphone class="h-5 w-5 text-orange-700 dark:text-orange-300" />
                        </div>
                        <div class="min-w-0 flex-1 space-y-1">
                            <p class="font-semibold text-orange-900 dark:text-orange-100">
                                {{ t('contacts.no_device_connected') }}
                            </p>
                            <p class="text-sm text-orange-800 dark:text-orange-200">
                                {{ t('contacts.connect_device_to_send') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Send Message Form -->
                <div
                    v-if="showSendForm && hasConnectedDevices"
                    class="rounded-lg border bg-card shadow-sm"
                >
                    <div class="space-y-6 p-4 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="shrink-0 rounded-lg bg-green-100 p-2 dark:bg-green-950">
                                    <MessageSquare class="h-5 w-5 text-green-600 dark:text-green-400" />
                                </div>
                                <h2 class="text-lg font-semibold">{{ t('contacts.send_message') }}</h2>
                            </div>
                            <Select
                                v-if="sessions.length > 1"
                                v-model="selectedSession"
                                class="w-full sm:w-[200px]"
                            >
                                <SelectTrigger>
                                    <SelectValue :placeholder="t('contacts.select_device')" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="session in sessions"
                                        :key="session.id"
                                        :value="session.id"
                                    >
                                        {{ session.device_label }}
                                        <span v-if="session.is_primary" class="text-xs text-muted-foreground">
                                            ({{ t('contacts.primary') }})
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <Tabs v-model="messageType" default-value="text" class="w-full">
                            <TabsList class="grid w-full grid-cols-2">
                                <TabsTrigger value="text">
                                    <MessageSquare class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                    {{ t('contacts.text') }}
                                </TabsTrigger>
                                <TabsTrigger value="media">
                                    <ImageIcon class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                    {{ t('contacts.media') }}
                                </TabsTrigger>
                            </TabsList>

                            <!-- Text Message Tab -->
                            <TabsContent value="text" class="mt-4">
                                <form @submit.prevent="sendTextMessage" class="space-y-4">
                                    <div class="space-y-2">
                                        <Label for="message">{{ t('contacts.message') }}</Label>
                                        <Textarea
                                            id="message"
                                            v-model="textForm.message"
                                            :placeholder="t('contacts.type_message')"
                                            required
                                            rows="6"
                                            class="resize-none"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            {{ textForm.message.length }} / 4096
                                        </p>
                                        <p v-if="textForm.errors.message" class="text-sm text-destructive">
                                            {{ textForm.errors.message }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="w-full sm:w-auto"
                                            @click="showSendForm = false"
                                        >
                                            {{ t('common.cancel') }}
                                        </Button>
                                        <Button
                                            type="submit"
                                            :disabled="textForm.processing"
                                            variant="default"
                                            class="w-full sm:w-auto"
                                        >
                                            <Send class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                            {{ textForm.processing ? t('common.sending') : t('common.send') }}
                                        </Button>
                                    </div>
                                </form>
                            </TabsContent>

                            <!-- Media Message Tab -->
                            <TabsContent value="media" class="mt-4">
                                <form @submit.prevent="sendMedia" class="space-y-4">
                                    <div class="space-y-2">
                                        <Label for="media_type">{{ t('contacts.media_type') }}</Label>
                                        <Select v-model="mediaForm.media_type">
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="image">
                                                    <div class="flex items-center">
                                                        <ImageIcon class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                                        {{ t('contacts.image') }}
                                                    </div>
                                                </SelectItem>
                                                <SelectItem value="video">
                                                    <div class="flex items-center">
                                                        <Video class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                                        {{ t('contacts.video') }}
                                                    </div>
                                                </SelectItem>
                                                <SelectItem value="audio">
                                                    <div class="flex items-center">
                                                        <Music class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                                        {{ t('contacts.audio') }}
                                                    </div>
                                                </SelectItem>
                                                <SelectItem value="file">
                                                    <div class="flex items-center">
                                                        <File class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                                        {{ t('contacts.document') }}
                                                    </div>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="file">{{ t('contacts.file') }}</Label>
                                        <Input
                                            id="file"
                                            type="file"
                                            required
                                            @change="handleFileChange"
                                            class="cursor-pointer"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            {{ t('contacts.max_file_size') }}
                                        </p>
                                        <p v-if="mediaForm.errors.media" class="text-sm text-destructive">
                                            {{ mediaForm.errors.media }}
                                        </p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="caption">
                                            {{ t('contacts.caption') }}
                                            <span class="text-xs text-muted-foreground">
                                                ({{ t('common.optional') }})
                                            </span>
                                        </Label>
                                        <Textarea
                                            id="caption"
                                            v-model="mediaForm.caption"
                                            :placeholder="t('contacts.add_caption')"
                                            rows="3"
                                            class="resize-none"
                                        />
                                    </div>
                                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="w-full sm:w-auto"
                                            @click="showSendForm = false"
                                        >
                                            {{ t('common.cancel') }}
                                        </Button>
                                        <Button
                                            type="submit"
                                            :disabled="mediaForm.processing"
                                            variant="default"
                                            class="w-full sm:w-auto"
                                        >
                                            <Send class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                            {{ mediaForm.processing ? t('common.sending') : t('common.send') }}
                                        </Button>
                                    </div>
                                </form>
                            </TabsContent>
                        </Tabs>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid gap-4 sm:gap-6 lg:grid-cols-3">
                    <!-- Contact Information Card -->
                    <div class="lg:col-span-1">
                        <div class="rounded-lg border bg-card shadow-sm">
                            <div class="space-y-6 p-4 sm:p-6">
                                <div class="flex items-center gap-3 border-b pb-4">
                                    <div class="shrink-0 rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                                        <User class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <h2 class="text-lg font-semibold">{{ t('contacts.contact_info') }}</h2>
                                </div>

                                <div class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <Phone class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium">{{ t('contacts.phone') }}</p>
                                            <p class="mt-1 break-all text-sm font-mono text-muted-foreground">
                                                {{ contact.phone_e164 }}
                                            </p>
                                        </div>
                                    </div>

                                    <div v-if="contact.email" class="flex items-start gap-3">
                                        <Mail class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium">{{ t('contacts.email') }}</p>
                                            <p class="mt-1 break-all text-sm text-muted-foreground">
                                                {{ contact.email }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3">
                                        <FileText class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium">{{ t('contacts.source') }}</p>
                                            <Badge variant="secondary" class="mt-1 text-xs">
                                                {{ contact.import_source }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3">
                                        <Calendar class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium">{{ t('contacts.added_on') }}</p>
                                            <p class="mt-1 text-sm text-muted-foreground">
                                                {{ contact.created_at }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="contact.extra_json && Object.keys(contact.extra_json).length > 0"
                                    class="border-t pt-4"
                                >
                                    <h3 class="mb-3 text-sm font-semibold">
                                        {{ t('contacts.additional_fields') }}
                                    </h3>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(value, key) in contact.extra_json"
                                            :key="key"
                                            class="flex items-start justify-between gap-2 text-sm"
                                        >
                                            <span class="font-medium capitalize">{{ key }}:</span>
                                            <span class="break-all text-muted-foreground ltr:text-right rtl:text-left">
                                                {{ value }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message History Card -->
                    <div class="lg:col-span-2">
                        <div class="rounded-lg border bg-card shadow-sm">
                            <div class="space-y-4 p-4 sm:p-6">
                                <div class="flex items-center gap-3 border-b pb-4">
                                    <div class="shrink-0 rounded-lg bg-green-100 p-2 dark:bg-green-950">
                                        <MessageSquare class="h-5 w-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <h2 class="text-lg font-semibold">{{ t('contacts.message_history') }}</h2>
                                </div>

                                <!-- Desktop Table View -->
                                <div v-if="messages.length > 0" class="hidden overflow-x-auto sm:block">
                                    <table class="w-full">
                                        <thead class="border-b bg-muted/30">
                                        <tr>
                                            <th class="px-4 py-3 text-start text-sm font-medium">
                                                {{ t('campaigns.name') }}
                                            </th>
                                            <th class="px-4 py-3 text-start text-sm font-medium">
                                                {{ t('contacts.message') }}
                                            </th>
                                            <th class="px-4 py-3 text-start text-sm font-medium">
                                                {{ t('dashboard.status') }}
                                            </th>
                                            <th class="px-4 py-3 text-start text-sm font-medium">
                                                {{ t('contacts.date') }}
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                        <tr
                                            v-for="message in messages"
                                            :key="message.id"
                                            class="transition-colors hover:bg-muted/30"
                                        >
                                            <td class="px-4 py-3 text-sm font-medium">
                                                {{ message.campaign_name }}
                                            </td>
                                            <td class="max-w-xs truncate px-4 py-3 text-sm text-muted-foreground">
                                                {{ message.body }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <Badge :variant="getStatusColor(message.status)">
                                                    {{ t(`campaigns.status_${message.status}`) }}
                                                </Badge>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-muted-foreground">
                                                {{ message.sent_at || message.created_at }}
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Mobile Card View -->
                                <div v-if="messages.length > 0" class="space-y-3 sm:hidden">
                                    <div
                                        v-for="message in messages"
                                        :key="message.id"
                                        class="rounded-lg border bg-muted/20 p-3 transition-colors hover:bg-muted/40"
                                    >
                                        <div class="mb-2 flex items-start justify-between gap-2">
                                            <p class="font-medium text-sm">{{ message.campaign_name }}</p>
                                            <Badge :variant="getStatusColor(message.status)" class="shrink-0">
                                                {{ t(`campaigns.status_${message.status}`) }}
                                            </Badge>
                                        </div>
                                        <p class="mb-2 line-clamp-2 text-sm text-muted-foreground">
                                            {{ message.body }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ message.sent_at || message.created_at }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Empty State -->
                                <div v-else class="flex flex-col items-center justify-center py-12">
                                    <div class="mb-4 rounded-full bg-muted p-4">
                                        <MessageSquare class="h-10 w-10 text-muted-foreground sm:h-12 sm:w-12" />
                                    </div>
                                    <h3 class="mb-2 text-center text-lg font-semibold">
                                        {{ t('contacts.no_messages') }}
                                    </h3>
                                    <p class="mb-4 text-center text-sm text-muted-foreground">
                                        {{ t('contacts.send_first_message') }}
                                    </p>
                                    <Button
                                        v-if="hasConnectedDevices"
                                        variant="default"
                                        @click="showSendForm = true"
                                    >
                                        <Send class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                        {{ t('contacts.send_message') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Cairo font for Arabic */
:root[dir="rtl"] {
    font-family: 'Cairo', sans-serif;
}

/* Inter font for English */
:root[dir="ltr"] {
    font-family: 'Inter', sans-serif;
}

/* Ensure proper text alignment in RTL */
[dir="rtl"] .text-start {
    text-align: right;
}

[dir="ltr"] .text-start {
    text-align: left;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
