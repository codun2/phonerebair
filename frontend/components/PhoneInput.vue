<template>
  <div class="pin" :class="{ open }" ref="root">
    <button type="button" class="pin-code" @click="toggle">
      <span class="flag">{{ current.flag }}</span>
      <span class="dial" dir="ltr">{{ current.dial }}</span>
      <i class="ti ti-chevron-down"></i>
    </button>
    <input
      ref="numEl"
      class="pin-num"
      :value="local"
      :placeholder="placeholder"
      dir="ltr"
      inputmode="tel"
      @input="onInput"
      @focus="$emit('focus')"
      @blur="$emit('blur')"
    />
    <div v-if="open" class="pin-menu">
      <div class="pin-search"><i class="ti ti-search"></i>
        <input v-model="q" ref="qEl" :placeholder="tr('بحث عن دولة أو مقدمة')" @keydown.enter.prevent="filtered[0] && choose(filtered[0])" />
      </div>
      <ul>
        <li v-for="c in filtered" :key="c.iso" :class="{ on: c.dial === current.dial }" @click="choose(c)">
          <span class="flag">{{ c.flag }}</span>
          <span class="nm">{{ isEn ? c.en : c.ar }}</span>
          <span class="dial" dir="ltr">{{ c.dial }}</span>
        </li>
        <li v-if="!filtered.length" class="none">{{ tr('لا نتائج') }}</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '5xxxxxxxx' },
  defaultDial: { type: String, default: '+966' },
})
const emit = defineEmits(['update:modelValue', 'focus', 'blur'])
const { t: tr, isEn } = useI18n()

const COUNTRIES = [
  { iso: 'SA', ar: 'السعودية', en: 'Saudi Arabia', dial: '+966', flag: '🇸🇦' },
  { iso: 'AE', ar: 'الإمارات', en: 'UAE', dial: '+971', flag: '🇦🇪' },
  { iso: 'KW', ar: 'الكويت', en: 'Kuwait', dial: '+965', flag: '🇰🇼' },
  { iso: 'QA', ar: 'قطر', en: 'Qatar', dial: '+974', flag: '🇶🇦' },
  { iso: 'BH', ar: 'البحرين', en: 'Bahrain', dial: '+973', flag: '🇧🇭' },
  { iso: 'OM', ar: 'عُمان', en: 'Oman', dial: '+968', flag: '🇴🇲' },
  { iso: 'JO', ar: 'الأردن', en: 'Jordan', dial: '+962', flag: '🇯🇴' },
  { iso: 'PS', ar: 'فلسطين', en: 'Palestine', dial: '+970', flag: '🇵🇸' },
  { iso: 'EG', ar: 'مصر', en: 'Egypt', dial: '+20', flag: '🇪🇬' },
  { iso: 'IQ', ar: 'العراق', en: 'Iraq', dial: '+964', flag: '🇮🇶' },
  { iso: 'LB', ar: 'لبنان', en: 'Lebanon', dial: '+961', flag: '🇱🇧' },
  { iso: 'SY', ar: 'سوريا', en: 'Syria', dial: '+963', flag: '🇸🇾' },
  { iso: 'YE', ar: 'اليمن', en: 'Yemen', dial: '+967', flag: '🇾🇪' },
  { iso: 'SD', ar: 'السودان', en: 'Sudan', dial: '+249', flag: '🇸🇩' },
  { iso: 'DZ', ar: 'الجزائر', en: 'Algeria', dial: '+213', flag: '🇩🇿' },
  { iso: 'MA', ar: 'المغرب', en: 'Morocco', dial: '+212', flag: '🇲🇦' },
  { iso: 'TN', ar: 'تونس', en: 'Tunisia', dial: '+216', flag: '🇹🇳' },
  { iso: 'LY', ar: 'ليبيا', en: 'Libya', dial: '+218', flag: '🇱🇾' },
  { iso: 'TR', ar: 'تركيا', en: 'Turkey', dial: '+90', flag: '🇹🇷' },
]
// الأطول أولاً حتى نطابق المقدمة الصحيحة عند التحليل
const BY_LEN = [...COUNTRIES].sort((a, b) => b.dial.length - a.dial.length)

const open = ref(false)
const q = ref('')
const root = ref(null)
const numEl = ref(null)
const qEl = ref(null)
const code = ref(props.defaultDial)
const local = ref('')

const current = computed(() => COUNTRIES.find((c) => c.dial === code.value) || COUNTRIES[0])
const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return COUNTRIES
  const digits = s.replace(/[^\d]/g, '')
  return COUNTRIES.filter((c) => c.ar.includes(s) || c.en.toLowerCase().includes(s) || (digits && c.dial.includes(digits)))
})

// يبني الرقم الدولي الكامل للإرسال: المقدمة + الرقم المحلي بدون صفر البداية
function fullValue() {
  const digits = (local.value || '').replace(/\D/g, '').replace(/^0+/, '')
  return digits ? code.value + digits : ''
}
function onInput(e) {
  local.value = e.target.value.replace(/[^\d\s-]/g, '')
  emit('update:modelValue', fullValue())
}
function choose(c) {
  code.value = c.dial
  open.value = false
  q.value = ''
  emit('update:modelValue', fullValue())
  nextTick(() => numEl.value?.focus())
}
function toggle() {
  open.value = !open.value
  if (open.value) nextTick(() => qEl.value?.focus())
}

// يحلّل القيمة القادمة (دولية مثل ‎+966..‎ أو محلية مثل 05..) إلى مقدمة + رقم محلي
function parse(val) {
  const raw = (val || '').trim()
  if (!raw) { local.value = ''; return }
  const digits = raw.replace(/[^\d]/g, '')
  if (raw.startsWith('+') || raw.startsWith('00')) {
    const d = raw.startsWith('00') ? digits.replace(/^00/, '') : digits
    const hit = BY_LEN.find((c) => d.startsWith(c.dial.slice(1)))
    if (hit) { code.value = hit.dial; local.value = d.slice(hit.dial.length - 1); return }
  }
  // رقم محلي: نُبقي المقدمة الحالية ونعرض الرقم كما هو
  local.value = digits
}

watch(() => props.modelValue, (v) => { if (v !== fullValue()) parse(v) })
onMounted(() => {
  code.value = props.defaultDial
  parse(props.modelValue)
  document.addEventListener('click', onDoc)
})
onBeforeUnmount(() => document.removeEventListener('click', onDoc))
function onDoc(e) { if (open.value && root.value && !root.value.contains(e.target)) open.value = false }
</script>

<style scoped>
.pin { position: relative; display: flex; align-items: stretch; border: 1px solid var(--line); border-radius: 10px; background: #fff; overflow: visible; }
.pin:focus-within { border-color: var(--accent); box-shadow: 0 0 0 3px #E6F1FB; }
.pin-code { display: flex; align-items: center; gap: 6px; padding: 0 11px; background: var(--bg); border: none; border-inline-end: 1px solid var(--line); cursor: pointer; font-family: inherit; font-size: 13px; color: var(--ink); border-start-start-radius: 10px; border-end-start-radius: 10px; white-space: nowrap; }
.pin-code:hover { background: #EEF1F5; }
.pin-code .flag { font-size: 17px; line-height: 1; }
.pin-code .dial { font-weight: 600; direction: ltr; }
.pin-code .ti { font-size: 15px; color: var(--muted); }
.pin-num { flex: 1; border: none; background: transparent; padding: 11px 12px; font-family: inherit; font-size: 14px; outline: none; min-width: 0; }
.pin-menu { position: absolute; top: calc(100% + 6px); inset-inline-start: 0; width: 290px; max-width: 86vw; background: #fff; border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 12px 34px rgba(20,30,50,.16); z-index: 60; overflow: hidden; animation: pin-in .13s ease; }
@keyframes pin-in { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
.pin-search { display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-bottom: 1px solid var(--line-2); }
.pin-search i { color: var(--muted); }
.pin-search input { flex: 1; border: none; outline: none; font-family: inherit; font-size: 13.5px; background: transparent; }
.pin-menu ul { list-style: none; margin: 0; padding: 5px; max-height: 260px; overflow-y: auto; }
.pin-menu li { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 8px; cursor: pointer; font-size: 13.5px; }
.pin-menu li:hover { background: var(--bg); }
.pin-menu li.on { background: #E6F1FB; }
.pin-menu li .flag { font-size: 18px; }
.pin-menu li .nm { flex: 1; }
.pin-menu li .dial { color: var(--muted); direction: ltr; font-weight: 600; }
.pin-menu li.none { justify-content: center; color: var(--muted); cursor: default; }
.pin-menu li.none:hover { background: none; }
</style>
