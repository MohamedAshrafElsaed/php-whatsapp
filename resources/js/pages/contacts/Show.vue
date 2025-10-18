<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    FileText,
    Image as ImageIcon,
    Link as LinkIcon,
    Mail,
    MapPin,
    MessageSquare,
    Music,
    Phone,
    Send,
    Trash2,
    User,
    Video,
    BarChart3,
    File,
    Smartphone,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface WaSession {
    id: number;
    device_id: string;
    device_label: string;
    phone: string | null;
    name: string | null;
    is_primary: boolean;
}

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
    sessions: WaSession[];
}>();

const showSendForm = ref(false);
const messageType = ref<'text' | 'media' | 'link' | 'location' | 'contact' | 'poll'>('text');
const selectedSession = ref<number | null>(null);

// Check if user has connected devices
const hasConnectedDevices = computed(() => props.sessions.length > 0);

// Get primary session or first session
const defaultSession = computed(() => {
    const primary = props.sessions.find(s => s.is_primary);
    return primary?.id || props.sessions[0]?.id || null;
});

// Text Message Form
const textForm = useForm({
    message: '',
    wa_session_id: null as number | null,
});

// Media Form
const mediaForm = useForm({
    media_type: 'image',
    media: null as File | null,
    caption: '',
    wa_session_id: null as number | null,
});

// Link Form
const linkForm = useForm({
    link: '',
    caption: '',
    wa_session_id: null as number | null,
});

// Location Form
const locationForm = useForm({
    latitude: '',
    longitude: '',
    wa_session_id: null as number | null,
});

// Contact Form
const contactForm = useForm({
    contact_name: '',
    contact_phone: '',
    wa_session_id: null as number | null,
});

// Poll Form
const pollForm = useForm({
    question: '',
    options: ['', ''] as string[],
    max_answer: 1,
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
        alert('Please select a file');
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

const sendLink = () => {
    linkForm.wa_session_id = selectedSession.value || defaultSession.value;
    linkForm.post(`/contacts/${props.contact.id}/send-link`, {
        preserveScroll: true,
        onSuccess: () => {
            linkForm.reset();
            showSendForm.value = false;
        },
    });
};

const sendLocation = () => {
    locationForm.wa_session_id = selectedSession.value || defaultSession.value;
    locationForm.post(`/contacts/${props.contact.id}/send-location`, {
        preserveScroll: true,
        onSuccess: () => {
            locationForm.reset();
            showSendForm.value = false;
        },
    });
};

const sendContact = () => {
    contactForm.wa_session_id = selectedSession.value || defaultSession.value;
    contactForm.post(`/contacts/${props.contact.id}/send-contact`, {
        preserveScroll: true,
        onSuccess: () => {
            contactForm.reset();
            showSendForm.value = false;
        },
    });
};

const sendPoll = () => {
    pollForm.wa_session_id = selectedSession.value || defaultSession.value;
    pollForm.post(`/contacts/${props.contact.id}/send-poll`, {
        preserveScroll: true,
        onSuccess: () => {
            pollForm.reset();
            showSendForm.value = false;
        },
    });
};

const addPollOption = () => {
    if (pollForm.options.length < 12) {
        pollForm.options.push('');
    }
};

const removePollOption = (index: number) => {
    if (pollForm.options.length > 2) {
        pollForm.options.splice(index, 1);
    }
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        mediaForm.media = target.files[0];
    }
};

const deleteContact = () => {
    if (
        confirm(
            'Are you sure you want to delete this contact? This action cannot be undone.',
        )
    ) {
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

        <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div
                class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <Link href="/contacts">
                        <Button size="icon" variant="ghost">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight break-words md:text-3xl">
                            {{ contact.full_name }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Contact Details
                        </p>
                    </div>
                </div>
                <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                    <Button
                        v-if="hasConnectedDevices"
                        class="flex-1 sm:flex-initial"
                        @click="showSendForm = !showSendForm"
                    >
                        <Send class="mr-2 h-4 w-4" />
                        Send Message
                    </Button>
                    <Link v-else href="/w/connect">
                        <Button class="flex-1 sm:flex-initial" variant="outline">
                            <Smartphone class="mr-2 h-4 w-4" />
                            Connect WhatsApp First
                        </Button>
                    </Link>
                    <Button
                        class="flex-1 sm:flex-initial"
                        variant="destructive"
                        @click="deleteContact"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- No Device Warning -->
            <div
                v-if="!hasConnectedDevices"
                class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20"
            >
                <div class="flex items-center gap-3">
                    <Smartphone class="h-6 w-6 text-yellow-600" />
                    <div>
                        <h3 class="font-semibold text-yellow-900 dark:text-yellow-100">
                            No WhatsApp Device Connected
                        </h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            Please connect a WhatsApp device to send messages.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Send Message Form -->
            <div
                v-if="showSendForm && hasConnectedDevices"
                class="rounded-lg border bg-card p-4 md:p-6"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold md:text-lg">Send Message</h2>

                    <!-- Device Selector -->
                    <div v-if="sessions.length > 1" class="flex items-center gap-2">
                        <Label class="text-xs">From:</Label>
                        <Select v-model="selectedSession">
                            <SelectTrigger class="w-[200px]">
                                <SelectValue placeholder="Select device" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="session in sessions"
                                    :key="session.id"
                                    :value="session.id"
                                >
                                    {{ session.device_label }}
                                    <span v-if="session.is_primary" class="text-xs text-muted-foreground">
                                        (Primary)
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <Tabs v-model="messageType" default-value="text">
                    <TabsList class="grid w-full grid-cols-3 md:grid-cols-6">
                        <TabsTrigger value="text">
                            <MessageSquare class="mr-1 h-4 w-4" />
                            <span class="hidden sm:inline">Text</span>
                        </TabsTrigger>
                        <TabsTrigger value="media">
                            <ImageIcon class="mr-1 h-4 w-4" />
                            <span class="hidden sm:inline">Media</span>
                        </TabsTrigger>
                        <TabsTrigger value="link">
                            <LinkIcon class="mr-1 h-4 w-4" />
                            <span class="hidden sm:inline">Link</span>
                        </TabsTrigger>
                        <TabsTrigger value="location">
                            <MapPin class="mr-1 h-4 w-4" />
                            <span class="hidden sm:inline">Location</span>
                        </TabsTrigger>
                        <TabsTrigger value="contact">
                            <User class="mr-1 h-4 w-4" />
                            <span class="hidden sm:inline">Contact</span>
                        </TabsTrigger>
                        <TabsTrigger value="poll">
                            <BarChart3 class="mr-1 h-4 w-4" />
                            <span class="hidden sm:inline">Poll</span>
                        </TabsTrigger>
                    </TabsList>

                    <!-- Text Message -->
                    <TabsContent value="text">
                        <form class="space-y-4" @submit.prevent="sendTextMessage">
                            <div class="space-y-2">
                                <Label for="text-message">Message</Label>
                                <Textarea
                                    id="text-message"
                                    v-model="textForm.message"
                                    placeholder="Type your message here..."
                                    required
                                    rows="6"
                                />
                                <p class="text-xs text-muted-foreground">
                                    {{ textForm.message.length }} / 4096 characters
                                </p>
                            </div>
                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showSendForm = false"
                                >
                                    Cancel
                                </Button>
                                <Button :disabled="textForm.processing" type="submit">
                                    <Send class="mr-2 h-4 w-4" />
                                    {{ textForm.processing ? 'Sending...' : 'Send' }}
                                </Button>
                            </div>
                        </form>
                    </TabsContent>

                    <!-- Media (Image/Video/Audio/File) -->
                    <TabsContent value="media">
                        <form class="space-y-4" @submit.prevent="sendMedia">
                            <div class="space-y-2">
                                <Label for="media-type">Media Type</Label>
                                <Select v-model="mediaForm.media_type">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="image">
                                            <ImageIcon class="mr-2 inline h-4 w-4" />
                                            Image
                                        </SelectItem>
                                        <SelectItem value="video">
                                            <Video class="mr-2 inline h-4 w-4" />
                                            Video
                                        </SelectItem>
                                        <SelectItem value="audio">
                                            <Music class="mr-2 inline h-4 w-4" />
                                            Audio
                                        </SelectItem>
                                        <SelectItem value="file">
                                            <File class="mr-2 inline h-4 w-4" />
                                            Document
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-2">
                                <Label for="media-file">File</Label>
                                <Input
                                    id="media-file"
                                    type="file"
                                    required
                                    @change="handleFileChange"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Max file size: 100MB
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="media-caption">Caption (Optional)</Label>
                                <Textarea
                                    id="media-caption"
                                    v-model="mediaForm.caption"
                                    placeholder="Add a caption..."
                                    rows="3"
                                />
                            </div>
                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showSendForm = false"
                                >
                                    Cancel
                                </Button>
                                <Button :disabled="mediaForm.processing" type="submit">
                                    <Send class="mr-2 h-4 w-4" />
                                    {{ mediaForm.processing ? 'Sending...' : 'Send' }}
                                </Button>
                            </div>
                        </form>
                    </TabsContent>

                    <!-- Link -->
                    <TabsContent value="link">
                        <form class="space-y-4" @submit.prevent="sendLink">
                            <div class="space-y-2">
                                <Label for="link-url">URL</Label>
                                <Input
                                    id="link-url"
                                    v-model="linkForm.link"
                                    placeholder="https://example.com"
                                    required
                                    type="url"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="link-caption">Caption (Optional)</Label>
                                <Textarea
                                    id="link-caption"
                                    v-model="linkForm.caption"
                                    placeholder="Add a description..."
                                    rows="3"
                                />
                            </div>
                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showSendForm = false"
                                >
                                    Cancel
                                </Button>
                                <Button :disabled="linkForm.processing" type="submit">
                                    <Send class="mr-2 h-4 w-4" />
                                    {{ linkForm.processing ? 'Sending...' : 'Send' }}
                                </Button>
                            </div>
                        </form>
                    </TabsContent>

                    <!-- Location -->
                    <TabsContent value="location">
                        <form class="space-y-4" @submit.prevent="sendLocation">
                            <div class="space-y-2">
                                <Label for="latitude">Latitude</Label>
                                <Input
                                    id="latitude"
                                    v-model="locationForm.latitude"
                                    placeholder="30.0444"
                                    required
                                    step="any"
                                    type="number"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="longitude">Longitude</Label>
                                <Input
                                    id="longitude"
                                    v-model="locationForm.longitude"
                                    placeholder="31.2357"
                                    required
                                    step="any"
                                    type="number"
                                />
                            </div>
                            <p class="text-xs text-muted-foreground">
                                You can get coordinates from Google Maps by right-clicking a location.
                            </p>
                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showSendForm = false"
                                >
                                    Cancel
                                </Button>
                                <Button :disabled="locationForm.processing" type="submit">
                                    <Send class="mr-2 h-4 w-4" />
                                    {{ locationForm.processing ? 'Sending...' : 'Send' }}
                                </Button>
                            </div>
                        </form>
                    </TabsContent>

                    <!-- Contact Card -->
                    <TabsContent value="contact">
                        <form class="space-y-4" @submit.prevent="sendContact">
                            <div class="space-y-2">
                                <Label for="contact-name">Contact Name</Label>
                                <Input
                                    id="contact-name"
                                    v-model="contactForm.contact_name"
                                    placeholder="John Doe"
                                    required
                                    type="text"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="contact-phone">Contact Phone</Label>
                                <Input
                                    id="contact-phone"
                                    v-model="contactForm.contact_phone"
                                    placeholder="+1234567890"
                                    required
                                    type="tel"
                                />
                            </div>
                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showSendForm = false"
                                >
                                    Cancel
                                </Button>
                                <Button :disabled="contactForm.processing" type="submit">
                                    <Send class="mr-2 h-4 w-4" />
                                    {{ contactForm.processing ? 'Sending...' : 'Send' }}
                                </Button>
                            </div>
                        </form>
                    </TabsContent>

                    <!-- Poll -->
                    <TabsContent value="poll">
                        <form class="space-y-4" @submit.prevent="sendPoll">
                            <div class="space-y-2">
                                <Label for="poll-question">Question</Label>
                                <Input
                                    id="poll-question"
                                    v-model="pollForm.question"
                                    placeholder="What's your favorite color?"
                                    required
                                    type="text"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Options (2-12)</Label>
                                <div
                                    v-for="(option, index) in pollForm.options"
                                    :key="index"
                                    class="flex gap-2"
                                >
                                    <Input
                                        v-model="pollForm.options[index]"
                                        :placeholder="`Option ${index + 1}`"
                                        required
                                        type="text"
                                    />
                                    <Button
                                        v-if="pollForm.options.length > 2"
                                        size="icon"
                                        type="button"
                                        variant="outline"
                                        @click="removePollOption(index)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                                <Button
                                    v-if="pollForm.options.length < 12"
                                    size="sm"
                                    type="button"
                                    variant="outline"
                                    @click="addPollOption"
                                >
                                    Add Option
                                </Button>
                            </div>
                            <div class="space-y-2">
                                <Label for="max-answer">Max Answers</Label>
                                <Input
                                    id="max-answer"
                                    v-model.number="pollForm.max_answer"
                                    min="1"
                                    :max="pollForm.options.length"
                                    type="number"
                                />
                            </div>
                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showSendForm = false"
                                >
                                    Cancel
                                </Button>
                                <Button :disabled="pollForm.processing" type="submit">
                                    <Send class="mr-2 h-4 w-4" />
                                    {{ pollForm.processing ? 'Sending...' : 'Send' }}
                                </Button>
                            </div>
                        </form>
                    </TabsContent>
                </Tabs>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Contact Information Card -->
                <div class="space-y-6 lg:col-span-1">
                    <div class="rounded-lg border bg-card p-4 md:p-6">
                        <h2 class="mb-4 text-base font-semibold md:text-lg">
                            Contact Information
                        </h2>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <Phone
                                    class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground"
                                />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">Phone</p>
                                    <p class="text-sm break-words text-muted-foreground">
                                        {{ contact.phone_e164 }}
                                    </p>
                                    <p
                                        v-if="contact.phone_raw !== contact.phone_e164"
                                        class="text-xs break-words text-muted-foreground"
                                    >
                                        Original: {{ contact.phone_raw }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="contact.email" class="flex items-start gap-3">
                                <Mail
                                    class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground"
                                />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">Email</p>
                                    <p class="text-sm break-words text-muted-foreground">
                                        {{ contact.email }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <FileText
                                    class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground"
                                />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">Import Source</p>
                                    <p class="text-sm break-words text-muted-foreground">
                                        {{ contact.import_source }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <Calendar
                                    class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground"
                                />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">Added On</p>
                                    <p class="text-sm break-words text-muted-foreground">
                                        {{ contact.created_at }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Extra Fields -->
                        <div
                            v-if="
                                contact.extra_json &&
                                Object.keys(contact.extra_json).length > 0
                            "
                            class="mt-6 border-t pt-4"
                        >
                            <h3 class="mb-3 text-sm font-semibold">Additional Fields</h3>
                            <div class="space-y-2">
                                <div
                                    v-for="(value, key) in contact.extra_json"
                                    :key="key"
                                    class="flex flex-col gap-1 text-sm sm:flex-row sm:justify-between"
                                >
                                    <span class="font-medium break-words capitalize">{{
                                            key
                                        }}:</span>
                                    <span class="break-words text-muted-foreground">{{
                                            value
                                        }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message History -->
                <div class="lg:col-span-2">
                    <div class="rounded-lg border bg-card p-4 md:p-6">
                        <h2 class="mb-4 text-base font-semibold md:text-lg">
                            Message History
                        </h2>

                        <div v-if="messages.length > 0" class="overflow-x-auto">
                            <table class="w-full min-w-[600px]">
                                <thead class="border-b bg-muted/50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-sm font-medium"
                                    >
                                        Campaign
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-sm font-medium"
                                    >
                                        Message
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-sm font-medium"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-sm font-medium"
                                    >
                                        Date
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="divide-y">
                                <tr
                                    v-for="message in messages"
                                    :key="message.id"
                                    class="hover:bg-muted/30"
                                >
                                    <td class="px-4 py-3 text-sm">
                                        {{ message.campaign_name }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-sm text-muted-foreground"
                                    >
                                        <div class="max-w-md truncate">
                                            {{ message.body }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge
                                            :class="getStatusColor(message.status)"
                                        >
                                            {{ message.status }}
                                        </Badge>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-sm text-muted-foreground"
                                    >
                                        {{ message.sent_at || message.created_at }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <p
                                v-if="messages.length === 50"
                                class="mt-4 text-center text-sm text-muted-foreground"
                            >
                                Showing last 50 messages
                            </p>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-else
                            class="flex flex-col items-center justify-center py-12"
                        >
                            <MessageSquare
                                class="mb-4 h-12 w-12 text-muted-foreground"
                            />
                            <h3 class="mb-2 text-base font-semibold md:text-lg">
                                No messages sent yet
                            </h3>
                            <p class="mb-4 text-sm text-muted-foreground">
                                Send your first message to this contact
                            </p>
                            <Button
                                v-if="hasConnectedDevices"
                                @click="showSendForm = true"
                            >
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
