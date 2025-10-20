<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Eye, Plus, Search, Trash2, Users,
    Mail, Phone as PhoneIcon, ArrowUpRight, Upload, MoreVertical
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const { t, isRTL } = useTranslation();

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
    if (confirm(t('contacts.confirm_delete'))) {
        router.delete(`/contacts/${contactId}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout>
        <Head :title="t('contacts.title')" />

        <div class="min-h-screen bg-background">
            <div class="mx-auto max-w-7xl space-y-4 p-4 sm:space-y-6 sm:p-6 lg:p-8">
                <!-- Header Section -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ t('contacts.title') }}
                        </h1>
                        <p class="text-sm text-muted-foreground sm:text-base">
                            {{ t('contacts.description') }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Link href="/contacts/imports" class="flex-1 sm:flex-none">
                            <Button variant="outline" class="w-full">
                                <Upload class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                <span class="hidden sm:inline">{{ t('contacts.import_contacts') }}</span>
                                <span class="sm:hidden">{{ t('contacts.import') }}</span>
                            </Button>
                        </Link>
                        <Link href="/contacts/create" class="flex-1 sm:flex-none">
                            <Button variant="default" class="w-full">
                                <Plus class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                {{ t('contacts.add') }}
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="rounded-lg border bg-gradient-to-br from-purple-50 to-blue-50 p-4 shadow-sm dark:from-purple-950/20 dark:to-blue-950/20">
                    <div class="flex items-center gap-3">
                        <div class="shrink-0 rounded-full bg-purple-100 p-2.5 dark:bg-purple-900">
                            <Users class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-base font-semibold sm:text-lg">
                                {{ contacts.total }} {{ t('contacts.title') }}
                            </p>
                            <p class="text-xs text-muted-foreground sm:text-sm">
                                {{ t('contacts.import_or_add') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="relative">
                    <Search
                        :class="[
                            'pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground',
                            isRTL ? 'right-3' : 'left-3'
                        ]"
                    />
                    <Input
                        v-model="searchQuery"
                        :class="isRTL ? 'pr-10' : 'pl-10'"
                        :placeholder="t('contacts.search')"
                        type="search"
                        class="h-10 sm:h-11"
                    />
                </div>

                <!-- Contacts List -->
                <div v-if="contacts.data.length > 0" class="rounded-lg border bg-card shadow-sm">
                    <!-- Desktop Table View -->
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="w-full">
                            <thead class="border-b bg-muted/30">
                            <tr>
                                <th class="px-4 py-3 text-start text-sm font-medium xl:px-6">
                                    {{ t('contacts.name') }}
                                </th>
                                <th class="px-4 py-3 text-start text-sm font-medium xl:px-6">
                                    {{ t('contacts.phone') }}
                                </th>
                                <th class="px-4 py-3 text-start text-sm font-medium xl:px-6">
                                    {{ t('contacts.email') }}
                                </th>
                                <th class="px-4 py-3 text-start text-sm font-medium xl:px-6">
                                    {{ t('contacts.source') }}
                                </th>
                                <th class="px-4 py-3 text-start text-sm font-medium xl:px-6">
                                    {{ t('contacts.last_message') }}
                                </th>
                                <th class="px-4 py-3 text-end text-sm font-medium xl:px-6">
                                    {{ t('contacts.actions') }}
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y">
                            <tr
                                v-for="contact in contacts.data"
                                :key="contact.id"
                                class="transition-colors hover:bg-muted/50"
                            >
                                <td class="px-4 py-4 xl:px-6">
                                    <div class="font-medium">
                                        {{ contact.full_name || t('contacts.no_name') }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-mono text-sm text-muted-foreground xl:px-6">
                                    {{ contact.phone_e164 }}
                                </td>
                                <td class="px-4 py-4 text-sm text-muted-foreground xl:px-6">
                                    {{ contact.email || '-' }}
                                </td>
                                <td class="px-4 py-4 xl:px-6">
                                    <Badge variant="secondary" class="text-xs">
                                        {{ contact.import_source }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 text-sm text-muted-foreground xl:px-6">
                                    {{ contact.last_message_date || t('contacts.never') }}
                                </td>
                                <td class="px-4 py-4 xl:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="`/contacts/${contact.id}`">
                                            <Button size="sm" variant="ghost">
                                                <Eye class="h-4 w-4" />
                                                <span class="sr-only">{{ t('common.view') }}</span>
                                            </Button>
                                        </Link>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            @click="deleteContact(contact.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            <span class="sr-only">{{ t('common.delete') }}</span>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tablet View -->
                    <div class="hidden divide-y sm:block lg:hidden">
                        <div
                            v-for="contact in contacts.data"
                            :key="contact.id"
                            class="flex items-center justify-between p-4 transition-colors hover:bg-muted/50"
                        >
                            <Link
                                :href="`/contacts/${contact.id}`"
                                class="flex min-w-0 flex-1 items-center gap-3"
                            >
                                <div class="shrink-0 rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                                    <Users class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">
                                        {{ contact.full_name || t('contacts.no_name') }}
                                    </p>
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <span class="truncate font-mono">{{ contact.phone_e164 }}</span>
                                        <Badge variant="secondary" class="shrink-0 text-xs">
                                            {{ contact.import_source }}
                                        </Badge>
                                    </div>
                                </div>
                            </Link>
                            <div class="flex shrink-0 items-center gap-1">
                                <Link :href="`/contacts/${contact.id}`">
                                    <Button size="icon" variant="ghost" class="h-8 w-8">
                                        <Eye class="h-4 w-4" />
                                        <span class="sr-only">{{ t('common.view') }}</span>
                                    </Button>
                                </Link>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    class="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    @click="deleteContact(contact.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    <span class="sr-only">{{ t('common.delete') }}</span>
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="divide-y sm:hidden">
                        <div
                            v-for="contact in contacts.data"
                            :key="contact.id"
                            class="flex items-center gap-3 p-4 transition-colors hover:bg-muted/50"
                        >
                            <Link
                                :href="`/contacts/${contact.id}`"
                                class="flex min-w-0 flex-1 items-center gap-3"
                            >
                                <div class="shrink-0 rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                                    <Users class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">
                                        {{ contact.full_name || t('contacts.no_name') }}
                                    </p>
                                    <p class="truncate font-mono text-sm text-muted-foreground">
                                        {{ contact.phone_e164 }}
                                    </p>
                                </div>
                            </Link>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button size="icon" variant="ghost" class="h-8 w-8 shrink-0">
                                        <MoreVertical class="h-4 w-4" />
                                        <span class="sr-only">{{ t('contacts.actions') }}</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link :href="`/contacts/${contact.id}`" class="flex items-center">
                                            <Eye class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                            {{ t('common.view') }}
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="deleteContact(contact.id)"
                                    >
                                        <Trash2 class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                        {{ t('common.delete') }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-lg border bg-card p-8 shadow-sm sm:p-12"
                >
                    <div class="mb-4 rounded-full bg-muted p-4">
                        <Users class="h-10 w-10 text-muted-foreground sm:h-12 sm:w-12" />
                    </div>
                    <h3 class="mb-2 text-center text-lg font-semibold">
                        {{ t('contacts.no_contacts') }}
                    </h3>
                    <p class="mb-4 text-center text-sm text-muted-foreground">
                        {{ t('contacts.import_or_add') }}
                    </p>
                    <div class="flex w-full max-w-sm flex-col gap-2 sm:flex-row">
                        <Link href="/contacts/imports" class="flex-1">
                            <Button variant="outline" class="w-full">
                                <Upload class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                {{ t('contacts.import_contacts') }}
                            </Button>
                        </Link>
                        <Link href="/contacts/create" class="flex-1">
                            <Button variant="default" class="w-full">
                                <Plus class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                {{ t('contacts.add') }}
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="contacts.data.length > 0"
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ t('contacts.showing', {
                        from: (contacts.current_page - 1) * contacts.per_page + 1,
                        to: Math.min(contacts.current_page * contacts.per_page, contacts.total),
                        total: contacts.total
                    }) }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="link in contacts.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'inline-flex min-w-[2.5rem] items-center justify-center rounded-md border px-3 py-2 text-sm transition-colors',
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'hover:bg-muted',
                                !link.url && 'pointer-events-none opacity-50'
                            ]"
                            v-html="link.label"
                        />
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

[dir="rtl"] .text-end {
    text-align: left;
}

[dir="ltr"] .text-end {
    text-align: right;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
