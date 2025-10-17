<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ShieldCheck } from 'lucide-vue-next';
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
        '/w/session',
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
        '/w/session/pairing',
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
        '/w/session/refresh',
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
        router.delete('/w/session', {
            onFinish: () => {
                isLoading.value = false;
            },
        });
    }
};

const pollStatus = async () => {
    try {
        const response = await axios.get('/w/session/status');
        const newSession = response.data.session;

        currentSession.value = newSession;

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
    stopPolling();

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

watch(
    () => props.session,
    (newSession) => {
        currentSession.value = newSession;

        if (newSession?.status === 'pending') {
            startPolling();
        } else {
            stopPolling();
        }
    },
);

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
    <Head>
        <title>Connect WhatsApp - Secure Connection</title>
        <meta
            content="Securely connect your WhatsApp Business account. Official WhatsApp Web API integration."
            name="description"
        />
        <meta content="noindex, nofollow" name="robots" />
        <meta content="WhatsApp Business Connection" property="og:title" />
        <meta
            content="Secure WhatsApp Business API integration"
            property="og:description"
        />
    </Head>
    <AppLayout>
        <Head title="WhatsApp Connection" />

        <div class="mx-auto max-w-2xl space-y-6 p-4 md:p-6">
            <!-- Trust badges -->
            <div
                class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20"
            >
                <div class="flex items-center gap-3">
                    <ShieldCheck
                        class="h-5 w-5 flex-shrink-0 text-blue-600 md:h-6 md:w-6"
                    />
                    <div>
                        <h3
                            class="text-sm font-semibold text-blue-900 md:text-base dark:text-blue-100"
                        >
                            Secure Connection
                        </h3>
                        <p
                            class="text-xs text-blue-700 md:text-sm dark:text-blue-300"
                        >
                            This page uses official WhatsApp Web API. Your data
                            is encrypted and secure.
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <h1 class="text-xl font-bold md:text-2xl">
                    WhatsApp Connection
                </h1>
                <p class="text-sm text-muted-foreground md:text-base">
                    Connect your WhatsApp account to send bulk messages
                </p>
            </div>

            <!-- Current Status -->
            <div v-if="currentSession" class="rounded-lg border p-4 md:p-6">
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="text-base font-semibold md:text-lg">
                            Connection Status
                        </h2>
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
                            class="h-10 w-10 rounded-full md:h-12 md:w-12"
                        />
                        <div>
                            <div class="text-sm font-medium md:text-base">
                                {{ currentSession.meta_json.name }}
                            </div>
                            <div
                                class="text-xs text-muted-foreground md:text-sm"
                            >
                                {{ currentSession.meta_json.phone }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Seen -->
                <div
                    v-if="currentSession.last_seen_at"
                    class="mt-4 text-xs text-muted-foreground md:text-sm"
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
                <div class="rounded-lg border p-4 md:p-6">
                    <h3 class="mb-4 text-sm font-semibold md:text-base">
                        Choose Connection Method:
                    </h3>
                    <div class="flex flex-wrap gap-3">
                        <Button
                            :variant="
                                connectionMethod === 'qr'
                                    ? 'default'
                                    : 'outline'
                            "
                            class="min-w-[120px] flex-1"
                            @click="connectionMethod = 'qr'"
                        >
                            QR Code
                        </Button>
                        <Button
                            :variant="
                                connectionMethod === 'pairing'
                                    ? 'default'
                                    : 'outline'
                            "
                            class="min-w-[120px] flex-1"
                            @click="connectionMethod = 'pairing'"
                        >
                            Pairing Code
                        </Button>
                    </div>
                </div>

                <!-- QR Code Method Instructions -->
                <div
                    v-if="connectionMethod === 'qr'"
                    class="rounded-lg bg-muted p-4 md:p-6"
                >
                    <h3 class="mb-3 text-sm font-semibold md:text-base">
                        How to Connect with QR Code:
                    </h3>
                    <ol
                        class="list-decimal space-y-2 pl-5 text-sm md:text-base"
                    >
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
                    class="space-y-4 rounded-lg bg-muted p-4 md:p-6"
                >
                    <h3 class="text-sm font-semibold md:text-base">
                        How to Connect with Pairing Code:
                    </h3>
                    <ol
                        class="list-decimal space-y-2 pl-5 text-sm md:text-base"
                    >
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
                            :disabled="isLoading"
                            placeholder="+201234567890"
                            type="tel"
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
                    class="flex flex-col items-center space-y-4 rounded-lg border p-4 md:p-6"
                >
                    <h3 class="text-sm font-semibold md:text-base">
                        Scan QR Code
                    </h3>
                    <img
                        :src="currentSession.meta_json.qr_base64"
                        alt="WhatsApp QR Code"
                        class="h-48 w-48 border md:h-64 md:w-64"
                    />
                    <p class="text-xs text-muted-foreground md:text-sm">
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
                    class="flex flex-col items-center space-y-4 rounded-lg border bg-primary/5 p-4 md:p-6"
                >
                    <h3 class="text-sm font-semibold md:text-base">
                        Your Pairing Code
                    </h3>
                    <div
                        class="rounded-lg bg-background px-6 py-4 text-center font-mono text-2xl font-bold tracking-widest md:px-8 md:py-6 md:text-4xl"
                    >
                        {{ currentSession.meta_json.pairing_code }}
                    </div>
                    <p
                        class="text-center text-xs text-muted-foreground md:text-sm"
                    >
                        Enter this code on your phone:
                        {{ currentSession.meta_json.pairing_phone }}
                    </p>
                    <p class="text-xs text-muted-foreground md:text-sm">
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
                        class="w-full sm:w-auto"
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
            <div class="flex flex-wrap gap-3">
                <Button
                    v-if="
                        currentSession?.status === 'expired' &&
                        currentSession?.meta_json?.method === 'qr'
                    "
                    :disabled="isLoading"
                    class="min-w-[140px] flex-1"
                    variant="outline"
                    @click="refreshQr"
                >
                    <span v-if="isLoading">Refreshing...</span>
                    <span v-else>Refresh QR Code</span>
                </Button>

                <Button
                    v-if="currentSession?.status === 'connected'"
                    :disabled="isLoading"
                    class="min-w-[140px] flex-1"
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
