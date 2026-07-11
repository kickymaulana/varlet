<script setup lang="ts">
defineProps<{
    nama: string
}>()
</script>

<template>
    <div style="padding: 30px; font-family: sans-serif; display: flex; flex-direction: column; gap: 15px; max-width: 400px;">
        <h1>Halo, {{ nama }}!</h1>
        <p>Inertia v3 + TS + Vue 3 + Varlet UI sukses dikawinkan!</p>

        <var-button type="primary">Tombol Utama Varlet</var-button>

        <var-button type="success" block>Tombol Sukses (Block)</var-button>
    </div>
</template>
