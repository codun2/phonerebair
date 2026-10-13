<template>
  <button class="lang-sw" :class="light ? 'light' : ''" @click="toggle" :title="isEn ? 'العربية' : 'English'">
    <i class="ti ti-language"></i> {{ isEn ? 'ع' : 'EN' }}
  </button>
</template>

<script setup>
defineProps({ light: { type: Boolean, default: false } })
const { isEn, toggle } = useI18n()
</script>

<style scoped>
.lang-sw {
  display: inline-flex; align-items: center; gap: 5px;
  height: 34px; padding: 0 12px; border-radius: 9px; cursor: pointer;
  font-family: inherit; font-size: 13px; font-weight: 600;
  border: 1px solid var(--line); background: #fff; color: var(--ink);
}
.lang-sw:hover { background: #F2F5F9; }
.lang-sw.light { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.18); color: #fff; }
.lang-sw.light:hover { background: rgba(255,255,255,.18); }
</style>
