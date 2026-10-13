<template>
  <SuperShell active="plans">
    <div class="page-title">{{ t('الباقات') }}</div>
    <p class="page-sub">{{ t('باقات الاشتراك المتاحة للمحلات') }}</p>

    <div class="panel">
      <div class="ph">
        <h3>{{ t('قائمة الباقات') }}</h3>
        <button class="btn btn-accent" @click="openNew"><i class="ti ti-plus"></i> {{ t('باقة جديدة') }}</button>
      </div>

      <div v-if="editing" class="addbox">
        <div class="arow">
          <input v-model="f.name" :placeholder="t('اسم الباقة')" />
          <input v-model.number="f.price" type="number" :placeholder="t('السعر (ر.س)')" dir="ltr" />
          <input v-model.number="f.duration_days" type="number" :placeholder="t('المدة (أيام)')" dir="ltr" />
          <input v-model.number="f.max_technicians" type="number" :placeholder="t('حد الفنيين (فارغ=∞)')" dir="ltr" />
          <label class="chk"><input type="checkbox" v-model="f.is_active" /> {{ t('مفعّلة') }}</label>
        </div>
        <input v-model="f.description" :placeholder="t('وصف مختصر')" style="width:100%;margin-top:8px;height:38px;border:1px solid var(--line);border-radius:9px;padding:0 10px;font-family:inherit" />
        <div v-if="err" class="err" style="margin-top:8px">{{ err }}</div>
        <div style="margin-top:10px;display:flex;gap:8px">
          <button class="btn btn-accent" :disabled="busy" @click="save">{{ f.id ? t('حفظ التعديل') : t('إضافة') }}</button>
          <button class="btn" @click="editing = false">{{ t('إلغاء') }}</button>
        </div>
      </div>

      <table>
        <thead><tr><th>{{ t('الباقة') }}</th><th>{{ t('السعر') }}</th><th>{{ t('المدة') }}</th><th>{{ t('حد الفنيين') }}</th><th>{{ t('محلات') }}</th><th>{{ t('الحالة') }}</th><th></th></tr></thead>
        <tbody>
          <tr v-for="p in rows" :key="p.id">
            <td><b>{{ p.name }}</b><div class="muted">{{ p.description || '' }}</div></td>
            <td dir="ltr">{{ Number(p.price).toLocaleString(isEn ? 'en-US' : 'ar-EG') }} {{ t('ر.س') }}</td>
            <td>{{ p.duration_days }} {{ t('يوم') }}</td>
            <td>{{ p.max_technicians ?? t('غير محدود') }}</td>
            <td>{{ p.shops_count }}</td>
            <td><span class="pill" :class="p.is_active ? 'pill-g' : 'pill-gray'">{{ p.is_active ? t('مفعّلة') : t('متوقفة') }}</span></td>
            <td class="acts">
              <button class="act" :title="t('تعديل')" @click="openEdit(p)"><i class="ti ti-edit"></i></button>
              <button class="act danger" :title="t('حذف')" @click="del(p)"><i class="ti ti-trash"></i></button>
            </td>
          </tr>
          <tr v-if="!rows.length"><td colspan="7" class="empty">{{ t('لا توجد باقات') }}</td></tr>
        </tbody>
      </table>
    </div>
  </SuperShell>
</template>

<script setup>
const sa = useSuperAdmin()
const { t, isEn } = useI18n()
const rows = ref([])
const editing = ref(false)
const busy = ref(false)
const err = ref('')
const blank = { id: null, name: '', price: 0, duration_days: 30, max_technicians: null, description: '', is_active: true }
const f = ref({ ...blank })

async function load() { rows.value = await sa.plans() }
function openNew() { f.value = { ...blank }; err.value = ''; editing.value = true }
function openEdit(p) { f.value = { ...p, max_technicians: p.max_technicians ?? null }; err.value = ''; editing.value = true }

async function save() {
  busy.value = true; err.value = ''
  try {
    const body = { ...f.value, max_technicians: f.value.max_technicians || null }
    if (f.value.id) await sa.updatePlan(f.value.id, body)
    else await sa.createPlan(body)
    editing.value = false
    await load()
  } catch (e) { err.value = e?.data?.message || 'تعذّر الحفظ' }
  finally { busy.value = false }
}
async function del(p) {
  if (!confirm(`حذف باقة "${p.name}"؟`)) return
  try { await sa.deletePlan(p.id); await load() }
  catch (e) { alert(e?.data?.message || 'تعذّر الحذف') }
}

onMounted(load)
</script>

<style scoped>
.page-title { font-size: 22px; font-weight: 800; }
.page-sub { color: var(--muted); margin-bottom: 18px; font-size: 14px; }
.panel { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 18px; }
.ph { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.ph h3 { font-size: 15px; font-weight: 700; }
.addbox { background: #F7F9FC; border: 1px solid var(--line); border-radius: 11px; padding: 14px; margin-bottom: 14px; }
.arow { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 8px; align-items: center; }
.arow input { height: 38px; border: 1px solid var(--line); border-radius: 9px; padding: 0 10px; font-family: inherit; font-size: 13px; }
.chk { display: flex; align-items: center; gap: 6px; font-size: 13px; white-space: nowrap; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: right; padding: 11px 10px; border-bottom: 1px solid var(--line-2); font-size: 14px; }
th { font-size: 12px; color: var(--muted); font-weight: 700; }
.muted { color: var(--muted); font-size: 12px; }
.acts { display: flex; gap: 5px; }
.act { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--line); background: #fff; color: var(--ink); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 15px; }
.act.danger { color: var(--accent); }
.empty { text-align: center; color: var(--muted); padding: 24px; }
@media (max-width: 800px) { .arow { grid-template-columns: 1fr 1fr; } }
</style>
