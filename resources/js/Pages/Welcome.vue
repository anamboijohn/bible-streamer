<script lang="ts" setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    success?: boolean;
}>();
</script>

<template>
    <Head title="Welcome" />

    <!-- Main content -->
    <div class="container mx-auto">
        <div
            class="flex items-center justify-center font-semibold"
            style="height: 90vh"
        >
            <div class="text-center">
                <h1 class="text-4xl font-bold text-red-500">
                    Welcome to Bile live trascription
                </h1>

                <p class="my-4 text-lg font-semibold">
                    Get live display of scriptural references
                </p>
                <div class="grid grid-cols-2 gap-5"> 
                    <Link
                        :href="route('login')"
                        class="rounded-xl bg-black px-3 py-2 text-white"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="route('register')"
                        class="rounded-xl bg-black px-3 py-2 text-white"
                    >
                        Register
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
