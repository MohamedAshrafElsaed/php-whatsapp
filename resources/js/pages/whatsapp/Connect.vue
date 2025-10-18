<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Plus,
    ShieldCheck,
    Star,
    Trash2,
    Smartphone,
    RefreshCw,
    Timer,
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, computed } from 'vue';

interface Session {
    id: number;
    device_id: string;
    device_label: string;
    is_primary: boolean;
    status: 'pending' | 'connected' | 'expired' | 'disconnected';
    meta_json: {
        qr_link?: string;
        phone?: string;
        name?: string;
        platform?: string;
        pairing_code?: string;
        pairing_phone?: string;
        method?: 'qr' | 'pairing';
        qr_duration?: number;
    };
    bridge_instance_url?: string;
    bridge_instance_port?: number;
    last_seen_at: string;
    expires_at: string;
    created_at: string;
}

const props = defineProps<{
    sessions: Session[];
    maxDevices: number;
}>();

const currentSessions = ref(props.sessions);
const polling = ref<number | null>(null);
const isLoading = ref(false);
const showAddDevice = ref(false);
const connectionMethod = ref<'qr' | 'pairing'>('qr');
const pairingPhone = ref('');
const deviceLabel = ref('');
const qrCode = ref<string | null>(null);
const pairingCode = ref<string | null>(null);
const currentDeviceId = ref<string | null>(null);

// QR Expiry Tracking
const qrTimeRemaining = ref<number>(0);
const qrExpiryInterval = ref<number | null>(null);

const statusColor = {
    pending: 'warning',
    connected: 'success',
    expired: 'destructive',
    disconnected: 'secondary',
};

const connectedCount = computed(() => {
    return currentSessions.value.filter((s) => s.status === 'connected').length;
});

const canAddDevice = computed(() => {
    return currentSessions.value.length < props.maxDevices;
});

const isQrExpired = computed(() => {
    return qrTimeRemaining.value <= 0 && qrCode.value !== null;
});

/**
 * Format phone number to display format
 */
const formatPhoneNumber = (phone: string | undefined): string => {
    if (!phone) return 'N/A';

    // Remove @s.whatsapp.net suffix
    const cleanPhone = phone.replace(/:.*@s\.whatsapp\.net$/, '').replace('@s.whatsapp.net', '');

    // Format with country code
    if (cleanPhone.startsWith('+')) {
        return cleanPhone;
    }
    return `+${cleanPhone}`;
};

/**
 * Start QR expiry countdown
 */
const startQrExpiryCountdown = (expiresIn: number) => {
    stopQrExpiryCountdown();
    qrTimeRemaining.value = expiresIn;

    qrExpiryInterval.value = window.setInterval(() => {
        qrTimeRemaining.value--;
        if (qrTimeRemaining.value <= 0) {
            stopQrExpiryCountdown();
        }
    }, 1000);
};

/**
 * Stop QR expiry countdown
 */
const stopQrExpiryCountdown = () => {
    if (qrExpiryInterval.value) {
        clearInterval(qrExpiryInterval.value);
        qrExpiryInterval.value = null;
    }
    qrTimeRemaining.value = 0;
};

/**
 * Refresh QR code
 */
const refreshQrCode = async () => {
    if (!currentDeviceId.value || isLoading.value) return;

    isLoading.value = true;
    try {
        const response = await axios.post(
            `/w/session/${currentDeviceId.value}/refresh-qr`,
        );

        if (response.data.success) {
            qrCode.value = response.data.qr_code;
            startQrExpiryCountdown(response.data.expires_in || 30);
        } else {
            alert('Failed to refresh QR code');
        }
    } catch (error: any) {
        console.error('QR refresh error:', error);
        alert(error.response?.data?.error || 'Failed to refresh QR code');
    } finally {
        isLoading.value = false;
    }
};

const generateQrCode = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    qrCode.value = null;
    stopQrExpiryCountdown();

    try {
        const response = await axios.post('/w/session/qr', {
            device_label: deviceLabel.value || undefined,
        });

        if (response.data.success) {
            qrCode.value = response.data.qr_code;
            currentDeviceId.value = response.data.device_id;
            startQrExpiryCountdown(response.data.expires_in || 30);
            startPolling();
        } else {
            alert('Failed to generate QR code');
        }
    } catch (error: any) {
        console.error('QR generation error:', error);
        alert(error.response?.data?.error || 'Failed to generate QR code');
    } finally {
        isLoading.value = false;
    }
};

const generatePairingCode = async () => {
    if (isLoading.value) return;

    if (!pairingPhone.value || pairingPhone.value.length < 10) {
        alert('Please enter a valid phone number');
        return;
    }

    isLoading.value = true;
    pairingCode.value = null;

    try {
        const response = await axios.post('/w/session/pairing', {
            phone: pairingPhone.value,
            device_label: deviceLabel.value || undefined,
        });

        if (response.data.success) {
            pairingCode.value = response.data.pairing_code;
            currentDeviceId.value = response.data.device_id;
            startPolling();
        } else {
            alert('Failed to generate pairing code');
        }
    } catch (error: any) {
        console.error('Pairing generation error:', error);
        alert(error.response?.data?.error || 'Failed to generate pairing code');
    } finally {
        isLoading.value = false;
    }
};

const disconnectDevice = (deviceId: string) => {
    if (confirm('Are you sure you want to disconnect this device?')) {
        isLoading.value = true;
        router.delete(`/w/session/${deviceId}`, {
            onFinish: () => {
                isLoading.value = false;
            },
        });
    }
};

const setPrimaryDevice = async (deviceId: string) => {
    try {
        const response = await axios.post(`/w/session/${deviceId}/set-primary`);
        if (response.data.success) {
            pollStatus();
        }
    } catch (error: any) {
        alert('Failed to set primary device');
    }
};

const pollStatus = async () => {
    try {
        const response = await axios.get('/w/session/status');
        currentSessions.value = response.data.sessions;

        // If current device connected, stop polling and reset
        if (currentDeviceId.value) {
            const currentSession = currentSessions.value.find(
                (s) => s.device_id === currentDeviceId.value,
            );
            if (currentSession?.status === 'connected') {
                console.log('Device connected successfully!');
                stopPolling();
                stopQrExpiryCountdown();
                resetAddDevice();

                // Show success message
                alert('WhatsApp device connected successfully!');
            }
        }
    } catch (error) {
        console.error('Polling error:', error);
    }
};

const startPolling = () => {
    stopPolling();
    // Poll immediately
    pollStatus();
    // Then poll every 3 seconds
    polling.value = window.setInterval(pollStatus, 3000);
};

const stopPolling = () => {
    if (polling.value) {
        clearInterval(polling.value);
        polling.value = null;
    }
};

const resetAddDevice = () => {
    showAddDevice.value = false;
    qrCode.value = null;
    pairingCode.value = null;
    deviceLabel.value = '';
    pairingPhone.value = '';
    currentDeviceId.value = null;
    stopQrExpiryCountdown();
};

onMounted(() => {
    const pendingSessions = currentSessions.value.filter((s) => s.status === 'pending');
    if (pendingSessions.length > 0) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
    stopQrExpiryCountdown();
});
</script>

<template>
    <Head>
        <title>WhatsApp Devices - Manage Multiple Accounts</title>
        <meta
            content="Manage multiple WhatsApp devices and accounts"
            name="description"
        />
    </Head>
    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-4 p-4 md:space-y-6 md:p-6">
            <!-- Trust Badge -->
            <div
                class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20 md:p-4"
            >
                <div class="flex items-center gap-3">
                    <ShieldCheck class="h-5 w-5 shrink-0 text-blue-600 md:h-6 md:w-6" />
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 md:text-base">
                            Secure Multi-Device Connection
                        </h3>
                        <p class="text-xs text-blue-700 dark:text-blue-300 md:text-sm">
                            Connect up to {{ maxDevices }} WhatsApp accounts. All data
                            encrypted.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold md:text-2xl">WhatsApp Devices</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ connectedCount }} of {{ currentSessions.length }} devices
                        connected
                    </p>
                </div>

                <Button
                    v-if="canAddDevice && !showAddDevice"
                    class="w-full sm:w-auto"
                    :disabled="isLoading"
                    @click="showAddDevice = true"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Add Device
                </Button>
            </div>

            <!-- Add New Device Card -->
            <Card v-if="showAddDevice">
                <CardHeader class="p-4 md:p-6">
                    <CardTitle class="text-lg md:text-xl">Add New Device</CardTitle>
                    <CardDescription class="text-sm">
                        Connect another WhatsApp account
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4 p-4 md:p-6">
                    <!-- Device Label -->
                    <div class="space-y-2">
                        <Label for="device-label">Device Label (Optional)</Label>
                        <Input
                            id="device-label"
                            v-model="deviceLabel"
                            :disabled="isLoading"
                            placeholder="e.g., Work Phone, Support Account"
                        />
                    </div>

                    <!-- Method Selector -->
                    <div class="space-y-2">
                        <Label>Connection Method</Label>
                        <div class="grid grid-cols-2 gap-2">
                            <Button
                                :variant="connectionMethod === 'qr' ? 'default' : 'outline'"
                                @click="
                                    connectionMethod = 'qr';
                                    pairingCode = null;
                                "
                            >
                                QR Code
                            </Button>
                            <Button
                                :variant="
                                    connectionMethod === 'pairing' ? 'default' : 'outline'
                                "
                                @click="
                                    connectionMethod = 'pairing';
                                    qrCode = null;
                                    stopQrExpiryCountdown();
                                "
                            >
                                Pairing Code
                            </Button>
                        </div>
                    </div>

                    <!-- QR Code Method -->
                    <div v-if="connectionMethod === 'qr'" class="space-y-4">
                        <div v-if="qrCode" class="flex flex-col items-center space-y-4">
                            <div class="relative">
                                <img
                                    :src="qrCode"
                                    alt="WhatsApp QR Code"
                                    class="h-48 w-48 border md:h-64 md:w-64"
                                    :class="{
                                        'opacity-50 grayscale': isQrExpired,
                                    }"
                                />
                                <div
                                    v-if="isQrExpired"
                                    class="absolute inset-0 flex items-center justify-center bg-black/50"
                                >
                                    <Button
                                        size="sm"
                                        :disabled="isLoading"
                                        @click="refreshQrCode"
                                    >
                                        <RefreshCw
                                            class="mr-2 h-4 w-4"
                                            :class="{ 'animate-spin': isLoading }"
                                        />
                                        Refresh
                                    </Button>
                                </div>
                            </div>

                            <!-- Timer -->
                            <div class="flex items-center gap-2 text-sm">
                                <Timer class="h-4 w-4" />
                                <span
                                    :class="{
                                        'text-red-600': qrTimeRemaining <= 10,
                                        'text-yellow-600':
                                            qrTimeRemaining > 10 &&
                                            qrTimeRemaining <= 20,
                                    }"
                                >
                                    {{
                                        isQrExpired
                                            ? 'QR Code Expired'
                                            : `Expires in ${qrTimeRemaining}s`
                                    }}
                                </span>
                            </div>

                            <Button
                                v-if="!isQrExpired"
                                size="sm"
                                variant="outline"
                                :disabled="isLoading"
                                @click="refreshQrCode"
                            >
                                <RefreshCw
                                    class="mr-2 h-4 w-4"
                                    :class="{ 'animate-spin': isLoading }"
                                />
                                Refresh Now
                            </Button>
                        </div>

                        <Button
                            v-if="!qrCode"
                            class="w-full"
                            :disabled="isLoading"
                            @click="generateQrCode"
                        >
                            {{ isLoading ? 'Generating...' : 'Generate QR Code' }}
                        </Button>
                    </div>

                    <!-- Pairing Code Method -->
                    <div v-if="connectionMethod === 'pairing'" class="space-y-4">
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
                        </div>

                        <div
                            v-if="pairingCode"
                            class="flex flex-col items-center space-y-4"
                        >
                            <div
                                class="rounded-lg bg-primary/10 px-6 py-4 font-mono text-3xl font-bold md:px-8 md:py-6 md:text-4xl"
                            >
                                {{ pairingCode }}
                            </div>
                            <p class="text-center text-sm text-muted-foreground">
                                Enter this code on {{ pairingPhone }}
                            </p>
                        </div>

                        <Button
                            class="w-full"
                            :disabled="isLoading"
                            @click="generatePairingCode"
                        >
                            {{ isLoading ? 'Generating...' : 'Generate Pairing Code' }}
                        </Button>
                    </div>

                    <Button class="w-full" variant="outline" @click="resetAddDevice">
                        Cancel
                    </Button>
                </CardContent>
            </Card>

            <!-- Connected Devices List -->
            <div class="grid gap-3 md:grid-cols-2 md:gap-4">
                <Card v-for="session in currentSessions" :key="session.id" class="overflow-hidden">
                    <CardHeader class="space-y-0 p-4 pb-3 md:p-6 md:pb-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 flex-1 items-start gap-2">
                                <Smartphone class="mt-0.5 h-4 w-4 shrink-0 md:h-5 md:w-5" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <CardTitle class="text-base md:text-lg">
                                            {{ session.device_label }}
                                        </CardTitle>
                                        <Star
                                            v-if="session.is_primary"
                                            class="h-3.5 w-3.5 shrink-0 fill-yellow-400 text-yellow-400 md:h-4 md:w-4"
                                        />
                                    </div>
                                </div>
                            </div>
                            <Badge
                                :variant="statusColor[session.status]"
                                class="shrink-0 self-start text-[10px] px-2 py-0.5 md:text-xs"
                            >
                                {{ session.status.toUpperCase() }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3 p-4 pt-0 md:space-y-4 md:p-6 md:pt-0">
                        <!-- Connection Info -->
                        <div
                            v-if="session.status === 'connected' && session.meta_json"
                            class="space-y-2 rounded-md bg-muted/30 p-3"
                        >
                            <div class="flex items-start gap-2 text-sm">
                                <span class="font-medium shrink-0">Name:</span>
                                <span class="truncate text-muted-foreground">
                                    {{ session.meta_json.name || 'N/A' }}
                                </span>
                            </div>
                            <div class="flex items-start gap-2 text-sm">
                                <span class="font-medium shrink-0">Phone:</span>
                                <span class="truncate text-muted-foreground">
                                    {{ formatPhoneNumber(session.meta_json.phone) }}
                                </span>
                            </div>
                            <div
                                v-if="session.last_seen_at"
                                class="text-xs text-muted-foreground"
                            >
                                Last seen: {{ new Date(session.last_seen_at).toLocaleString() }}
                            </div>
                        </div>

                        <!-- Pending State -->
                        <div
                            v-if="session.status === 'pending'"
                            class="rounded-md bg-yellow-50 p-3 text-sm text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300"
                        >
                            Waiting for connection...
                        </div>

                        <!-- Expired State -->
                        <div
                            v-if="session.status === 'expired'"
                            class="rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300"
                        >
                            Connection expired. Please try again.
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <Button
                                v-if="session.status === 'connected' && !session.is_primary"
                                class="flex-1 text-sm"
                                size="sm"
                                variant="outline"
                                @click="setPrimaryDevice(session.device_id)"
                            >
                                Set as Primary
                            </Button>

                            <Button
                                v-if="session.status === 'connected'"
                                class="flex-1 text-sm"
                                size="sm"
                                variant="destructive"
                                :disabled="isLoading"
                                @click="disconnectDevice(session.device_id)"
                            >
                                <Trash2 class="mr-2 h-3.5 w-3.5 md:h-4 md:w-4" />
                                Disconnect
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-if="currentSessions.length === 0">
                <CardContent class="flex flex-col items-center justify-center py-12">
                    <Smartphone class="mb-4 h-12 w-12 text-muted-foreground" />
                    <p class="mb-4 text-center text-sm text-muted-foreground md:text-base">
                        No devices connected. Add your first WhatsApp device to get
                        started.
                    </p>
                    <Button class="w-full sm:w-auto" @click="showAddDevice = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Add First Device
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
