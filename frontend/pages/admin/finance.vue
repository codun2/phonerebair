<template>
  <DashShell active="finance">
    <div class="ph">
      <div><div class="page-title">{{ t('المالية') }}</div><p class="page-sub">{{ t('الإيرادات والمصروفات والأرباح والديون') }}</p></div>
      <div class="range">
        <input v-model="from" type="date" dir="ltr" @change="load" />
        <span class="muted">←</span>
        <input v-model="to" type="date" dir="ltr" @change="load" />
      </div>
    </div>

    <div class="kpis">
      <div class="kpi"><div class="ic" style="background:#E1F5EE;color:var(--green)"><i class="ti ti-cash"></i></div><div class="num">{{ fmt(cards.revenue) }}</div><div class="lbl">{{ t('المحصّل في الفترة') }}</div></div>
      <div class="kpi"><div class="ic" style="background:#FBF1DA;color:#B8861E"><i class="ti ti-receipt-2"></i></div><div class="num">{{ fmt(cards.expenses) }}</div><div class="lbl">{{ t('المصروفات') }}</div></div>
      <div class="kpi"><div class="ic" style="background:#EEEDFE;color:#534AB7"><i class="ti ti-packages"></i></div><div class="num">{{ fmt(cards.parts_cost) }}</div><div class="lbl">{{ t('تكلفة القطع المستخدمة') }}</div></div>
      <div class="kpi"><div class="ic" :style="cards.net >= 0 ? 'background:#E1F5EE;color:var(--green)' : 'background:#FBE9E8;color:#A32020'"><i class="ti ti-chart-line"></i></div><div class="num" :style="cards.net < 0 ? 'color:#A32020' : ''">{{ fmt(cards.net) }}</div><div class="lbl">{{ t('صافي الربح التقريبي') }}</div></div>
      <div class="kpi"><div class="ic" style="background:#FBE9E8;color:#A32020"><i class="ti ti-alert-circle"></i></div><div class="num" :style="cards.debts > 0 ? 'color:#A32020' : ''">{{ fmt(cards.debts) }}</div><div class="lbl">{{ t('ديون العملاء (إجمالي)') }}</div></div>
      <div class="kpi"><div class="ic" style="background:#E6F1FB;color:var(--accent)"><i class="ti ti-building-warehouse"></i></div><div class="num">{{ fmt(cards.inventory_value) }}</div><div class="lbl">{{ t('قيمة المخزون (تكلفة)') }}</div></div>
    </div>

    <div class="methods" v-if="Object.keys(methodTotals).length">
      <span v-for="(v, k) in methodTotals" :key="k" class="chip2"><b>{{ t(methodLabels[k] || k) }}:</b> {{ fmt(v) }}</span>
    </div>

    <div class="cols">
      <div>
        <div class="panel">
          <div class="phh"><h3>{{ t('المدفوعات المحصّلة في الفترة') }}</h3></div>
          <table>
            <thead><tr><th>{{ t('المبلغ') }}</th><th>{{ t('الطريقة') }}</th><th>{{ t('التذكرة') }}</th><th>{{ t('الزبون') }}</th><th>{{ t('الوقت') }}</th></tr></thead>
            <tbody>
              <tr v-for="p in payments" :key="p.id">
                <td><b>{{ fmt(p.amount) }}</b></td>
                <td>{{ t(p.method_label) }}</td>
                <td><NuxtLink :to="'/admin/tickets/' + p.ticket_id" class="link">{{ p.ticket_no }}</NuxtLink></td>
                <td>{{ p.customer }}</td>
                <td class="muted">{{ p.paid_at }}</td>
              </tr>
              <tr v-if="!payments.length"><td colspan="5" class="empty">{{ t('لا مدفوعات في هذه الفترة') }}</td></tr>
            </tbody>
          </table>
        </div>

        <div class="panel">
          <div class="phh"><h3>{{ t('ديون العملاء (تذاكر عليها متبقٍ)') }}</h3></div>
          <table>
            <thead><tr><th>{{ t('التذكرة') }}</th><th>{{ t('الزبون') }}</th><th>{{ t('السعر') }}</th><th>{{ t('المدفوع') }}</th><th>{{ t('المتبقي') }}</th><th>{{ t('الحالة') }}</th></tr></thead>
            <tbody>
              <tr v-for="d in debts" :key="d.ticket_id" @click="navigateTo('/admin/tickets/' + d.ticket_id)" style="cursor:pointer">
                <td><b style="color:var(--accent-d)">{{ d.ticket_no }}</b></td>
                <td>{{ d.customer_name }}<div class="muted" dir="ltr" style="text-align:right">{{ d.customer_phone }}</div></td>
                <td>{{ fmt(d.price) }}</td>
                <td>{{ fmt(d.paid) }}</td>
                <td><b style="color:#A32020">{{ fmt(d.balance) }}</b></td>
                <td><StatusPill :status="d.status" :label="d.status_label" /></td>
              </tr>
              <tr v-if="!debts.length"><td colspan="6" class="empty">{{ t('لا ديون — ممتاز 👌') }}</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="panel">
        <div class="phh"><h3>{{ t('المصروفات') }}</h3></div>
        <div class="g2 frow">
          <input v-model="exp.title" :placeholder="t('البيان (إيجار، كهرباء...)')" />
          <input v-model.number="exp.amount" type="number" :placeholder="t('المبلغ')" dir="ltr" />
        </div>
        <div class="g2 frow">
          <select v-model="exp.category">
            <option v-for="c in categories" :key="c" :value="c">{{ t(c) }}</option>
          </select>
          <input v-model="exp.spent_at" type="date" dir="ltr" />
        </div>
        <button class="btn btn-accent btn-block" :disabled="!exp.title || !exp.amount" @click="addExpense"><i class="ti ti-plus"></i> {{ t('تسجيل مصروف') }}</button>
        <div v-if="err" class="err">{{ err }}</div>

        <div class="explist">
          <div v-for="e in expenses" :key="e.id" class="exprow">
            <div><b>{{ e.title }}</b> <span class="pill pill-gray">{{ t(e.category || 'أخرى') }}</span>
              <div class="muted">{{ e.spent_at?.slice(0, 10) }} · {{ e.user?.name || '' }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
              <b>{{ fmt(e.amount) }}</b>
              <button class="act danger sm" @click="removeExpense(e)"><i class="ti ti-trash"></i></button>
            </div>
          </div>
          <div v-if="!expenses.length" class="empty">{{ t('لا مصروفات في هذه الفترة') }}</div>
        </div>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t, isEn } = useI18n()
const { confirmDlg } = useConfirm()

const today = new Date().toISOString().slice(0, 10)
const monthStart = today.slice(0, 8) + '01'
const from = ref(monthStart)
const to = ref(today)
const cards = ref({ revenue: 0, expenses: 0, parts_cost: 0, net: 0, debts: 0, inventory_value: 0 })
const methodTotals = ref({})
const methodLabels = ref({})
const payments = ref([])
const debts = ref([])
const expenses = ref([])
const err = ref('')
const categories = ['إيجار', 'كهرباء وماء', 'رواتب', 'مشتريات', 'اتصالات', 'أخرى']
const exp = ref({ title: '', amount: null, category: 'أخرى', spent_at: today })

const fmt = (n) => Number(n || 0).toLocaleString(isEn.value ? 'en-US' : 'ar-EG')

async function load() {
  const qs = `?from=${from.value}&to=${to.value}`
  const r = await api('/dashboard/finance' + qs)
  cards.value = r.cards; methodTotals.value = r.method_totals || {}
  methodLabels.value = r.method_labels || {}
  payments.value = r.payments; debts.value = r.debts
  expenses.value = await api('/dashboard/expenses' + qs)
}

async function addExpense() {
  err.value = ''
  try {
    await api('/dashboard/expenses', { method: 'POST', body: exp.value })
    exp.value = { title: '', amount: null, category: exp.value.category, spent_at: to.value }
    await load()
  } catch (e) { err.value = e?.data?.message || t('تعذّر الحفظ') }
}
async function removeExpense(e) {
  if (!await confirmDlg(t('حذف المصروف'), e.title)) return
  await api('/dashboard/expenses/' + e.id, { method: 'DELETE' })
  await load()
}

onMounted(load)
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.range { display: flex; align-items: center; gap: 8px; }
.kpis { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 14px; }
.kpi { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 15px; }
.ic { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px; }
.num { font-size: 21px; font-weight: 800; }
.lbl { color: var(--muted); font-size: 12px; }
.methods { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
.chip2 { background: #fff; border: 1px solid var(--line); border-radius: 8px; padding: 6px 12px; font-size: 12.5px; }
.cols { display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px; align-items: start; }
.cols > div > .panel { margin-bottom: 16px; }
.phh { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.phh h3 { font-size: 15px; font-weight: 700; }
.muted { color: var(--muted); font-size: 12px; }
.explist { margin-top: 14px; }
.exprow { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px solid var(--line-2); font-size: 13.5px; gap: 10px; }
.act.sm { width: 26px; height: 26px; font-size: 13px; }
@media (max-width: 1100px) { .kpis { grid-template-columns: repeat(3, 1fr); } .cols { grid-template-columns: 1fr; } }
@media (max-width: 700px) { .kpis { grid-template-columns: 1fr 1fr; } }
</style>
