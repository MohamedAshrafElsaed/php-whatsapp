<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, Tag, Users, ChevronDown, Save, AlertCircle
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useTranslation } from '@/composables/useTranslation';

interface Contact {
    id: number;
    full_name: string;
    phone_e164: string;
    email: string | null;
}

interface Segment {
    id: number;
    name: string;
    description: string | null;
    recipient_ids: number[];
}

const props = defineProps<{
    segment: Segment;
    contacts: Contact[];
}>();

const { t } = useTranslation();

const form = useForm({
    name: props.segment.name,
    description: props.segment.description || '',
    recipient_ids: [...props.segment.recipient_ids],
});

const contactsExpanded = ref(false);
const searchQuery = ref('');

/**
 * Filter contacts based on search query
 */
const filteredContacts = computed((): Contact[] => {
    if (!searchQuery.value) return props.contacts;

    const query = searchQuery.value.toLowerCase();
    return props.contacts.filter(contact =>
        contact.full_name.toLowerCase().includes(query) ||
        contact.phone_e164.includes(query) ||
        (contact.email && contact.email.toLowerCase().includes(query))
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
    return filteredContacts.value.length > 0 &&
        filteredContacts.value.every(c => form.recipient_ids.includes(c.id));
});

/**
 * Check if some but not all contacts are selected
 */
const someContactsSelected = computed((): boolean => {
    return form.recipient_ids.length > 0 && !allContactsSelected.value;
});

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
 * Toggle all filtered contacts selection
 */
const toggleAllContacts = (): void => {
    if (allContactsSelected.value) {
        filteredContacts.value.forEach(contact => {
            const index = form.recipient_ids.indexOf(contact.id);
            if (index > -1) {
                form.recipient_ids.splice(index, 1);
            }
        });
    } else {
        filteredContacts.value.forEach(contact => {
            if (!form.recipient_ids.includes(contact.id)) {
                form.recipient_ids.push(contact.id);
            }
        });
    }
};

/**
 * Submit form to update segment
 */
const submit = (): void => {
    form.put(`/contacts/segments/${props.segment.id}`);
};
</script>

<template>
    <AppLayout>
        <Head :title="t('segments.edit')" />

        <div class="min-h-screen bg-background">
            <div class="mx-auto max-w-4xl space-y-4 p-4 sm:space-y-6 sm:p-6 lg:p-8">
                <!-- Header -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <Link :href="`/contacts/segments/${segment.id}`">
                        <Button size="icon" variant="ghost" class="h-9 w-9 shrink-0 sm:h-10 sm:w-10">
                            <ArrowLeft class="h-4 w-4 sm:h-5 sm:w-5" />
                            <span class="sr-only">{{ t('common.back') }}</span>
                        </Button>
                    </Link>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ t('segments.edit') }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground sm:text-base">
                            {{ t('segments.edit_description') }}
                        </p>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="rounded-lg border bg-card shadow-sm">
                        <div class="space-y-6 p-4 sm:p-6">
                            <!-- Segment Details -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-3 border-b pb-4">
                                    <div class="shrink-0 rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                                        <Tag class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <h2 class="text-lg font-semibold">{{ t('segments.segment_details') }}</h2>
                                </div>

                                <!-- Segment Name -->
                                <div class="space-y-2">
                                    <Label for="name">
                                        {{ t('segments.segment_name') }}
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        :placeholder="t('segments.segment_name_placeholder')"
                                        type="text"
                                        class="h-10 sm:h-11"
                                        required
                                    />
                                    <p v-if="form.errors.name" class="text-sm text-destructive">
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- Description -->
                                <div class="space-y-2">
                                    <Label for="description">
                                        {{ t('segments.description') }}
                                    </Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        :placeholder="t('segments.description_placeholder')"
                                        rows="3"
                                        class="resize-none"
                                    />
                                    <p v-if="form.errors.description" class="text-sm text-destructive">
                                        {{ form.errors.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Contact Selection -->
                            <div class="space-y-4 rounded-lg border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 p-4 dark:border-blue-800 dark:from-blue-950/50 dark:to-blue-900/50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <Users class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        <h3 class="font-semibold text-blue-900 dark:text-blue-100">
                                            {{ t('segments.select_contacts') }}
                                        </h3>
                                    </div>
                                    <span class="text-sm text-blue-700 dark:text-blue-300">
                                        {{ selectedContactsCount }} {{ t('segments.selected') }}
                                    </span>
                                </div>

                                <!-- Search Contacts -->
                                <Input
                                    v-model="searchQuery"
                                    type="search"
                                    :placeholder="t('segments.search_contacts')"
                                    class="h-9 bg-white dark:bg-slate-950"
                                />

                                <!-- Contacts List -->
                                <Collapsible v-model:open="contactsExpanded" class="rounded-lg border bg-white dark:bg-slate-950">
                                    <CollapsibleTrigger class="flex w-full items-center justify-between p-3 hover:bg-blue-50 dark:hover:bg-blue-950/30">
                                        <div class="flex items-center gap-2">
                                            <Checkbox
                                                :checked="allContactsSelected"
                                                :indeterminate="someContactsSelected"
                                                @click.stop="toggleAllContacts"
                                            />
                                            <span class="text-sm font-medium">
                                                {{ t('segments.all_contacts') }} ({{ filteredContacts.length }})
                                            </span>
                                        </div>
                                        <ChevronDown
                                            :class="[
                                                'h-4 w-4 transition-transform',
                                                contactsExpanded && 'rotate-180'
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
                                                    <p class="truncate text-sm font-medium">{{ contact.full_name }}</p>
                                                    <p class="truncate text-xs text-muted-foreground">{{ contact.phone_e164 }}</p>
                                                </div>
                                            </div>
                                            <div v-if="filteredContacts.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                                                {{ t('segments.no_contacts_found') }}
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </Collapsible>

                                <p v-if="form.errors.recipient_ids" class="text-sm text-destructive">
                                    {{ form.errors.recipient_ids }}
                                </p>

                                <!-- Warning if no contacts selected -->
                                <div v-if="selectedContactsCount === 0" class="flex items-start gap-2 rounded-lg bg-yellow-50 p-3 dark:bg-yellow-950/30">
                                    <AlertCircle class="h-4 w-4 shrink-0 text-yellow-600 dark:text-yellow-400" />
                                    <p class="text-xs text-yellow-800 dark:text-yellow-200">
                                        {{ t('segments.select_at_least_one') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Link :href="`/contacts/segments/${segment.id}`" class="flex-1 sm:flex-none">
                            <Button type="button" variant="outline" class="w-full">
                                {{ t('common.cancel') }}
                            </Button>
                        </Link>
                        <Button
                            type="submit"
                            :disabled="form.processing || selectedContactsCount === 0"
                            class="flex-1 sm:flex-none"
                        >
                            <Save class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ form.processing ? t('segments.updating') : t('segments.update_segment') }}
                        </Button>
                    </div>
                </form>
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

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
