<template>
  <DashShell active="tickets">
    <div class="ph">
      <div><div class="page-title">{{ tr('التذاكر') }}</div><p class="page-sub">{{ tr('إدارة كل طلبات التصليح') }}</p></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-out" @click="quick = { customer_name: '', customer_phone: '', make: '', model: '', description: '' }"><i class="ti ti-bolt"></i> {{ tr('استقبال سريع') }}</button>
        <NuxtLink to="/admin/ticket-new" class="btn btn-accent"><i class="ti ti-plus"></i> {{ tr('تذكرة جديدة') }}</NuxtLink>
      </div>
    </div>

    <div class="toolbar">
      <div class="search"><i class="ti ti-search"></i>
        <input v-model="q" :placeholder="tr('بحث: اسم / جوال / رقم تذكرة / IMEI / سيريال')" @input="onSearch" />
      </div>
    </div>

    <div class="chips">
      <button class="chip" :class="{ on: !filter }" @click="setFilter('')">{{ tr('الكل') }} ({{ total }})</button>
      <button v-for="st in statuses" :key="st.key" class="chip" :class="{ on: filter === st.key }" @click="setFilter(st.key)">
        {{ tr(st.label) }} ({{ counts[st.key] || 0 }})
      </button>
    </div>

    <table>
      <thead><tr><th>{{ tr('التذكرة') }}</th><th>{{ tr('الزبون') }}</th><th>{{ tr('الجهاز / العطل') }}</th><th>{{ tr('السعر') }}</th><th>{{ tr('المتبقي') }}</th><th>{{ tr('الحالة') }}</th><th>{{ tr('التسليم') }}</th><th>{{ tr('الفني') }}</th></tr></thead>
      <tbody>
        <tr v-for="t in tickets" :key="t.id" @click="open(t)" style="cursor:pointer">
          <td>
            <b style="color:var(--accent-d)">{{ t.ticket_no }}</b>
            <div v-if="t._pending" class="muted" style="color:#8A5300"><i class="ti ti-cloud-upload"></i> {{ tr('بانتظار الرفع للسيرفر') }}</div>
          </td>
          <td>{{ t.customer_name }}<div class="muted" dir="ltr" style="text-align:right">{{ t.customer_phone }}</div></td>
          <td>{{ t.device }} <span class="muted">· {{ t.repair_type || '—' }}</span></td>
          <td>{{ t.price ? fmt(t.price) : '—' }}</td>
          <td><b v-if="t.balance > 0" style="color:#A32020">{{ fmt(t.balance) }}</b><span v-else class="muted">—</span></td>
          <td><StatusPill :status="t.status" :label="t.status_label" /></td>
          <td>
            <span v-if="t.promised_at" :style="t.overdue ? 'color:#A32020;font-weight:700' : ''">{{ t.promised_at }} <i v-if="t.overdue" class="ti ti-alert-triangle"></i></span>
            <span v-else class="muted">—</span>
          </td>
          <td>{{ t.technician || '—' }}</td>
        </tr>
        <tr v-if="!tickets.length"><td colspan="8" class="empty">{{ tr('لا توجد تذاكر بهذا الفلتر') }}</td></tr>
      </tbody>
    </table>

    <!-- استقبال سريع: تذكرة مؤقتة بأقل بيانات، تُستكمل لاحقاً -->
    <div v-if="quick" class="ov" @click.self="quick = null">
      <div class="modal">
        <div class="sec-t"><i class="ti ti-bolt" style="color:var(--accent)"></i> {{ tr('استقبال سريع (تذكرة مؤقتة)') }}</div>
        <p class="qhint">{{ tr('سجّل الزبون والجهاز بسرعة وقت الزحمة — تُفتح تذكرة «مسودة» تكمّل بياناتها لاحقاً من صفحتها.') }}</p>
        <div class="g2 frow">
          <div><label>{{ tr('اسم الزبون *') }}</label><input ref="quickName" v-model="quick.customer_name" /></div>
          <div><label>{{ tr('رقم الجوال *') }}</label><input v-model="quick.customer_phone" placeholder="05xxxxxxxx" dir="ltr" /></div>
        </div>
        <div class="g2 frow">
          <div><label>{{ tr('الماركة') }}</label>
            <select v-model="quick.make" @change="quick.model = ''">
              <option value="">{{ tr('— اختر —') }}</option>
              <option v-for="m in cat.makes" :key="m.id" :value="m.name">{{ m.name }}</option>
            </select>
          </div>
          <div><label>{{ tr('الموديل') }}</label>
            <select v-model="quick.model" :disabled="!quick.make">
              <option value="">{{ quick.make ? tr('— اختر —') : tr('اختر الماركة أولاً') }}</option>
              <option v-for="md in quickModels" :key="md.id" :value="md.name">{{ md.name }}</option>
            </select>
          </div>
        </div>
        <div class="frow"><label>{{ tr('العطل باختصار') }}</label><input v-model="quick.description" :placeholder="tr('شاشة مكسورة...')" /></div>
        <div v-if="qerr" class="err">{{ qerr }}</div>
        <div style="display:flex;gap:10px">
          <button class="btn btn-accent" :disabled="qbusy" @click="saveQuick"><i class="ti ti-bolt"></i> {{ tr('فتح تذكرة مؤقتة') }}</button>
          <button class="btn btn-out" @click="quick = null">{{ tr('إلغاء') }}</button>
        </div>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t: tr, isEn } = useI18n()
const route = useRoute()

const tickets = ref([])
const counts = ref({})
const statuses = ref([])
const cat = ref({ makes: [] })
const filter = ref('')
const q = ref('')
// موديلات الماركة المختارة في «الاستقبال السريع»
const quickModels = computed(() => cat.value.makes?.find((m) => m.name === quick.value?.make)?.models || [])
const quick = ref(null)
const quickName = ref(null)
const qerr = ref('')
const qbusy = ref(false)
let debounce = null

const total = computed(() => Object.values(counts.value).reduce((a, b) => a + Number(b), 0))
const fmt = (n) => Number(n).toLocaleString(isEn.value ? 'en-US' : 'ar-EG')

async function load() {
  const params = new URLSearchParams()
  if (filter.value) params.set('status', filter.value)
  if (q.value.trim()) params.set('search', q.value.trim())
  const r = await api('/dashboard/tickets' + (params.size ? '?' + params.toString() : ''))
  tickets.value = r.tickets; counts.value = r.counts; statuses.value = r.statuses
}
function setFilter(f) { filter.value = f; load() }
function onSearch() { clearTimeout(debounce); debounce = setTimeout(load, 350) }
function open(t) { navigateTo('/admin/tickets/' + t.id) }

watch(quick, (v) => { if (v) { qerr.value = ''; nextTick(() => quickName.value?.focus()) } })
async function saveQuick() {
  qerr.value = ''
  if (!quick.value.customer_name || !quick.value.customer_phone) { qerr.value = tr('اسم الزبون ورقم الجوال مطلوبان'); return }
  qbusy.value = true
  try {
    const r = await api('/dashboard/tickets', { method: 'POST', body: { ...quick.value, status: 'draft' } })
    quick.value = null
    if (r.id && !r._queued) navigateTo('/admin/tickets/' + r.id)
    else await load()
  } catch { qerr.value = tr('تعذّر حفظ التذكرة — تحقق من الحقول') } finally { qbusy.value = false }
}

onMounted(async () => {
  await load()
  try { cat.value = await api('/site/catalog') } catch {}
  if (route.query.open) navigateTo('/admin/tickets/' + route.query.open, { replace: true })
})
// تحديث مباشر للقائمة: كل 10 ثوانٍ تظهر التذاكر الجديدة وتغيّرات الحالة دون إعادة تحميل
usePoll(load, 10000)
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.toolbar { margin-bottom: 12px; }
.search { position: relative; max-width: 420px; }
.search i { position: absolute; inset-inline-start: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); }
.search input { width: 100%; padding-inline-start: 36px; }
.chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.chip { font-family: inherit; padding: 8px 15px; border-radius: 20px; border: 1px solid var(--line); background: #fff; font-size: 13px; font-weight: 600; color: var(--muted); cursor: pointer; }
.chip:hover { border-color: #C7CCD6; } .chip.on { background: var(--accent); border-color: var(--accent); color: #fff; }
.muted { color: var(--muted); font-size: 12px; }
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.4); z-index: 50; display: flex; align-items: center; justify-content: center; }
.modal { background: #fff; border-radius: 14px; padding: 22px; width: 480px; max-width: 92vw; }
.qhint { font-size: 12.5px; color: var(--muted); margin-bottom: 14px; line-height: 1.7; }
</style>
