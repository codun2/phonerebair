<template>
  <span class="pill" :class="cls">{{ t(label || '') }}</span>
</template>

<script setup>
const props = defineProps({ status: String, label: String })
const { t } = useI18n()
const map = {
  draft: 'pill-gray',
  received: 'pill-r',
  inspecting: 'pill-y',
  awaiting_approval: 'pill-y',
  waiting_part: 'pill-y',
  in_repair: 'pill-y',
  testing: 'pill-y',
  ready: 'pill-g',
  delivered: 'pill-gray',
  rejected: 'pill-r',
  unrepairable: 'pill-r',
  cancelled: 'pill-gray',
}
const cls = computed(() => map[props.status] || 'pill-gray')
</script>
