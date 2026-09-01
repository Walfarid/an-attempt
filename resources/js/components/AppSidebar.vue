<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Briefcase,
    FileText,
    FolderGit2,
    GraduationCap,
    Images,
    LayoutGrid,
    PenLine,
    Shield,
    UserRoundPen,
    Wrench,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import educations from '@/routes/dashboard/educations';
import experience from '@/routes/dashboard/experience';
import media from '@/routes/dashboard/media';
import posts from '@/routes/dashboard/posts';
import privacy from '@/routes/dashboard/privacy';
import profile from '@/routes/dashboard/profile';
import projects from '@/routes/dashboard/projects';
import publications from '@/routes/dashboard/publications';
import skills from '@/routes/dashboard/skills';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Projects',
        href: projects.index.url(),
        icon: FolderGit2,
    },
    {
        title: 'Posts',
        href: posts.index.url(),
        icon: PenLine,
    },
    {
        title: 'Media',
        href: media.index.url(),
        icon: Images,
    },
    {
        title: 'Experience',
        href: experience.index.url(),
        icon: Briefcase,
    },
    {
        title: 'Education',
        href: educations.index.url(),
        icon: GraduationCap,
    },
    {
        title: 'Publications',
        href: publications.index.url(),
        icon: FileText,
    },
    {
        title: 'Skills',
        href: skills.index.url(),
        icon: Wrench,
    },
    {
        title: 'Privacy',
        href: privacy.edit.url(),
        icon: Shield,
    },
    {
        title: 'Profile',
        href: profile.edit.url(),
        icon: UserRoundPen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="border-t border-(--rule)">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="border-t border-(--rule)">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child :tooltip="'View homepage'">
                        <Link :href="home()">
                            <ArrowUpRight />
                            <span>View homepage</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
