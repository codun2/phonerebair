<template>
  <DashShell active="customers">
    <div class="ph">
      <div><div class="page-title">{{ isEdit ? t('تعديل بيانات العميل') : t('عميل جديد') }}</div>
        <p class="page-sub">{{ t('سجّل بيانات العميل كاملة لمتابعة أجهزته وفواتيره') }}</p></div>
      <NuxtLink :to="isEdit ? '/admin/customers/' + editId : '/admin/customers'" class="btn btn-out"><i class="ti ti-arrow-right"></i> {{ t('رجوع') }}</NuxtLink>
    </div>

    <div class="cols">
      <div>
        <!-- نوع العميل -->
        <div class="panel">
          <div class="sec-t"><i class="ti ti-user-circle" style="color:var(--accent)"></i> {{ t('نوع العميل') }}</div>
          <div class="seg">
            <button type="button" class="seg-btn" :class="{ on: f.customer_type === 'individual' }" @click="f.customer_type = 'individual'">
              <i class="ti ti-user"></i> {{ t('فرد') }}
            </button>
            <button type="button" class="seg-btn" :class="{ on: f.customer_type === 'business' }" @click="f.customer_type = 'business'">
              <i class="ti ti-building-store"></i> {{ t('شركة / مؤسسة') }}
            </button>
          </div>
        </div>

        <!-- البيانات الأساسية -->
        <div class="panel">
          <div class="sec-t"><i class="ti ti-id" style="color:var(--accent)"></i> {{ t('البيانات الأساسية') }}</div>
          <div class="g2 frow">
            <div><label>{{ t('الاسم') }} <span class="req">*</span></label><input v-model="f.name" :placeholder="t('الاسم الكامل')" /></div>
            <div><label>{{ t('رقم الجوال') }} <span class="req">*</span></label><input v-model="f.phone" placeholder="05xxxxxxxx" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          </div>
          <div class="g2 frow">
            <div><label>{{ t('جوال إضافي') }}</label><input v-model="f.alt_phone" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
            <div><label>{{ t('البريد الإلكتروني') }}</label><input v-model="f.email" type="email" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          </div>
        </div>

        <!-- بيانات الشركة (للشركات) -->
        <div class="panel" v-if="f.customer_type === 'business'">
          <div class="sec-t"><i class="ti ti-building" style="color:var(--accent)"></i> {{ t('بيانات الشركة') }}</div>
          <div class="g2 frow">
            <div><label>{{ t('اسم الشركة / المؤسسة') }}</label><input v-model="f.company" /></div>
            <div><label>{{ t('الرقم الضريبي') }}</label><input v-model="f.tax_number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" :placeholder="t('يظهر في الفاتورة')" /></div>
          </div>
        </div>

        <!-- العنوان -->
        <div class="panel">
          <div class="sec-t"><i class="ti ti-map-pin" style="color:var(--accent)"></i> {{ t('العنوان') }}</div>
          <div class="g2 frow">
            <div><label>{{ t('المدينة') }}</label><input v-model="f.city" /></div>
            <div><label>{{ t('العنوان التفصيلي') }}</label><input v-model="f.address" /></div>
          </div>
        </div>
      </div>

      <!-- جانبي: ملاحظات + حفظ -->
      <div class="panel side">
        <div class="sec-t"><i class="ti ti-notes" style="color:var(--accent)"></i> {{ t('ملاحظات') }}</div>
        <div class="frow"><textarea v-model="f.notes" rows="5" :placeholder="t('ملاحظات وشكاوى سابقة أو تفضيلات العميل…')"></textarea></div>
        <div v-if="err" class="err">{{ err }}</div>
        <button class="btn btn-accent btn-block btn-lg" :disabled="busy" @click="save">
          <i class="ti ti-check"></i> {{ busy ? '...' : (isEdit ? t('حفظ التعديل') : t('حفظ العميل')) }}
        </button>
        <p class="side-hint">{{ t('بعد الحفظ يمكنك فتح تذكرة له مباشرة وتتبّع كل أجهزته.') }}</p>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t, isEn } = useI18n()
const route = useRoute()

const editId = computed(() => route.query.edit || null)
const isEdit = computed(() => !!editId.value)
const busy = ref(false)
const err = ref('')

const blank = () => ({ customer_type: 'individual', name: '', phone: '', alt_phone: '', email: '', company: '', tax_number: '', city: '', address: '', notes: '' })
const f = ref(blank())

async function save() {
  err.value = ''
  if (!f.value.name || !f.value.phone) { err.value = t('الاسم ورقم الجوال مطلوبان'); return }
  busy.value = true
  try {
    if (isEdit.value) {
      await api('/dashboard/customers/' + editId.value, { method: 'PUT', body: f.value })
      await navigateTo('/admin/customers/' + editId.value)
    } else {
      const r = await api('/dashboard/customers', { method: 'POST', body: f.value })
      await navigateTo(r?.id ? '/admin/customers/' + r.id : '/admin/customers')
    }
  } catch (e) { err.value = e?.data?.message || t('تعذّر الحفظ') } finally { busy.value = false }
}

onMounted(async () => {
  if (isEdit.value) {
    try {
      const r = await api('/dashboard/customers/' + editId.value)
      const c = r.customer
      f.value = { customer_type: c.customer_type || 'individual', name: c.name, phone: c.phone, alt_phone: c.alt_phone, email: c.email, company: c.company, tax_number: c.tax_number, city: c.city, address: c.address, notes: c.notes }
    } catch { err.value = t('تعذّر تحميل بيانات العميل') }
  }
})
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.cols { display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px; align-items: start; }
.cols > div > .panel { margin-bottom: 16px; }
.req { color: #A32020; }
.seg { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.seg-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px; border: 1.5px solid var(--line); border-radius: 11px; background: #fff; font-family: inherit; font-size: 14px; font-weight: 600; color: var(--muted); cursor: pointer; transition: all .15s; }
.seg-btn i { font-size: 19px; }
.seg-btn:hover { border-color: #C7CCD6; }
.seg-btn.on { border-color: var(--accent); background: #E6F1FB; color: var(--accent-d); }
.side { position: sticky; top: 20px; }
.side-hint { font-size: 12px; color: var(--muted); margin-top: 12px; text-align: center; line-height: 1.7; }
@media (max-width: 900px) { .cols { grid-template-columns: 1fr; } .side { position: static; } }
</style>
