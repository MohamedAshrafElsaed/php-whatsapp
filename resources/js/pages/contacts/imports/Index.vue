<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Upload, FileSpreadsheet, Download } from 'lucide-vue-next';
import { ref, computed } from 'vue';

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

const viewImport = (importId: number) => {
    router.visit(`/contacts/imports/${importId}`);
};

const deleteImport = (importId: number) => {
    if (confirm('Are you sure you want to delete this import?')) {
        router.delete(`/contacts/imports/${importId}`);
    }
};

const statusColor: Record<string, string> = {
    pending: 'secondary',
    validated: 'warning',
    ready: 'default',
    deleted: 'destructive',
};
</script>

<template>
    <AppLayout>
        <Head title="Contact Imports" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Contact Imports</h1>
                    <p class="text-muted-foreground">
                        Upload and manage your contact lists
                    </p>
                </div>
            </div>

            <!-- Success Alert -->
            <Alert v-if="flashSuccess" variant="default" class="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950">
                <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                <AlertTitle class="text-green-800 dark:text-green-200">Success</AlertTitle>
                <AlertDescription class="text-green-700 dark:text-green-300">
                    {{ flashSuccess }}
                </AlertDescription>
            </Alert>

            <!-- Error Alert -->
            <Alert v-if="flashError" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>
                    {{ flashError }}
                </AlertDescription>
            </Alert>

            <!-- Upload Form Errors -->
            <Alert v-if="uploadForm.errors.file" variant="destructive">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Upload Error</AlertTitle>
                <AlertDescription>
                    {{ uploadForm.errors.file }}
                </AlertDescription>
            </Alert>

            <!-- Actions -->
            <div class="flex gap-3">
                <Button variant="outline" @click="downloadTemplate('csv')">
                    <Download class="mr-2 h-4 w-4" />
                    Download CSV Template
                </Button>
                <Button variant="outline" @click="downloadTemplate('xlsx')">
                    <Download class="mr-2 h-4 w-4" />
                    Download Excel Template
                </Button>
            </div>

            <!-- Upload Form -->
            <div class="rounded-lg border bg-card p-6">
                <div class="mb-4 flex items-center gap-2">
                    <Upload class="h-5 w-5 text-muted-foreground" />
                    <h2 class="text-lg font-semibold">Upload Contacts</h2>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center gap-4">
                        <Input
                            ref="fileInput"
                            :disabled="uploadForm.processing"
                            accept=".csv,.xlsx"
                            type="file"
                            class="flex-1"
                            @change="handleFileChange"
                        />
                        <span
                            v-if="uploadForm.processing"
                            class="text-sm text-muted-foreground"
                        >
                            Processing...
                        </span>
                    </div>
                    <div class="rounded-md bg-muted/50 p-4">
                        <p class="mb-2 text-sm font-medium">File Requirements:</p>
                        <ul class="space-y-1 text-sm text-muted-foreground">
                            <li>• Accepted formats: CSV or Excel (.xlsx)</li>
                            <li>• Maximum file size: 10MB</li>
                            <li>• Required column: <span class="font-mono font-semibold">phone</span> (with country code, e.g., +1234567890)</li>
                            <li>• Optional columns: <span class="font-mono">first_name</span>, <span class="font-mono">last_name</span>, <span class="font-mono">email</span></li>
                            <li>• First row must contain column headers</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Imports Table -->
            <div class="rounded-lg border bg-card">
                <div class="border-b bg-muted/50 p-4">
                    <div class="flex items-center gap-2">
                        <FileSpreadsheet class="h-5 w-5 text-muted-foreground" />
                        <h2 class="text-lg font-semibold">Import History</h2>
                    </div>
                </div>

                <div v-if="imports.data.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/30">
                        <tr>
                            <th class="p-4 text-left font-medium">Filename</th>
                            <th class="p-4 text-left font-medium">Total</th>
                            <th class="p-4 text-left font-medium">Valid</th>
                            <th class="p-4 text-left font-medium">Invalid</th>
                            <th class="p-4 text-left font-medium">Status</th>
                            <th class="p-4 text-left font-medium">Date</th>
                            <th class="p-4 text-right font-medium">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        <tr
                            v-for="item in imports.data"
                            :key="item.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="p-4 font-medium">{{ item.filename }}</td>
                            <td class="p-4">{{ item.total_rows }}</td>
                            <td class="p-4 text-green-600 dark:text-green-400">
                                {{ item.valid_rows }}
                            </td>
                            <td class="p-4 text-red-600 dark:text-red-400">
                                {{ item.invalid_rows }}
                            </td>
                            <td class="p-4">
                                <Badge :variant="statusColor[item.status]">
                                    {{ item.status }}
                                </Badge>
                            </td>
                            <td class="p-4 text-sm text-muted-foreground">
                                {{ new Date(item.created_at).toLocaleDateString() }}
                            </td>
                            <td class="p-4">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="viewImport(item.id)"
                                    >
                                        View
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="deleteImport(item.id)"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center p-12 text-center"
                >
                    <FileSpreadsheet class="mb-4 h-12 w-12 text-muted-foreground" />
                    <p class="text-lg font-medium">No imports yet</p>
                    <p class="text-sm text-muted-foreground">
                        Upload a contact file to get started
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
