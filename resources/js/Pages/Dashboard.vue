<script setup lang="ts">
import Button from '@/Components/ui/button/Button.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Mic } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';

const URL = window.URL || window.webkitURL;
const isRecording = ref(false);
const audio = ref<Blob | null>(null);
const transcripts = ref<string[]>([]);
let mediaRecorder: MediaRecorder | null = null;
let socket: WebSocket | null = null;

const GROQ_URL = 'wss://api.deepgram.com/v1/listen?language=en';
const GROQ_KEY = '6dc15e174972ce916c930e5d4ec25006b249fc34';

async function startAudioTranscription() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: true,
        });
        mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

        socket = new WebSocket(GROQ_URL, ['token', GROQ_KEY]);
        socket.onopen = () => {
            mediaRecorder?.start(250);
            mediaRecorder?.addEventListener('dataavailable', (event) => {
                if (event.data.size > 0 && socket?.readyState === 1) {
                    socket.send(event.data);
                }
            });
        };

        socket.onmessage = (message) => {
            const received = JSON.parse(message.data);
            const transcript = received.channel.alternatives[0].transcript;
            if (transcript) {
                transcripts.value.push(transcript);
            }
        };

        isRecording.value = true;
    } catch (err) {
        console.error('Error accessing microphone:', err);
    }
}

function stopAudioTranscription() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        isRecording.value = false;
        console.log('Audio transcription stopped.');
    } else {
        console.log('No active audio transcription to stop.');
    }

    if (socket) {
        socket.close();
    }
}

function handleAudioTranscription() {
    if (isRecording.value) {
        stopAudioTranscription();
    } else {
        startAudioTranscription();
    }
}

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
                    <p
                        class="text-center"
                        v-for="(transcription, index) in transcripts"
                        :key="index"
                    >
                        {{ transcription }}
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
                            <Mic />
                            {{
                                isRecording
                                    ? 'Stop Listening'
                                    : 'Start Listening'
                            }}
                        </Button>
                        <div v-if="isRecording" class="mt-2 text-red-500">
                            Recording...
                        </div>
                    </div>
                </div>
                <div>
                    <h3>Transcripts:</h3>
                    <ul>
                        <li
                            v-for="(transcript, index) in transcripts"
                            :key="index"
                        >
                            {{ transcript }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
