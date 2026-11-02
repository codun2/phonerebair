<template>
  <DashShell active="customers">
    <div v-if="!c.id" class="empty" style="padding:60px 0">{{ t('جاري التحميل...') }}</div>
    <template v-else>
      <div class="ph">
        <div>
          <div class="page-title">{{ c.name }}</div>
          <p class="page-sub" dir="ltr" style="text-align:start">{{ c.phone }}<template v-if="c.alt_phone"> · {{ c.alt_phone }}</template></p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <a :href="'https://wa.me/' + intl(c.phone)" target="_blank" class="btn btn-wa"><i class="ti ti-brand-whatsapp"></i> {{ t('واتساب') }}</a>
          <NuxtLink :to="'/admin/customers/new?edit=' + c.id" class="btn btn-out"><i class="ti ti-edit"></i> {{ t('تعديل') }}</NuxtLink>
          <NuxtLink to="/admin/customers" class="btn btn-out"><i class="ti ti-arrow-right"></i> {{ t('رجوع') }}</NuxtLink>
        </div>
      </div>

      <div class="kpis">
        <div class="kpi"><div class="ic" style="background:#E6F1FB;color:var(--accent)"><i class="ti ti-device-mobile"></i></div><div class="num">{{ totals.tickets }}</div><div class="lbl">{{ t('أجهزة/تذاكر') }}</div></div>
        <div class="kpi"><div class="ic" style="background:#E1F5EE;color:var(--green)"><i class="ti ti-cash"></i></div><div class="num">{{ fmt(totals.paid) }}</div><div class="lbl">{{ t('المدفوع') }}</div></div>
        <div class="kpi"><div class="ic" style="background:#FBE9E8;color:#A32020"><i class="ti ti-alert-circle"></i></div><div class="num" :style="totals.balance > 0 ? 'color:#A32020' : ''">{{ fmt(totals.balance) }}</div><div class="lbl">{{ t('الدين المتبقي') }}</div></div>
        <div class="kpi"><div class="ic" style="background:#E4F6EF;color:var(--green)"><i class="ti ti-shield-check"></i></div><div class="num">{{ totals.active_warranties }}</div><div class="lbl">{{ t('ضمانات سارية') }}</div></div>
      </div>

      <div class="panel" v-if="hasInfo">
        <div class="rowline" v-if="c.customer_type === 'business'"><span>{{ t('نوع العميل') }}</span><b>{{ t('شركة / مؤسسة') }}</b></div>
        <div class="rowline" v-if="c.company"><span>{{ t('اسم الشركة / المؤسسة') }}</span><b>{{ c.company }}</b></div>
        <div class="rowline" v-if="c.tax_number"><span>{{ t('الرقم الضريبي') }}</span><b dir="ltr">{{ c.tax_number }}</b></div>
        <div class="rowline" v-if="c.email"><span>{{ t('البريد') }}</span><b dir="ltr">{{ c.email }}</b></div>
        <div class="rowline" v-if="c.city"><span>{{ t('المدينة') }}</span><b>{{ c.city }}</b></div>
        <div class="rowline" v-if="c.address"><span>{{ t('العنوان') }}</span><b>{{ c.address }}</b></div>
        <div class="note" v-if="c.notes">{{ c.notes }}</div>
      </div>

      <div class="panel">
        <div class="phh"><h3>{{ t('سجل الأجهزة والإصلاحات') }}</h3>
          <NuxtLink :to="'/admin/ticket-new'" class="btn btn-accent btn-sm"><i class="ti ti-plus"></i> {{ t('تذكرة جديدة') }}</NuxtLink>
        </div>
        <table>
          <thead><tr><th>{{ t('التذكرة') }}</th><th>{{ t('الجهاز') }}</th><th>{{ t('العطل') }}</th><th>{{ t('السعر') }}</th><th>{{ t('المتبقي') }}</th><th>{{ t('الحالة') }}</th><th>{{ t('الضمان') }}</th><th>{{ t('التاريخ') }}</th></tr></thead>
          <tbody>
            <tr v-for="tk in tickets" :key="tk.id" @click="navigateTo('/admin/tickets/' + tk.id)" style="cursor:pointer">
              <td><b style="color:var(--accent-d)">{{ tk.ticket_no }}</b></td>
              <td>{{ tk.device || '—' }}<div v-if="tk.imei" class="muted" dir="ltr" style="text-align:right">{{ tk.imei }}</div></td>
              <td>{{ tk.repair_type || '—' }}</td>
              <td>{{ tk.price ? fmt(tk.price) : '—' }}</td>
              <td><b v-if="tk.balance > 0" style="color:#A32020">{{ fmt(tk.balance) }}</b><span v-else class="muted">—</span></td>
              <td><StatusPill :status="tk.status" :label="tk.status_label" /></td>
              <td>
                <span v-if="tk.warranty_active" class="pill pill-g">{{ t('ساري حتى') }} {{ tk.warranty_until }}</span>
                <span v-else-if="tk.warranty_until" class="pill pill-gray">{{ t('منتهي') }}</span>
                <span v-else class="muted">—</span>
              </td>
              <td class="muted">{{ tk.created_at }}</td>
            </tr>
            <tr v-if="!tickets.length"><td colspan="8" class="empty">{{ t('لا توجد تذاكر لهذا العميل') }}</td></tr>
          </tbody>
        </table>
      </div>

    </template>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t, isEn } = useI18n()
const route = useRoute()
const c = ref({})
const tickets = ref([])
const totals = ref({ tickets: 0, charged: 0, paid: 0, balance: 0, active_warranties: 0 })

const fmt = (n) => Number(n || 0).toLocaleString(isEn.value ? 'en-US' : 'ar-EG')
const hasInfo = computed(() => c.value.customer_type === 'business' || c.value.company || c.value.tax_number || c.value.email || c.value.city || c.value.address || c.value.notes)
function intl(p) {
  let n = (p || '').replace(/\D/g, '')
  if (n.startsWith('00')) n = n.slice(2)
  else if (n.startsWith('0')) n = '966' + n.slice(1)
  return n
}

async function load() {
  const r = await api('/dashboard/customers/' + route.params.id)
  c.value = r.customer; tickets.value = r.tickets; totals.value = r.totals
}
onMounted(load)
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px; }
.kpi { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 18px; }
.ic { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; }
.num { font-size: 26px; font-weight: 800; }
.lbl { color: var(--muted); font-size: 13px; }
.panel { margin-bottom: 16px; }
.phh { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.phh h3 { font-size: 15px; font-weight: 700; }
.rowline { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--line-2); font-size: 13.5px; gap: 12px; }
.rowline span { color: var(--muted); }
.note { background: var(--bg); border-radius: 9px; padding: 12px; font-size: 13px; margin-top: 10px; }
.btn-sm { padding: 7px 14px; font-size: 12.5px; }
.muted { color: var(--muted); font-size: 12px; }
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.4); z-index: 50; display: flex; align-items: center; justify-content: center; }
.modal { background: #fff; border-radius: 14px; padding: 22px; width: 500px; max-width: 92vw; }
@media (max-width: 900px) { .kpis { grid-template-columns: 1fr 1fr; } }
</style>
