<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Camera } from 'lucide-vue-next'

const props = defineProps({
    member: Object,
})

const initials = props.member.name
    ?.split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2) ?? 'U'

// Profile form
const profileForm = useForm({
    name:  props.member.name,
    phone: props.member.phone ?? '',
})

const submitProfile = () => {
    profileForm.patch(route('admin.profile.update'))
}

// Photo form
const photoForm    = useForm({ photo: null })
const photoPreview = ref(null)

const handlePhoto = (e) => {
    const file = e.target.files[0]
    photoForm.photo = file
    photoPreview.value = URL.createObjectURL(file)
}

const submitPhoto = () => {
    photoForm.post(route('admin.profile.photo'), {
        onSuccess: () => {
            photoPreview.value = null
        },
    })
}

// Password form
const passwordForm = useForm({
    current_password: '',
    password:         '',
    password_confirmation: '',
})

const submitPassword = () => {
    passwordForm.patch(route('admin.profile.password'), {
        onSuccess: () => passwordForm.reset(),
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-2xl">

            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-foreground">My Profile</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Manage your personal information and password
                </p>
            </div>

            <!-- Profile Photo Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Profile Photo</CardTitle>
                    <CardDescription>Upload a photo to personalize your account</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-6">
                        <div class="relative">
                            <Avatar class="h-20 w-20">
                                <!-- :src="photoPreview ?? member.profile_photo" -->
                                <AvatarImage
                                :src="photoPreview ?? (member.profile_photo ? `/storage/${member.profile_photo}` : null)"
                                />
                                <AvatarFallback class="bg-primary text-primary-foreground text-xl">
                                    {{ initials }}
                                </AvatarFallback>
                            </Avatar>
                        </div>
                        <div class="space-y-3 flex-1">
                            <Input
                                type="file"
                                accept=".jpg,.jpeg,.png"
                                @change="handlePhoto"
                                :class="photoForm.errors.photo ? 'border-destructive' : ''"
                            />
                            <p v-if="photoForm.errors.photo" class="text-xs text-destructive">
                                {{ photoForm.errors.photo }}
                            </p>
                            <Button
                                size="sm"
                                :disabled="!photoForm.photo || photoForm.processing"
                                @click="submitPhoto"
                            >
                                <Camera class="h-4 w-4 mr-2" />
                                {{ photoForm.processing ? 'Uploading...' : 'Upload Photo' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Personal Info Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Personal Information</CardTitle>
                    <CardDescription>Update your name and phone number</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitProfile" class="space-y-4">

                        <!-- Member ID (readonly) -->
                        <div class="space-y-2">
                            <Label>Member ID</Label>
                            <Input
                                :value="member.member_id"
                                disabled
                                class="bg-muted text-muted-foreground"
                            />
                        </div>

                        <!-- Email (readonly) -->
                        <div class="space-y-2">
                            <Label>Email Address</Label>
                            <Input
                                :value="member.email"
                                disabled
                                class="bg-muted text-muted-foreground"
                            />
                        </div>

                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Full Name <span class="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                v-model="profileForm.name"
                                :class="profileForm.errors.name ? 'border-destructive' : ''"
                            />
                            <p v-if="profileForm.errors.name" class="text-xs text-destructive">
                                {{ profileForm.errors.name }}
                            </p>
                        </div>

                        <!-- Phone -->
                        <div class="space-y-2">
                            <Label for="phone">Phone Number</Label>
                            <Input
                                id="phone"
                                v-model="profileForm.phone"
                                placeholder="e.g. 08012345678"
                                :class="profileForm.errors.phone ? 'border-destructive' : ''"
                            />
                            <p v-if="profileForm.errors.phone" class="text-xs text-destructive">
                                {{ profileForm.errors.phone }}
                            </p>
                        </div>

                        <Button type="submit" :disabled="profileForm.processing">
                            {{ profileForm.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>

                    </form>
                </CardContent>
            </Card>

            <!-- Change Password Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Change Password</CardTitle>
                    <CardDescription>Update your account password</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitPassword" class="space-y-4">

                        <!-- Current Password -->
                        <div class="space-y-2">
                            <Label for="current_password">Current Password <span class="text-destructive">*</span></Label>
                            <Input
                                id="current_password"
                                v-model="passwordForm.current_password"
                                type="password"
                                placeholder="••••••••"
                                :class="passwordForm.errors.current_password ? 'border-destructive' : ''"
                            />
                            <p v-if="passwordForm.errors.current_password" class="text-xs text-destructive">
                                {{ passwordForm.errors.current_password }}
                            </p>
                        </div>

                        <!-- New Password -->
                        <div class="space-y-2">
                            <Label for="password">New Password <span class="text-destructive">*</span></Label>
                            <Input
                                id="password"
                                v-model="passwordForm.password"
                                type="password"
                                placeholder="••••••••"
                                :class="passwordForm.errors.password ? 'border-destructive' : ''"
                            />
                            <p v-if="passwordForm.errors.password" class="text-xs text-destructive">
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>

                        <!-- Confirm Password -->
                        <div class="space-y-2">
                            <Label for="password_confirmation">Confirm New Password <span class="text-destructive">*</span></Label>
                            <Input
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                placeholder="••••••••"
                            />
                        </div>

                        <Button type="submit" :disabled="passwordForm.processing">
                            {{ passwordForm.processing ? 'Changing...' : 'Change Password' }}
                        </Button>

                    </form>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
