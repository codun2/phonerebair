<template>
  <div class="ms" ref="root">
    <div class="ms-box" :class="{ open }" @click="toggleOpen">
      <div class="ms-tags" v-if="selected.length">
        <span v-for="o in selected" :key="o.id" class="ms-tag">
          {{ o.name }}
          <i class="ti ti-x" @click.stop="remove(o.id)"></i>
        </span>
      </div>
      <span v-else class="ms-ph">{{ placeholder }}</span>
      <i class="ti ti-chevron-down ms-arrow" :class="{ up: open }"></i>
    </div>

    <transition name="ms-fade">
      <div v-if="open" class="ms-menu">
        <div v-if="searchable" class="ms-search">
          <i class="ti ti-search"></i>
          <input ref="searchEl" v-model="q" :placeholder="searchPlaceholder" @click.stop />
        </div>
        <div class="ms-list">
          <button
            v-for="o in filtered" :key="o.id" type="button"
            class="ms-opt" :class="{ on: isOn(o.id) }" @click.stop="toggle(o.id)"
          >
            <i class="ti" :class="isOn(o.id) ? 'ti-square-rounded-check-filled' : 'ti-square-rounded'"></i>
            <span>{{ o.name }}</span>
          </button>
          <div v-if="!filtered.length" class="ms-empty">{{ emptyText }}</div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] }, // [{ id, name }]
  placeholder: { type: String, default: '— اختر —' },
  searchable: { type: Boolean, default: true },
  searchPlaceholder: { type: String, default: 'بحث…' },
  emptyText: { type: String, default: 'لا نتائج' },
})
const emit = defineEmits(['update:modelValue', 'change'])

const root = ref(null)
const searchEl = ref(null)
const open = ref(false)
const q = ref('')

const selected = computed(() => props.modelValue.map((id) => props.options.find((o) => o.id === id)).filter(Boolean))
const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return props.options
  return props.options.filter((o) => o.name.toLowerCase().includes(s))
})
const isOn = (id) => props.modelValue.includes(id)

function toggleOpen() {
  open.value = !open.value
  if (open.value && props.searchable) nextTick(() => searchEl.value?.focus())
}
function toggle(id) {
  const next = isOn(id) ? props.modelValue.filter((x) => x !== id) : [...props.modelValue, id]
  emit('update:modelValue', next); emit('change', next)
}
function remove(id) {
  const next = props.modelValue.filter((x) => x !== id)
  emit('update:modelValue', next); emit('change', next)
}
function onDocClick(e) { if (root.value && !root.value.contains(e.target)) open.value = false }
onMounted(() => document.addEventListener('click', onDocClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocClick))
</script>

<style scoped>
.ms { position: relative; }
.ms-box { min-height: 44px; border: 1px solid var(--line); border-radius: 10px; background: #fff; padding: 6px 12px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
.ms-box:hover { border-color: #C7CCD6; }
.ms-box.open { border-color: var(--accent); box-shadow: 0 0 0 3px #E6F1FB; }
.ms-tags { display: flex; flex-wrap: wrap; gap: 6px; flex: 1; }
.ms-tag { display: inline-flex; align-items: center; gap: 5px; background: #E6F1FB; color: var(--accent-d); border-radius: 7px; padding: 4px 9px; font-size: 13px; font-weight: 600; }
.ms-tag i { cursor: pointer; font-size: 14px; opacity: .7; }
.ms-tag i:hover { opacity: 1; }
.ms-ph { flex: 1; color: var(--muted); font-size: 14px; }
.ms-arrow { color: var(--muted); transition: transform .2s; }
.ms-arrow.up { transform: rotate(180deg); }
.ms-menu { position: absolute; top: calc(100% + 6px); inset-inline: 0; background: #fff; border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 10px 30px rgba(20,33,58,.14); z-index: 40; overflow: hidden; }
.ms-search { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-bottom: 1px solid var(--line-2); }
.ms-search i { color: var(--muted); }
.ms-search input { flex: 1; border: none; outline: none; font-family: inherit; font-size: 13.5px; background: none; }
.ms-list { max-height: 240px; overflow-y: auto; padding: 6px; }
.ms-opt { display: flex; align-items: center; gap: 10px; width: 100%; text-align: start; padding: 9px 11px; border: none; background: none; cursor: pointer; font-family: inherit; font-size: 14px; color: var(--ink); border-radius: 8px; }
.ms-opt:hover { background: var(--bg); }
.ms-opt.on { color: var(--accent-d); font-weight: 600; }
.ms-opt i { font-size: 19px; color: var(--muted); }
.ms-opt.on i { color: var(--accent); }
.ms-empty { padding: 14px; text-align: center; color: var(--muted); font-size: 13px; }
.ms-fade-enter-active, .ms-fade-leave-active { transition: opacity .15s, transform .15s; }
.ms-fade-enter-from, .ms-fade-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
