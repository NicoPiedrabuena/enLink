<script setup lang="ts">
import { onMounted, ref } from 'vue';

const model = defineModel<string>({ required: true });

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        class="rounded-md border-slate-300 bg-white text-[#171713] shadow-sm placeholder:text-slate-400 focus:border-[#6e35ff] focus:ring-[#6e35ff] dark:border-slate-300 dark:bg-white dark:text-[#171713] dark:focus:border-[#6e35ff] dark:focus:ring-[#6e35ff]"
        v-model="model"
        ref="input"
    />
</template>
