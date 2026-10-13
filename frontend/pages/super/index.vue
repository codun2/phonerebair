<template>
  <SuperShell active="shops">
    <div class="page-title">{{ t('نظرة عامة') }}</div>
    <p class="page-sub">{{ t('إدارة محلات المنصّة والاشتراكات') }}</p>

    <div class="kpis">
      <div class="kpi"><div class="ic" style="background:#E6F1FB;color:var(--accent)"><i class="ti ti-building-store"></i></div><div class="num">{{ st.shops_total ?? '—' }}</div><div class="lbl">{{ t('إجمالي المحلات') }}</div></div>
      <div class="kpi"><div class="ic" style="background:#E1F5EE;color:var(--green)"><i class="ti ti-circle-check"></i></div><div class="num">{{ st.shops_active ?? '—' }}</div><div class="lbl">{{ t('محلات فعّالة') }}</div></div>
      <div class="kpi"><div class="ic" style="background:#FEF3E0;color:#B5790E"><i class="ti ti-clock"></i></div><div class="num">{{ st.shops_pending ?? '—' }}</div><div class="lbl">{{ t('بانتظار الموافقة') }}</div></div>
      <div class="kpi"><div class="ic" style="background:#FBE9E8;color:var(--accent-d)"><i class="ti ti-ticket"></i></div><div class="num">{{ st.tickets_total ?? '—' }}</div><div class="lbl">{{ t('إجمالي التذاكر') }}</div></div>
    </div>

    <div v-if="pending.length" class="reqs">
      <div class="reqs-h"><i class="ti ti-bell-ringing"></i> {{ t('طلبات تسجيل جديدة بانتظار قرارك') }} ({{ pending.length }})</div>
      <div v-for="s in pending" :key="s.id" class="req">
        <div class="req-info">
          <b>{{ s.name }}</b>
          <span class="muted">· {{ s.owner_name || '—' }}</span>
          <span class="muted" dir="ltr">· {{ s.phone || '' }}</span>
          <span class="muted" dir="ltr">· {{ s.email || '' }}</span>
        </div>
        <div class="req-acts">
          <button class="btn btn-accept" @click="doApprove(s)"><i class="ti ti-check"></i> {{ t('قبول') }}</button>
          <button class="btn btn-reject" @click="doReject(s)"><i class="ti ti-x"></i> {{ t('رفض') }}</button>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="ph">
        <h3>{{ t('المحلات') }}</h3>
        <div style="display:flex;gap:8px;align-items:center">
          <input v-model="q" @input="debouncedLoad" :placeholder="t('بحث بالاسم/الهاتف…')" class="search" />
          <select v-model="statusFilter" @change="load" class="search" style="width:150px">
            <option value="">{{ t('كل الحالات') }}</option>
            <option value="pending">{{ t('بانتظار الموافقة') }}</option>
            <option value="active">{{ t('فعّال') }}</option>
            <option value="suspended">{{ t('موقوف') }}</option>
          </select>
          <button class="btn btn-accent" @click="showAdd = !showAdd"><i class="ti ti-plus"></i> {{ t('محل جديد') }}</button>
        </div>
      </div>

      <div v-if="showAdd" class="addbox">
        <div class="arow">
          <input v-model="nw.shop_name" :placeholder="t('اسم المحل')" />
          <input v-model="nw.owner_name" :placeholder="t('اسم المالك')" />
          <input v-model="nw.phone" :placeholder="t('الهاتف')" dir="ltr" />
          <input v-model="nw.email" :placeholder="t('البريد')" dir="ltr" />
          <input v-model="nw.password" :placeholder="t('كلمة المرور')" dir="ltr" />
          <select v-model="nw.plan_id"><option :value="null">{{ t('بدون باقة') }}</option><option v-for="p in plansList" :key="p.id" :value="p.id">{{ p.name }}</option></select>
          <button class="btn btn-accent" :disabled="busy" @click="add">{{ t('حفظ') }}</button>
        </div>
        <div v-if="addErr" class="err" style="margin-top:8px">{{ addErr }}</div>
      </div>

      <div v-if="featShop" class="addbox">
        <div style="font-weight:700;margin-bottom:10px"><i class="ti ti-adjustments"></i> مميزات محل: {{ featShop.name }}</div>
        <div class="feat-grid">
          <label v-for="(label, key) in featCatalog" :key="key" class="tg">
            <input type="checkbox" :value="key" v-model="featSel" /> <span>{{ label }}</span>
          </label>
        </div>
        <div style="margin-top:12px;display:flex;gap:8px">
          <button class="btn btn-accent" :disabled="busy" @click="saveFeatures"><i class="ti ti-device-floppy"></i> حفظ المميزات</button>
          <button class="btn" @click="featShop = null">إلغاء</button>
        </div>
      </div>

      <table>
        <thead><tr><th>{{ t('المحل') }}</th><th>{{ t('التواصل') }}</th><th>{{ t('الحالة') }}</th><th>{{ t('الباقة') }}</th><th>{{ t('ينتهي') }}</th><th>{{ t('تذاكر') }}</th><th>{{ t('إجراءات') }}</th></tr></thead>
        <tbody>
          <tr v-for="s in rows" :key="s.id">
            <td><b>{{ s.name }}</b><div class="muted">/s/{{ s.slug }}</div></td>
            <td><div>{{ s.owner_name || '—' }}</div><div class="muted" dir="ltr">{{ s.phone || '' }}</div></td>
            <td><span class="pill" :class="stateCls(s.access_state)">{{ t(stateLabel(s.access_state)) }}</span></td>
            <td>
              <select :value="s.plan_id || ''" @change="setPlan(s, $event.target.value)" class="mini">
                <option value="">—</option>
                <option v-for="p in plansList" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </td>
            <td class="muted">{{ fmtDate(s.subscription_ends_at) }}</td>
            <td>{{ s.tickets_count }}</td>
            <td class="acts">
              <button v-if="s.status === 'pending'" class="act g" :title="t('اعتماد')" @click="doApprove(s)"><i class="ti ti-check"></i></button>
              <button v-if="s.status === 'pending'" class="act danger" :title="t('رفض الطلب')" @click="doReject(s)"><i class="ti ti-x"></i></button>
              <button class="act" :title="t('تجديد الاشتراك')" @click="doRenew(s)"><i class="ti ti-refresh"></i></button>
              <button v-if="s.status !== 'suspended'" class="act y" :title="t('إيقاف')" @click="doSuspend(s)"><i class="ti ti-ban"></i></button>
              <button v-else class="act g" :title="t('تفعيل')" @click="doActivate(s)"><i class="ti ti-player-play"></i></button>
              <button class="act" :title="t('المميزات')" @click="openFeatures(s)"><i class="ti ti-adjustments"></i></button>
              <a class="act" :title="t('فتح الموقع')" :href="'/s/' + s.slug" target="_blank"><i class="ti ti-external-link"></i></a>
              <button class="act danger" :title="t('حذف')" @click="doDelete(s)"><i class="ti ti-trash"></i></button>
            </td>
          </tr>
          <tr v-if="!rows.length"><td colspan="7" class="empty">{{ t('لا توجد محلات') }}</td></tr>
        </tbody>
      </table>
    </div>
  </SuperShell>
</template>

<script setup>
const sa = useSuperAdmin()
const { t } = useI18n()
const st = ref({})
const rows = ref([])
const plansList = ref([])
const q = ref('')
const statusFilter = ref('')
const showAdd = ref(false)
const busy = ref(false)
const addErr = ref('')
const nw = ref({ shop_name: '', owner_name: '', phone: '', email: '', password: '', plan_id: null })
const featCatalog = ref({})
const featShop = ref(null)
const featSel = ref([])

const pending = computed(() => rows.value.filter(s => s.status === 'pending'))

const fmtDate = (d) => d ? new Date(d).toLocaleDateString('ar-EG', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'
const stateLabel = (s) => ({ pending: 'بانتظار الموافقة', active: 'فعّال', suspended: 'موقوف', expired: 'منتهٍ' }[s] || s)
const stateCls = (s) => ({ pending: 'pill-y', active: 'pill-g', suspended: 'pill-gray', expired: 'pill-r' }[s] || 'pill-gray')

async function load() {
  const params = {}
  if (statusFilter.value) params.status = statusFilter.value
  if (q.value) params.q = q.value
  rows.value = await sa.shops(params)
}
let debTimer = null
function debouncedLoad() { clearTimeout(debTimer); debTimer = setTimeout(load, 300) }

async function refreshStats() { st.value = await sa.stats() }

async function setPlan(s, planId) { await sa.updateShop(s.id, { plan_id: planId || null }); await load() }
async function doApprove(s) { await sa.approveShop(s.id); await load(); await refreshStats() }
async function doReject(s) {
  if (!confirm(`رفض طلب "${s.name}" وحذفه نهائيًا؟`)) return
  await sa.rejectShop(s.id); await load(); await refreshStats()
}
async function doRenew(s) { await sa.renewShop(s.id); await load() }
async function doSuspend(s) { await sa.updateShop(s.id, { status: 'suspended' }); await load(); await refreshStats() }
async function doActivate(s) { await sa.updateShop(s.id, { status: 'active' }); await load(); await refreshStats() }
async function doDelete(s) {
  if (!confirm(`حذف محل "${s.name}" وكل بياناته؟`)) return
  await sa.deleteShop(s.id); await load(); await refreshStats()
}
async function add() {
  busy.value = true; addErr.value = ''
  try {
    await sa.createShop(nw.value)
    showAdd.value = false
    nw.value = { shop_name: '', owner_name: '', phone: '', email: '', password: '', plan_id: null }
    await load(); await refreshStats()
  } catch (e) { addErr.value = e?.data?.message || 'تعذّر إنشاء المحل' }
  finally { busy.value = false }
}

function openFeatures(s) {
  featShop.value = s
  featSel.value = Array.isArray(s.features) ? [...s.features] : []
  showAdd.value = false
}
async function saveFeatures() {
  busy.value = true
  try {
    await sa.updateShop(featShop.value.id, { features: featSel.value })
    featShop.value = null
    await load()
  } finally { busy.value = false }
}

onMounted(async () => {
  try { featCatalog.value = await sa.featureCatalog() } catch {}
  try { plansList.value = await sa.plans() } catch {}
  try { await refreshStats() } catch {}
  try { await load() } catch {}
})
</script>

<style scoped>
.page-title { font-size: 22px; font-weight: 800; }
.page-sub { color: var(--muted); margin-bottom: 18px; font-size: 14px; }
.kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 18px; }
.kpi { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 18px; }
.ic { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; }
.num { font-size: 28px; font-weight: 800; } .lbl { color: var(--muted); font-size: 13px; }
.reqs { background: #FFF8EC; border: 1px solid #F0D9A8; border-radius: 14px; padding: 16px 18px; margin-bottom: 18px; }
.reqs-h { font-size: 14px; font-weight: 700; color: #92600A; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.req { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-top: 1px solid #F0E2C4; flex-wrap: wrap; }
.req-info { font-size: 14px; } .req-info b { font-size: 15px; }
.req-acts { display: flex; gap: 8px; }
.btn-accept { background: var(--green); color: #fff; }
.btn-reject { background: #fff; color: var(--accent); border: 1px solid #E7B9B6; }
.panel { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 18px; }
.ph { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px; }
.ph h3 { font-size: 15px; font-weight: 700; }
.search { height: 38px; border: 1px solid var(--line); border-radius: 9px; padding: 0 12px; font-family: inherit; font-size: 13px; }
.addbox { background: #F7F9FC; border: 1px solid var(--line); border-radius: 11px; padding: 14px; margin-bottom: 14px; }
.arow { display: grid; grid-template-columns: repeat(6, 1fr) auto; gap: 8px; }
.arow input, .arow select { height: 38px; border: 1px solid var(--line); border-radius: 9px; padding: 0 10px; font-family: inherit; font-size: 13px; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: right; padding: 11px 10px; border-bottom: 1px solid var(--line-2); font-size: 14px; vertical-align: middle; }
th { font-size: 12px; color: var(--muted); font-weight: 700; }
.muted { color: var(--muted); font-size: 12px; }
.mini { height: 34px; width: 150px; border: 1px solid var(--line); border-radius: 8px; font-family: inherit; font-size: 13px; padding: 0 10px; background: #fff; color: var(--ink); }
.acts { display: flex; gap: 5px; }
.act { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--line); background: #fff; color: var(--ink); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 15px; }
.act:hover { background: #F2F5F9; }
.act.g { color: var(--green); border-color: #Bfe5d7; } .act.y { color: #B5790E; } .act.danger { color: var(--accent); }
.empty { text-align: center; color: var(--muted); padding: 24px; }
.reqs { background: #FFF8EC; border: 1px solid #F3D8A0; border-radius: 14px; padding: 15px 17px; margin-bottom: 18px; }
.reqs-h { font-weight: 700; color: #92600A; font-size: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.req { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: #fff; border: 1px solid #F0E0BE; border-radius: 10px; padding: 11px 14px; margin-top: 8px; flex-wrap: wrap; }
.req-info { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; font-size: 14px; }
.req-info b { font-size: 15px; }
.req-acts { display: flex; gap: 8px; }
.btn-accept { background: var(--green); color: #fff; }
.btn-reject { background: #fff; color: var(--accent); border: 1px solid var(--accent); }
.feat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.feat-grid .tg { display: flex; align-items: center; gap: 9px; background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 11px 14px; font-size: 14px; cursor: pointer; }
.feat-grid .tg input { width: 17px; height: 17px; cursor: pointer; }
@media (max-width: 800px) { .feat-grid { grid-template-columns: 1fr; } }
@media (max-width: 1000px) { .kpis { grid-template-columns: 1fr 1fr; } .arow { grid-template-columns: 1fr 1fr; } }
</style>
