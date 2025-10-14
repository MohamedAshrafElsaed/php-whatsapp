<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Head, Link } from '@inertiajs/vue3';
import { MessageSquare, Users, Send, Upload, CheckCircle2, ArrowRight } from 'lucide-vue-next';
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-gradient-to-b from-background to-muted/30">
        <!-- Header -->
        <header class="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto flex h-16 items-center justify-between px-4">
                <div class="flex items-center gap-2">
                    <MessageSquare class="h-6 w-6 text-primary" />
                    <span class="text-xl font-bold">BulkWhatsApp</span>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/login">
                        <Button variant="ghost">Login</Button>
                    </Link>
                    <Link href="/register">
                        <Button>Get Started</Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-20 text-center">
            <Badge variant="secondary" class="mb-6">
                Bulk Messaging Made Simple
            </Badge>
            <h1 class="mb-6 text-5xl font-bold tracking-tight md:text-6xl">
                Send WhatsApp Messages<br />
                <span class="text-primary">in Bulk</span>
            </h1>
            <p class="mx-auto mb-8 max-w-2xl text-lg text-muted-foreground">
                Connect your WhatsApp, import contacts, and send personalized bulk messages effortlessly.
                Perfect for businesses, marketers, and teams who need to reach their audience at scale.
            </p>
            <div class="flex items-center justify-center gap-4">
                <Link href="/register">
                    <Button size="lg" class="gap-2">
                        Start Free Trial
                        <ArrowRight class="h-4 w-4" />
                    </Button>
                </Link>
                <Link href="/login">
                    <Button size="lg" variant="outline">
                        Sign In
                    </Button>
                </Link>
            </div>
        </section>

        <!-- Features Section -->
        <section class="container mx-auto px-4 py-16">
            <h2 class="mb-12 text-center text-3xl font-bold">Everything You Need</h2>
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                <!-- Feature 1 -->
                <div class="rounded-lg border bg-card p-6 text-center">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <MessageSquare class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold">WhatsApp Connection</h3>
                    <p class="text-sm text-muted-foreground">
                        Connect your WhatsApp account with QR code or pairing code in seconds
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="rounded-lg border bg-card p-6 text-center">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <Upload class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold">Import Contacts</h3>
                    <p class="text-sm text-muted-foreground">
                        Upload CSV or Excel files with automatic phone validation and deduplication
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="rounded-lg border bg-card p-6 text-center">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <Send class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold">Bulk Campaigns</h3>
                    <p class="text-sm text-muted-foreground">
                        Create and schedule campaigns with personalized messages and smart rate limiting
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="rounded-lg border bg-card p-6 text-center">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <Users class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold">Contact Management</h3>
                    <p class="text-sm text-muted-foreground">
                        Organize contacts, track message history, and send individual messages
                    </p>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="border-y bg-muted/30 py-16">
            <div class="container mx-auto px-4">
                <h2 class="mb-12 text-center text-3xl font-bold">Why Choose BulkWhatsApp?</h2>
                <div class="mx-auto max-w-3xl space-y-4">
                    <div class="flex items-start gap-3">
                        <CheckCircle2 class="mt-1 h-5 w-5 flex-shrink-0 text-primary" />
                        <div>
                            <h3 class="font-semibold">Simple & Intuitive</h3>
                            <p class="text-sm text-muted-foreground">
                                No technical knowledge required. Connect WhatsApp and start sending messages in minutes.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <CheckCircle2 class="mt-1 h-5 w-5 flex-shrink-0 text-primary" />
                        <div>
                            <h3 class="font-semibold">Safe & Reliable</h3>
                            <p class="text-sm text-muted-foreground">
                                Built-in rate limiting protects your WhatsApp account from being banned.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <CheckCircle2 class="mt-1 h-5 w-5 flex-shrink-0 text-primary" />
                        <div>
                            <h3 class="font-semibold">Personalized Messages</h3>
                            <p class="text-sm text-muted-foreground">
                                Use merge tags to customize each message with recipient's name and other details.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <CheckCircle2 class="mt-1 h-5 w-5 flex-shrink-0 text-primary" />
                        <div>
                            <h3 class="font-semibold">Track Everything</h3>
                            <p class="text-sm text-muted-foreground">
                                Monitor campaign progress, message delivery status, and message history.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="container mx-auto px-4 py-16">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="mb-4 text-3xl font-bold">Simple Pricing</h2>
                <p class="mb-8 text-muted-foreground">
                    One plan, unlimited features. Start your free trial today.
                </p>
                <div class="rounded-lg border bg-card p-8">
                    <Badge variant="secondary" class="mb-4">
                        Coming Soon
                    </Badge>
                    <div class="mb-2 text-5xl font-bold">700 LE</div>
                    <div class="mb-6 text-muted-foreground">per month</div>
                    <ul class="mb-8 space-y-3 text-left">
                        <li class="flex items-center gap-2">
                            <CheckCircle2 class="h-5 w-5 text-primary" />
                            <span>Unlimited contacts</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <CheckCircle2 class="h-5 w-5 text-primary" />
                            <span>Unlimited campaigns</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <CheckCircle2 class="h-5 w-5 text-primary" />
                            <span>CSV/Excel import</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <CheckCircle2 class="h-5 w-5 text-primary" />
                            <span>Message personalization</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <CheckCircle2 class="h-5 w-5 text-primary" />
                            <span>Campaign scheduling</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <CheckCircle2 class="h-5 w-5 text-primary" />
                            <span>Email support</span>
                        </li>
                    </ul>
                    <Link href="/register">
                        <Button size="lg" class="w-full">
                            Start Free Trial
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="border-t bg-primary py-16 text-primary-foreground">
            <div class="container mx-auto px-4 text-center">
                <h2 class="mb-4 text-3xl font-bold">Ready to Get Started?</h2>
                <p class="mb-8 text-lg opacity-90">
                    Join hundreds of businesses using BulkWhatsApp to reach their customers.
                </p>
                <Link href="/register">
                    <Button size="lg" variant="secondary" class="gap-2">
                        Create Free Account
                        <ArrowRight class="h-4 w-4" />
                    </Button>
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t py-8">
            <div class="container mx-auto px-4 text-center text-sm text-muted-foreground">
                <p>&copy; 2025 BulkWhatsApp. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
