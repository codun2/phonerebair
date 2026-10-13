<template>
  <DashShell active="inventory">
    <div class="ph">
      <div><div class="page-title">{{ t('المخزون') }}</div><p class="page-sub">{{ t('قطع الغيار والإكسسوارات') }}</p></div>
      <button class="btn btn-accent" @click="openNew"><i class="ti ti-plus"></i> {{ t('إضافة قطعة') }}</button>
    </div>

    <table>
      <thead><tr><th>{{ t('القطعة') }}</th><th>{{ t('التوافق') }}</th><th>{{ t('الكمية') }}</th><th>{{ t('التكلفة') }}</th><th>{{ t('الحالة') }}</th><th></th></tr></thead>
      <tbody>
        <tr v-for="it in items" :key="it.id">
          <td><b>{{ it.name }}</b><div class="muted">{{ it.sku }}</div></td>
          <td>{{ it.compatible || '—' }}</td>
          <td>{{ it.qty }}</td>
          <td>{{ it.cost ? fmt(it.cost) + ' ' + t('ر.س') : '—' }}</td>
          <td><span class="pill" :class="it.is_low ? 'pill-r' : 'pill-g'">{{ it.qty === 0 ? t('نفدت') : (it.is_low ? t('قارب النفاد') : t('متوفر')) }}</span></td>
          <td style="text-align:left">
            <button class="act" @click="edit(it)"><i class="ti ti-edit"></i></button>
            <button class="act danger" @click="remove(it)"><i class="ti ti-trash"></i></button>
          </td>
        </tr>
        <tr v-if="!items.length"><td colspan="6" class="empty">{{ t('لا توجد قطع') }}</td></tr>
      </tbody>
    </table>

    <div v-if="form" class="ov" @click.self="form = null">
      <div class="modal">
        <div class="sec-t">{{ form.id ? t('تعديل قطعة') : t('قطعة جديدة') }}</div>
        <div class="frow"><label>{{ t('الاسم *') }}</label><input v-model="form.name" placeholder="شاشة OLED" /></div>
        <div class="g2 frow">
          <div><label>SKU</label><input v-model="form.sku" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          <div><label>{{ t('التوافق') }}</label><input v-model="form.compatible" placeholder="iPhone 14" /></div>
        </div>
        <div class="g3 frow">
          <div><label>{{ t('الكمية') }}</label><input v-model.number="form.qty" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          <div><label>{{ t('التكلفة') }}</label><input v-model.number="form.cost" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          <div><label>{{ t('حد التنبيه') }}</label><input v-model.number="form.low_threshold" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
        </div>
        <div v-if="err" class="err">{{ err }}</div>
        <div style="display:flex;gap:10px;margin-top:6px">
          <button class="btn btn-accent" @click="save"><i class="ti ti-check"></i> {{ t('حفظ') }}</button>
          <button class="btn btn-out" @click="form = null">{{ t('إلغاء') }}</button>
        </div>
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
const err = ref('')
const fmt = (n) => Number(n).toLocaleString('ar-EG')

async function load() { items.value = await api('/dashboard/inventory') }
function openNew() { err.value = ''; form.value = { name: '', sku: '', compatible: '', qty: 0, cost: null, low_threshold: 3 } }
function edit(it) { err.value = ''; form.value = { ...it } }
async function save() {
  err.value = ''
  if (!form.value.name) { err.value = t('الاسم مطلوب'); return }
  try {
    if (form.value.id) await api('/dashboard/inventory/' + form.value.id, { method: 'PUT', body: form.value })
    else await api('/dashboard/inventory', { method: 'POST', body: form.value })
    form.value = null; await load()
  } catch { err.value = t('تعذّر الحفظ') }
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
.muted { color: var(--muted); font-size: 12px; }
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.4); z-index: 50; display: flex; align-items: center; justify-content: center; }
.modal { background: #fff; border-radius: 14px; padding: 22px; width: 460px; max-width: 92vw; }
</style>
