<template>
  <DashShell active="suppliers">
    <div class="ph">
      <div><div class="page-title">{{ isEdit ? t('تعديل بيانات المورد') : t('مورّد جديد') }}</div>
        <p class="page-sub">{{ t('سجّل بيانات المورد كاملة لمتابعة مشترياتك منه') }}</p></div>
      <NuxtLink :to="isEdit ? '/admin/suppliers/' + editId : '/admin/suppliers'" class="btn btn-out"><i class="ti ti-arrow-right"></i> {{ t('رجوع') }}</NuxtLink>
    </div>

    <div class="cols">
      <div>
        <div class="panel">
          <div class="sec-t"><i class="ti ti-truck" style="color:var(--accent)"></i> {{ t('البيانات الأساسية') }}</div>
          <div class="g2 frow">
            <div><label>{{ t('اسم المورد') }} <span class="req">*</span></label><input v-model="f.name" :placeholder="t('مثال: شركة القطع الذهبية')" /></div>
            <div><label>{{ t('الشخص المسؤول') }}</label><input v-model="f.contact_name" /></div>
          </div>
          <div class="g2 frow">
            <div><label>{{ t('التخصص') }}</label><input v-model="f.categories" :placeholder="t('شاشات، بطاريات، إكسسوارات…')" /></div>
            <div><label>{{ t('الحالة') }}</label>
              <select v-model="f.active"><option :value="true">{{ t('نشط') }}</option><option :value="false">{{ t('متوقف') }}</option></select>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="sec-t"><i class="ti ti-phone" style="color:var(--accent)"></i> {{ t('التواصل') }}</div>
          <div class="g2 frow">
            <div><label>{{ t('الجوال') }}</label><input v-model="f.phone" dir="ltr" :style="ltr" /></div>
            <div><label>{{ t('جوال إضافي') }}</label><input v-model="f.alt_phone" dir="ltr" :style="ltr" /></div>
          </div>
          <div class="g2 frow">
            <div><label>{{ t('واتساب') }}</label><input v-model="f.whatsapp" dir="ltr" :style="ltr" /></div>
            <div><label>{{ t('البريد الإلكتروني') }}</label><input v-model="f.email" type="email" dir="ltr" :style="ltr" /></div>
          </div>
          <div class="frow"><label>{{ t('الموقع الإلكتروني') }}</label><input v-model="f.website" dir="ltr" :style="ltr" placeholder="https://" /></div>
        </div>

        <div class="panel">
          <div class="sec-t"><i class="ti ti-map-pin" style="color:var(--accent)"></i> {{ t('العنوان') }}</div>
          <div class="g2 frow">
            <div><label>{{ t('الدولة') }}</label><input v-model="f.country" /></div>
            <div><label>{{ t('المدينة') }}</label><input v-model="f.city" /></div>
          </div>
          <div class="frow"><label>{{ t('العنوان التفصيلي') }}</label><input v-model="f.address" /></div>
        </div>

        <div class="panel">
          <div class="sec-t"><i class="ti ti-file-invoice" style="color:var(--accent)"></i> {{ t('التعامل المالي') }}</div>
          <div class="g2 frow">
            <div><label>{{ t('الرقم الضريبي') }}</label><input v-model="f.tax_number" dir="ltr" :style="ltr" /></div>
            <div><label>{{ t('شروط الدفع') }}</label><input v-model="f.payment_terms" :placeholder="t('نقدي / آجل 30 يوم…')" /></div>
          </div>
          <div class="frow"><label>{{ t('بيانات الحساب البنكي') }}</label><input v-model="f.bank_details" :placeholder="t('اسم البنك، IBAN…')" /></div>
        </div>
      </div>

      <div class="panel side">
        <div class="sec-t"><i class="ti ti-notes" style="color:var(--accent)"></i> {{ t('ملاحظات') }}</div>
        <div class="frow"><textarea v-model="f.notes" rows="5" :placeholder="t('جودة القطع، مدة التوصيل، ملاحظات التعامل…')"></textarea></div>
        <div v-if="err" class="err">{{ err }}</div>
        <button class="btn btn-accent btn-block btn-lg" :disabled="busy" @click="save">
          <i class="ti ti-check"></i> {{ busy ? '...' : (isEdit ? t('حفظ التعديل') : t('حفظ المورد')) }}
        </button>
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
const ltr = computed(() => ({ textAlign: isEn.value ? 'left' : 'right' }))

const blank = () => ({ name: '', contact_name: '', categories: '', active: true, phone: '', alt_phone: '', whatsapp: '', email: '', website: '', country: '', city: '', address: '', tax_number: '', payment_terms: '', bank_details: '', notes: '' })
const f = ref(blank())

async function save() {
  err.value = ''
  if (!f.value.name) { err.value = t('اسم المورد مطلوب'); return }
  busy.value = true
  try {
    if (isEdit.value) {
      await api('/dashboard/suppliers/' + editId.value, { method: 'PUT', body: f.value })
      await navigateTo('/admin/suppliers/' + editId.value)
    } else {
      const r = await api('/dashboard/suppliers', { method: 'POST', body: f.value })
      await navigateTo(r?.id ? '/admin/suppliers/' + r.id : '/admin/suppliers')
    }
  } catch (e) { err.value = e?.data?.message || t('تعذّر الحفظ') } finally { busy.value = false }
}

onMounted(async () => {
  if (isEdit.value) {
    try {
      const { supplier: s } = await api('/dashboard/suppliers/' + editId.value)
      f.value = { ...blank(), ...Object.fromEntries(Object.entries(s).filter(([k]) => k in blank())) }
    } catch { err.value = t('تعذّر تحميل بيانات المورد') }
  }
})
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.cols { display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px; align-items: start; }
.cols > div > .panel { margin-bottom: 16px; }
.req { color: #A32020; }
.side { position: sticky; top: 0; }
@media (max-width: 900px) { .cols { grid-template-columns: 1fr; } .side { position: static; } }
</style>
