<template>
  <DashShell active="ticket-new">
    <div class="ph">
      <div><div class="page-title">{{ tr('تذكرة تصليح جديدة') }}</div><p class="page-sub">{{ tr('سجّل جهاز الزبون وافتح تذكرة') }}</p></div>
      <NuxtLink to="/admin/tickets" class="btn btn-out"><i class="ti ti-arrow-right"></i> {{ tr('رجوع') }}</NuxtLink>
    </div>

    <div v-if="done" class="ok-box" style="max-width:520px">
      <i class="ti ti-circle-check" style="font-size:26px"></i>
      <div style="margin-top:8px">{{ tr('تم فتح التذكرة') }} <b>{{ done }}</b></div>
      <div style="margin-top:12px;display:flex;gap:10px;justify-content:center">
        <NuxtLink to="/admin/tickets" class="btn btn-out">{{ tr('عرض التذاكر') }}</NuxtLink>
        <button class="btn btn-accent" @click="reset">{{ tr('تذكرة أخرى') }}</button>
      </div>
    </div>

    <div v-else class="cols">
      <div class="panel">
        <div class="sec-t"><i class="ti ti-user" style="color:var(--accent)"></i> {{ tr('بيانات الزبون والجهاز') }}</div>
        <div class="g2 frow">
          <div><label>{{ tr('اسم الزبون *') }}</label><input v-model="f.customer_name" :placeholder="tr('الاسم الكامل')" /></div>
          <div><label>{{ tr('رقم الجوال *') }}</label><input v-model="f.customer_phone" placeholder="05xxxxxxxx" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
        </div>
        <div class="g2 frow">
          <div><label>{{ tr('الماركة') }}</label>
            <select v-model="f.make" @change="onMake"><option v-for="m in cat.makes" :key="m.id" :value="m.name">{{ m.name }}</option></select>
          </div>
          <div><label>{{ tr('الموديل') }}</label>
            <select v-model="f.model"><option v-for="md in models" :key="md.id" :value="md.name">{{ md.name }}</option></select>
          </div>
        </div>
        <div class="g2 frow">
          <div><label>{{ tr('IMEI / الرقم التسلسلي') }}</label><input v-model="f.imei" placeholder="356..." dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          <div><label>{{ tr('نوع العطل') }}</label>
            <select v-model="f.repair_type_id" @change="autoPrice"><option :value="null">{{ tr('— اختر —') }}</option><option v-for="t in cat.repair_types" :key="t.id" :value="t.id">{{ t.name }}</option></select>
          </div>
        </div>
        <div class="frow"><label>{{ tr('وصف المشكلة') }}</label><textarea v-model="f.description" rows="3" :placeholder="tr('مثال: الشاشة مكسورة واللمس لا يعمل...')"></textarea></div>
      </div>

      <div class="panel">
        <div class="sec-t"><i class="ti ti-receipt" style="color:var(--accent)"></i> {{ tr('السعر والإسناد') }}</div>
        <div class="frow"><label>{{ tr('السعر التقديري (ر.س)') }}</label><input v-model.number="f.price" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
        <div class="frow"><label>{{ tr('الضمان (أشهر)') }}</label>
          <select v-model.number="f.warranty_months"><option :value="6">{{ tr('٦ أشهر') }}</option><option :value="3">{{ tr('٣ أشهر') }}</option><option :value="1">{{ tr('شهر واحد') }}</option><option :value="0">{{ tr('بدون') }}</option></select>
        </div>
        <div class="frow"><label>{{ tr('إسناد إلى فني') }}</label>
          <select v-model="f.technician_id"><option :value="null">{{ tr('— بدون —') }}</option><option v-for="t in techs" :key="t.id" :value="t.id">{{ t.name }}</option></select>
        </div>
        <div class="frow"><label>{{ tr('الحالة') }}</label>
          <select v-model="f.status"><option v-for="st in statuses" :key="st.key" :value="st.key">{{ tr(st.label) }}</option></select>
        </div>
        <div class="hint"><i class="ti ti-bell"></i> {{ tr('سيصل الزبون رسالة عند فتح التذكرة وكل تغيير للحالة (عند تفعيل واتساب).') }}</div>
        <div v-if="err" class="err">{{ err }}</div>
        <button class="btn btn-accent btn-block btn-lg" :disabled="busy" @click="submit"><i class="ti ti-check"></i> {{ tr('فتح التذكرة') }}</button>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t: tr, isEn } = useI18n()
const cat = ref({ makes: [], repair_types: [] })
const techs = ref([])
const statuses = ref([])
const done = ref('')
const err = ref('')
const busy = ref(false)

const blank = () => ({ customer_name: '', customer_phone: '', make: '', model: '', imei: '', repair_type_id: null, description: '', price: null, warranty_months: 6, technician_id: null, status: 'received' })
const f = ref(blank())

const models = computed(() => cat.value.makes.find(m => m.name === f.value.make)?.models || [])
function onMake() { f.value.model = models.value[0]?.name || '' }

async function autoPrice() {
  const make = cat.value.makes.find(m => m.name === f.value.make)
  const model = models.value.find(x => x.name === f.value.model)
  if (!make || !f.value.repair_type_id) return
  try {
    const r = await api('/site/quote', { method: 'POST', body: { make_id: make.id, car_model_id: model?.id || null, repair_type_id: f.value.repair_type_id } })
    if (r.found) { f.value.price = r.price; f.value.warranty_months = r.warranty_months }
  } catch {}
}

async function submit() {
  err.value = ''
  if (!f.value.customer_name || !f.value.customer_phone) { err.value = 'اسم الزبون ورقم الجوال مطلوبان'; return }
  busy.value = true
  try {
    const r = await api('/dashboard/tickets', { method: 'POST', body: f.value })
    done.value = r.ticket_no
  } catch (e) { err.value = 'تعذّر حفظ التذكرة — تحقق من الحقول' } finally { busy.value = false }
}
function reset() { done.value = ''; f.value = blank(); onMake() }

onMounted(async () => {
  cat.value = await api('/site/catalog')
  f.value.make = cat.value.makes[0]?.name || ''; onMake()
  techs.value = await api('/dashboard/technicians')
  statuses.value = (await api('/dashboard/tickets')).statuses.filter(s => s.key !== 'cancelled')
})
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.cols { display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px; align-items: start; }
.hint { background: #E6F1FB; border-radius: 9px; padding: 11px 13px; font-size: 12px; color: var(--accent-d); display: flex; gap: 8px; margin: 4px 0 14px; }
@media (max-width: 900px) { .cols { grid-template-columns: 1fr; } }
</style>
