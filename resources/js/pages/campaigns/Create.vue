<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

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
    imports: Import[];
    previewRecipient: Recipient | null;
    availableVariables: string[];
}>();

const form = useForm({
    name: '',
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

    preview = preview.replace(
        /\{\{first_name\}\}/g,
        recipient.first_name || '[first_name]',
    );
    preview = preview.replace(
        /\{\{last_name\}\}/g,
        recipient.last_name || '[last_name]',
    );
    preview = preview.replace(/\{\{email\}\}/g, recipient.email || '[email]');

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

watch(
    () => form.import_id,
    (newImportId) => {
        if (newImportId) {
            router.visit(`/campaigns/create?import_id=${newImportId}`, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    },
);

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
            textarea.selectionStart = textarea.selectionEnd =
                start + variableText.length;
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
</script>

<template>
    <AppLayout>
        <Head title="Create Campaign" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1
                    class="text-xl font-semibold text-gray-900 md:text-2xl dark:text-gray-100"
                >
                    Create Campaign
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Set up a new WhatsApp bulk messaging campaign
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div
                        class="rounded-lg border border-gray-200 bg-white p-4 md:p-6 dark:border-gray-800 dark:bg-gray-950"
                    >
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Campaign Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g., Welcome Campaign 2024"
                                    type="text"
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="import_id">Select Import *</Label>
                                <select
                                    id="import_id"
                                    v-model="form.import_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option :value="null">
                                        Choose an import...
                                    </option>
                                    <option
                                        v-for="importItem in imports"
                                        :key="importItem.id"
                                        :value="importItem.id"
                                    >
                                        {{ importItem.filename }} ({{
                                            importItem.valid_rows
                                        }}
                                        contacts)
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.import_id"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.import_id }}
                                </p>
                                <p
                                    v-if="selectedImport"
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    {{ selectedImport.valid_rows }} valid
                                    recipients will receive this message
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="message_template"
                                    >Message Template *</Label
                                >
                                <Textarea
                                    id="message_template"
                                    v-model="form.message_template"
                                    name="message_template"
                                    placeholder="Hi {{first_name}}, welcome to {{company}}!"
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

                            <div
                                class="space-y-4 rounded-md border border-gray-200 p-4 dark:border-gray-700"
                            >
                                <h3
                                    class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                >
                                    Rate Limiting Settings
                                </h3>
                                <p
                                    class="text-xs text-gray-600 dark:text-gray-400"
                                >
                                    Conservative settings to avoid WhatsApp
                                    account blocks
                                </p>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="messages_per_minute"
                                            >Messages per Minute</Label
                                        >
                                        <Input
                                            id="messages_per_minute"
                                            v-model.number="
                                                form.messages_per_minute
                                            "
                                            max="30"
                                            min="5"
                                            type="number"
                                        />
                                        <p
                                            class="text-xs text-gray-600 dark:text-gray-400"
                                        >
                                            Recommended: 10-20 to stay safe
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="delay_seconds"
                                            >Delay Between Messages
                                            (seconds)</Label
                                        >
                                        <Input
                                            id="delay_seconds"
                                            v-model.number="form.delay_seconds"
                                            max="10"
                                            min="2"
                                            type="number"
                                        />
                                        <p
                                            class="text-xs text-gray-600 dark:text-gray-400"
                                        >
                                            Recommended: 3-5 seconds
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex flex-col-reverse justify-end gap-3 sm:flex-row"
                    >
                        <Button
                            class="w-full sm:w-auto"
                            type="button"
                            variant="outline"
                            @click="router.visit('/campaigns')"
                        >
                            Cancel
                        </Button>
                        <Button
                            :disabled="form.processing"
                            class="w-full sm:w-auto"
                            type="button"
                            variant="outline"
                            @click="saveDraft"
                        >
                            Save as Draft
                        </Button>
                        <Button
                            :disabled="form.processing"
                            class="w-full sm:w-auto"
                            type="button"
                            @click="createAndStart"
                        >
                            <template v-if="form.processing"
                                >Creating...</template
                            >
                            <template v-else>Create & Start Sending</template>
                        </Button>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="sticky top-6 space-y-4">
                        <div
                            class="rounded-lg border border-gray-200 bg-white p-4 md:p-6 dark:border-gray-800 dark:bg-gray-950"
                        >
                            <h3
                                class="mb-4 text-base font-medium text-gray-900 md:text-lg dark:text-gray-100"
                            >
                                Message Preview
                            </h3>

                            <div
                                v-if="previewRecipient"
                                class="mb-4 rounded-md bg-gray-50 p-3 dark:bg-gray-900"
                            >
                                <p
                                    class="text-xs font-medium text-gray-600 dark:text-gray-400"
                                >
                                    Preview for:
                                </p>
                                <p
                                    class="text-sm font-medium break-words text-gray-900 dark:text-gray-100"
                                >
                                    {{ previewRecipient.first_name }}
                                    {{ previewRecipient.last_name }}
                                </p>
                                <p
                                    class="text-xs break-words text-gray-600 dark:text-gray-400"
                                >
                                    {{ previewRecipient.phone_e164 }}
                                </p>
                            </div>

                            <div
                                class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900"
                            >
                                <p
                                    class="text-sm break-words whitespace-pre-wrap text-gray-900 dark:text-gray-100"
                                    v-html="
                                        previewMessage.replace(/\n/g, '<br>')
                                    "
                                />
                            </div>
                        </div>

                        <div
                            class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900 dark:bg-yellow-950"
                        >
                            <h4
                                class="mb-2 text-sm font-medium text-yellow-900 dark:text-yellow-100"
                            >
                                Tips
                            </h4>
                            <ul
                                class="space-y-1 text-xs text-yellow-800 dark:text-yellow-200"
                            >
                                <li>
                                    Use personalization to improve engagement
                                </li>
                                <li>Keep messages concise and clear</li>
                                <li>Test with a small campaign first</li>
                                <li>Lower rate limits reduce ban risk</li>
                                <li>Messages are queued and sent gradually</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
