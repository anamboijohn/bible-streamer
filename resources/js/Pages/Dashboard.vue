<script setup lang="ts">
import Button from '@/Components/ui/button/Button.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Mic } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const isRecording = ref(false);
const transcripts = ref<string[]>([]);
let mediaRecorder: MediaRecorder | null = null;
let socket: WebSocket | null = null;

const GROQ_URL = 'wss://api.deepgram.com/v1/listen?language=en';
const GROQ_KEY = '6dc15e174972ce916c930e5d4ec25006b249fc34';

const lastFiveTranscriptions = computed(() => {
    const start = Math.max(0, transcripts.value.length - 5);
    const text = transcripts.value.slice(start).join(' ');
    console.log(text);
    return text;
});

async function sendTranscript() {
    try {
        let form = useForm({
            text: lastFiveTranscriptions.value,
        });

        form.post(route('transcribe.llm'));
    } catch (e) {
        console.log(e);
    }
}
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

        socket.onmessage = async (message) => {
            const received = JSON.parse(message.data);
            const transcript = received.channel.alternatives[0].transcript;
            if (transcript) {
                transcripts.value.push(transcript);
                await sendTranscript();
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
onMounted(() => {
    window.Echo.channel('bible-verses').listen(
        'BibleVerseRetrieved',
        (e: any) => {
            // Handle the event
            console.log(e);
        },
    );
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

                <div
                    class="flex flex-col justify-between overflow-hidden bg-white py-10 shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-center text-gray-900">
                        {{
                            isRecording
                                ? 'Recording...'
                                : 'Click the button below to start recording.'
                        }}
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
