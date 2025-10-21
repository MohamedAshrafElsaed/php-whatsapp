<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    ChevronDown,
    FileText,
    Info,
    Lightbulb,
    MessageSquare,
    Save,
    Send,
    Settings,
    Smartphone,
    Tag,
    Users,
    Image as ImageIcon,
    Video,
    Music,
    File as FileIcon,
    Link2,
    MapPin,
    User as UserIcon,
    ListChecks,
    Plus,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface WaSession {
    id: number;
    device_id: string;
    device_label: string;
    phone: string | null;
    is_primary: boolean;
}

interface Import {
    id: number;
    filename: string;
    valid_rows: number;
    created_at: string;
}

interface Segment {
    id: number;
    name: string;
    description: string | null;
    valid_contacts: number;
}

interface Contact {
    id: number;
    full_name: string;
    phone_e164: string;
    email: string | null;
}

interface Recipient {
    first_name: string;
    last_name: string;
    email: string;
    phone_e164: string;
    extra_json: Record<string, any>;
}

const props = defineProps<{
    connectedDevices: WaSession[];
    imports: Import[];
    segments: Segment[];
    contacts: Contact[];
    previewRecipient: Recipient | null;
    availableVariables: string[];
}>();

const { t, isRTL } = useTranslation();

const form = useForm({
    name: '',
    wa_session_id: props.connectedDevices[0]?.id || null,
    selection_type: 'import' as 'import' | 'segment' | 'contacts',
    import_id: null as number | null,
    segment_id: null as number | null,
    recipient_ids: [] as number[],

    // Message type
    message_type: 'text' as 'text' | 'image' | 'video' | 'audio' | 'file' | 'link' | 'location' | 'contact' | 'poll',
    message_template: '',

    // Media
    media: null as File | null,
    caption: '',

    // Link
    link_url: '',

    // Location
    latitude: null as number | null,
    longitude: null as number | null,

    // Contact
    contact_name: '',
    contact_phone: '',

    // Poll
    poll_question: '',
    poll_options: ['', ''] as string[],
    poll_max_answer: 1,

    messages_per_minute: 15,
    delay_seconds: 4,
    start_immediately: false,
});

const contactsExpanded = ref(false);
const searchQuery = ref('');

/**
 * Filter contacts based on search query
 */
const filteredContacts = computed((): Contact[] => {
    if (!searchQuery.value) return props.contacts;

    const query = searchQuery.value.toLowerCase();
    return props.contacts.filter(
        (contact) =>
            contact.full_name.toLowerCase().includes(query) ||
            contact.phone_e164.includes(query) ||
            (contact.email && contact.email.toLowerCase().includes(query)),
    );
});

/**
 * Get count of selected contacts
 */
const selectedContactsCount = computed((): number => form.recipient_ids.length);

/**
 * Check if all filtered contacts are selected
 */
const allContactsSelected = computed((): boolean => {
    return (
        filteredContacts.value.length > 0 &&
        filteredContacts.value.every((c) => form.recipient_ids.includes(c.id))
    );
});

/**
 * Check if some but not all contacts are selected
 */
const someContactsSelected = computed((): boolean => {
    return form.recipient_ids.length > 0 && !allContactsSelected.value;
});

/**
 * Compute preview message with variable replacements
 */
const previewMessage = computed((): string => {
    if (form.message_type !== 'text' || !form.message_template || !props.previewRecipient) {
        return getMessageTypePreview();
    }

    let preview = form.message_template;
    const recipient = props.previewRecipient;

    preview = preview.replace(
        /\{\{first_name\}\}/g,
        recipient.first_name || '[first_name]',
    );
    preview = preview.replace(
        /\{\{last_name\}\}/g,
        recipient.last_name || '[last_name]',
    );
    preview = preview.replace(/\{\{email\}\}/g, recipient.email || '[email]');
    preview = preview.replace(
        /\{\{phone\}\}/g,
        recipient.phone_e164 || '[phone]',
    );

    if (recipient.extra_json) {
        Object.entries(recipient.extra_json).forEach(([key, value]) => {
            const regex = new RegExp(`\\{\\{${key}\\}\\}`, 'g');
            preview = preview.replace(regex, String(value));
        });
    }

    return preview;
});

/**
 * Get message type preview
 */
const getMessageTypePreview = (): string => {
    switch (form.message_type) {
        case 'text':
            return t('campaigns.preview_placeholder');
        case 'image':
            return `📷 ${t('contacts.image')}${form.caption ? ': ' + form.caption : ''}`;
        case 'video':
            return `🎥 ${t('contacts.video')}${form.caption ? ': ' + form.caption : ''}`;
        case 'audio':
            return `🎵 ${t('contacts.audio')}`;
        case 'file':
            return `📎 ${t('contacts.document')}${form.caption ? ': ' + form.caption : ''}`;
        case 'link':
            return `🔗 ${form.link_url || t('contacts.link')}${form.caption ? '\n' + form.caption : ''}`;
        case 'location':
            return `📍 ${t('contacts.location')}: ${form.latitude || '0'}, ${form.longitude || '0'}`;
        case 'contact':
            return `👤 ${t('contacts.contact')}: ${form.contact_name || 'N/A'}`;
        case 'poll':
            return `📊 ${t('contacts.poll')}: ${form.poll_question || 'N/A'}`;
        default:
            return t('campaigns.preview_placeholder');
    }
};

/**
 * Handle media file change
 */
const handleMediaChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.media = target.files[0];
    }
};

/**
 * Add poll option
 */
const addPollOption = (): void => {
    if (form.poll_options.length < 12) {
        form.poll_options.push('');
    }
};

/**
 * Remove poll option
 */
const removePollOption = (index: number): void => {
    if (form.poll_options.length > 2) {
        form.poll_options.splice(index, 1);
    }
};

/**
 * Format variable for display
 */
const formatVariable = (variable: string): string => {
    return `{{${variable}}}`;
};

/**
 * Insert variable at cursor position in textarea
 */
const insertVariable = (variable: string): void => {
    const textarea = document.querySelector(
        'textarea[name="message_template"]',
    ) as HTMLTextAreaElement;

    if (textarea) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = form.message_template;
        const variableText = `{{${variable}}}`;

        form.message_template =
            text.substring(0, start) + variableText + text.substring(end);

        setTimeout(() => {
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd =
                start + variableText.length;
        }, 0);
    }
};

/**
 * Toggle individual contact selection
 */
const toggleContact = (contactId: number): void => {
    const index = form.recipient_ids.indexOf(contactId);
    if (index > -1) {
        form.recipient_ids.splice(index, 1);
    } else {
        form.recipient_ids.push(contactId);
    }
};

/**
 * Toggle all filtered contacts
 */
const toggleAllContacts = (): void => {
    if (allContactsSelected.value) {
        filteredContacts.value.forEach((contact) => {
            const index = form.recipient_ids.indexOf(contact.id);
            if (index > -1) {
                form.recipient_ids.splice(index, 1);
            }
        });
    } else {
        filteredContacts.value.forEach((contact) => {
            if (!form.recipient_ids.includes(contact.id)) {
                form.recipient_ids.push(contact.id);
            }
        });
    }
};

/**
 * Save campaign as draft
 */
const saveDraft = (): void => {
    form.start_immediately = false;
    form.post('/campaigns', {
        preserveScroll: true,
        onError: () => {
            console.error('Campaign creation failed');
        }
    });
};

/**
 * Create and start campaign immediately
 */
const createAndStart = (): void => {
    form.start_immediately = true;
    form.post('/campaigns', {
        preserveScroll: true,
        onError: () => {
            console.error('Campaign creation failed');
        }
    });
};

/**
 * Get selected import details
 */
const selectedImport = computed(() => {
    return props.imports.find((imp) => imp.id === form.import_id);
});

/**
 * Get selected segment details
 */
const selectedSegment = computed(() => {
    return props.segments.find((seg) => seg.id === form.segment_id);
});

/**
 * Get selected device details
 */
const selectedDevice = computed(() => {
    return props.connectedDevices.find((dev) => dev.id === form.wa_session_id);
});

/**
 * Check if no devices connected
 */
const hasNoDevices = computed(
    (): boolean => props.connectedDevices.length === 0,
);

/**
 * Check if no imports available
 */
const hasNoImports = computed((): boolean => props.imports.length === 0);

/**
 * Check if no segments available
 */
const hasNoSegments = computed((): boolean => props.segments.length === 0);

/**
 * Check if no contacts available
 */
const hasNoContacts = computed((): boolean => props.contacts.length === 0);

/**
 * Calculate total recipient count based on selection type
 */
const recipientCount = computed((): number => {
    if (form.selection_type === 'import') {
        return selectedImport.value?.valid_rows || 0;
    }
    if (form.selection_type === 'segment') {
        return selectedSegment.value?.valid_contacts || 0;
    }
    return form.recipient_ids.length;
});
</script>

<template>
    <AppLayout>
        <Head :title="t('campaigns.create')" />

        <div class="min-h-screen bg-background">
            <div class="mx-auto max-w-7xl space-y-4 p-4 sm:space-y-6 sm:p-6 lg:p-8">
                <!-- Header -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <Link href="/campaigns">
                        <Button class="h-9 w-9 shrink-0 sm:h-10 sm:w-10" size="icon" variant="ghost">
                            <ArrowLeft class="h-4 w-4 sm:h-5 sm:w-5" />
                            <span class="sr-only">{{ t('common.back') }}</span>
                        </Button>
                    </Link>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ t('campaigns.create') }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground sm:text-base">
                            {{ t('campaigns.description') }}
                        </p>
                    </div>
                </div>

                <!-- No Devices Warning -->
                <div
                    v-if="hasNoDevices"
                    class="rounded-lg border-2 border-orange-200 bg-gradient-to-r from-orange-50 to-orange-100 p-4 shadow-sm sm:p-6 dark:border-orange-800 dark:from-orange-950/50 dark:to-orange-900/50"
                >
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="shrink-0 rounded-lg bg-orange-200 p-2 dark:bg-orange-800">
                            <Smartphone class="h-5 w-5 text-orange-700 sm:h-6 sm:w-6 dark:text-orange-300" />
                        </div>
                        <div class="min-w-0 flex-1 space-y-2">
                            <h3 class="font-semibold text-orange-900 dark:text-orange-100">
                                {{ t('campaigns.no_devices') }}
                            </h3>
                            <p class="text-sm text-orange-700 dark:text-orange-300">
                                {{ t('campaigns.no_devices_desc') }}
                            </p>
                            <Link href="/w/connect">
                                <Button class="mt-2" size="sm" variant="outline">
                                    {{ t('campaigns.connect_whatsapp') }}
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- No Recipients Warning -->
                <div
                    v-if="!hasNoDevices && hasNoImports && hasNoSegments && hasNoContacts"
                    class="rounded-lg border-2 border-orange-200 bg-gradient-to-r from-orange-50 to-orange-100 p-4 shadow-sm sm:p-6 dark:border-orange-800 dark:from-orange-950/50 dark:to-orange-900/50"
                >
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="shrink-0 rounded-lg bg-orange-200 p-2 dark:bg-orange-800">
                            <Users class="h-5 w-5 text-orange-700 sm:h-6 sm:w-6 dark:text-orange-300" />
                        </div>
                        <div class="min-w-0 flex-1 space-y-2">
                            <h3 class="font-semibold text-orange-900 dark:text-orange-100">
                                {{ t('campaigns.no_recipients') }}
                            </h3>
                            <p class="text-sm text-orange-700 dark:text-orange-300">
                                {{ t('campaigns.no_recipients_desc') }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <Link href="/contacts/imports">
                                    <Button class="mt-2" size="sm" variant="outline">
                                        {{ t('campaigns.import_contacts') }}
                                    </Button>
                                </Link>
                                <Link href="/contacts/segments/create">
                                    <Button class="mt-2" size="sm" variant="outline">
                                        {{ t('campaigns.create_segment') }}
                                    </Button>
                                </Link>
                                <Link href="/contacts/create">
                                    <Button class="mt-2" size="sm" variant="outline">
                                        {{ t('campaigns.add_contact') }}
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div
                    v-if="!hasNoDevices && (!hasNoImports || !hasNoSegments || !hasNoContacts)"
                    class="grid gap-4 sm:gap-6 lg:grid-cols-3"
                >
                    <!-- Form Section -->
                    <div class="space-y-4 sm:space-y-6 lg:col-span-2">
                        <div class="rounded-lg border bg-card shadow-sm">
                            <div class="space-y-6 p-4 sm:p-6">
                                <!-- Campaign Details Section -->
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3 border-b pb-4">
                                        <div class="shrink-0 rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                                            <MessageSquare class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <h2 class="text-lg font-semibold">
                                            {{ t('campaigns.campaign_details') }}
                                        </h2>
                                    </div>

                                    <!-- Campaign Name -->
                                    <div class="space-y-2">
                                        <Label for="name">
                                            {{ t('campaigns.campaign_name') }}
                                            <span class="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            :placeholder="t('campaigns.campaign_name_placeholder')"
                                            class="h-10 sm:h-11"
                                            required
                                            type="text"
                                        />
                                        <p v-if="form.errors.name" class="text-sm text-destructive">
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <!-- WhatsApp Device Selection -->
                                    <div class="space-y-2">
                                        <Label for="wa_session_id">
                                            {{ t('campaigns.whatsapp_device') }}
                                            <span class="text-destructive">*</span>
                                        </Label>
                                        <Select v-model="form.wa_session_id">
                                            <SelectTrigger class="h-10 sm:h-11">
                                                <SelectValue :placeholder="t('campaigns.select_device')" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="device in connectedDevices"
                                                    :key="device.id"
                                                    :value="device.id"
                                                >
                                                    {{ device.device_label }}
                                                    {{ device.is_primary ? `(${t('campaigns.primary')})` : '' }}
                                                    - {{ device.phone }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.wa_session_id" class="text-sm text-destructive">
                                            {{ form.errors.wa_session_id }}
                                        </p>
                                    </div>

                                    <!-- Recipient Selection Type -->
                                    <div class="space-y-4 rounded-lg border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 p-4 dark:border-blue-800 dark:from-blue-950/50 dark:to-blue-900/50">
                                        <div class="flex items-center gap-2">
                                            <Users class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                            <h3 class="font-semibold text-blue-900 dark:text-blue-100">
                                                {{ t('campaigns.select_recipients') }}
                                            </h3>
                                        </div>

                                        <!-- Selection Type Tabs -->
                                        <div class="grid grid-cols-3 gap-2">
                                            <button
                                                :class="[
                                                    'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3 sm:text-sm',
                                                    form.selection_type === 'import'
                                                        ? 'border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500'
                                                        : 'border-blue-300 bg-white text-blue-900 hover:border-blue-400 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100',
                                                ]"
                                                :disabled="hasNoImports"
                                                type="button"
                                                @click="form.selection_type = 'import'"
                                            >
                                                <FileText class="mx-auto mb-1 h-4 w-4 sm:h-5 sm:w-5" />
                                                {{ t('campaigns.from_import') }}
                                            </button>
                                            <button
                                                :class="[
                                                    'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3 sm:text-sm',
                                                    form.selection_type === 'segment'
                                                        ? 'border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500'
                                                        : hasNoSegments
                                                          ? 'cursor-not-allowed border-gray-300 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-600'
                                                          : 'border-blue-300 bg-white text-blue-900 hover:border-blue-400 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100',
                                                ]"
                                                :disabled="hasNoSegments"
                                                type="button"
                                                @click="form.selection_type = 'segment'"
                                            >
                                                <Tag class="mx-auto mb-1 h-4 w-4 sm:h-5 sm:w-5" />
                                                {{ t('campaigns.from_segment') }}
                                            </button>
                                            <button
                                                :class="[
                                                    'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3 sm:text-sm',
                                                    form.selection_type === 'contacts'
                                                        ? 'border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500'
                                                        : 'border-blue-300 bg-white text-blue-900 hover:border-blue-400 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100',
                                                ]"
                                                :disabled="hasNoContacts"
                                                type="button"
                                                @click="form.selection_type = 'contacts'"
                                            >
                                                <Users class="mx-auto mb-1 h-4 w-4 sm:h-5 sm:w-5" />
                                                {{ t('campaigns.individual_contacts') }}
                                            </button>
                                        </div>

                                        <!-- Import Selection -->
                                        <div v-if="form.selection_type === 'import'" class="space-y-2">
                                            <Label for="import_id">
                                                {{ t('campaigns.select_import') }}
                                                <span class="text-destructive">*</span>
                                            </Label>
                                            <Select v-model="form.import_id">
                                                <SelectTrigger class="h-10 bg-white sm:h-11 dark:bg-slate-950">
                                                    <SelectValue :placeholder="t('campaigns.choose_import')" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="importItem in imports"
                                                        :key="importItem.id"
                                                        :value="importItem.id"
                                                    >
                                                        {{ importItem.filename }} ({{ importItem.valid_rows }} {{ t('campaigns.contacts_count') }})
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p v-if="form.errors.import_id" class="text-sm text-destructive">
                                                {{ form.errors.import_id }}
                                            </p>
                                            <p v-if="selectedImport" class="flex items-center gap-1.5 text-xs text-blue-700 dark:text-blue-300">
                                                <Info class="h-3.5 w-3.5 shrink-0" />
                                                {{ selectedImport.valid_rows }} {{ t('campaigns.valid_recipients') }}
                                            </p>
                                        </div>

                                        <!-- Segment Selection -->
                                        <div v-if="form.selection_type === 'segment'" class="space-y-2">
                                            <Label for="segment_id">
                                                {{ t('campaigns.select_segment') }}
                                                <span class="text-destructive">*</span>
                                            </Label>
                                            <Select v-model="form.segment_id">
                                                <SelectTrigger class="h-10 bg-white sm:h-11 dark:bg-slate-950">
                                                    <SelectValue :placeholder="t('campaigns.choose_segment')" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="segment in segments"
                                                        :key="segment.id"
                                                        :value="segment.id"
                                                    >
                                                        {{ segment.name }} ({{ segment.valid_contacts }} {{ t('campaigns.contacts_count') }})
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p v-if="form.errors.segment_id" class="text-sm text-destructive">
                                                {{ form.errors.segment_id }}
                                            </p>
                                            <p v-if="selectedSegment" class="flex items-center gap-1.5 text-xs text-blue-700 dark:text-blue-300">
                                                <Info class="h-3.5 w-3.5 shrink-0" />
                                                {{ selectedSegment.valid_contacts }} {{ t('campaigns.valid_recipients') }}
                                            </p>
                                        </div>

                                        <!-- Individual Contacts Selection -->
                                        <div v-if="form.selection_type === 'contacts'" class="space-y-3">
                                            <div class="flex items-center justify-between">
                                                <Label>
                                                    {{ t('campaigns.select_contacts') }}
                                                    <span class="text-destructive">*</span>
                                                </Label>
                                                <span class="text-xs text-blue-700 dark:text-blue-300">
                                                    {{ selectedContactsCount }} {{ t('campaigns.selected') }}
                                                </span>
                                            </div>

                                            <!-- Search Contacts -->
                                            <Input
                                                v-model="searchQuery"
                                                :placeholder="t('campaigns.search_contacts')"
                                                class="h-9 bg-white dark:bg-slate-950"
                                                type="search"
                                            />

                                            <!-- Contacts List -->
                                            <Collapsible
                                                v-model:open="contactsExpanded"
                                                class="rounded-lg border bg-white dark:bg-slate-950"
                                            >
                                                <CollapsibleTrigger class="flex w-full items-center justify-between p-3 hover:bg-blue-50 dark:hover:bg-blue-950/30">
                                                    <div class="flex items-center gap-2">
                                                        <Checkbox
                                                            :checked="allContactsSelected"
                                                            :indeterminate="someContactsSelected"
                                                            @click.stop="toggleAllContacts"
                                                        />
                                                        <span class="text-sm font-medium">
                                                            {{ t('campaigns.all_contacts') }} ({{ filteredContacts.length }})
                                                        </span>
                                                    </div>
                                                    <ChevronDown
                                                        :class="[
                                                            'h-4 w-4 transition-transform',
                                                            contactsExpanded && 'rotate-180',
                                                        ]"
                                                    />
                                                </CollapsibleTrigger>

                                                <CollapsibleContent>
                                                    <div class="max-h-60 overflow-y-auto border-t">
                                                        <div
                                                            v-for="contact in filteredContacts"
                                                            :key="contact.id"
                                                            class="flex items-center gap-3 border-b p-3 last:border-b-0 hover:bg-blue-50 dark:hover:bg-blue-950/30"
                                                        >
                                                            <Checkbox
                                                                :checked="form.recipient_ids.includes(contact.id)"
                                                                @update:checked="toggleContact(contact.id)"
                                                            />
                                                            <div class="min-w-0 flex-1">
                                                                <p class="truncate text-sm font-medium">
                                                                    {{ contact.full_name }}
                                                                </p>
                                                                <p class="truncate text-xs text-muted-foreground">
                                                                    {{ contact.phone_e164 }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div
                                                            v-if="filteredContacts.length === 0"
                                                            class="p-4 text-center text-sm text-muted-foreground"
                                                        >
                                                            {{ t('campaigns.no_contacts_found') }}
                                                        </div>
                                                    </div>
                                                </CollapsibleContent>
                                            </Collapsible>

                                            <p v-if="form.errors.recipient_ids" class="text-sm text-destructive">
                                                {{ form.errors.recipient_ids }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Message Content Section -->
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-3 border-b pb-4">
                                            <div class="shrink-0 rounded-lg bg-green-100 p-2 dark:bg-green-950">
                                                <MessageSquare class="h-5 w-5 text-green-600 dark:text-green-400" />
                                            </div>
                                            <h2 class="text-lg font-semibold">{{ t('campaigns.message_content') }}</h2>
                                        </div>

                                        <!-- Message Type Selection -->
                                        <div class="space-y-2">
                                            <Label>{{ t('campaigns.message_type') }} <span class="text-destructive">*</span></Label>
                                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                                                <button
                                                    :class="[
                                                        'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3',
                                                        form.message_type === 'text'
                                                            ? 'border-green-600 bg-green-600 text-white'
                                                            : 'border-gray-300 bg-white text-gray-900 hover:border-green-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100'
                                                    ]"
                                                    type="button"
                                                    @click="form.message_type = 'text'"
                                                >
                                                    <MessageSquare class="mx-auto mb-1 h-4 w-4" />
                                                    {{ t('contacts.text') }}
                                                </button>
                                                <button
                                                    :class="[
                                                        'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3',
                                                        form.message_type === 'image'
                                                            ? 'border-green-600 bg-green-600 text-white'
                                                            : 'border-gray-300 bg-white text-gray-900 hover:border-green-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100'
                                                    ]"
                                                    type="button"
                                                    @click="form.message_type = 'image'"
                                                >
                                                    <ImageIcon class="mx-auto mb-1 h-4 w-4" />
                                                    {{ t('contacts.image') }}
                                                </button>
                                                <button
                                                    :class="[
                                                        'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3',
                                                        form.message_type === 'video'
                                                            ? 'border-green-600 bg-green-600 text-white'
                                                            : 'border-gray-300 bg-white text-gray-900 hover:border-green-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100'
                                                    ]"
                                                    type="button"
                                                    @click="form.message_type = 'video'"
                                                >
                                                    <Video class="mx-auto mb-1 h-4 w-4" />
                                                    {{ t('contacts.video') }}
                                                </button>
                                                <button
                                                    :class="[
                                                        'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3',
                                                        form.message_type === 'audio'
                                                            ? 'border-green-600 bg-green-600 text-white'
                                                            : 'border-gray-300 bg-white text-gray-900 hover:border-green-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100'
                                                    ]"
                                                    type="button"
                                                    @click="form.message_type = 'audio'"
                                                >
                                                    <Music class="mx-auto mb-1 h-4 w-4" />
                                                    {{ t('contacts.audio') }}
                                                </button>
                                                <button
                                                    :class="[
                                                        'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3',
                                                        form.message_type === 'file'
                                                            ? 'border-green-600 bg-green-600 text-white'
                                                            : 'border-gray-300 bg-white text-gray-900 hover:border-green-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100'
                                                    ]"
                                                    type="button"
                                                    @click="form.message_type = 'file'"
                                                >
                                                    <FileIcon class="mx-auto mb-1 h-4 w-4" />
                                                    {{ t('contacts.document') }}
                                                </button>
                                                <button
                                                    :class="[
                                                        'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3',
                                                        form.message_type === 'link'
                                                            ? 'border-green-600 bg-green-600 text-white'
                                                            : 'border-gray-300 bg-white text-gray-900 hover:border-green-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100'
                                                    ]"
                                                    type="button"
                                                    @click="form.message_type = 'link'"
                                                >
                                                    <Link2 class="mx-auto mb-1 h-4 w-4" />
                                                    {{ t('contacts.link') }}
                                                </button>
                                                <button
                                                    :class="[
                                                        'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3',
                                                        form.message_type === 'location'
                                                            ? 'border-green-600 bg-green-600 text-white'
                                                            : 'border-gray-300 bg-white text-gray-900 hover:border-green-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100'
                                                    ]"
                                                    type="button"
                                                    @click="form.message_type = 'location'"
                                                >
                                                    <MapPin class="mx-auto mb-1 h-4 w-4" />
                                                    {{ t('contacts.location') }}
                                                </button>
                                                <button
                                                    :class="[
                                                        'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3',
                                                        form.message_type === 'contact'
                                                            ? 'border-green-600 bg-green-600 text-white'
                                                            : 'border-gray-300 bg-white text-gray-900 hover:border-green-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100'
                                                    ]"
                                                    type="button"
                                                    @click="form.message_type = 'contact'"
                                                >
                                                    <UserIcon class="mx-auto mb-1 h-4 w-4" />
                                                    {{ t('contacts.contact') }}
                                                </button>
                                                <button
                                                    :class="[
                                                        'rounded-lg border-2 px-3 py-2 text-xs font-medium transition-all sm:px-4 sm:py-3',
                                                        form.message_type === 'poll'
                                                            ? 'border-green-600 bg-green-600 text-white'
                                                            : 'border-gray-300 bg-white text-gray-900 hover:border-green-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100'
                                                    ]"
                                                    type="button"
                                                    @click="form.message_type = 'poll'"
                                                >
                                                    <ListChecks class="mx-auto mb-1 h-4 w-4" />
                                                    {{ t('contacts.poll') }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Text Message -->
                                        <div v-if="form.message_type === 'text'" class="space-y-2">
                                            <Label for="message_template">
                                                {{ t('campaigns.message_template') }}
                                                <span class="text-destructive">*</span>
                                            </Label>
                                            <Textarea
                                                id="message_template"
                                                v-model="form.message_template"
                                                name="message_template"
                                                :placeholder="t('campaigns.message_placeholder')"
                                                rows="6"
                                                class="resize-none"
                                                required
                                            />
                                            <p v-if="form.errors.message_template" class="text-sm text-destructive">
                                                {{ form.errors.message_template }}
                                            </p>

                                            <!-- Variable Buttons -->
                                            <div
                                                v-if="availableVariables.length > 0"
                                                class="rounded-lg border-2 border-yellow-200 bg-gradient-to-br from-yellow-50 to-yellow-100 p-4 dark:border-yellow-800 dark:from-yellow-950/50 dark:to-yellow-900/50"
                                            >
                                                <p class="mb-3 flex items-center gap-2 text-sm font-medium text-yellow-900 dark:text-yellow-100">
                                                    <Lightbulb class="h-4 w-4" />
                                                    {{ t('campaigns.available_variables') }}
                                                </p>
                                                <div class="flex flex-wrap gap-2">
                                                    <Button
                                                        v-for="variable in availableVariables"
                                                        :key="variable"
                                                        size="sm"
                                                        type="button"
                                                        variant="outline"
                                                        class="h-8 text-xs"
                                                        @click="insertVariable(variable)"
                                                    >
                                                        {{ formatVariable(variable) }}
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Media Messages (Image/Video/Audio/File) -->
                                        <div v-if="['image', 'video', 'audio', 'file'].includes(form.message_type)" class="space-y-4">
                                            <div class="space-y-2">
                                                <Label for="media">
                                                    {{ t('contacts.file') }} <span class="text-destructive">*</span>
                                                </Label>
                                                <Input
                                                    id="media"
                                                    type="file"
                                                    required
                                                    @change="handleMediaChange"
                                                    class="cursor-pointer"
                                                />
                                                <p class="text-xs text-muted-foreground">
                                                    {{ t('contacts.max_file_size') }}
                                                </p>
                                                <p v-if="form.errors.media" class="text-sm text-destructive">
                                                    {{ form.errors.media }}
                                                </p>
                                            </div>

                                            <div v-if="form.message_type !== 'audio'" class="space-y-2">
                                                <Label for="caption">
                                                    {{ t('contacts.caption') }}
                                                    <span class="text-xs text-muted-foreground">({{ t('common.optional') }})</span>
                                                </Label>
                                                <Textarea
                                                    id="caption"
                                                    v-model="form.caption"
                                                    :placeholder="t('contacts.add_caption')"
                                                    rows="3"
                                                    class="resize-none"
                                                />
                                            </div>
                                        </div>

                                        <!-- Link Message -->
                                        <div v-if="form.message_type === 'link'" class="space-y-4">
                                            <div class="space-y-2">
                                                <Label for="link_url">
                                                    {{ t('contacts.link') }} <span class="text-destructive">*</span>
                                                </Label>
                                                <Input
                                                    id="link_url"
                                                    v-model="form.link_url"
                                                    type="url"
                                                    placeholder="https://example.com"
                                                    required
                                                />
                                                <p v-if="form.errors.link_url" class="text-sm text-destructive">
                                                    {{ form.errors.link_url }}
                                                </p>
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="link_caption">
                                                    {{ t('contacts.caption') }}
                                                    <span class="text-xs text-muted-foreground">({{ t('common.optional') }})</span>
                                                </Label>
                                                <Textarea
                                                    id="link_caption"
                                                    v-model="form.caption"
                                                    :placeholder="t('contacts.add_caption')"
                                                    rows="3"
                                                />
                                            </div>
                                        </div>

                                        <!-- Location Message -->
                                        <div v-if="form.message_type === 'location'" class="space-y-4">
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <Label for="latitude">
                                                        {{ t('contacts.latitude') }} <span class="text-destructive">*</span>
                                                    </Label>
                                                    <Input
                                                        id="latitude"
                                                        v-model.number="form.latitude"
                                                        type="number"
                                                        step="any"
                                                        placeholder="30.0444"
                                                        required
                                                    />
                                                    <p v-if="form.errors.latitude" class="text-sm text-destructive">
                                                        {{ form.errors.latitude }}
                                                    </p>
                                                </div>
                                                <div class="space-y-2">
                                                    <Label for="longitude">
                                                        {{ t('contacts.longitude') }} <span class="text-destructive">*</span>
                                                    </Label>
                                                    <Input
                                                        id="longitude"
                                                        v-model.number="form.longitude"
                                                        type="number"
                                                        step="any"
                                                        placeholder="31.2357"
                                                        required
                                                    />
                                                    <p v-if="form.errors.longitude" class="text-sm text-destructive">
                                                        {{ form.errors.longitude }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Contact Message -->
                                        <div v-if="form.message_type === 'contact'" class="space-y-4">
                                            <div class="space-y-2">
                                                <Label for="contact_name">
                                                    {{ t('contacts.contact_name') }} <span class="text-destructive">*</span>
                                                </Label>
                                                <Input
                                                    id="contact_name"
                                                    v-model="form.contact_name"
                                                    placeholder="John Doe"
                                                    required
                                                />
                                                <p v-if="form.errors.contact_name" class="text-sm text-destructive">
                                                    {{ form.errors.contact_name }}
                                                </p>
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="contact_phone">
                                                    {{ t('contacts.contact_phone') }} <span class="text-destructive">*</span>
                                                </Label>
                                                <Input
                                                    id="contact_phone"
                                                    v-model="form.contact_phone"
                                                    type="tel"
                                                    placeholder="+1234567890"
                                                    required
                                                />
                                                <p v-if="form.errors.contact_phone" class="text-sm text-destructive">
                                                    {{ form.errors.contact_phone }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Poll Message -->
                                        <div v-if="form.message_type === 'poll'" class="space-y-4">
                                            <div class="space-y-2">
                                                <Label for="poll_question">
                                                    {{ t('contacts.poll_question') }} <span class="text-destructive">*</span>
                                                </Label>
                                                <Input
                                                    id="poll_question"
                                                    v-model="form.poll_question"
                                                    placeholder="What is your favorite color?"
                                                    required
                                                />
                                                <p v-if="form.errors.poll_question" class="text-sm text-destructive">
                                                    {{ form.errors.poll_question }}
                                                </p>
                                            </div>

                                            <div class="space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <Label>{{ t('contacts.poll_options') }} <span class="text-destructive">*</span></Label>
                                                    <Button
                                                        v-if="form.poll_options.length < 12"
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        @click="addPollOption"
                                                    >
                                                        <Plus class="h-4 w-4 ltr:mr-1.5 rtl:ml-1.5" />
                                                        Add Option
                                                    </Button>
                                                </div>

                                                <div v-for="(option, index) in form.poll_options" :key="index" class="flex gap-2">
                                                    <Input
                                                        v-model="form.poll_options[index]"
                                                        :placeholder="`Option ${index + 1}`"
                                                        required
                                                        class="flex-1"
                                                    />
                                                    <Button
                                                        v-if="form.poll_options.length > 2"
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        @click="removePollOption(index)"
                                                    >
                                                        <X class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                                <p v-if="form.errors.poll_options" class="text-sm text-destructive">
                                                    {{ form.errors.poll_options }}
                                                </p>
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="poll_max_answer">
                                                    {{ t('contacts.max_answers') }}
                                                </Label>
                                                <Input
                                                    id="poll_max_answer"
                                                    v-model.number="form.poll_max_answer"
                                                    type="number"
                                                    min="1"
                                                    :max="form.poll_options.length"
                                                    placeholder="1"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rate Limiting Settings -->
                                <div class="space-y-4 rounded-lg border bg-muted/50 p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="shrink-0 rounded-lg bg-orange-100 p-2 dark:bg-orange-950">
                                            <Settings class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="font-semibold">
                                                {{ t('campaigns.rate_limiting') }}
                                            </h3>
                                            <p class="text-xs text-muted-foreground">
                                                {{ t('campaigns.rate_limiting_desc') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="messages_per_minute">
                                                {{ t('campaigns.messages_per_minute') }}
                                            </Label>
                                            <Input
                                                id="messages_per_minute"
                                                v-model.number="form.messages_per_minute"
                                                max="30"
                                                min="5"
                                                type="number"
                                                class="h-10"
                                            />
                                            <p class="text-xs text-muted-foreground">
                                                {{ t('campaigns.messages_per_minute_hint') }}
                                            </p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="delay_seconds">
                                                {{ t('campaigns.delay_seconds') }}
                                            </Label>
                                            <Input
                                                id="delay_seconds"
                                                v-model.number="form.delay_seconds"
                                                max="10"
                                                min="2"
                                                type="number"
                                                class="h-10"
                                            />
                                            <p class="text-xs text-muted-foreground">
                                                {{ t('campaigns.delay_seconds_hint') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <Link href="/campaigns" class="flex-1 sm:flex-none">
                                <Button type="button" variant="outline" class="w-full">
                                    {{ t('campaigns.cancel') }}
                                </Button>
                            </Link>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="form.processing || recipientCount === 0"
                                class="flex-1 sm:flex-none"
                                @click="saveDraft"
                            >
                                <Save class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                {{ t('campaigns.save_draft') }}
                            </Button>
                            <Button
                                type="button"
                                :disabled="form.processing || recipientCount === 0"
                                class="flex-1 sm:flex-none"
                                @click="createAndStart"
                            >
                                <Send class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                {{ form.processing ? t('campaigns.creating') : t('campaigns.create_start') }}
                            </Button>
                        </div>
                    </div>

                    <!-- Preview Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-6 space-y-4">
                            <!-- Preview Card -->
                            <div class="rounded-lg border bg-card shadow-sm">
                                <div class="space-y-4 p-4 sm:p-6">
                                    <h3 class="flex items-center gap-2 text-lg font-semibold">
                                        <MessageSquare class="h-5 w-5 text-primary" />
                                        {{ t('campaigns.preview') }}
                                    </h3>

                                    <!-- Selected Device Info -->
                                    <div v-if="selectedDevice" class="rounded-lg bg-muted p-3">
                                        <p class="text-xs font-medium text-muted-foreground">
                                            {{ t('campaigns.sending_from') }}
                                        </p>
                                        <p class="mt-1 truncate font-semibold">
                                            {{ selectedDevice.device_label }}
                                        </p>
                                        <p class="truncate text-xs text-muted-foreground">
                                            {{ selectedDevice.phone }}
                                        </p>
                                    </div>

                                    <!-- Recipients Count -->
                                    <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-950/30">
                                        <p class="text-xs font-medium text-muted-foreground">
                                            {{ t('campaigns.total_recipients') }}
                                        </p>
                                        <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">
                                            {{ recipientCount }}
                                        </p>
                                    </div>

                                    <!-- Preview Recipient Info -->
                                    <div v-if="previewRecipient" class="rounded-lg bg-muted p-3">
                                        <p class="text-xs font-medium text-muted-foreground">
                                            {{ t('campaigns.preview_for') }}
                                        </p>
                                        <p class="mt-1 truncate font-semibold">
                                            {{ previewRecipient.first_name }} {{ previewRecipient.last_name }}
                                        </p>
                                        <p class="truncate text-xs text-muted-foreground">
                                            {{ previewRecipient.phone_e164 }}
                                        </p>
                                    </div>

                                    <!-- Preview Message -->
                                    <div class="rounded-lg border bg-muted/50 p-4">
                                        <p class="text-sm break-words whitespace-pre-wrap" v-html="previewMessage.replace(/\n/g, '<br>')"/>
                                    </div>
                                </div>
                            </div>

                            <!-- Tips Card -->
                            <div class="rounded-lg border-2 border-yellow-200 bg-gradient-to-br from-yellow-50 to-yellow-100 p-4 shadow-sm dark:border-yellow-900 dark:from-yellow-950/50 dark:to-yellow-900/50">
                                <h4 class="mb-3 flex items-center gap-2 text-sm font-semibold text-yellow-900 dark:text-yellow-100">
                                    <Lightbulb class="h-4 w-4" />
                                    {{ t('campaigns.tips') }}
                                </h4>
                                <ul class="space-y-2 text-xs text-yellow-800 dark:text-yellow-200">
                                    <li class="flex items-start gap-2">
                                        <AlertCircle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                        <span>{{ t('campaigns.tip_personalization') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <AlertCircle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                        <span>{{ t('campaigns.tip_concise') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <AlertCircle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                        <span>{{ t('campaigns.tip_test') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <AlertCircle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                        <span>{{ t('campaigns.tip_rate_limit') }}</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <AlertCircle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                        <span>{{ t('campaigns.tip_queued') }}</span>
                                    </li>
                                </ul>
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
:root[dir='rtl'] {
    font-family: 'Cairo', sans-serif;
}

/* Inter font for English */
:root[dir='ltr'] {
    font-family: 'Inter', sans-serif;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
