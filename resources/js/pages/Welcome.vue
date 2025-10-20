<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslation } from '@/composables/useTranslation';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    MessageSquare,
    Send,
    Upload,
    Users,
    Smartphone,
    FileText,
    Zap,
    Shield,
    BarChart3,
    Clock,
    Globe2,
    TrendingUp,
} from 'lucide-vue-next';

const { t } = useTranslation();

const features = [
    {
        icon: MessageSquare,
        title: t('features.whatsapp_connection.title'),
        description: t('features.whatsapp_connection.description'),
        gradient: 'from-green-500 to-emerald-600',
    },
    {
        icon: Upload,
        title: t('features.import_contacts.title'),
        description: t('features.import_contacts.description'),
        gradient: 'from-blue-500 to-cyan-600',
    },
    {
        icon: Send,
        title: t('features.bulk_campaigns.title'),
        description: t('features.bulk_campaigns.description'),
        gradient: 'from-purple-500 to-pink-600',
    },
    {
        icon: Users,
        title: t('features.contact_management.title'),
        description: t('features.contact_management.description'),
        gradient: 'from-orange-500 to-red-600',
    },
    {
        icon: Smartphone,
        title: t('features.multi_device.title'),
        description: t('features.multi_device.description'),
        gradient: 'from-indigo-500 to-purple-600',
    },
    {
        icon: FileText,
        title: t('features.message_types.title'),
        description: t('features.message_types.description'),
        gradient: 'from-teal-500 to-green-600',
    },
    {
        icon: Zap,
        title: t('features.personalization.title'),
        description: t('features.personalization.description'),
        gradient: 'from-yellow-500 to-orange-600',
    },
    {
        icon: Shield,
        title: t('features.rate_limiting.title'),
        description: t('features.rate_limiting.description'),
        gradient: 'from-red-500 to-pink-600',
    },
];

const benefits = [
    {
        title: t('benefits.simple.title'),
        description: t('benefits.simple.description'),
    },
    {
        title: t('benefits.safe.title'),
        description: t('benefits.safe.description'),
    },
    {
        title: t('benefits.personalized.title'),
        description: t('benefits.personalized.description'),
    },
    {
        title: t('benefits.tracking.title'),
        description: t('benefits.tracking.description'),
    },
];

const pricingFeatures = [
    t('pricing.features.unlimited_contacts'),
    t('pricing.features.unlimited_campaigns'),
    t('pricing.features.csv_excel'),
    t('pricing.features.personalization'),
    t('pricing.features.scheduling'),
    t('pricing.features.support'),
];
</script>

<template>
    <Head>
        <title>{{ t('app_name') }} - {{ t('tagline') }}</title>
        <meta :content="t('description')" name="description" />
    </Head>

    <div class="min-h-screen bg-gradient-to-b from-background via-background to-muted/30">
        <!-- Header -->
        <header
            class="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div
                class="container mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <Link
                    class="flex items-center gap-2 transition-opacity hover:opacity-80"
                    href="/"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 shadow-lg"
                    >
                        <MessageSquare class="h-5 w-5 text-white" />
                    </div>
                    <span class="hidden text-xl font-bold sm:inline-block">{{
                            t('app_name')
                        }}</span>
                </Link>
                <div class="flex items-center gap-2 sm:gap-3">
                    <LanguageSwitcher />
                    <Link href="/login">
                        <Button
                            class="hidden sm:inline-flex"
                            size="sm"
                            variant="ghost"
                        >{{ t('nav.login') }}</Button
                        >
                    </Link>
                    <Link href="/register">
                        <Button class="shadow-lg" size="sm">{{
                                t('nav.get_started')
                            }}</Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-24">
            <div class="text-center">
                <Badge
                    class="mb-6 px-4 py-2 text-sm font-medium shadow-sm"
                    variant="secondary"
                >
                    <Zap class="mr-2 h-4 w-4" />
                    {{ t('hero.badge') }}
                </Badge>

                <h1
                    class="mx-auto mb-6 max-w-4xl text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl lg:text-7xl"
                >
                    {{ t('hero.title') }}
                    <span
                        class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent"
                    >{{ t('hero.title_highlight') }}</span
                    >
                </h1>

                <p
                    class="mx-auto mb-8 max-w-2xl text-base text-muted-foreground sm:text-lg md:text-xl"
                >
                    {{ t('hero.description') }}
                </p>

                <div
                    class="flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4"
                >
                    <Link class="w-full sm:w-auto" href="/register">
                        <Button
                            class="group w-full gap-2 shadow-lg transition-all hover:shadow-xl sm:w-auto"
                            size="lg"
                            variant="default"
                        >
                            {{ t('hero.start_trial') }}
                            <ArrowRight
                                class="h-4 w-4 transition-transform group-hover:translate-x-1"
                            />
                        </Button>
                    </Link>
                    <Link class="w-full sm:w-auto" href="/login">
                        <Button
                            class="w-full shadow-sm sm:w-auto"
                            size="lg"
                            variant="outline"
                        >
                            {{ t('hero.sign_in') }}
                        </Button>
                    </Link>
                </div>

                <!-- Stats -->
                <div
                    class="mt-16 grid grid-cols-2 gap-6 sm:grid-cols-4 sm:gap-8"
                >
                    <div class="text-center">
                        <div
                            class="mb-2 text-3xl font-bold text-foreground sm:text-4xl"
                        >
                            1000+
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ t('features.multi_device.title') }}
                        </div>
                    </div>
                    <div class="text-center">
                        <div
                            class="mb-2 text-3xl font-bold text-foreground sm:text-4xl"
                        >
                            15/min
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Safe Rate Limit
                        </div>
                    </div>
                    <div class="text-center">
                        <div
                            class="mb-2 text-3xl font-bold text-foreground sm:text-4xl"
                        >
                            8+
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Message Types
                        </div>
                    </div>
                    <div class="text-center">
                        <div
                            class="mb-2 text-3xl font-bold text-foreground sm:text-4xl"
                        >
                            100%
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Secure & Private
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="border-y bg-muted/30 py-16 sm:py-20 lg:py-24">
            <div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center sm:mb-16">
                    <h2
                        class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl"
                    >
                        {{ t('features.title') }}
                    </h2>
                    <p
                        class="mx-auto max-w-2xl text-base text-muted-foreground sm:text-lg"
                    >
                        Everything you need to run successful WhatsApp marketing
                        campaigns
                    </p>
                </div>

                <div
                    class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8"
                >
                    <Card
                        v-for="(feature, index) in features"
                        :key="index"
                        class="group relative overflow-hidden border-2 transition-all hover:border-primary/50 hover:shadow-xl"
                    >
                        <CardContent class="p-6">
                            <div
                                :class="[
                                    'mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg transition-transform group-hover:scale-110',
                                    feature.gradient,
                                ]"
                            >
                                <component
                                    :is="feature.icon"
                                    class="h-7 w-7 text-white"
                                />
                            </div>
                            <h3
                                class="mb-2 text-lg font-semibold text-foreground"
                            >
                                {{ feature.title }}
                            </h3>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                {{ feature.description }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="py-16 sm:py-20 lg:py-24">
            <div class="container mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center sm:mb-16">
                    <h2
                        class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl"
                    >
                        {{ t('benefits.title') }}
                    </h2>
                </div>

                <div class="space-y-6">
                    <Card
                        v-for="(benefit, index) in benefits"
                        :key="index"
                        class="border-2 transition-all hover:border-primary/50 hover:shadow-lg"
                    >
                        <CardContent class="flex items-start gap-4 p-6">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10"
                            >
                                <CheckCircle2
                                    class="h-6 w-6 text-primary"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3
                                    class="mb-2 text-lg font-semibold text-foreground"
                                >
                                    {{ benefit.title }}
                                </h3>
                                <p class="text-sm leading-relaxed text-muted-foreground">
                                    {{ benefit.description }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="border-y bg-muted/30 py-16 sm:py-20 lg:py-24">
            <div class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="mb-12 text-center sm:mb-16">
                    <h2
                        class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl"
                    >
                        {{ t('pricing.title') }}
                    </h2>
                    <p
                        class="mx-auto max-w-2xl text-base text-muted-foreground sm:text-lg"
                    >
                        {{ t('pricing.subtitle') }}
                    </p>
                </div>

                <Card
                    class="border-2 border-primary/20 shadow-2xl"
                >
                    <CardContent class="p-8 sm:p-10">
                        <div class="text-center">
                            <Badge
                                class="mb-6 px-4 py-2"
                                variant="secondary"
                            >
                                {{ t('pricing.coming_soon') }}
                            </Badge>
                            <div
                                class="mb-2 text-5xl font-extrabold sm:text-6xl"
                            >
                                700 LE
                            </div>
                            <div
                                class="mb-8 text-base text-muted-foreground sm:text-lg"
                            >
                                {{ t('pricing.per_month') }}
                            </div>

                            <div class="mb-8 space-y-4">
                                <div
                                    v-for="(feature, index) in pricingFeatures"
                                    :key="index"
                                    class="flex items-center gap-3 text-left"
                                >
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10"
                                    >
                                        <CheckCircle2
                                            class="h-5 w-5 text-primary"
                                        />
                                    </div>
                                    <span class="text-sm sm:text-base">{{
                                            feature
                                        }}</span>
                                </div>
                            </div>

                            <Link href="/register">
                                <Button
                                    class="w-full shadow-lg transition-all hover:shadow-xl"
                                    size="lg"
                                >
                                    {{ t('pricing.start_trial') }}
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- CTA Section -->
        <section
            class="bg-gradient-to-br from-green-600 via-emerald-600 to-teal-600 py-16 text-white sm:py-20 lg:py-24"
        >
            <div
                class="container mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8"
            >
                <h2
                    class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl"
                >
                    {{ t('cta.title') }}
                </h2>
                <p class="mb-8 text-base opacity-90 sm:text-lg md:text-xl">
                    {{ t('cta.description') }}
                </p>
                <Link href="/register">
                    <Button
                        class="group gap-2 bg-white text-green-600 shadow-xl transition-all hover:bg-white/90 hover:shadow-2xl"
                        size="lg"
                    >
                        {{ t('cta.create_account') }}
                        <ArrowRight
                            class="h-4 w-4 transition-transform group-hover:translate-x-1"
                        />
                    </Button>
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t py-8 sm:py-12">
            <div
                class="container mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8"
            >
                <p class="text-sm text-muted-foreground">
                    {{ t('footer.copyright') }}
                </p>
            </div>
        </footer>
    </div>
</template>
