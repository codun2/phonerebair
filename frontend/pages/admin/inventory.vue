<template>
  <DashShell active="inventory">
    <div class="ph">
      <div><div class="page-title">{{ t('المخزون') }}</div><p class="page-sub">{{ t('قطع الغيار والإكسسوارات وحركاتها') }}</p></div>
      <button class="btn btn-accent" @click="openNew"><i class="ti ti-plus"></i> {{ t('إضافة قطعة') }}</button>
    </div>

    <div class="toolbar">
      <div class="search"><i class="ti ti-search"></i><input v-model="q" :placeholder="t('بحث بالاسم / SKU / التوافق / المورّد')" /></div>
    </div>

    <table>
      <thead><tr><th>{{ t('القطعة') }}</th><th>{{ t('التوافق') }}</th><th>{{ t('المورّد') }}</th><th>{{ t('الكمية') }}</th><th>{{ t('التكلفة') }}</th><th>{{ t('سعر البيع') }}</th><th>{{ t('الحالة') }}</th><th></th></tr></thead>
      <tbody>
        <tr v-for="it in filtered" :key="it.id">
          <td><b>{{ it.name }}</b><div class="muted">{{ it.sku }}</div></td>
          <td>{{ it.compatible || '—' }}</td>
          <td>{{ it.supplier || '—' }}</td>
          <td><b>{{ it.qty }}</b></td>
          <td>{{ it.cost ? fmt(it.cost) : '—' }}</td>
          <td>{{ it.sale_price ? fmt(it.sale_price) : '—' }}</td>
          <td><span class="pill" :class="it.is_low ? 'pill-r' : 'pill-g'">{{ it.qty === 0 ? t('نفدت') : (it.is_low ? t('قارب النفاد') : t('متوفر')) }}</span></td>
          <td style="text-align:left;white-space:nowrap">
            <button class="act" :title="t('حركة مخزون')" @click="openMove(it)"><i class="ti ti-arrows-exchange"></i></button>
            <button class="act" :title="t('سجل الحركات')" @click="openLog(it)"><i class="ti ti-history"></i></button>
            <button class="act" @click="edit(it)"><i class="ti ti-edit"></i></button>
            <button class="act danger" @click="remove(it)"><i class="ti ti-trash"></i></button>
          </td>
        </tr>
        <tr v-if="!filtered.length"><td colspan="8" class="empty">{{ t('لا توجد قطع') }}</td></tr>
      </tbody>
    </table>

    <!-- إضافة/تعديل -->
    <div v-if="form" class="ov" @click.self="form = null">
      <div class="modal">
        <div class="sec-t">{{ form.id ? t('تعديل قطعة') : t('قطعة جديدة') }}</div>
        <div class="frow"><label>{{ t('الاسم *') }}</label><input v-model="form.name" placeholder="شاشة OLED" /></div>
        <div class="g2 frow">
          <div><label>SKU</label><input v-model="form.sku" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          <div><label>{{ t('التوافق') }}</label><input v-model="form.compatible" placeholder="iPhone 14" /></div>
        </div>
        <div class="g2 frow">
          <div><label>{{ t('المورّد') }}</label><input v-model="form.supplier" :placeholder="t('اسم المورّد')" /></div>
          <div v-if="!form.id"><label>{{ t('الكمية الافتتاحية') }}</label><input v-model.number="form.qty" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          <div v-else><label>{{ t('الكمية') }}</label><input :value="form.qty" disabled :title="t('تتغير عبر الحركات فقط')" /></div>
        </div>
        <div class="g3 frow">
          <div><label>{{ t('تكلفة الشراء') }}</label><input v-model.number="form.cost" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          <div><label>{{ t('سعر البيع') }}</label><input v-model.number="form.sale_price" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          <div><label>{{ t('حد التنبيه') }}</label><input v-model.number="form.low_threshold" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
        </div>
        <div v-if="form.id" class="hint"><i class="ti ti-info-circle"></i> {{ t('الكمية تتغير فقط عبر «حركة مخزون» (توريد/تالف/تسوية) حتى يبقى السجل دقيقاً.') }}</div>
        <div v-if="err" class="err">{{ err }}</div>
        <div style="display:flex;gap:10px;margin-top:6px">
          <button class="btn btn-accent" @click="save"><i class="ti ti-check"></i> {{ t('حفظ') }}</button>
          <button class="btn btn-out" @click="form = null">{{ t('إلغاء') }}</button>
        </div>
      </div>
    </div>

    <!-- حركة مخزون -->
    <div v-if="mv" class="ov" @click.self="mv = null">
      <div class="modal">
        <div class="sec-t">{{ t('حركة مخزون') }} — {{ mv.item.name }} <span class="muted">({{ t('المتوفر') }} {{ mv.item.qty }})</span></div>
        <div class="frow"><label>{{ t('نوع الحركة') }}</label>
          <select v-model="mv.type">
            <option value="purchase">{{ t('شراء/توريد (+)') }}</option>
            <option value="return">{{ t('إرجاع من زبون (+)') }}</option>
            <option value="damage">{{ t('تالف (−)') }}</option>
            <option value="adjust">{{ t('تسوية جرد (تحديد الكمية الفعلية)') }}</option>
          </select>
        </div>
        <div class="g2 frow">
          <div><label>{{ mv.type === 'adjust' ? t('الكمية الفعلية بعد الجرد') : t('الكمية') }}</label><input v-model.number="mv.qty" type="number" min="0" dir="ltr" /></div>
          <div v-if="mv.type === 'purchase'"><label>{{ t('تكلفة الوحدة الجديدة') }}</label><input v-model.number="mv.unit_cost" type="number" dir="ltr" /></div>
        </div>
        <div class="frow"><label>{{ t('ملاحظة') }}</label><input v-model="mv.note" :placeholder="t('رقم فاتورة المورّد...')" /></div>
        <div v-if="errMv" class="err">{{ errMv }}</div>
        <div style="display:flex;gap:10px">
          <button class="btn btn-accent" :disabled="mv.qty === null || mv.qty < 0" @click="saveMove"><i class="ti ti-check"></i> {{ t('تسجيل الحركة') }}</button>
          <button class="btn btn-out" @click="mv = null">{{ t('إلغاء') }}</button>
        </div>
      </div>
    </div>

    <!-- سجل الحركات -->
    <div v-if="log" class="ov" @click.self="log = null">
      <div class="modal" style="width:620px">
        <div class="sec-t">{{ t('سجل الحركات') }} — {{ log.item.name }}</div>
        <table>
          <thead><tr><th>{{ t('النوع') }}</th><th>{{ t('الكمية') }}</th><th>{{ t('التذكرة') }}</th><th>{{ t('ملاحظة') }}</th><th>{{ t('بواسطة') }}</th><th>{{ t('الوقت') }}</th></tr></thead>
          <tbody>
            <tr v-for="m in log.movements" :key="m.id">
              <td>{{ t(m.type_label) }}</td>
              <td><b :style="m.qty < 0 ? 'color:#A32020' : 'color:var(--green)'" dir="ltr">{{ m.qty > 0 ? '+' + m.qty : m.qty }}</b></td>
              <td><NuxtLink v-if="m.ticket_id" :to="'/admin/tickets/' + m.ticket_id" class="link">{{ m.ticket_no }}</NuxtLink><span v-else class="muted">—</span></td>
              <td class="muted">{{ m.note || '—' }}</td>
              <td class="muted">{{ m.user || '—' }}</td>
              <td class="muted">{{ m.created_at }}</td>
            </tr>
            <tr v-if="!log.movements.length"><td colspan="6" class="empty">{{ t('لا حركات بعد') }}</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { confirmDlg } = useConfirm()
const { has, ensure } = useFeatures()
const { t, isEn } = useI18n()
const items = ref([])
const form = ref(null)
const mv = ref(null)
const log = ref(null)
const q = ref('')
const err = ref('')
const errMv = ref('')
const fmt = (n) => Number(n).toLocaleString(isEn.value ? 'en-US' : 'ar-EG')

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return items.value
  return items.value.filter((i) => [i.name, i.sku, i.compatible, i.supplier].some((x) => (x || '').toLowerCase().includes(s)))
})

async function load() { items.value = await api('/dashboard/inventory') }
function openNew() { err.value = ''; form.value = { name: '', sku: '', compatible: '', supplier: '', qty: 0, cost: null, sale_price: null, low_threshold: 3 } }
function edit(it) { err.value = ''; form.value = { ...it } }
function openMove(it) { errMv.value = ''; mv.value = { item: it, type: 'purchase', qty: null, unit_cost: it.cost, note: '' } }
async function openLog(it) { log.value = await api('/dashboard/inventory/' + it.id + '/movements') }

async function save() {
  err.value = ''
  if (!form.value.name) { err.value = t('الاسم مطلوب'); return }
  try {
    if (form.value.id) await api('/dashboard/inventory/' + form.value.id, { method: 'PUT', body: form.value })
    else await api('/dashboard/inventory', { method: 'POST', body: form.value })
    form.value = null; await load()
  } catch (e) { err.value = e?.data?.message || t('تعذّر الحفظ') }
}
async function saveMove() {
  errMv.value = ''
  try {
    await api('/dashboard/inventory/' + mv.value.item.id + '/movements', { method: 'POST', body: { type: mv.value.type, qty: mv.value.qty, unit_cost: mv.value.unit_cost || undefined, note: mv.value.note || undefined } })
    mv.value = null; await load()
  } catch (e) { errMv.value = e?.data?.message || t('تعذّر الحفظ') }
}
async function remove(it) {
  if (!await confirmDlg(t('حذف القطعة'), it.name)) return
  await api('/dashboard/inventory/' + it.id, { method: 'DELETE' }); await load()
}
onMounted(async () => {
  await ensure()
  if (!has('inventory')) return navigateTo('/admin')
  await load()
})
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.toolbar { margin-bottom: 14px; }
.search { position: relative; max-width: 420px; }
.search i { position: absolute; inset-inline-start: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); }
.search input { width: 100%; padding-inline-start: 36px; }
.g3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.muted { color: var(--muted); font-size: 12px; }
.hint { background: #E6F1FB; border-radius: 9px; padding: 10px 13px; font-size: 12px; color: var(--accent-d); display: flex; gap: 8px; margin-bottom: 12px; }
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.4); z-index: 50; display: flex; align-items: center; justify-content: center; }
.modal { background: #fff; border-radius: 14px; padding: 22px; width: 480px; max-width: 92vw; max-height: 90vh; overflow-y: auto; }
</style>
