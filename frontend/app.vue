<template>
  <NuxtPage />
</template>

<script setup>
const { isEn } = useI18n()
useHead({
  htmlAttrs: {
    lang: computed(() => (isEn.value ? 'en' : 'ar')),
    dir: computed(() => (isEn.value ? 'ltr' : 'rtl')),
  },
})
</script>
