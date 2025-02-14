<script setup lang="ts">
import Button from '@/Components/ui/button/Button.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Mic } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
const URL = window.URL || window.webkitURL;

// Initialize the Groq client
// const groq = new Groq();

// Function to send an audio blob to Groq API for transcription
async function transcribeAudioBlob(audioBlob: Blob) {
    const form = useForm({
        audio: audioBlob,
    });
    const response = await form.post(route('transcribe.show'), {
        onSuccess: (res) => {
            // Handle successful response
            console.log(res);
        },
        onError: (error) => {
            // Handle errors
            console.error(error);
        },
        preserveScroll: true,
        forceFormData: true,
    });

    return response;
}

// Set up live audio capture and transcription
let mediaRecorder: MediaRecorder | null = null;
const isRecording = ref(false);

let audio = ref<Blob | null>(null);

function startAudioTranscription() {
    navigator.mediaDevices
        .getUserMedia({ audio: true })
        .then((stream) => {
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.ondataavailable = async (event) => {
                audio.value = event.data;
                console.log(event.data);
                await transcribeAudioBlob(event.data);
            };
            mediaRecorder.start(5000);
            isRecording.value = true;
        })
        .catch((err) => {
            console.error('Error accessing microphone:', err);
        });
}

function stopAudioTranscription() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        isRecording.value = false;
        console.log('Audio transcription stopped.');
    } else {
        console.log('No active audio transcription to stop.');
    }
}
function handleAudioTranscription() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        stopAudioTranscription();
    } else {
        startAudioTranscription();
    }
}
// Cleanup function to stop audio transcription when the component is unmounted
onUnmounted(() => {
    stopAudioTranscription();
});
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
                    <p class="text-center">
                        <audio
                            :src="audio ? URL.createObjectURL(audio) : ''"
                            controls
                        ></audio>
                    </p>
                </div>

                <div
                    class="flex flex-col justify-between overflow-hidden bg-white py-10 shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-center text-gray-900">
                        You're logged in!
                    </div>
                    <div class="mx-auto">
                        <Button @click="handleAudioTranscription">
                            <Mic /> Start Listening
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
