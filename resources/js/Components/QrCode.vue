<script setup>
import { ref, onMounted, watch } from 'vue';
import QRCode from 'qrcode';

const props = defineProps({
    text: { type: String, required: true },
    size: { type: Number, default: 200 },
})

const canvas = ref(null);

const generate = async () => {
    if (!canvas.value || !props.text) return;
    await QRCode.toCanvas(canvas.value, props.text, {
        width: props.size,
        margin: 2,
        color: { dark: '#1f2937', light: '#ffffff' },
    });
};

onMounted(generate);
watch(() => [props.text, props.size], generate);
</script>

<template>
    <canvas ref="canvas" class="rounded-xl" />
</template>
