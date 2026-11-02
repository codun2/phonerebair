<template>
  <NuxtPage :page-key="pageKey" />
</template>

<script setup>
const { isEn } = useI18n()
const { syncTick } = useOffline()
useHead({
  htmlAttrs: {
    lang: computed(() => (isEn.value ? 'en' : 'ar')),
    dir: computed(() => (isEn.value ? 'ltr' : 'rtl')),
  },
})
// بعد رفع عمليات الانتظار تُعاد تهيئة الصفحة لتعرض بيانات السيرفر (عدا النماذج حتى لا يضيع المُدخل)
const FORMS = ['/admin/ticket-new', '/admin/settings']
const pageKey = (route) => (FORMS.includes(route.path) ? route.fullPath : route.fullPath + '#' + syncTick.value)
</script>
