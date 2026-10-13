<template>
  <DashShell active="prices">
    <div class="ph">
      <div><div class="page-title">{{ tr('الأسعار والأعطال') }}</div><p class="page-sub">{{ tr('جدول الأسعار الذي يغذّي حاسبة السعر في الموقع') }}</p></div>
      <button class="btn btn-accent" @click="openNew"><i class="ti ti-plus"></i> {{ tr('إضافة سعر') }}</button>
    </div>

    <div class="info"><i class="ti ti-info-circle"></i> {{ tr('أي تعديل هنا يظهر مباشرة للزبون في «حاسبة السعر الفوري».') }}</div>

    <table>
      <thead><tr><th>{{ tr('الماركة') }}</th><th>{{ tr('الموديل') }}</th><th>{{ tr('العطل') }}</th><th>{{ tr('السعر') }}</th><th>{{ tr('المدة') }}</th><th>{{ tr('الضمان') }}</th><th></th></tr></thead>
      <tbody>
        <tr v-for="r in rules" :key="r.id">
          <td>{{ r.make?.name }}</td>
          <td>{{ r.model?.name || tr('كل الموديلات') }}</td>
          <td>{{ r.repair_type?.name }}</td>
          <td><b>{{ fmt(r.price) }} {{ tr('ر.س') }}</b></td>
          <td>{{ r.duration_minutes }} {{ tr('د') }}</td>
          <td>{{ r.warranty_months }} {{ tr('ش') }}</td>
          <td style="text-align:left">
            <button class="act" @click="edit(r)"><i class="ti ti-edit"></i></button>
            <button class="act danger" @click="remove(r)"><i class="ti ti-trash"></i></button>
          </td>
        </tr>
        <tr v-if="!rules.length"><td colspan="7" class="empty">{{ tr('لا توجد أسعار بعد') }}</td></tr>
      </tbody>
    </table>

    <div v-if="form" class="ov" @click.self="form = null">
      <div class="modal">
        <div class="sec-t">{{ form.id ? tr('تعديل سعر') : tr('إضافة سعر') }}</div>
        <div class="g2 frow">
          <div><label>{{ tr('الماركة *') }}</label><select v-model="form.make_id" :disabled="!!form.id" @change="form.car_model_id = null"><option :value="null">{{ tr('— اختر —') }}</option><option v-for="m in cat.makes" :key="m.id" :value="m.id">{{ m.name }}</option></select></div>
          <div><label>{{ tr('الموديل') }}</label><select v-model="form.car_model_id" :disabled="!!form.id"><option :value="null">{{ tr('كل الموديلات') }}</option><option v-for="md in modelsFor" :key="md.id" :value="md.id">{{ md.name }}</option></select></div>
        </div>
        <div class="frow"><label>{{ tr('نوع العطل *') }}</label><select v-model="form.repair_type_id" :disabled="!!form.id"><option :value="null">{{ tr('— اختر —') }}</option><option v-for="t in cat.repair_types" :key="t.id" :value="t.id">{{ t.name }}</option></select></div>
        <div class="g3 frow">
          <div><label>{{ tr('السعر (ر.س) *') }}</label><input v-model.number="form.price" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          <div><label>{{ tr('المدة (دقيقة)') }}</label><input v-model.number="form.duration_minutes" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          <div><label>{{ tr('الضمان (أشهر)') }}</label><input v-model.number="form.warranty_months" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
        </div>
        <div v-if="err" class="err">{{ err }}</div>
        <div style="display:flex;gap:10px;margin-top:6px">
          <button class="btn btn-accent" @click="save"><i class="ti ti-check"></i> {{ tr('حفظ') }}</button>
          <button class="btn btn-out" @click="form = null">{{ tr('إلغاء') }}</button>
        </div>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { confirmDlg } = useConfirm()
const { t: tr, isEn } = useI18n()
const rules = ref([])
const cat = ref({ makes: [], repair_types: [] })
const form = ref(null)
const err = ref('')
const fmt = (n) => Number(n).toLocaleString('ar-EG')

const modelsFor = computed(() => cat.value.makes.find(m => m.id === form.value?.make_id)?.models || [])

async function load() { rules.value = await api('/dashboard/price-rules') }
function openNew() { err.value = ''; form.value = { make_id: null, car_model_id: null, repair_type_id: null, price: null, duration_minutes: 45, warranty_months: 3 } }
function edit(r) {
  err.value = ''
  form.value = {
    id: r.id,
    make_id: r.make?.id ?? r.make_id,
    car_model_id: r.model?.id ?? r.car_model_id ?? null,
    repair_type_id: r.repair_type?.id ?? r.repair_type_id,
    price: Number(r.price),
    duration_minutes: r.duration_minutes,
    warranty_months: r.warranty_months,
  }
}
async function save() {
  err.value = ''
  if (!form.value.make_id || !form.value.repair_type_id || !form.value.price) { err.value = tr('الماركة والعطل والسعر مطلوبة'); return }
  try {
    if (form.value.id) await api('/dashboard/price-rules/' + form.value.id, { method: 'PUT', body: form.value })
    else await api('/dashboard/price-rules', { method: 'POST', body: form.value })
    form.value = null; await load()
  } catch { err.value = tr('تعذّر الحفظ') }
}
async function remove(r) {
  if (!await confirmDlg(tr('حذف السعر'), `${r.make?.name} - ${r.repair_type?.name}`)) return
  await api('/dashboard/price-rules/' + r.id, { method: 'DELETE' }); await load()
}
onMounted(async () => { await load(); cat.value = await api('/site/catalog') })
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; flex-wrap: wrap; gap: 12px; }
.info { background: #E6F1FB; color: var(--accent-d); padding: 12px 15px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; display: flex; gap: 8px; }
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.4); z-index: 50; display: flex; align-items: center; justify-content: center; }
.modal { background: #fff; border-radius: 14px; padding: 22px; width: 480px; max-width: 92vw; }
</style>
