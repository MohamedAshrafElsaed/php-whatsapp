<script lang="ts" setup>
import NavFooter from '@/components/NavFooter.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/composables/useTranslation';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    LayoutGrid,
    MessageCircleReply,
    MessageSquare,
    MessageSquareText,
    Send,
    Upload,
    Users,
    UsersRound,
    Tag,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const { t, isRTL } = useTranslation();
const page = usePage();

const sidebarSide = computed(() => (isRTL.value ? 'right' : 'left'));

const isActive = (href: string) => page.url.startsWith(href);

const dashboardItems: NavItem[] = [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const contactsGroup: NavItem[] = [
    {
        title: t('contacts.title'),
        href: '/contacts',
        icon: Users,
    },
    {
        title: t('contacts.import_contacts'),
        href: '/contacts/imports',
        icon: Upload,
    },
    {
        title: t('segments.title'),
        href: '/contacts/segments',
        icon: Tag,
    },
];

const campaignsGroup: NavItem[] = [
    {
        title: t('campaigns.title'),
        href: '/campaigns',
        icon: Send,
    },
];

const whatsappGroup: NavItem[] = [
    {
        title: t('whatsapp.devices'),
        href: '/w/connect',
        icon: MessageSquare,
    },
    {
        title: t('whatsapp.groups'),
        href: '/whatsapp-groups',
        icon: UsersRound,
    },
    {
        title: t('whatsapp.auto_reply'),
        href: '/auto-reply',
        icon: MessageCircleReply,
    },
];

const reportsGroup: NavItem[] = [
    {
        title: t('reports.title'),
        href: '/reports',
        icon: BarChart3,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: t('feature_requests.title'),
        href: '/feature-requests',
        icon: MessageSquareText,
    },
];
</script>

<template>
    <Sidebar :side="sidebarSide" collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child size="lg">
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in dashboardItems" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isActive(item.href)">
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup>
                <SidebarGroupLabel>{{ t('sidebar.contacts') }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in contactsGroup" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isActive(item.href)">
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup>
                <SidebarGroupLabel>{{ t('sidebar.campaigns') }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in campaignsGroup" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isActive(item.href)">
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup>
                <SidebarGroupLabel>{{ t('sidebar.whatsapp') }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in whatsappGroup" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isActive(item.href)">
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup>
                <SidebarGroupLabel>{{ t('sidebar.analytics') }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in reportsGroup" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isActive(item.href)">
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
