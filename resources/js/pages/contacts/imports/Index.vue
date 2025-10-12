<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
    ready: 'success',
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

            <!-- Actions -->
            <div class="flex gap-3">
                <Button variant="outline" @click="downloadTemplate('csv')">
                    Download CSV Template
                </Button>
                <Button variant="outline" @click="downloadTemplate('xlsx')">
                    Download Excel Template
                </Button>
            </div>

            <!-- Upload Form -->
            <div class="rounded-lg border p-6">
                <h2 class="mb-4 text-lg font-semibold">Upload Contacts</h2>
                <div class="flex items-center gap-4">
                    <Input
                        ref="fileInput"
                        :disabled="uploadForm.processing"
                        accept=".csv,.xlsx"
                        type="file"
                        @change="handleFileChange"
                    />
                    <span
                        v-if="uploadForm.processing"
                        class="text-sm text-muted-foreground"
                    >
                        Uploading...
                    </span>
                </div>
                <p class="mt-2 text-sm text-muted-foreground">
                    Accepted formats: CSV, Excel (.xlsx). Maximum file size:
                    10MB
                </p>
            </div>

            <!-- Imports Table -->
            <div class="rounded-lg border">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="p-4 text-left font-medium">
                                    Filename
                                </th>
                                <th class="p-4 text-left font-medium">Total</th>
                                <th class="p-4 text-left font-medium">Valid</th>
                                <th class="p-4 text-left font-medium">
                                    Invalid
                                </th>
                                <th class="p-4 text-left font-medium">
                                    Status
                                </th>
                                <th class="p-4 text-left font-medium">Date</th>
                                <th class="p-4 text-left font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in imports.data"
                                :key="item.id"
                                class="border-b hover:bg-muted/30"
                            >
                                <td class="p-4">{{ item.filename }}</td>
                                <td class="p-4">{{ item.total_rows }}</td>
                                <td class="p-4 text-green-600">
                                    {{ item.valid_rows }}
                                </td>
                                <td class="p-4 text-red-600">
                                    {{ item.invalid_rows }}
                                </td>
                                <td class="p-4">
                                    <Badge :variant="statusColor[item.status]">
                                        {{ item.status }}
                                    </Badge>
                                </td>
                                <td class="p-4 text-sm text-muted-foreground">
                                    {{
                                        new Date(
                                            item.created_at,
                                        ).toLocaleDateString()
                                    }}
                                </td>
                                <td class="p-4">
                                    <div class="flex gap-2">
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
                    v-if="imports.data.length === 0"
                    class="flex flex-col items-center justify-center p-12 text-center"
                >
                    <p class="text-lg font-medium">No imports yet</p>
                    <p class="text-sm text-muted-foreground">
                        Upload a contact file to get started
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
