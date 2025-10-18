<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, Plus, Search, Send, Trash2, Users, Mail, Phone as PhoneIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Contact {
    id: number;
    full_name: string;
    phone_e164: string;
    email: string | null;
    import_source: string;
    last_message_date: string | null;
}

interface PaginatedContacts {
    data: Contact[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

const props = defineProps<{
    contacts: PaginatedContacts;
    search: string | null;
}>();

const searchForm = useForm({
    search: props.search || '',
});

const searchQuery = ref(props.search || '');

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchForm.search = value;
        searchForm.get('/contacts', {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
});

const deleteContact = (contactId: number) => {
    if (
        confirm(
            'Are you sure you want to delete this contact? This action cannot be undone.',
        )
    ) {
        router.delete(`/contacts/${contactId}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout>
        <Head title="All Contacts" />

        <div class="mx-auto max-w-7xl space-y-4 p-4 md:space-y-6 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight md:text-2xl lg:text-3xl">
                        All Contacts
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground md:mt-2">
                        Manage and send messages to your contacts
                    </p>
                </div>
                <Link href="/contacts/create">
                    <Button class="w-full sm:w-auto">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Contact
                    </Button>
                </Link>
            </div>

            <!-- Search Bar -->
            <div class="flex items-center gap-4">
                <div class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="searchQuery"
                        class="pl-10"
                        placeholder="Search by name, phone, or email..."
                        type="text"
                    />
                </div>
            </div>

            <!-- Desktop Table View (hidden on mobile) -->
            <div class="hidden overflow-hidden rounded-lg border bg-card md:block">
                <div v-if="contacts.data.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium md:px-6">
                                Name
                            </th>
                            <th class="px-4 py-3 text-left text-sm font-medium md:px-6">
                                Phone
                            </th>
                            <th class="px-4 py-3 text-left text-sm font-medium md:px-6">
                                Email
                            </th>
                            <th class="px-4 py-3 text-left text-sm font-medium md:px-6">
                                Source
                            </th>
                            <th class="px-4 py-3 text-left text-sm font-medium md:px-6">
                                Last Message
                            </th>
                            <th class="px-4 py-3 text-right text-sm font-medium md:px-6">
                                Actions
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        <tr
                            v-for="contact in contacts.data"
                            :key="contact.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-4 text-sm font-medium md:px-6">
                                {{ contact.full_name || 'N/A' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-muted-foreground md:px-6">
                                {{ contact.phone_e164 }}
                            </td>
                            <td class="px-4 py-4 text-sm text-muted-foreground md:px-6">
                                {{ contact.email || '-' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-muted-foreground md:px-6">
                                {{ contact.import_source }}
                            </td>
                            <td class="px-4 py-4 text-sm text-muted-foreground md:px-6">
                                {{ contact.last_message_date || 'Never contacted' }}
                            </td>
                            <td class="px-4 py-4 text-right md:px-6">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/contacts/${contact.id}`">
                                        <Button size="sm" variant="ghost">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Link :href="`/contacts/${contact.id}`">
                                        <Button size="sm" variant="ghost">
                                            <Send class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        @click="deleteContact(contact.id)"
                                    >
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Desktop Empty State -->
                <div
                    v-if="contacts.data.length === 0"
                    class="flex flex-col items-center justify-center px-4 py-12"
                >
                    <Users class="mb-4 h-12 w-12 text-muted-foreground" />
                    <h3 class="mb-2 text-lg font-semibold">No contacts yet</h3>
                    <p class="mb-4 text-center text-sm text-muted-foreground">
                        {{
                            searchQuery
                                ? 'No contacts match your search.'
                                : 'Import contacts or add them manually to get started.'
                        }}
                    </p>
                    <div v-if="!searchQuery" class="flex gap-2">
                        <Link href="/contacts/imports">
                            <Button variant="outline">Import Contacts</Button>
                        </Link>
                        <Link href="/contacts/create">
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Add Contact
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Mobile Card View (visible only on mobile) -->
            <div class="space-y-3 md:hidden">
                <div
                    v-for="contact in contacts.data"
                    :key="contact.id"
                    class="overflow-hidden rounded-lg border bg-card"
                >
                    <div class="p-4">
                        <!-- Contact Name -->
                        <div class="mb-3 flex items-start justify-between gap-2">
                            <h3 class="text-base font-semibold">
                                {{ contact.full_name || 'N/A' }}
                            </h3>
                        </div>

                        <!-- Contact Info -->
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <PhoneIcon class="h-4 w-4 shrink-0" />
                                <span class="truncate">{{ contact.phone_e164 }}</span>
                            </div>
                            <div v-if="contact.email" class="flex items-center gap-2 text-muted-foreground">
                                <Mail class="h-4 w-4 shrink-0" />
                                <span class="truncate">{{ contact.email }}</span>
                            </div>
                        </div>

                        <!-- Additional Info -->
                        <div class="mt-3 flex items-center justify-between border-t pt-3 text-xs text-muted-foreground">
                            <span class="truncate">{{ contact.import_source }}</span>
                            <span class="shrink-0">
                                {{ contact.last_message_date || 'Never contacted' }}
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="mt-3 flex gap-2">
                            <Link :href="`/contacts/${contact.id}`" class="flex-1">
                                <Button size="sm" variant="outline" class="w-full">
                                    <Eye class="mr-2 h-4 w-4" />
                                    View
                                </Button>
                            </Link>
                            <Link :href="`/contacts/${contact.id}`" class="flex-1">
                                <Button size="sm" class="w-full">
                                    <Send class="mr-2 h-4 w-4" />
                                    Message
                                </Button>
                            </Link>
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="deleteContact(contact.id)"
                            >
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Mobile Empty State -->
                <div
                    v-if="contacts.data.length === 0"
                    class="flex flex-col items-center justify-center rounded-lg border bg-card p-8"
                >
                    <Users class="mb-4 h-12 w-12 text-muted-foreground" />
                    <h3 class="mb-2 text-base font-semibold">No contacts yet</h3>
                    <p class="mb-4 text-center text-sm text-muted-foreground">
                        {{
                            searchQuery
                                ? 'No contacts match your search.'
                                : 'Import contacts or add them manually to get started.'
                        }}
                    </p>
                    <div v-if="!searchQuery" class="flex w-full flex-col gap-2">
                        <Link href="/contacts/imports">
                            <Button variant="outline" class="w-full">
                                Import Contacts
                            </Button>
                        </Link>
                        <Link href="/contacts/create">
                            <Button class="w-full">
                                <Plus class="mr-2 h-4 w-4" />
                                Add Contact
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="contacts.data.length > 0"
                class="flex flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <p class="text-sm text-muted-foreground">
                    Showing {{ contacts.data.length }} of {{ contacts.total }} contacts
                </p>
                <div class="flex flex-wrap justify-center gap-2">
                    <Link
                        v-for="link in contacts.links"
                        :key="link.label"
                        :class="[
                            'rounded border px-3 py-1 text-sm',
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-background hover:bg-muted',
                            !link.url && 'pointer-events-none opacity-50',
                        ]"
                        :href="link.url || '#'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
