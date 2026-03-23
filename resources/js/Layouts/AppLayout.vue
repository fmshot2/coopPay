<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
// import { Toaster } from '@/components/ui/sonner'
import { Toaster } from 'vue-sonner'
import { toast } from 'vue-sonner'
// import { router } from '@inertiajs/vue3'

import {
    LayoutDashboard,
    Users,
    CreditCard,
    CheckCircle,
    PiggyBank,
    Megaphone,
    ActivitySquare,
    Settings,
    ChevronLeft,
    LogOut,
    Menu,
    User,
    Tags
} from 'lucide-vue-next'
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet'
import { Separator } from '@/components/ui/separator'
import { Button } from '@/components/ui/button'

const page = usePage()
const user = computed(() => page.props.auth.user)
const flash = computed(() => page.props.flash)
const isAdmin = computed(() => user.value?.roles?.includes('admin'))

// Sidebar collapsed state
const collapsed = ref(false)

// Admin menu items
const adminMenu = [
    { label: 'Dashboard', route: 'admin.dashboard', icon: LayoutDashboard },
    { label: 'Members', route: 'admin.members.index', icon: Users },
    { label: 'Loan Types', route: 'admin.loan-types.index', icon: Tags },
    { label: 'Loans', route: 'admin.loans.index', icon: CreditCard },
    { label: 'Deductions', route: 'admin.deductions.index', icon: CheckCircle },
    { label: 'Contributions', route: 'admin.contributions.index', icon: PiggyBank },
    { label: 'Announcements', route: 'admin.announcements.index', icon: Megaphone },
    { label: 'Activity Log', route: 'admin.activity.index', icon: ActivitySquare },
    { label: 'Profile', route: 'admin.profile.index', icon: User },
]

// Member menu items
const memberMenu = [
    { label: 'Dashboard', route: 'member.dashboard', icon: LayoutDashboard },
    { label: 'My Loan', route: 'member.loan.index', icon: CreditCard },
    { label: 'Deductions', route: 'member.deductions.index', icon: CheckCircle },
    { label: 'Extra Payments', route: 'member.payments.index', icon: PiggyBank },
    { label: 'Announcements', route: 'member.announcements.index', icon: Megaphone },
    { label: 'Profile', route: 'member.profile.index', icon: User },
]

const menuItems = computed(() => isAdmin.value ? adminMenu : memberMenu)

// Get initials for avatar
const initials = computed(() => {
    return user.value?.name
        ?.split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2) ?? 'U'
})

// Logout
const logout = () => {
    router.post(route('logout'))
}

// Flash messages via sonner
// const showFlash = () => {
//     if (flash.value?.success) toast.success(flash.value.success)
//     if (flash.value?.error) toast.error(flash.value.error)
//     if (flash.value?.info) toast.info(flash.value.info)
// }

const showFlash = (newVal) => {
    const success = newVal?.success
    const error = newVal?.error
    const info = newVal?.info

    if (success) toast.success(success)
    if (error) toast.error(error)
    if (info) toast.info(info)
}

// Watch for flash messages
// import { watch } from 'vue'
// watch(flash, showFlash, { immediate: true })
// watch(flash, showFlash, { deep: true })
router.on('finish', () => {
    showFlash(flash.value)
})
</script>

<template>
    <TooltipProvider>
        <div class="flex h-screen bg-background overflow-hidden">

            <!-- ===== SIDEBAR (desktop) ===== -->
            <aside :class="[
                'hidden md:flex flex-col border-r border-sidebar-border bg-sidebar transition-all duration-300',
                collapsed ? 'w-16' : 'w-64'
            ]">
                <!-- Logo / App Name -->
                <div class="flex items-center justify-between px-4 py-4 border-b border-sidebar-border">
                    <span v-if="!collapsed" class="text-sidebar-foreground font-bold text-lg tracking-tight">
                        CoopPay
                    </span>
                    <Button variant="ghost" size="icon" class="text-sidebar-foreground hover:bg-sidebar-accent ml-auto"
                        @click="collapsed = !collapsed">
                        <ChevronLeft class="h-4 w-4 transition-transform duration-300"
                            :class="collapsed ? 'rotate-180' : ''" />
                    </Button>
                </div>

                <!-- Nav Items -->
                <nav class="flex-1 py-4 space-y-1 px-2 overflow-y-auto">
                    <template v-for="item in menuItems" :key="item.route">
                        <Tooltip :delay-duration="0">
                            <TooltipTrigger as-child>
                                <Link :href="route(item.route)" :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors',
                                    'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                    $page.url.startsWith('/' + item.route.split('.')[1])
                                        ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                        : ''
                                ]">
                                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                                    <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                                </Link>
                            </TooltipTrigger>
                            <TooltipContent v-if="collapsed" side="right">
                                {{ item.label }}
                            </TooltipContent>
                        </Tooltip>
                    </template>
                </nav>

                <!-- User section -->
                <div class="border-t border-sidebar-border p-3">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button :class="[
                                'flex items-center gap-3 w-full rounded-md px-2 py-2',
                                'hover:bg-sidebar-accent transition-colors text-left'
                            ]">
                                <Avatar class="h-8 w-8 shrink-0">
                                    <AvatarImage :src="user?.profile_photo" />
                                    <AvatarFallback class="bg-sidebar-primary text-sidebar-primary-foreground text-xs">
                                        {{ initials }}
                                    </AvatarFallback>
                                </Avatar>
                                <div v-if="!collapsed" class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-sidebar-foreground truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-sidebar-foreground/60 truncate">{{ user?.member_id }}</p>
                                </div>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent side="top" align="start" class="w-56">
                            <DropdownMenuLabel>{{ user?.name }}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link :href="isAdmin ? route('admin.profile.index') : route('member.profile.index')">
                                    Profile
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem class="text-destructive focus:text-destructive cursor-pointer"
                                @click="logout">
                                <LogOut class="mr-2 h-4 w-4" />
                                Logout
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </aside>

            <!-- ===== MOBILE SIDEBAR (sheet) ===== -->
            <Sheet>
                <SheetTrigger as-child>
                    <Button variant="ghost" size="icon" class="md:hidden fixed top-4 left-4 z-50">
                        <Menu class="h-5 w-5" />
                    </Button>
                </SheetTrigger>
                <SheetContent side="left" class="w-64 p-0 bg-sidebar border-sidebar-border">
                    <div class="flex flex-col h-full">
                        <div class="px-4 py-4 border-b border-sidebar-border">
                            <span class="text-sidebar-foreground font-bold text-lg">CoopPay</span>
                        </div>
                        <nav class="flex-1 py-4 space-y-1 px-2 overflow-y-auto">
                            <Link v-for="item in menuItems" :key="item.route" :href="route(item.route)"
                                class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-sidebar-foreground hover:bg-sidebar-accent transition-colors">
                                <component :is="item.icon" class="h-5 w-5 shrink-0" />
                                {{ item.label }}
                            </Link>
                        </nav>
                    </div>
                </SheetContent>
            </Sheet>

            <!-- ===== MAIN CONTENT ===== -->
            <div class="flex-1 flex flex-col overflow-hidden">

                <!-- Topbar -->
                <header class="h-14 border-b bg-card flex items-center justify-between px-6 shrink-0">
                    <h1 class="text-sm font-semibold text-foreground">
                        {{ $page.props.title ?? 'CoopPay' }}
                    </h1>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-muted-foreground hidden sm:block">
                            {{ user?.member_id }}
                        </span>
                        <Avatar class="h-8 w-8">
                            <AvatarImage :src="user?.profile_photo" />
                            <AvatarFallback class="bg-primary text-primary-foreground text-xs">
                                {{ initials }}
                            </AvatarFallback>
                        </Avatar>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto p-6">
                    <slot />
                </main>
            </div>
            <!-- end MAIN CONTENT -->

        </div>
        <!-- end flex container -->

    </TooltipProvider>
    <Toaster position="top-right" theme="light" :duration="3000" />

</template>
