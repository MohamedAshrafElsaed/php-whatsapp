<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, onUnmounted, ref, watch } from 'vue';

interface Session {
    id: number;
    status: 'pending' | 'connected' | 'expired' | 'disconnected';
    meta_json: {
        qr_base64?: string;
        phone?: string;
        name?: string;
        avatar?: string;
        pairing_code?: string;
        pairing_phone?: string;
        method?: 'qr' | 'pairing';
    };
    last_seen_at: string;
    expires_at: string;
}

const props = defineProps<{
    session: Session | null;
}>();

const currentSession = ref(props.session);
const polling = ref<number | null>(null);
const isLoading = ref(false);
const connectionMethod = ref<'qr' | 'pairing'>('qr');
const pairingPhone = ref('');

const statusColor = {
    pending: 'warning',
    connected: 'success',
    expired: 'destructive',
    disconnected: 'secondary',
};

const connectWhatsApp = () => {
    if (connectionMethod.value === 'pairing') {
        connectWithPairing();
    } else {
        connectWithQr();
    }
};

const connectWithQr = () => {
    isLoading.value = true;
    router.post(
        '/wa/session',
        {},
        {
            onFinish: () => {
                isLoading.value = false;
            },
            onSuccess: () => {
                startPolling();
            },
        },
    );
};

const connectWithPairing = () => {
    if (!pairingPhone.value || pairingPhone.value.length < 10) {
        alert('Please enter a valid phone number');
        return;
    }

    isLoading.value = true;
    router.post(
        '/wa/session/pairing',
        {
            phone: pairingPhone.value,
        },
        {
            onFinish: () => {
                isLoading.value = false;
            },
            onSuccess: () => {
                startPolling();
            },
        },
    );
};

const refreshQr = () => {
    isLoading.value = true;
    router.post(
        '/wa/session/refresh',
        {},
        {
            onFinish: () => {
                isLoading.value = false;
            },
            onSuccess: () => {
                startPolling();
            },
        },
    );
};

const disconnect = () => {
    if (confirm('Are you sure you want to disconnect WhatsApp?')) {
        isLoading.value = true;
        router.delete('/wa/session', {
            onFinish: () => {
                isLoading.value = false;
            },
        });
    }
};

const pollStatus = async () => {
    try {
        const response = await axios.get('/wa/session/status');
        const newSession = response.data.session;

        // Update session
        currentSession.value = newSession;

        // Stop polling if connected or disconnected
        if (
            !newSession ||
            newSession.status === 'connected' ||
            newSession.status === 'disconnected'
        ) {
            stopPolling();
        }
    } catch (error) {
        console.error('Polling error:', error);
    }
};

const startPolling = () => {
    stopPolling(); // Clear any existing interval

    // Only poll if session exists and is pending
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

// Watch for session prop changes from Inertia page visits
watch(
    () => props.session,
    (newSession) => {
        currentSession.value = newSession;

        // Start polling only if status is pending
        if (newSession?.status === 'pending') {
            startPolling();
        } else {
            stopPolling();
        }
    },
);

onMounted(() => {
    // Start polling only if current session is pending
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
                        <Badge
                            :variant="statusColor[currentSession.status]"
                            class="mt-2"
                        >
                            {{ currentSession.status.toUpperCase() }}
                        </Badge>
                    </div>

                    <!-- Connected Account Info -->
                    <div
                        v-if="
                            currentSession.status === 'connected' &&
                            currentSession.meta_json
                        "
                        class="flex items-center gap-3"
                    >
                        <img
                            v-if="currentSession.meta_json.avatar"
                            :src="currentSession.meta_json.avatar"
                            class="h-12 w-12 rounded-full"
                        />
                        <div>
                            <div class="font-medium">
                                {{ currentSession.meta_json.name }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{ currentSession.meta_json.phone }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Seen -->
                <div
                    v-if="currentSession.last_seen_at"
                    class="mt-4 text-sm text-muted-foreground"
                >
                    Last seen:
                    {{ new Date(currentSession.last_seen_at).toLocaleString() }}
                </div>
            </div>

            <!-- Connection Method Selection -->
            <div
                v-if="!currentSession || currentSession.status !== 'connected'"
                class="space-y-4"
            >
                <!-- Method Selector -->
                <div class="rounded-lg border p-6">
                    <h3 class="mb-4 font-semibold">Choose Connection Method:</h3>
                    <div class="flex gap-3">
                        <Button
                            :variant="connectionMethod === 'qr' ? 'default' : 'outline'"
                            @click="connectionMethod = 'qr'"
                        >
                            QR Code
                        </Button>
                        <Button
                            :variant="connectionMethod === 'pairing' ? 'default' : 'outline'"
                            @click="connectionMethod = 'pairing'"
                        >
                            Pairing Code
                        </Button>
                    </div>
                </div>

                <!-- QR Code Method Instructions -->
                <div
                    v-if="connectionMethod === 'qr'"
                    class="rounded-lg bg-muted p-6"
                >
                    <h3 class="mb-3 font-semibold">How to Connect with QR Code:</h3>
                    <ol class="list-decimal space-y-2 pl-5">
                        <li>Click "Generate QR Code" button below</li>
                        <li>Open WhatsApp on your phone</li>
                        <li>Tap Menu or Settings → Linked Devices</li>
                        <li>Tap "Link a Device"</li>
                        <li>Scan the QR code that appears below</li>
                    </ol>
                </div>

                <!-- Pairing Code Method Instructions -->
                <div
                    v-if="connectionMethod === 'pairing'"
                    class="space-y-4 rounded-lg bg-muted p-6"
                >
                    <h3 class="font-semibold">How to Connect with Pairing Code:</h3>
                    <ol class="list-decimal space-y-2 pl-5">
                        <li>Enter your phone number below</li>
                        <li>Click "Generate Pairing Code" button</li>
                        <li>Open WhatsApp on your phone</li>
                        <li>Tap Menu or Settings → Linked Devices</li>
                        <li>Tap "Link a Device"</li>
                        <li>Tap "Link with phone number instead"</li>
                        <li>Enter the 8-digit code shown below</li>
                    </ol>

                    <div class="space-y-2">
                        <Label for="pairing-phone">
                            Phone Number (with country code)
                        </Label>
                        <Input
                            id="pairing-phone"
                            v-model="pairingPhone"
                            type="tel"
                            placeholder="+201234567890"
                            :disabled="isLoading"
                        />
                        <p class="text-xs text-muted-foreground">
                            Include country code (e.g., +20 for Egypt)
                        </p>
                    </div>
                </div>

                <!-- QR Code Display -->
                <div
                    v-if="
                        currentSession?.status === 'pending' &&
                        currentSession?.meta_json?.method === 'qr' &&
                        currentSession?.meta_json?.qr_base64
                    "
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

                <!-- Pairing Code Display -->
                <div
                    v-if="
                        currentSession?.status === 'pending' &&
                        currentSession?.meta_json?.method === 'pairing' &&
                        currentSession?.meta_json?.pairing_code
                    "
                    class="flex flex-col items-center space-y-4 rounded-lg border bg-primary/5 p-6"
                >
                    <h3 class="font-semibold">Your Pairing Code</h3>
                    <div
                        class="rounded-lg bg-background px-8 py-6 text-center font-mono text-4xl font-bold tracking-widest"
                    >
                        {{ currentSession.meta_json.pairing_code }}
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Enter this code on your phone: {{ currentSession.meta_json.pairing_phone }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Code expires in 5 minutes
                    </p>
                </div>

                <!-- Connect Button -->
                <div class="flex justify-center">
                    <Button
                        v-if="
                            !currentSession ||
                            currentSession.status === 'disconnected'
                        "
                        :disabled="isLoading"
                        size="lg"
                        @click="connectWhatsApp"
                    >
                        <span v-if="isLoading">
                            {{
                                connectionMethod === 'qr'
                                    ? 'Generating QR Code...'
                                    : 'Generating Pairing Code...'
                            }}
                        </span>
                        <span v-else>
                            {{
                                connectionMethod === 'qr'
                                    ? 'Generate QR Code'
                                    : 'Generate Pairing Code'
                            }}
                        </span>
                    </Button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <Button
                    v-if="
                        currentSession?.status === 'expired' &&
                        currentSession?.meta_json?.method === 'qr'
                    "
                    :disabled="isLoading"
                    variant="outline"
                    @click="refreshQr"
                >
                    <span v-if="isLoading">Refreshing...</span>
                    <span v-else>Refresh QR Code</span>
                </Button>

                <Button
                    v-if="currentSession?.status === 'connected'"
                    :disabled="isLoading"
                    variant="destructive"
                    @click="disconnect"
                >
                    <span v-if="isLoading">Disconnecting...</span>
                    <span v-else>Disconnect</span>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
