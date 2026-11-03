<template>
  <DashShell active="prices">
    <div class="ph">
      <div><div class="page-title">{{ tr('الأسعار والأعطال') }}</div><p class="page-sub">{{ tr('جدول الأسعار الذي يغذّي حاسبة السعر في الموقع') }}</p></div>
      <button class="btn btn-accent" @click="openNew"><i class="ti ti-plus"></i> {{ tr('إضافة سعر') }}</button>
    </div>

    <div class="info"><i class="ti ti-info-circle"></i> {{ tr('أي تعديل هنا يظهر مباشرة للزبون في «حاسبة السعر الفوري».') }}</div>

    <div class="toolbar">
      <div class="search"><i class="ti ti-search"></i>
        <input v-model="q" :placeholder="tr('بحث: ماركة / موديل / عطل')" />
      </div>
      <div class="view-toggle">
        <button :class="{ on: view === 'table' }" @click="setView('table')" :title="tr('جدول')"><i class="ti ti-table"></i></button>
        <button :class="{ on: view === 'cards' }" @click="setView('cards')" :title="tr('شرائح')"><i class="ti ti-layout-grid"></i></button>
      </div>
    </div>

    <!-- عرض جدول -->
    <table v-if="view === 'table'">
      <thead><tr><th>{{ tr('الماركة') }}</th><th>{{ tr('الموديل') }}</th><th>{{ tr('العطل') }}</th><th>{{ tr('التكلفة') }}</th><th>{{ tr('السعر') }}</th><th>{{ tr('الربح') }}</th><th>{{ tr('المدة') }}</th><th>{{ tr('الضمان') }}</th><th></th></tr></thead>
      <tbody>
        <tr v-for="r in filtered" :key="r.id">
          <td>{{ r.make?.name }}</td>
          <td>{{ r.model?.name || tr('كل الموديلات') }}</td>
          <td>{{ r.repair_type?.name }}</td>
          <td class="muted">{{ r.cost != null ? fmt(r.cost) + ' ' + tr('ر.س') : '—' }}</td>
          <td><b>{{ fmt(r.price) }} {{ tr('ر.س') }}</b></td>
          <td class="profit">{{ profitOf(r) != null ? '+' + fmt(profitOf(r)) : '—' }}</td>
          <td>{{ r.duration_minutes }} {{ tr('د') }}</td>
          <td>{{ r.warranty_months }} {{ tr('ش') }}</td>
          <td style="text-align:left">
            <button class="act" @click="edit(r)"><i class="ti ti-edit"></i></button>
            <button class="act danger" @click="remove(r)"><i class="ti ti-trash"></i></button>
          </td>
        </tr>
        <tr v-if="!filtered.length"><td colspan="9" class="empty">{{ q ? tr('لا نتائج لبحثك') : tr('لا توجد أسعار بعد') }}</td></tr>
      </tbody>
    </table>

    <!-- عرض شرائح -->
    <div v-else class="grid">
      <div v-for="r in filtered" :key="r.id" class="pcard">
        <div class="pc-head">
          <div>
            <b>{{ r.repair_type?.name }}</b>
            <div class="pc-dev">{{ r.make?.name }} · {{ r.model?.name || tr('كل الموديلات') }}</div>
          </div>
          <div class="pc-acts">
            <button class="act" @click="edit(r)"><i class="ti ti-edit"></i></button>
            <button class="act danger" @click="remove(r)"><i class="ti ti-trash"></i></button>
          </div>
        </div>
        <div class="pc-price">{{ fmt(r.price) }} <span>{{ tr('ر.س') }}</span></div>
        <div v-if="r.cost != null" class="pc-cost">
          <span>{{ tr('التكلفة') }}: {{ fmt(r.cost) }}</span>
          <span class="profit">{{ tr('الربح') }}: +{{ fmt(profitOf(r)) }}</span>
        </div>
        <div class="pc-meta">
          <span><i class="ti ti-clock"></i> {{ r.duration_minutes }} {{ tr('د') }}</span>
          <span><i class="ti ti-shield-check"></i> {{ tr('ضمان') }} {{ r.warranty_months }} {{ tr('ش') }}</span>
        </div>
      </div>
      <div v-if="!filtered.length" class="empty" style="grid-column:1/-1">{{ q ? tr('لا نتائج لبحثك') : tr('لا توجد أسعار بعد') }}</div>
    </div>

    <div v-if="form" class="ov" @click.self="form = null">
      <div class="modal">
        <div class="sec-t">{{ form.id ? tr('تعديل سعر') : tr('إضافة سعر') }}</div>
        <div class="g2 frow">
          <div><label>{{ tr('الماركة *') }}</label><select v-model="form.make_id" :disabled="!!form.id" @change="form.car_model_id = null"><option :value="null">{{ tr('— اختر —') }}</option><option v-for="m in cat.makes" :key="m.id" :value="m.id">{{ m.name }}</option></select></div>
          <div><label>{{ tr('الموديل') }}</label><select v-model="form.car_model_id" :disabled="!!form.id"><option :value="null">{{ tr('كل الموديلات') }}</option><option v-for="md in modelsFor" :key="md.id" :value="md.id">{{ md.name }}</option></select></div>
        </div>
        <div class="frow"><label>{{ tr('نوع العطل *') }}</label><select v-model="form.repair_type_id" :disabled="!!form.id"><option :value="null">{{ tr('— اختر —') }}</option><option v-for="t in cat.repair_types" :key="t.id" :value="t.id">{{ t.name }}</option></select></div>

        <div class="cost-box">
          <div class="g2">
            <div><label>{{ tr('تكلفة الفني (قطع + أجرة)') }}</label><input v-model.number="form.cost" type="number" min="0" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" @input="recalc" :placeholder="tr('ر.س')" /></div>
            <div><label>{{ tr('هامش الربح %') }}</label><input v-model.number="form.margin" type="number" min="0" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" @input="recalc" /></div>
          </div>
          <div class="cost-hint">
            <i class="ti ti-bulb"></i>
            {{ tr('السعر للزبون يُحسب تلقائياً = التكلفة + هامش الربح. تقدر تعدّل السعر يدوياً. التكلفة لا تظهر للزبون.') }}
          </div>
        </div>

        <div class="g3 frow">
          <div>
            <label>{{ tr('السعر للزبون (ر.س) *') }}</label>
            <input v-model.number="form.price" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" />
            <div v-if="formProfit != null" class="profit-line" :class="{ neg: formProfit < 0 }">
              {{ formProfit >= 0 ? tr('ربح') : tr('خسارة') }}: {{ formProfit >= 0 ? '+' : '' }}{{ fmt(formProfit) }} {{ tr('ر.س') }}
            </div>
          </div>
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
const q = ref('')
const view = ref('table')
const defaultMargin = ref(30) // هامش الربح الافتراضي للمحل (من الإعدادات)
const fmt = (n) => Number(n).toLocaleString('ar-EG')

// الربح لقاعدة سعر محفوظة = السعر - التكلفة (تقريب لأقرب عدد صحيح)
const profitOf = (r) => (r.cost == null ? null : Math.round(Number(r.price) - Number(r.cost)))
// الربح الحيّ داخل النموذج
const formProfit = computed(() => {
  const c = form.value?.cost, p = form.value?.price
  if (c == null || c === '' || p == null || p === '') return null
  return Math.round(Number(p) - Number(c))
})
// حساب سعر البيع من التكلفة والهامش (يُستدعى عند تغيير أيٍّ منهما)
function recalc() {
  const c = Number(form.value.cost), m = Number(form.value.margin)
  if (c > 0 && !Number.isNaN(m) && m >= 0) form.value.price = Math.round(c * (1 + m / 100))
}

const modelsFor = computed(() => cat.value.makes.find(m => m.id === form.value?.make_id)?.models || [])
const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return rules.value
  return rules.value.filter(r => [r.make?.name, r.model?.name, r.repair_type?.name].some(x => (x || '').toLowerCase().includes(s)))
})
function setView(v) { view.value = v; if (import.meta.client) try { localStorage.setItem('pr_prices_view', v) } catch {} }

async function load() { rules.value = await api('/dashboard/price-rules') }
function openNew() { err.value = ''; form.value = { make_id: null, car_model_id: null, repair_type_id: null, cost: null, margin: defaultMargin.value, price: null, duration_minutes: 45, warranty_months: 3 } }
function edit(r) {
  err.value = ''
  const cost = r.cost != null ? Number(r.cost) : null
  const price = Number(r.price)
  // اشتقاق الهامش من (السعر/التكلفة) عند وجود تكلفة، وإلا الهامش الافتراضي للمحل
  const margin = cost && cost > 0 ? Math.round((price / cost - 1) * 100) : defaultMargin.value
  form.value = {
    id: r.id,
    make_id: r.make?.id ?? r.make_id,
    car_model_id: r.model?.id ?? r.car_model_id ?? null,
    repair_type_id: r.repair_type?.id ?? r.repair_type_id,
    cost,
    margin,
    price,
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
onMounted(async () => {
  if (import.meta.client) try { view.value = localStorage.getItem('pr_prices_view') || 'table' } catch {}
  await load(); cat.value = await api('/site/catalog')
  try { const s = await api('/dashboard/settings'); if (s?.profit_margin) defaultMargin.value = Number(s.profit_margin) } catch {}
})
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; flex-wrap: wrap; gap: 12px; }
.info { background: #E6F1FB; color: var(--accent-d); padding: 12px 15px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; display: flex; gap: 8px; }
.toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
.search { position: relative; flex: 1; max-width: 420px; }
.search i { position: absolute; inset-inline-start: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); }
.search input { width: 100%; padding-inline-start: 36px; }
.view-toggle { display: flex; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; background: #fff; margin-inline-start: auto; }
.view-toggle button { width: 40px; height: 40px; border: none; background: none; cursor: pointer; color: var(--muted); font-size: 18px; display: flex; align-items: center; justify-content: center; }
.view-toggle button.on { background: var(--accent); color: #fff; }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; }
.pcard { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px; transition: box-shadow .15s, transform .15s; }
.pcard:hover { box-shadow: 0 8px 22px rgba(20,33,58,.08); transform: translateY(-2px); }
.pc-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.pc-head b { font-size: 15px; }
.pc-dev { font-size: 12.5px; color: var(--muted); margin-top: 2px; }
.pc-acts { display: flex; }
.pc-acts .act { margin-inline-start: 4px; width: 30px; height: 30px; font-size: 14px; }
.pc-price { font-size: 26px; font-weight: 800; color: var(--accent-d); margin: 14px 0 10px; }
.pc-price span { font-size: 13px; font-weight: 600; color: var(--muted); }
.pc-meta { display: flex; gap: 12px; font-size: 12.5px; color: var(--muted); }
.pc-meta i { margin-inline-end: 3px; }
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.4); z-index: 50; display: flex; align-items: center; justify-content: center; }
.modal { background: #fff; border-radius: 14px; padding: 22px; width: 480px; max-width: 92vw; max-height: 92vh; overflow-y: auto; }
td.muted { color: var(--muted); font-size: 13px; }
td.profit, .pc-cost .profit { color: var(--green); font-weight: 700; }
.cost-box { background: #F7F9FC; border: 1px solid var(--line); border-radius: 12px; padding: 14px; margin: 4px 0 12px; }
.cost-box .g2 { margin-bottom: 0; }
.cost-hint { display: flex; align-items: flex-start; gap: 6px; font-size: 12px; color: var(--muted); margin-top: 10px; line-height: 1.5; }
.cost-hint i { color: var(--gold); font-size: 15px; flex-shrink: 0; margin-top: 1px; }
.profit-line { font-size: 12px; font-weight: 700; color: var(--green); margin-top: 6px; }
.profit-line.neg { color: #C0392B; }
.pc-cost { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; color: var(--muted); margin: -4px 0 10px; }
</style>
