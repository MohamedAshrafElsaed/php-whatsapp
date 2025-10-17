<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BreadcrumbNav from '@/components/BreadcrumbNav.vue';
import { Button } from '@/components/ui/button';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { toUrl, urlIsActive } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, NavItem } from '@/types';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, MessageSquare, Users, Upload, Send } from 'lucide-vue-next';
import { computed } from 'vue';
import AppearanceToggle from './AppearanceToggle.vue';
import UserMenu from './UserMenu.vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);

const isCurrentRoute = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) =>
        urlIsActive(url, page.url),
);

const activeItemStyles = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) =>
        isCurrentRoute.value(toUrl(url))
            ? 'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100'
            : '',
);

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'All Contacts',
        href: '/contacts',
        icon: Users,
    },
    {
        title: 'Import Contacts',
        href: '/contacts/imports',
        icon: Upload,
    },
    {
        title: 'Campaigns',
        href: '/campaigns',
        icon: Send,
    },
    {
        title: 'Connect WhatsApp',
        href: '/w/connect',
        icon: MessageSquare,
    },
];

const rightNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                class="mr-2 h-9 w-9"
                                size="icon"
                                variant="ghost"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent class="w-[300px] p-6" side="left">
                            <SheetTitle class="sr-only"
                                >Navigation Menu
                            </SheetTitle>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon
                                    class="size-6 fill-current text-black dark:text-white"
                                />
                            </SheetHeader>
                            <div
                                class="flex h-full flex-1 flex-col justify-between space-y-4 py-6"
                            >
                                <nav class="-mx-3 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :class="activeItemStyles(item.href)"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                    >
                                        <component
                                            :is="item.icon"
                                            v-if="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <div class="flex flex-col space-y-4">
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="toUrl(item.href)"
                                        class="flex items-center space-x-2 text-sm font-medium"
                                        rel="noopener noreferrer"
                                        target="_blank"
                                    >
                                        <component
                                            :is="item.icon"
                                            v-if="item.icon"
                                            class="h-5 w-5"
                                        />
                                        <span>{{ item.title }}</span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="dashboard()" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-6">
                        <NavigationMenuList>
                            <NavigationMenuItem
                                v-for="item in mainNavItems"
                                :key="item.title"
                            >
                                <NavigationMenuLink :as-child="true">
                                    <Link
                                        :class="[
                                            'group inline-flex h-9 w-max items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground focus:outline-hidden disabled:pointer-events-none disabled:opacity-50',
                                            isCurrentRoute(item.href)
                                                ? 'bg-accent text-accent-foreground'
                                                : '',
                                        ]"
                                        :href="item.href"
                                    >
                                        <component
                                            :is="item.icon"
                                            v-if="item.icon"
                                            class="mr-2 h-4 w-4"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </NavigationMenuLink>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <!-- Right Side Actions -->
                <div class="ml-auto flex items-center gap-2">
                    <div class="hidden gap-1 lg:flex">
                        <Button
                            v-for="item in rightNavItems"
                            :key="item.title"
                            :as-child="true"
                            size="icon"
                            variant="ghost"
                        >
                            <a
                                :href="toUrl(item.href)"
                                rel="noopener noreferrer"
                                target="_blank"
                            >
                                <component
                                    :is="item.icon"
                                    v-if="item.icon"
                                    class="h-5 w-5"
                                />
                            </a>
                        </Button>
                    </div>

                    <AppearanceToggle />
                    <UserMenu v-if="auth?.user" :user="auth.user" />
                </div>
            </div>
        </div>

        <!-- Breadcrumbs -->
        <BreadcrumbNav v-if="breadcrumbs.length" :items="breadcrumbs" />
    </div>
</template>
