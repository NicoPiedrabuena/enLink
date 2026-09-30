<script setup lang="ts">
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps<{
    checked: boolean;
    value?: any;
}>();

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded border-slate-300 bg-white text-[#6e35ff] shadow-sm focus:ring-[#6e35ff] dark:border-slate-300 dark:bg-white dark:focus:ring-[#6e35ff] dark:focus:ring-offset-white"
    />
</template>
