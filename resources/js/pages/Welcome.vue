<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    MessageSquare,
    Send,
    Upload,
    Users,
} from 'lucide-vue-next';
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-gradient-to-b from-background to-muted/30">
        <!-- Header -->
        <header
            class="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div
                class="container mx-auto flex h-16 items-center justify-between px-4"
            >
                <div class="flex items-center gap-2">
                    <MessageSquare class="h-5 w-5 text-primary md:h-6 md:w-6" />
                    <span class="text-lg font-bold md:text-xl"
                        >BulkWhatsApp</span
                    >
                </div>
                <div class="flex items-center gap-2 md:gap-3">
                    <Link href="/login">
                        <Button class="text-sm" size="sm" variant="ghost"
                            >Login</Button
                        >
                    </Link>
                    <Link href="/register">
                        <Button class="text-sm" size="sm">Get Started</Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-12 text-center md:py-20">
            <Badge class="mb-4 md:mb-6" variant="secondary">
                Bulk Messaging Made Simple
            </Badge>
            <h1
                class="mb-4 text-3xl font-bold tracking-tight md:mb-6 md:text-5xl lg:text-6xl"
            >
                Send WhatsApp Messages<br />
                <span class="text-primary">in Bulk</span>
            </h1>
            <p
                class="mx-auto mb-6 max-w-2xl px-4 text-base text-muted-foreground md:mb-8 md:text-lg"
            >
                Connect your WhatsApp, import contacts, and send personalized
                bulk messages effortlessly. Perfect for businesses, marketers,
                and teams who need to reach their audience at scale.
            </p>
            <div
                class="flex flex-col items-center justify-center gap-3 sm:flex-row md:gap-4"
            >
                <Link class="w-full sm:w-auto" href="/register">
                    <Button class="w-full gap-2 sm:w-auto" size="lg">
                        Start Free Trial
                        <ArrowRight class="h-4 w-4" />
                    </Button>
                </Link>
                <Link class="w-full sm:w-auto" href="/login">
                    <Button
                        class="w-full sm:w-auto"
                        size="lg"
                        variant="outline"
                    >
                        Sign In
                    </Button>
                </Link>
            </div>
        </section>

        <!-- Features Section -->
        <section class="container mx-auto px-4 py-12 md:py-16">
            <h2
                class="mb-8 text-center text-2xl font-bold md:mb-12 md:text-3xl"
            >
                Everything You Need
            </h2>
            <div class="grid gap-6 sm:grid-cols-2 md:gap-8 lg:grid-cols-4">
                <!-- Feature 1 -->
                <div class="rounded-lg border bg-card p-4 text-center md:p-6">
                    <div
                        class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary/10"
                    >
                        <MessageSquare class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mb-2 text-base font-semibold md:text-lg">
                        WhatsApp Connection
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Connect your WhatsApp account with QR code or pairing
                        code in seconds
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="rounded-lg border bg-card p-4 text-center md:p-6">
                    <div
                        class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary/10"
                    >
                        <Upload class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mb-2 text-base font-semibold md:text-lg">
                        Import Contacts
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Upload CSV or Excel files with automatic phone
                        validation and deduplication
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="rounded-lg border bg-card p-4 text-center md:p-6">
                    <div
                        class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary/10"
                    >
                        <Send class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mb-2 text-base font-semibold md:text-lg">
                        Bulk Campaigns
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Create and schedule campaigns with personalized messages
                        and smart rate limiting
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="rounded-lg border bg-card p-4 text-center md:p-6">
                    <div
                        class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary/10"
                    >
                        <Users class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mb-2 text-base font-semibold md:text-lg">
                        Contact Management
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Organize contacts, track message history, and send
                        individual messages
                    </p>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="border-y bg-muted/30 py-12 md:py-16">
            <div class="container mx-auto px-4">
                <h2
                    class="mb-8 text-center text-2xl font-bold md:mb-12 md:text-3xl"
                >
                    Why Choose BulkWhatsApp?
                </h2>
                <div class="mx-auto max-w-3xl space-y-4">
                    <div class="flex items-start gap-3">
                        <CheckCircle2
                            class="mt-1 h-5 w-5 flex-shrink-0 text-primary"
                        />
                        <div>
                            <h3 class="text-sm font-semibold md:text-base">
                                Simple & Intuitive
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                No technical knowledge required. Connect
                                WhatsApp and start sending messages in minutes.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <CheckCircle2
                            class="mt-1 h-5 w-5 flex-shrink-0 text-primary"
                        />
                        <div>
                            <h3 class="text-sm font-semibold md:text-base">
                                Safe & Reliable
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Built-in rate limiting protects your WhatsApp
                                account from being banned.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <CheckCircle2
                            class="mt-1 h-5 w-5 flex-shrink-0 text-primary"
                        />
                        <div>
                            <h3 class="text-sm font-semibold md:text-base">
                                Personalized Messages
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Use merge tags to customize each message with
                                recipient's name and other details.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <CheckCircle2
                            class="mt-1 h-5 w-5 flex-shrink-0 text-primary"
                        />
                        <div>
                            <h3 class="text-sm font-semibold md:text-base">
                                Track Everything
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Monitor campaign progress, message delivery
                                status, and message history.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="container mx-auto px-4 py-12 md:py-16">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="mb-4 text-2xl font-bold md:text-3xl">
                    Simple Pricing
                </h2>
                <p
                    class="mb-6 text-sm text-muted-foreground md:mb-8 md:text-base"
                >
                    One plan, unlimited features. Start your free trial today.
                </p>
                <div class="rounded-lg border bg-card p-6 md:p-8">
                    <Badge class="mb-4" variant="secondary">
                        Coming Soon
                    </Badge>
                    <div class="mb-2 text-4xl font-bold md:text-5xl">
                        700 LE
                    </div>
                    <div
                        class="mb-6 text-sm text-muted-foreground md:text-base"
                    >
                        per month
                    </div>
                    <ul class="mb-6 space-y-3 text-left md:mb-8">
                        <li
                            class="flex items-center gap-2 text-sm md:text-base"
                        >
                            <CheckCircle2
                                class="h-5 w-5 shrink-0 text-primary"
                            />
                            <span>Unlimited contacts</span>
                        </li>
                        <li
                            class="flex items-center gap-2 text-sm md:text-base"
                        >
                            <CheckCircle2
                                class="h-5 w-5 shrink-0 text-primary"
                            />
                            <span>Unlimited campaigns</span>
                        </li>
                        <li
                            class="flex items-center gap-2 text-sm md:text-base"
                        >
                            <CheckCircle2
                                class="h-5 w-5 shrink-0 text-primary"
                            />
                            <span>CSV/Excel import</span>
                        </li>
                        <li
                            class="flex items-center gap-2 text-sm md:text-base"
                        >
                            <CheckCircle2
                                class="h-5 w-5 shrink-0 text-primary"
                            />
                            <span>Message personalization</span>
                        </li>
                        <li
                            class="flex items-center gap-2 text-sm md:text-base"
                        >
                            <CheckCircle2
                                class="h-5 w-5 shrink-0 text-primary"
                            />
                            <span>Campaign scheduling</span>
                        </li>
                        <li
                            class="flex items-center gap-2 text-sm md:text-base"
                        >
                            <CheckCircle2
                                class="h-5 w-5 shrink-0 text-primary"
                            />
                            <span>Email support</span>
                        </li>
                    </ul>
                    <Link href="/register">
                        <Button class="w-full" size="lg">
                            Start Free Trial
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section
            class="border-t bg-primary py-12 text-primary-foreground md:py-16"
        >
            <div class="container mx-auto px-4 text-center">
                <h2 class="mb-4 text-2xl font-bold md:text-3xl">
                    Ready to Get Started?
                </h2>
                <p class="mb-6 text-base opacity-90 md:mb-8 md:text-lg">
                    Join hundreds of businesses using BulkWhatsApp to reach
                    their customers.
                </p>
                <Link href="/register">
                    <Button class="gap-2" size="lg" variant="secondary">
                        Create Free Account
                        <ArrowRight class="h-4 w-4" />
                    </Button>
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t py-6 md:py-8">
            <div
                class="container mx-auto px-4 text-center text-sm text-muted-foreground"
            >
                <p>&copy; 2025 BulkWhatsApp. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
