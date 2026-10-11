<template>
  <DashShell active="settings">
    <h1 class="page-title">الإعدادات</h1>
    <p class="page-sub">معلومات المحل والنصوص التي تظهر للزبائن في الموقع</p>

    <div v-if="saved" class="ok-box" style="margin-bottom:16px;max-width:720px">تم حفظ الإعدادات ✓</div>

    <div class="panel" style="max-width:720px">
      <h3 class="sec-t"><i class="ti ti-building-store" style="color:var(--accent)"></i> معلومات المحل</h3>
      <div class="g2">
        <div class="frow"><label>اسم المحل</label><input v-model="f.shop_name" /></div>
        <div class="frow"><label>الهاتف</label><input v-model="f.phone" dir="ltr" style="text-align:right" /></div>
        <div class="frow"><label>واتساب (صيغة دولية)</label><input v-model="f.whatsapp" placeholder="9705..." dir="ltr" style="text-align:right" /></div>
        <div class="frow"><label>ساعات الدوام</label><input v-model="f.hours" /></div>
      </div>
      <div class="frow"><label>العنوان</label><input v-model="f.address" /></div>
      <div class="frow"><label>نبذة عن المحل</label><textarea v-model="f.about" rows="2"></textarea></div>
    </div>

    <div class="panel" style="max-width:720px;margin-top:16px">
      <h3 class="sec-t"><i class="ti ti-typography" style="color:var(--accent)"></i> نصوص الصفحة الرئيسية</h3>
      <div class="frow"><label>شارة الهيرو</label><input v-model="f.hero_badge" placeholder="صيانة معتمدة · ضمان على كل تصليح" /></div>
      <div class="frow"><label>عنوان الهيرو</label><input v-model="f.hero_title" placeholder="جهازك بأمان — تصليح احترافي بنفس اليوم" /></div>
      <div class="frow"><label>وصف الهيرو</label><textarea v-model="f.hero_subtitle" rows="2"></textarea></div>
      <div class="g2">
        <div class="frow"><label>عنوان دعوة التتبّع</label><input v-model="f.cta_title" placeholder="تابع حالة تصليحك" /></div>
        <div class="frow"><label>نص دعوة التتبّع</label><input v-model="f.cta_text" /></div>
      </div>
    </div>

    <button class="btn btn-accent btn-lg" style="margin-top:16px" @click="save" :disabled="busy"><i class="ti ti-device-floppy"></i> {{ busy ? '...' : 'حفظ الإعدادات' }}</button>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const f = ref({})
const busy = ref(false)
const saved = ref(false)

async function save() {
  busy.value = true; saved.value = false
  try { await api('/dashboard/settings', { method: 'PUT', body: f.value }); saved.value = true }
  finally { busy.value = false }
}
onMounted(async () => { f.value = await api('/dashboard/settings') })
</script>

<style scoped>
.sec-t i { margin-inline-end: 4px; }
</style>
