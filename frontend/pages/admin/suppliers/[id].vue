<template>
  <DashShell active="suppliers">
    <div v-if="!s.id" class="empty" style="padding:60px 0">{{ t('جاري التحميل...') }}</div>
    <template v-else>
      <div class="ph">
        <div>
          <div class="page-title" style="display:flex;align-items:center;gap:10px">{{ s.name }}
            <span class="pill" :class="s.active ? 'pill-g' : 'pill-gray'">{{ s.active ? t('نشط') : t('متوقف') }}</span>
          </div>
          <p class="page-sub">{{ s.categories || t('مورّد قطع غيار') }}<template v-if="s.contact_name"> · {{ s.contact_name }}</template></p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <a v-if="s.whatsapp || s.phone" :href="'https://wa.me/' + intl(s.whatsapp || s.phone)" target="_blank" class="btn btn-wa"><i class="ti ti-brand-whatsapp"></i> {{ t('واتساب') }}</a>
          <a v-if="s.phone" :href="'tel:' + s.phone" class="btn btn-out"><i class="ti ti-phone"></i> {{ t('اتصال') }}</a>
          <NuxtLink :to="'/admin/suppliers/new?edit=' + s.id" class="btn btn-out"><i class="ti ti-edit"></i> {{ t('تعديل') }}</NuxtLink>
          <button class="btn btn-out" style="color:#A32020" @click="remove"><i class="ti ti-trash"></i> {{ t('حذف') }}</button>
          <NuxtLink to="/admin/suppliers" class="btn btn-out"><i class="ti ti-arrow-right"></i> {{ t('رجوع') }}</NuxtLink>
        </div>
      </div>

      <div class="kpis">
        <div class="kpi"><div class="ic" style="background:#E6F1FB;color:var(--accent)"><i class="ti ti-packages"></i></div><div class="num">{{ totals.items }}</div><div class="lbl">{{ t('قطع من هذا المورد') }}</div></div>
        <div class="kpi"><div class="ic" style="background:#EEEDFE;color:#534AB7"><i class="ti ti-building-warehouse"></i></div><div class="num">{{ fmt(totals.stock_value) }}</div><div class="lbl">{{ t('قيمة مخزونه الحالي') }}</div></div>
        <div class="kpi"><div class="ic" style="background:#E1F5EE;color:var(--green)"><i class="ti ti-shopping-cart"></i></div><div class="num">{{ fmt(totals.purchased) }}</div><div class="lbl">{{ t('إجمالي آخر التوريدات') }}</div></div>
      </div>

      <div class="cols">
        <div>
          <div class="panel">
            <div class="sec-t"><i class="ti ti-id" style="color:var(--accent)"></i> {{ t('بيانات المورد') }}</div>
            <div class="grid2">
              <div class="rowline" v-if="s.phone"><span>{{ t('الجوال') }}</span><b dir="ltr">{{ s.phone }}</b></div>
              <div class="rowline" v-if="s.alt_phone"><span>{{ t('جوال إضافي') }}</span><b dir="ltr">{{ s.alt_phone }}</b></div>
              <div class="rowline" v-if="s.whatsapp"><span>{{ t('واتساب') }}</span><b dir="ltr">{{ s.whatsapp }}</b></div>
              <div class="rowline" v-if="s.email"><span>{{ t('البريد') }}</span><b dir="ltr">{{ s.email }}</b></div>
              <div class="rowline" v-if="s.website"><span>{{ t('الموقع') }}</span><b dir="ltr"><a :href="s.website" target="_blank" class="link">{{ s.website }}</a></b></div>
              <div class="rowline" v-if="s.city || s.country"><span>{{ t('المدينة') }}</span><b>{{ [s.city, s.country].filter(Boolean).join('، ') }}</b></div>
              <div class="rowline" v-if="s.address"><span>{{ t('العنوان') }}</span><b>{{ s.address }}</b></div>
              <div class="rowline" v-if="s.tax_number"><span>{{ t('الرقم الضريبي') }}</span><b dir="ltr">{{ s.tax_number }}</b></div>
              <div class="rowline" v-if="s.payment_terms"><span>{{ t('شروط الدفع') }}</span><b>{{ s.payment_terms }}</b></div>
              <div class="rowline" v-if="s.bank_details"><span>{{ t('الحساب البنكي') }}</span><b>{{ s.bank_details }}</b></div>
            </div>
            <div class="note" v-if="s.notes">{{ s.notes }}</div>
          </div>

          <div class="panel">
            <div class="phh"><h3>{{ t('القطع الموردة') }}</h3><NuxtLink to="/admin/inventory" class="link">{{ t('المخزون ←') }}</NuxtLink></div>
            <table>
              <thead><tr><th>{{ t('القطعة') }}</th><th>{{ t('التوافق') }}</th><th>{{ t('الكمية') }}</th><th>{{ t('التكلفة') }}</th><th>{{ t('الحالة') }}</th></tr></thead>
              <tbody>
                <tr v-for="it in items" :key="it.id">
                  <td><b>{{ it.name }}</b><div class="muted">{{ it.sku }}</div></td>
                  <td>{{ it.compatible || '—' }}</td>
                  <td>{{ it.qty }}</td>
                  <td>{{ it.cost ? fmt(it.cost) : '—' }}</td>
                  <td><span class="pill" :class="it.is_low ? 'pill-r' : 'pill-g'">{{ it.qty === 0 ? t('نفدت') : (it.is_low ? t('قارب النفاد') : t('متوفر')) }}</span></td>
                </tr>
                <tr v-if="!items.length"><td colspan="5" class="empty">{{ t('لا قطع مربوطة بهذا المورد بعد — اربطها من صفحة المخزون') }}</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="panel">
          <div class="sec-t"><i class="ti ti-history" style="color:var(--accent)"></i> {{ t('سجل التوريدات') }}</div>
          <div v-for="p in purchases" :key="p.id" class="prow">
            <div><b>{{ p.item }}</b> <span class="muted">× {{ p.qty }}</span>
              <div class="muted">{{ p.created_at }}<template v-if="p.note"> · {{ p.note }}</template></div>
            </div>
            <b>{{ fmt(p.total) }}</b>
          </div>
          <div v-if="!purchases.length" class="empty">{{ t('لا توريدات مسجّلة بعد') }}</div>
        </div>
      </div>
    </template>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t, isEn } = useI18n()
const { confirmDlg } = useConfirm()
const route = useRoute()
const s = ref({})
const items = ref([])
const purchases = ref([])
const totals = ref({ items: 0, stock_value: 0, purchased: 0 })

const fmt = (n) => Number(n || 0).toLocaleString(isEn.value ? 'en-US' : 'ar-EG')
function intl(p) {
  let n = (p || '').replace(/\D/g, '')
  if (n.startsWith('00')) n = n.slice(2); else if (n.startsWith('0')) n = '966' + n.slice(1)
  return n
}
async function load() {
  const r = await api('/dashboard/suppliers/' + route.params.id)
  s.value = r.supplier; items.value = r.items; purchases.value = r.purchases; totals.value = r.totals
}
async function remove() {
  if (!await confirmDlg(t('حذف المورد'), t('ستبقى القطع في المخزون ويُفكّ ربطها به فقط'))) return
  await api('/dashboard/suppliers/' + s.value.id, { method: 'DELETE' })
  navigateTo('/admin/suppliers')
}
onMounted(load)
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px; }
.kpi { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 18px; }
.ic { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; }
.num { font-size: 26px; font-weight: 800; }
.lbl { color: var(--muted); font-size: 13px; }
.cols { display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px; align-items: start; }
.cols > div > .panel { margin-bottom: 16px; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 26px; }
.rowline { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--line-2); font-size: 13.5px; gap: 12px; }
.rowline span { color: var(--muted); }
.note { background: var(--bg); border-radius: 9px; padding: 12px; font-size: 13px; margin-top: 12px; }
.phh { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.phh h3 { font-size: 15px; font-weight: 700; }
.prow { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px solid var(--line-2); font-size: 13.5px; gap: 10px; }
.muted { color: var(--muted); font-size: 12px; }
@media (max-width: 1000px) { .cols, .grid2 { grid-template-columns: 1fr; } .kpis { grid-template-columns: 1fr; } }
</style>
