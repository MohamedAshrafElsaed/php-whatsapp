<script lang="ts" setup>
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    Download,
    FileSpreadsheet,
    Upload,
    ArrowUpRight,
    Info,
    Trash2,
    Eye,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const { t } = useTranslation();

interface Import {
    id: number;
    filename: string;
    total_rows: number;
    valid_rows: number;
    invalid_rows: number;
    status: string;
    created_at: string;
}

defineProps<{
    imports: {
        data: Import[];
        links: any;
        meta: any;
    };
}>();

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const fileInput = ref<HTMLInputElement | null>(null);
const uploadForm = useForm({
    file: null as File | null,
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        uploadForm.file = target.files[0];
        uploadForm.post('/contacts/imports', {
            onSuccess: () => {
                uploadForm.reset();
                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            },
            onError: () => {
                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            },
        });
    }
};

const downloadTemplate = (format: string) => {
    window.location.href = `/contacts/imports/template?format=${format}`;
};

const deleteImport = (importId: number) => {
    if (confirm(t('imports.confirm_delete'))) {
        router.delete(`/contacts/imports/${importId}`);
    }
};

const statusVariant: Record<string, any> = {
    pending: 'secondary',
    validated: 'secondary',
    ready: 'default',
    deleted: 'destructive',
};
</script>

<template>
    <AppLayout>
        <Head :title="t('imports.title')" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ t('imports.title') }}</h1>
                    <p class="text-muted-foreground mt-1">{{ t('imports.description') }}</p>
                </div>
            </div>

            <Alert v-if="flashSuccess" class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950">
                <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                <AlertTitle class="text-green-800 dark:text-green-200">{{ t('imports.success') }}</AlertTitle>
                <AlertDescription class="text-green-700 dark:text-green-300">{{ flashSuccess }}</AlertDescription>
            </Alert>

            <Alert v-if="flashError" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>{{ t('imports.error') }}</AlertTitle>
                <AlertDescription>{{ flashError }}</AlertDescription>
            </Alert>

            <Alert v-if="uploadForm.errors.file" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>{{ t('imports.upload_error') }}</AlertTitle>
                <AlertDescription>{{ uploadForm.errors.file }}</AlertDescription>
            </Alert>

            <div class="grid gap-4 md:grid-cols-2">
                <Button variant="outline" @click="downloadTemplate('csv')" class="w-full">
                    <Download class="mr-2 h-4 w-4" />
                    {{ t('imports.download_csv') }}
                </Button>
                <Button variant="outline" @click="downloadTemplate('xlsx')" class="w-full">
                    <Download class="mr-2 h-4 w-4" />
                    {{ t('imports.download_excel') }}
                </Button>
            </div>

            <div class="rounded-lg border bg-card overflow-hidden">
                <div class="border-b bg-gradient-to-r from-blue-50 to-purple-50 p-4 dark:from-blue-950/20 dark:to-purple-950/20">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-950">
                            <Upload class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h2 class="text-lg font-semibold">{{ t('imports.upload_contacts') }}</h2>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <Input
                            ref="fileInput"
                            :disabled="uploadForm.processing"
                            accept=".csv,.xlsx"
                            class="flex-1"
                            type="file"
                            @change="handleFileChange"
                        />
                        <span v-if="uploadForm.processing" class="text-sm text-muted-foreground">
                            {{ t('imports.processing') }}
                        </span>
                    </div>
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/20">
                        <div class="flex gap-3">
                            <Info class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                            <div class="space-y-2 text-sm">
                                <p class="font-semibold text-blue-900 dark:text-blue-100">{{ t('imports.requirements') }}</p>
                                <ul class="space-y-1 text-blue-800 dark:text-blue-200">
                                    <li>• {{ t('imports.req_format') }}</li>
                                    <li>• {{ t('imports.req_size') }}</li>
                                    <li>• {{ t('imports.req_phone') }}</li>
                                    <li>• {{ t('imports.req_optional') }}</li>
                                    <li>• {{ t('imports.req_headers') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-card overflow-hidden">
                <div class="border-b bg-muted/30 p-4">
                    <div class="flex items-center gap-2">
                        <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                            <FileSpreadsheet class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h2 class="text-lg font-semibold">{{ t('imports.history') }}</h2>
                    </div>
                </div>

                <div v-if="imports.data.length > 0">
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/30">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.filename') }}</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.total') }}</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.valid') }}</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.invalid') }}</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.status') }}</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">{{ t('imports.date') }}</th>
                                <th class="px-6 py-3 text-right text-sm font-medium">{{ t('imports.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y">
                            <tr v-for="item in imports.data" :key="item.id" class="hover:bg-muted/50 transition-colors">
                                <td class="px-6 py-4 font-medium max-w-[200px] truncate">{{ item.filename }}</td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">{{ item.total_rows }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-green-600 dark:text-green-400">{{ item.valid_rows }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-red-600 dark:text-red-400">{{ item.invalid_rows }}</td>
                                <td class="px-6 py-4">
                                    <Badge :variant="statusVariant[item.status]">{{ item.status }}</Badge>
                                </td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">
                                    {{ new Date(item.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/contacts/imports/${item.id}`">
                                            <Button size="sm" variant="ghost">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button size="sm" variant="ghost" class="text-destructive" @click="deleteImport(item.id)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="divide-y md:hidden">
                        <Link
                            v-for="item in imports.data"
                            :key="item.id"
                            :href="`/contacts/imports/${item.id}`"
                            class="flex items-center justify-between p-4 hover:bg-muted/50 transition-colors"
                        >
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-950">
                                    <FileSpreadsheet class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium truncate">{{ item.filename }}</p>
                                    <div class="flex items-center gap-2 text-xs text-muted-foreground mt-1">
                                        <span class="text-green-600 dark:text-green-400">{{ item.valid_rows }} {{ t('imports.valid') }}</span>
                                        <span>•</span>
                                        <span class="text-red-600 dark:text-red-400">{{ item.invalid_rows }} {{ t('imports.invalid') }}</span>
                                    </div>
                                </div>
                            </div>
                            <ArrowUpRight class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                        </Link>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center p-12">
                    <div class="rounded-full bg-muted p-4 mb-4">
                        <FileSpreadsheet class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <h3 class="text-lg font-semibold mb-2">{{ t('imports.no_imports') }}</h3>
                    <p class="text-sm text-muted-foreground">{{ t('imports.upload_to_start') }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
