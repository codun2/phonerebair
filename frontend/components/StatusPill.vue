<template>
  <span class="pill" :class="cls">{{ label }}</span>
</template>

<script setup>
const props = defineProps({ status: String, label: String })
const map = {
  received: 'pill-r',
  awaiting_approval: 'pill-y',
  in_repair: 'pill-y',
  ready: 'pill-g',
  delivered: 'pill-gray',
  cancelled: 'pill-gray',
}
const cls = computed(() => map[props.status] || 'pill-gray')
</script>
