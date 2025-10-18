<script lang="ts" setup>
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
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Smartphone } from 'lucide-vue-next';
import { computed } from 'vue';

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
    previewRecipient: Recipient | null;
    availableVariables: string[];
}>();

const form = useForm({
    name: '',
    wa_session_id: props.connectedDevices[0]?.id || null,
    import_id: null as number | null,
    message_template: '',
    messages_per_minute: 15,
    delay_seconds: 4,
    start_immediately: false,
});

const previewMessage = computed(() => {
    if (!form.message_template || !props.previewRecipient) {
        return 'Select an import and write a message to see preview';
    }

    let preview = form.message_template;
    const recipient = props.previewRecipient;

    preview = preview.replace(/\{\{first_name\}\}/g, recipient.first_name || '[first_name]');
    preview = preview.replace(/\{\{last_name\}\}/g, recipient.last_name || '[last_name]');
    preview = preview.replace(/\{\{email\}\}/g, recipient.email || '[email]');
    preview = preview.replace(/\{\{phone\}\}/g, recipient.phone_e164 || '[phone]');

    if (recipient.extra_json) {
        Object.entries(recipient.extra_json).forEach(([key, value]) => {
            const regex = new RegExp(`\\{\\{${key}\\}\\}`, 'g');
            preview = preview.replace(regex, String(value));
        });
    }

    return preview;
});

const formatVariable = (variable: string) => {
    return `{{${variable}}}`;
};

const insertVariable = (variable: string) => {
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
            textarea.selectionStart = textarea.selectionEnd = start + variableText.length;
        }, 0);
    }
};

const saveDraft = () => {
    form.start_immediately = false;
    form.post('/campaigns');
};

const createAndStart = () => {
    form.start_immediately = true;
    form.post('/campaigns');
};

const selectedImport = computed(() => {
    return props.imports.find((imp) => imp.id === form.import_id);
});

const selectedDevice = computed(() => {
    return props.connectedDevices.find((dev) => dev.id === form.wa_session_id);
});

const hasNoDevices = computed(() => props.connectedDevices.length === 0);
const hasNoImports = computed(() => props.imports.length === 0);
</script>

<template>
    <AppLayout>
        <Head title="Create Campaign" />

        <div class="space-y-4 p-4 md:space-y-6 md:p-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 md:text-2xl dark:text-gray-100">
                    Create Campaign
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Set up a new WhatsApp bulk messaging campaign
                </p>
            </div>

            <!-- No Devices Warning -->
            <div
                v-if="hasNoDevices"
                class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20"
            >
                <div class="flex items-center gap-3">
                    <Smartphone class="h-5 w-5 text-yellow-600" />
                    <div>
                        <h3 class="font-semibold text-yellow-900 dark:text-yellow-100">
                            No WhatsApp Device Connected
                        </h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            Please connect a WhatsApp device before creating a campaign.
                        </p>
                        <Link href="/w/connect">
                            <Button size="sm" class="mt-2" variant="outline">
                                Connect WhatsApp
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- No Imports Warning -->
            <div
                v-if="hasNoImports && !hasNoDevices"
                class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20"
            >
                <div class="flex items-center gap-3">
                    <div>
                        <h3 class="font-semibold text-yellow-900 dark:text-yellow-100">
                            No Contacts Imported
                        </h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            Please import contacts before creating a campaign.
                        </p>
                        <Link href="/contacts/imports">
                            <Button size="sm" class="mt-2" variant="outline">
                                Import Contacts
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div v-if="!hasNoDevices && !hasNoImports" class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2 md:space-y-6">
                    <div class="rounded-lg border bg-card p-4 md:p-6">
                        <div class="space-y-4">
                            <!-- Campaign Name -->
                            <div class="space-y-2">
                                <Label for="name">Campaign Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g., Welcome Campaign 2024"
                                    type="text"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- WhatsApp Device Selection -->
                            <div class="space-y-2">
                                <Label for="wa_session_id">WhatsApp Device *</Label>
                                <Select v-model="form.wa_session_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select device" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="device in connectedDevices"
                                            :key="device.id"
                                            :value="device.id"
                                        >
                                            {{ device.device_label }}
                                            {{ device.is_primary ? '(Primary)' : '' }}
                                            - {{ device.phone }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="form.errors.wa_session_id"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.wa_session_id }}
                                </p>
                            </div>

                            <!-- Import Selection -->
                            <div class="space-y-2">
                                <Label for="import_id">Select Import *</Label>
                                <Select v-model="form.import_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Choose an import..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="importItem in imports"
                                            :key="importItem.id"
                                            :value="importItem.id"
                                        >
                                            {{ importItem.filename }} ({{
                                                importItem.valid_rows
                                            }}
                                            contacts)
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.import_id" class="text-sm text-red-600">
                                    {{ form.errors.import_id }}
                                </p>
                                <p
                                    v-if="selectedImport"
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    {{ selectedImport.valid_rows }} valid recipients will
                                    receive this message
                                </p>
                            </div>

                            <!-- Message Template -->
                            <div class="space-y-2">
                                <Label for="message_template">Message Template *</Label>
                                <Textarea
                                    id="message_template"
                                    v-model="form.message_template"
                                    name="message_template"
                                    placeholder="Hi {{first_name}}, welcome to our service!"
                                    rows="6"
                                />
                                <p
                                    v-if="form.errors.message_template"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.message_template }}
                                </p>

                                <div
                                    v-if="availableVariables.length > 0"
                                    class="rounded-md bg-blue-50 p-3 dark:bg-blue-950"
                                >
                                    <p
                                        class="mb-2 text-sm font-medium text-blue-900 dark:text-blue-100"
                                    >
                                        Available Variables:
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <Button
                                            v-for="variable in availableVariables"
                                            :key="variable"
                                            size="sm"
                                            type="button"
                                            variant="outline"
                                            @click="insertVariable(variable)"
                                        >
                                            {{ formatVariable(variable) }}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <!-- Rate Limiting Settings -->
                            <div class="space-y-4 rounded-md border p-4">
                                <h3 class="text-sm font-medium">Rate Limiting Settings</h3>
                                <p class="text-xs text-muted-foreground">
                                    Conservative settings to avoid WhatsApp account blocks
                                </p>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="messages_per_minute">
                                            Messages per Minute
                                        </Label>
                                        <Input
                                            id="messages_per_minute"
                                            v-model.number="form.messages_per_minute"
                                            max="30"
                                            min="5"
                                            type="number"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Recommended: 10-20 to stay safe
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="delay_seconds">
                                            Delay Between Messages (seconds)
                                        </Label>
                                        <Input
                                            id="delay_seconds"
                                            v-model.number="form.delay_seconds"
                                            max="10"
                                            min="2"
                                            type="number"
                                        />
                                        <p class="text-xs text-muted-foreground">
                                            Recommended: 3-5 seconds
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Link href="/campaigns">
                            <Button class="w-full sm:w-auto" type="button" variant="outline">
                                Cancel
                            </Button>
                        </Link>
                        <Button
                            class="w-full sm:w-auto"
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="saveDraft"
                        >
                            Save as Draft
                        </Button>
                        <Button
                            class="w-full sm:w-auto"
                            type="button"
                            :disabled="form.processing"
                            @click="createAndStart"
                        >
                            <template v-if="form.processing">Creating...</template>
                            <template v-else>Create & Start Sending</template>
                        </Button>
                    </div>
                </div>

                <!-- Preview Sidebar -->
                <div class="lg:col-span-1">
                    <div class="sticky top-6 space-y-4">
                        <div class="rounded-lg border bg-card p-4 md:p-6">
                            <h3 class="mb-4 text-base font-medium md:text-lg">
                                Message Preview
                            </h3>

                            <!-- Selected Device Info -->
                            <div
                                v-if="selectedDevice"
                                class="mb-4 rounded-md bg-muted p-3"
                            >
                                <p class="text-xs font-medium text-muted-foreground">
                                    Sending from:
                                </p>
                                <p class="text-sm font-medium">
                                    {{ selectedDevice.device_label }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ selectedDevice.phone }}
                                </p>
                            </div>

                            <!-- Preview Recipient Info -->
                            <div v-if="previewRecipient" class="mb-4 rounded-md bg-muted p-3">
                                <p class="text-xs font-medium text-muted-foreground">
                                    Preview for:
                                </p>
                                <p class="text-sm font-medium break-words">
                                    {{ previewRecipient.first_name }}
                                    {{ previewRecipient.last_name }}
                                </p>
                                <p class="text-xs break-words text-muted-foreground">
                                    {{ previewRecipient.phone_e164 }}
                                </p>
                            </div>

                            <!-- Preview Message -->
                            <div class="rounded-lg border bg-muted p-4">
                                <p
                                    class="text-sm break-words whitespace-pre-wrap"
                                    v-html="previewMessage.replace(/\n/g, '<br>')"
                                />
                            </div>
                        </div>

                        <!-- Tips -->
                        <div
                            class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900 dark:bg-yellow-950"
                        >
                            <h4 class="mb-2 text-sm font-medium text-yellow-900 dark:text-yellow-100">
                                Tips
                            </h4>
                            <ul class="space-y-1 text-xs text-yellow-800 dark:text-yellow-200">
                                <li>• Use personalization to improve engagement</li>
                                <li>• Keep messages concise and clear</li>
                                <li>• Test with a small campaign first</li>
                                <li>• Lower rate limits reduce ban risk</li>
                                <li>• Messages are queued and sent gradually</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
