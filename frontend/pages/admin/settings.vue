<template>
  <DashShell active="settings">
    <h1 class="page-title">{{ t('الإعدادات') }}</h1>
    <p class="page-sub">{{ t('معلومات المحل والنصوص التي تظهر للزبائن في الموقع') }}</p>

    <div v-if="saved" class="ok-box" style="margin-bottom:16px;max-width:720px">{{ t('تم حفظ الإعدادات ✓') }}</div>

    <div class="panel" style="max-width:720px">
      <h3 class="sec-t"><i class="ti ti-building-store" style="color:var(--accent)"></i> {{ t('معلومات المحل') }}</h3>
      <div class="g2">
        <div class="frow"><label>{{ t('اسم المحل') }}</label><input v-model="f.shop_name" /></div>
        <div class="frow"><label>{{ t('الهاتف') }}</label><input v-model="f.phone" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
        <div class="frow"><label>{{ t('واتساب (صيغة دولية)') }}</label><input v-model="f.whatsapp" placeholder="9705..." dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
        <div class="frow"><label>{{ t('ساعات الدوام') }}</label><input v-model="f.hours" /></div>
      </div>
      <div class="frow"><label>{{ t('العنوان') }}</label><input v-model="f.address" /></div>
      <div class="frow"><label>{{ t('نبذة عن المحل') }}</label><textarea v-model="f.about" rows="2"></textarea></div>
    </div>

    <div class="panel" style="max-width:720px;margin-top:16px">
      <h3 class="sec-t"><i class="ti ti-typography" style="color:var(--accent)"></i> {{ t('نصوص الصفحة الرئيسية') }}</h3>
      <div class="frow"><label>{{ t('شارة الهيرو') }}</label><input v-model="f.hero_badge" :placeholder="t('صيانة معتمدة · ضمان على كل تصليح')" /></div>
      <div class="frow"><label>{{ t('عنوان الهيرو') }}</label><input v-model="f.hero_title" :placeholder="t('جهازك بأمان — تصليح احترافي بنفس اليوم')" /></div>
      <div class="frow"><label>{{ t('وصف الهيرو') }}</label><textarea v-model="f.hero_subtitle" rows="2"></textarea></div>
      <div class="g2">
        <div class="frow"><label>{{ t('عنوان دعوة التتبّع') }}</label><input v-model="f.cta_title" :placeholder="t('تابع حالة تصليحك')" /></div>
        <div class="frow"><label>{{ t('نص دعوة التتبّع') }}</label><input v-model="f.cta_text" /></div>
      </div>
    </div>

    <div class="panel" style="max-width:720px;margin-top:16px">
      <h3 class="sec-t"><i class="ti ti-palette" style="color:var(--accent)"></i> {{ t('مظهر الموقع') }}</h3>

      <div class="frow">
        <label>{{ t('اللون الأساسي للموقع') }}</label>
        <div class="color-row">
          <input type="color" v-model="f.theme_primary" class="swatch" />
          <input v-model="f.theme_primary" dir="ltr" class="hex" placeholder="#185FA5" />
          <span v-for="c in presets" :key="c" class="preset" :style="{ background: c }" @click="f.theme_primary = c" :title="c"></span>
          <button class="btn mini-btn" type="button" @click="f.theme_primary = '#185FA5'">{{ t('افتراضي') }}</button>
        </div>
        <div class="hint">{{ t('يُطبَّق على الأزرار والشعار والعناوين في موقع محلك العام.') }}</div>
      </div>

      <label class="block-label">{{ t('الأقسام الظاهرة في الصفحة الرئيسية') }}</label>
      <div class="toggles">
        <label class="tg"><input type="checkbox" v-model="f.show_quote" true-value="1" false-value="0" /> <span>{{ t('حاسبة السعر الفوري') }}</span></label>
        <label class="tg"><input type="checkbox" v-model="f.show_services" true-value="1" false-value="0" /> <span>{{ t('قائمة الخدمات') }}</span></label>
        <label class="tg"><input type="checkbox" v-model="f.show_why" true-value="1" false-value="0" /> <span>{{ t('قسم «ليش تختارنا»') }}</span></label>
        <label class="tg"><input type="checkbox" v-model="f.show_track_cta" true-value="1" false-value="0" /> <span>{{ t('صندوق تتبّع الطلب') }}</span></label>
      </div>

      <a :href="'/s/' + (f.slug || 'main')" target="_blank" class="link" style="display:inline-block;margin-top:14px"><i class="ti ti-external-link"></i> {{ t('معاينة موقعي العام') }}</a>
    </div>

    <button class="btn btn-accent btn-lg" style="margin-top:16px" @click="save" :disabled="busy"><i class="ti ti-device-floppy"></i> {{ busy ? '...' : t('حفظ الإعدادات') }}</button>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t, isEn } = useI18n()
const f = ref({})
const busy = ref(false)
const saved = ref(false)
const presets = ['#185FA5', '#0F6E56', '#7A3EB1', '#C2410C', '#B4245B', '#111827']

async function save() {
  busy.value = true; saved.value = false
  try { await api('/dashboard/settings', { method: 'PUT', body: f.value }); saved.value = true }
  finally { busy.value = false }
}

onMounted(async () => {
  const s = await api('/dashboard/settings')
  // قيم افتراضية للمظهر
  if (!s.theme_primary) s.theme_primary = '#185FA5'
  for (const k of ['show_quote', 'show_services', 'show_why', 'show_track_cta']) {
    if (s[k] === undefined || s[k] === null || s[k] === '') s[k] = '1'
  }
  f.value = s
})
</script>

<style scoped>
.sec-t i { margin-inline-end: 4px; }
.color-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.swatch { width: 52px; height: 40px; padding: 2px; border: 1px solid var(--line); border-radius: 8px; cursor: pointer; background: #fff; }
.hex { width: 130px; text-align: left; }
.preset { width: 26px; height: 26px; border-radius: 7px; cursor: pointer; border: 2px solid #fff; box-shadow: 0 0 0 1px var(--line); }
.preset:hover { transform: scale(1.1); }
.mini-btn { height: 34px; font-size: 13px; }
.hint { font-size: 12px; color: var(--muted); margin-top: 6px; }
.block-label { display: block; margin: 18px 0 10px; font-size: 13px; font-weight: 700; color: var(--ink); }
.toggles { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.tg { display: flex; align-items: center; gap: 9px; background: #F7F9FC; border: 1px solid var(--line); border-radius: 10px; padding: 11px 14px; font-size: 14px; cursor: pointer; }
.tg input { width: 17px; height: 17px; cursor: pointer; }
@media (max-width: 700px) { .toggles { grid-template-columns: 1fr; } }
</style>
