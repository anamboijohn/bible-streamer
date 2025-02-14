<script setup lang="ts">
import Button from '@/Components/ui/button/Button.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Mic } from 'lucide-vue-next';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto grid grid-flow-row grid-rows-2 gap-6">
                <div>
                    <p class="text-center">
                        Lorem, ipsum dolor sit amet consectetur adipisicing
                        elit. Id at commodi accusantium, consectetur nulla
                        soluta, vero aspernatur quae debitis, minima modi ipsa
                        iure! Facilis libero optio quidem, ab numquam impedit.
                    </p>
                </div>
                <div
                    class="flex flex-col justify-between overflow-hidden bg-white py-10 shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-center text-gray-900">
                        You're logged in!
                    </div>
                    <div class="mx-auto">
                        <Button> <Mic /> Start Listening </Button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
