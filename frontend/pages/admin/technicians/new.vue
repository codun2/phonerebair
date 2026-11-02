<template>
  <DashShell active="technicians">
    <div class="ph">
      <div><div class="page-title">{{ isEdit ? tr('تعديل فني') : tr('إضافة فني جديد') }}</div>
        <p class="page-sub">{{ tr('بيانات الفني وتخصصه وحالته في الفريق') }}</p></div>
      <NuxtLink to="/admin/technicians" class="btn btn-out"><i class="ti ti-arrow-right"></i> {{ tr('رجوع') }}</NuxtLink>
    </div>

    <div class="cols">
      <div>
        <div class="panel">
          <div class="sec-t"><i class="ti ti-user" style="color:var(--accent)"></i> {{ tr('المعلومات الأساسية') }}</div>
          <div class="frow"><label>{{ tr('الاسم') }} <span class="req">*</span></label><input v-model="f.name" :placeholder="tr('مثال: خالد ناصر')" /></div>
          <div class="g2 frow">
            <div><label>{{ tr('الجوال') }}</label><input v-model="f.phone" dir="ltr" :style="ltr" placeholder="05xxxxxxxx" /></div>
            <div><label>{{ tr('البريد الإلكتروني') }}</label><input v-model="f.email" type="email" dir="ltr" :style="ltr" placeholder="name@example.com" /></div>
          </div>
        </div>

        <div class="panel">
          <div class="sec-t"><i class="ti ti-tool" style="color:var(--accent)"></i> {{ tr('بيانات العمل') }}</div>
          <div class="frow">
            <label>{{ tr('التخصص') }}</label>
            <div class="chips">
              <button v-for="s in specialties" :key="s" type="button" class="chip" :class="{ on: f.specialty === s }" @click="f.specialty = s">{{ tr(s) }}</button>
            </div>
            <input v-model="f.specialty" :placeholder="tr('أو اكتب تخصصاً آخر')" style="margin-top:8px" />
          </div>
          <div class="frow">
            <label>{{ tr('الحالة') }}</label>
            <div class="seg">
              <button type="button" class="seg-btn" :class="{ on: f.active }" @click="f.active = true"><i class="ti ti-circle-check"></i> {{ tr('نشط') }}</button>
              <button type="button" class="seg-btn" :class="{ on: !f.active }" @click="f.active = false"><i class="ti ti-player-pause"></i> {{ tr('متوقف') }}</button>
            </div>
          </div>
        </div>
      </div>

      <div class="panel side">
        <div class="avatar">{{ initials(f.name) || '+' }}</div>
        <div class="sec-t" style="margin-top:14px"><i class="ti ti-notes" style="color:var(--accent)"></i> {{ tr('ملاحظات') }}</div>
        <div class="frow"><textarea v-model="f.notes" rows="4" :placeholder="tr('أي ملاحظة إضافية عن الفني (اختياري)')"></textarea></div>
        <div v-if="err" class="err">{{ err }}</div>
        <button class="btn btn-accent btn-block btn-lg" :disabled="busy" @click="save">
          <i class="ti ti-device-floppy"></i> {{ busy ? '...' : (isEdit ? tr('حفظ التعديل') : tr('حفظ الفني')) }}
        </button>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t: tr, isEn } = useI18n()
const route = useRoute()

const editId = computed(() => route.query.edit || null)
const isEdit = computed(() => !!editId.value)
const busy = ref(false)
const err = ref('')
const ltr = computed(() => ({ textAlign: isEn.value ? 'left' : 'right' }))
const specialties = ['فني أول', 'شاشات', 'بطاريات', 'لوحات أم (مايكرو)', 'سوفتوير', 'كاميرات ومنافذ شحن', 'صيانة عامة']
const initials = (name) => (name || '').trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('')

const f = ref({ name: '', phone: '', email: '', specialty: '', notes: '', active: true })

async function save() {
  err.value = ''
  if (!f.value.name) { err.value = tr('الاسم مطلوب'); return }
  busy.value = true
  try {
    if (isEdit.value) await api('/dashboard/technicians/' + editId.value, { method: 'PUT', body: f.value })
    else await api('/dashboard/technicians', { method: 'POST', body: f.value })
    await navigateTo('/admin/technicians')
  } catch (e) { err.value = e?.data?.message || tr('تعذّر الحفظ') } finally { busy.value = false }
}

onMounted(async () => {
  if (!isEdit.value) return
  try {
    const t = (await api('/dashboard/technicians')).find(x => String(x.id) === String(editId.value))
    if (t) f.value = { name: t.name, phone: t.phone || '', email: t.email || '', specialty: t.specialty || '', notes: t.notes || '', active: !!t.active }
    else err.value = tr('الفني غير موجود')
  } catch { err.value = tr('تعذّر تحميل بيانات الفني') }
})
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.cols { display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px; align-items: start; }
.cols > div > .panel { margin-bottom: 16px; }
.req { color: #A32020; }
.chips { display: flex; flex-wrap: wrap; gap: 8px; }
.chip { border: 1px solid var(--line); border-radius: 18px; padding: 7px 14px; font-size: 13px; background: #fff; font-family: inherit; cursor: pointer; color: var(--ink); }
.chip.on { background: var(--accent); border-color: var(--accent); color: #fff; }
.seg { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.seg-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: 1.5px solid var(--line); border-radius: 11px; background: #fff; font-family: inherit; font-size: 14px; font-weight: 600; color: var(--muted); cursor: pointer; }
.seg-btn.on { border-color: var(--accent); background: #E6F1FB; color: var(--accent-d); }
.side { position: sticky; top: 0; text-align: start; }
.avatar { width: 64px; height: 64px; border-radius: 16px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 22px; margin: 0 auto; }
@media (max-width: 900px) { .cols { grid-template-columns: 1fr; } .side { position: static; } }
</style>
