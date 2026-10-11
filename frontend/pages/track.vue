<template>
  <div>
    <SiteHeader />
    <section class="phead">
      <h1 class="page-title" style="color:#fff">تتبّع حالة التصليح</h1>
      <p style="color:#B5D4F4">أدخل رقم التذكرة أو رقم جوالك لمعرفة آخر تحديث</p>
    </section>

    <div class="box">
      <div class="panel">
        <div style="display:flex;gap:10px">
          <input v-model="q" placeholder="RX-10248" @keyup.enter="run" />
          <button class="btn btn-accent" :disabled="loading" @click="run"><i class="ti ti-search"></i> تتبّع</button>
        </div>

        <div v-if="err" class="err" style="margin-top:16px">{{ err }}</div>

        <div v-if="ticket" style="margin-top:24px">
          <div class="tk-head">
            <div><div class="muted">التذكرة</div><b style="color:var(--accent-d)">{{ ticket.ticket_no }}</b></div>
            <div><div class="muted">الجهاز</div><b>{{ ticket.device }}</b></div>
            <div><div class="muted">العطل</div><b>{{ ticket.repair_type || '—' }}</b></div>
            <span class="pill" :class="pillClass(ticket.status)">{{ ticket.status_label }}</span>
          </div>

          <!-- timeline -->
          <div class="tl">
            <template v-for="(s, i) in statuses" :key="s.key">
              <div class="step" :class="stepState(s.key)">
                <span class="dot"><i :class="'ti ' + stepIcon(s.key)"></i></span>
                <div class="lab">{{ s.label }}</div>
              </div>
              <div v-if="i < statuses.length - 1" class="line" :class="lineState(i)"></div>
            </template>
          </div>

          <div v-if="lastNote" class="note"><i class="ti ti-info-circle"></i> آخر تحديث: {{ lastNote }}</div>
        </div>
      </div>
    </div>
    <SiteFooter />
  </div>
</template>

<script setup>
const { api } = useApi()
const route = useRoute()
const q = ref(route.query.q || '')
const ticket = ref(null)
const statuses = ref([])
const err = ref('')
const loading = ref(false)

const order = ['received', 'awaiting_approval', 'in_repair', 'ready', 'delivered']
const icons = { received: 'ti-check', awaiting_approval: 'ti-clock', in_repair: 'ti-tool', ready: 'ti-package', delivered: 'ti-circle-check' }
const idx = (s) => order.indexOf(s)

const stepIcon = (k) => icons[k] || 'ti-point'
const curIdx = computed(() => ticket.value ? idx(ticket.value.status) : -1)
function stepState(k) {
  const i = idx(k)
  if (i < curIdx.value) return 'done'
  if (i === curIdx.value) return 'curr'
  return 'todo'
}
function lineState(i) { return i < curIdx.value ? 'done' : (i === curIdx.value ? 'curr' : 'todo') }
const lastNote = computed(() => ticket.value?.events?.[0]?.note || '')

function pillClass(s) { return ({ ready: 'pill-g', in_repair: 'pill-y', awaiting_approval: 'pill-y', delivered: 'pill-gray', received: 'pill-r' })[s] || 'pill-gray' }

async function run() {
  if (!q.value) return
  loading.value = true; err.value = ''
  try {
    const r = await api('/site/track', { method: 'POST', body: { q: q.value } })
    ticket.value = r.ticket
    statuses.value = r.statuses
  } catch (e) {
    ticket.value = null
    err.value = 'لم نجد تذكرة بهذا الرقم — تأكد من الرقم وحاول مجدداً'
  } finally { loading.value = false }
}

onMounted(() => { if (q.value) run() })
</script>

<style scoped>
.phead { background: var(--accent-d); padding: 40px 22px; text-align: center; }
.box { max-width: 720px; margin: 30px auto; padding: 0 22px; }
.tk-head { display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--line); border-radius: 14px; padding: 16px 18px; gap: 12px; flex-wrap: wrap; }
.muted { color: var(--muted); font-size: 12px; }
.tl { display: flex; align-items: flex-start; justify-content: space-between; margin: 30px 0 10px; }
.step { text-align: center; flex-shrink: 0; }
.dot { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; }
.lab { font-size: 12px; margin-top: 8px; color: var(--muted); font-weight: 500; }
.line { height: 3px; flex: 1; margin: 16px 6px 0; border-radius: 3px; background: var(--line); }
.done .dot { background: var(--green); color: #fff; } .line.done { background: var(--green); }
.curr .dot { background: var(--gold); color: #fff; } .curr .lab { color: var(--ink); font-weight: 700; } .line.curr { background: var(--gold); }
.todo .dot { background: #fff; border: 1px solid #C7CCD6; color: var(--muted); }
.note { background: #E6F1FB; color: var(--accent-d); padding: 13px 16px; border-radius: 10px; margin-top: 18px; font-size: 13px; }
</style>
