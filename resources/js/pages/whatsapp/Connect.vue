<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';

interface Session {
    id: number;
    status: 'pending' | 'connected' | 'expired' | 'disconnected';
    meta_json: {
        qr_base64?: string;
        phone?: string;
        name?: string;
        avatar?: string;
    };
    last_seen_at: string;
    expires_at: string;
}

const props = defineProps<{
    session: Session | null;
}>();

const currentSession = ref(props.session);
const polling = ref<number | null>(null);

const statusColor = {
    pending: 'warning',
    connected: 'success',
    expired: 'destructive',
    disconnected: 'secondary',
};

const connectWhatsApp = () => {
    router.post('/wa/session');
};

const refreshQr = () => {
    router.post('/wa/session/refresh');
};

const disconnect = () => {
    if (confirm('Are you sure you want to disconnect WhatsApp?')) {
        router.delete('/wa/session');
    }
};

const pollStatus = async () => {
    try {
        const response = await axios.get('/wa/session/status');
        currentSession.value = response.data.session;

        if (response.data.session?.status === 'connected') {
            stopPolling();
        }
    } catch (error) {
        console.error('Polling error:', error);
    }
};

const startPolling = () => {
    if (currentSession.value?.status === 'pending') {
        polling.value = window.setInterval(pollStatus, 3000);
    }
};

const stopPolling = () => {
    if (polling.value) {
        clearInterval(polling.value);
        polling.value = null;
    }
};

onMounted(() => {
    if (currentSession.value?.status === 'pending') {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <AppLayout>
        <Head title="WhatsApp Connection" />

        <div class="mx-auto max-w-2xl space-y-6 p-6">
            <div>
                <h1 class="text-2xl font-bold">WhatsApp Connection</h1>
                <p class="text-muted-foreground">
                    Connect your WhatsApp account to send bulk messages
                </p>
            </div>

            <!-- Current Status -->
            <div v-if="currentSession" class="rounded-lg border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Connection Status</h2>
                        <Badge :variant="statusColor[currentSession.status]" class="mt-2">
                            {{ currentSession.status.toUpperCase() }}
                        </Badge>
                    </div>

                    <!-- Connected Account Info -->
                    <div
                        v-if="currentSession.status === 'connected' && currentSession.meta_json"
                        class="flex items-center gap-3"
                    >
                        <img
                            v-if="currentSession.meta_json.avatar"
                            :src="currentSession.meta_json.avatar"
                            class="h-12 w-12 rounded-full"
                        />
                        <div>
                            <div class="font-medium">{{ currentSession.meta_json.name }}</div>
                            <div class="text-sm text-muted-foreground">
                                {{ currentSession.meta_json.phone }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Seen -->
                <div v-if="currentSession.last_seen_at" class="mt-4 text-sm text-muted-foreground">
                    Last seen: {{ new Date(currentSession.last_seen_at).toLocaleString() }}
                </div>
            </div>

            <!-- Instructions -->
            <div v-if="!currentSession || currentSession.status !== 'connected'" class="space-y-4">
                <div class="rounded-lg bg-muted p-6">
                    <h3 class="mb-3 font-semibold">How to Connect:</h3>
                    <ol class="list-decimal space-y-2 pl-5">
                        <li>Open WhatsApp on your phone</li>
                        <li>Tap Menu or Settings → Linked Devices</li>
                        <li>Tap "Link a Device"</li>
                        <li>Scan the QR code below with your phone</li>
                    </ol>
                </div>

                <!-- QR Code -->
                <div
                    v-if="currentSession?.status === 'pending' && currentSession?.meta_json?.qr_base64"
                    class="flex flex-col items-center space-y-4 rounded-lg border p-6"
                >
                    <h3 class="font-semibold">Scan QR Code</h3>
                    <img
                        :src="currentSession.meta_json.qr_base64"
                        alt="WhatsApp QR Code"
                        class="h-64 w-64 border"
                    />
                    <p class="text-sm text-muted-foreground">
                        QR code expires in 5 minutes
                    </p>
                </div>

                <!-- Connect Button -->
                <div class="flex justify-center">
                    <Button
                        v-if="!currentSession || currentSession.status === 'disconnected'"
                        @click="connectWhatsApp"
                        size="lg"
                    >
                        Generate QR Code
                    </Button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <Button
                    v-if="currentSession?.status === 'expired'"
                    @click="refreshQr"
                    variant="outline"
                >
                    Refresh QR Code
                </Button>

                <Button
                    v-if="currentSession?.status === 'connected'"
                    @click="disconnect"
                    variant="destructive"
                >
                    Disconnect
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
