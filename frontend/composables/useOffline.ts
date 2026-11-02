// العمل دون اتصال: صندوق انتظار (outbox) في IndexedDB لعمليات الكتابة الآمنة،
// يُرفع تلقائياً عند عودة الاتصال مع Idempotency-Key حتى لا تتكرر العملية على السيرفر.
// القراءة دون اتصال يتولاها sw.js (آخر نسخة محفوظة)، وهنا ندمج فوقها العمليات المعلّقة.

type Op = {
  id: string
  key: string // Idempotency-Key
  method: string
  path: string
  body?: any
  tmp?: string // معرّف مؤقت للسجل المُنشأ (tmp_...)
  meta?: any // بيانات عرض (اسم العطل/الفني...) للدمج في القوائم
  label: string
  status: 'pending' | 'failed'
  error?: string
  created_at: string
}

// قواعد العمليات المسموح تأجيلها — المال والملفات والإعدادات الحساسة لا تُؤجَّل
const RULES: { m: string; re: RegExp; label: string; create?: boolean }[] = [
  { m: 'POST', re: /^\/dashboard\/tickets$/, label: 'فتح تذكرة', create: true },
  { m: 'PUT', re: /^\/dashboard\/tickets\/[^/]+$/, label: 'تعديل تذكرة' },
  { m: 'POST', re: /^\/dashboard\/tickets\/[^/]+\/status$/, label: 'تغيير حالة تذكرة' },
  { m: 'DELETE', re: /^\/dashboard\/tickets\/[^/]+$/, label: 'حذف تذكرة' },
  { m: 'POST', re: /^\/dashboard\/inventory$/, label: 'إضافة قطعة للمخزون', create: true },
  { m: 'PUT', re: /^\/dashboard\/inventory\/[^/]+$/, label: 'تعديل قطعة' },
  { m: 'DELETE', re: /^\/dashboard\/inventory\/[^/]+$/, label: 'حذف قطعة' },
  { m: 'POST', re: /^\/dashboard\/technicians$/, label: 'إضافة فني', create: true },
  { m: 'PUT', re: /^\/dashboard\/technicians\/[^/]+$/, label: 'تعديل فني' },
  { m: 'DELETE', re: /^\/dashboard\/technicians\/[^/]+$/, label: 'حذف فني' },
  { m: 'POST', re: /^\/dashboard\/bookings\/[^/]+\/status$/, label: 'تحديث حجز' },
  { m: 'PUT', re: /^\/dashboard\/settings$/, label: 'حفظ الإعدادات' },
  { m: 'POST', re: /^\/dashboard\/customers$/, label: 'إضافة عميل', create: true },
  { m: 'PUT', re: /^\/dashboard\/customers\/[^/]+$/, label: 'تعديل عميل' },
]

// شاشات تُحمَّل مسبقاً حتى تفتح دون اتصال حتى لو لم يزرها المستخدم
const WARM = [
  '/me', '/dashboard/stats', '/dashboard/tickets', '/dashboard/technicians', '/dashboard/bookings',
  '/dashboard/repair-types', '/dashboard/price-rules', '/dashboard/catalog', '/dashboard/settings',
  '/dashboard/customers', '/dashboard/finance', '/dashboard/expenses',
  '/site/catalog', '/site/settings',
]
const WARM_ROUTES = ['/admin', '/admin/tickets', '/admin/tickets/1', '/admin/ticket-new', '/admin/inventory', '/admin/technicians',
  '/admin/bookings', '/admin/prices', '/admin/catalog', '/admin/settings', '/admin/sync',
  '/admin/customers', '/admin/customers/1', '/admin/finance', '/admin/print/1']

const online = ref(true)
const ops = ref<Op[]>([])
const syncing = ref(false)
const fromCache = ref(false)
const syncTick = ref(0)
const lastSync = ref<string | null>(null)
const canInstall = ref(false)
let installEvt: any = null
let dbp: Promise<IDBDatabase> | null = null
let dbName = ''
let curUser: any = 'anon'
let apiBase = ''
let getToken: () => string | null = () => null

function uid() {
  return (globalThis.crypto?.randomUUID?.() || Date.now().toString(36) + Math.random().toString(36).slice(2))
}

function setUser(id: any) {
  if (!id || String(id) === String(curUser)) return
  curUser = id
  try { localStorage.setItem('pr_uid', String(id)) } catch {}
  if (started) readyP = reload().catch(() => {})
}

function db(): Promise<IDBDatabase> {
  const name = 'mobilefix_' + curUser
  if (dbp && dbName === name) return dbp
  dbName = name
  dbp = new Promise((res, rej) => {
    const r = indexedDB.open(name, 1)
    r.onupgradeneeded = () => {
      const d = r.result
      if (!d.objectStoreNames.contains('outbox')) d.createObjectStore('outbox', { keyPath: 'id' })
      if (!d.objectStoreNames.contains('meta')) d.createObjectStore('meta')
    }
    r.onsuccess = () => res(r.result)
    r.onerror = () => rej(r.error)
  })
  return dbp
}

async function tx<T>(store: string, mode: IDBTransactionMode, fn: (s: IDBObjectStore) => IDBRequest | void): Promise<T> {
  const d = await db()
  return new Promise((res, rej) => {
    const t = d.transaction(store, mode)
    const req = fn(t.objectStore(store))
    t.oncomplete = () => res((req as IDBRequest)?.result as T)
    t.onerror = () => rej(t.error)
  })
}

async function reload() {
  if (!import.meta.client) return
  const all = await tx<Op[]>('outbox', 'readonly', (s) => s.getAll())
  ops.value = (all || []).sort((a, b) => a.created_at.localeCompare(b.created_at))
  lastSync.value = await tx<string>('meta', 'readonly', (s) => s.get('last_sync'))
}

async function getIdmap(): Promise<Record<string, any>> {
  return (await tx<any>('meta', 'readonly', (s) => s.get('idmap'))) || {}
}

function ruleFor(method: string, path: string) {
  const p = path.split('?')[0]
  return RULES.find((r) => r.m === method && r.re.test(p))
}

function isNetworkError(e: any) {
  return !e?.response && !e?.statusCode && !e?.status
}

// يُرجع رداً متفائلاً حتى تكمل الشاشة كأن العملية نجحت
async function enqueue(method: string, path: string, body: any, meta?: any) {
  const rule = ruleFor(method, path)!
  const op: Op = {
    id: uid(), key: uid(), method, path, body: body ? JSON.parse(JSON.stringify(body)) : undefined,
    meta, label: rule.label, status: 'pending', created_at: new Date().toISOString(),
  }
  if (rule.create) op.tmp = 'tmp_' + op.id.slice(0, 8)
  await tx('outbox', 'readwrite', (s) => s.put(op))
  await reload()
  const base = { message: 'تم الحفظ على الجهاز — سيُرفع عند عودة الاتصال', _queued: true }
  if (op.tmp) {
    const row = { ...(op.body || {}), id: op.tmp, _pending: true }
    if (path === '/dashboard/tickets') row.ticket_no = 'مؤقت-' + op.tmp.slice(4, 8).toUpperCase()
    return { ...base, ...row }
  }
  return { ...base, ticket: op.body, item: op.body, technician: op.body, booking: op.body }
}

async function flush() {
  if (!import.meta.client || syncing.value || !navigator.onLine) return
  await reload()
  if (!ops.value.some((o) => o.status === 'pending')) return
  syncing.value = true
  const token = getToken()
  let done = 0
  try {
    const idmap = await getIdmap()
    for (const op of ops.value) {
      if (op.status !== 'pending') continue
      const path = op.path.replace(/tmp_[a-z0-9]+/gi, (t) => idmap[t] ?? t)
      if (/tmp_/.test(path)) { // السجل الأصلي فشل رفعه
        await tx('outbox', 'readwrite', (s) => s.put({ ...op, status: 'failed', error: 'السجل المرتبط لم يُرفع بعد' }))
        continue
      }
      try {
        const r: any = await $fetch(apiBase + path, {
          method: op.method as any, body: op.body,
          headers: { Accept: 'application/json', 'Idempotency-Key': op.key, ...(token ? { Authorization: `Bearer ${token}` } : {}) },
        })
        if (op.tmp && r?.id) {
          idmap[op.tmp] = r.id
          await tx('meta', 'readwrite', (s) => s.put(idmap, 'idmap'))
        }
        await tx('outbox', 'readwrite', (s) => s.delete(op.id))
        done++
      } catch (e: any) {
        if (isNetworkError(e)) break
        const st = e?.response?.status || e?.statusCode
        if (op.method === 'DELETE' && st === 404) { await tx('outbox', 'readwrite', (s) => s.delete(op.id)); done++; continue }
        if (st >= 500 || st === 401 || st === 429) break // نعيد لاحقاً
        const msg = e?.data?.message || 'رفض السيرفر العملية'
        await tx('outbox', 'readwrite', (s) => s.put({ ...op, status: 'failed', error: msg }))
      }
    }
    await tx('meta', 'readwrite', (s) => s.put(new Date().toISOString(), 'last_sync'))
  } finally {
    syncing.value = false
    await reload()
    if (done) syncTick.value++ // تُعيد الصفحة الحالية تحميل بياناتها من السيرفر
  }
}

async function retry(id: string) {
  const op = ops.value.find((o) => o.id === id)
  if (!op) return
  await tx('outbox', 'readwrite', (s) => s.put({ ...op, status: 'pending', error: undefined }))
  await flush()
}

async function discard(id: string) {
  await tx('outbox', 'readwrite', (s) => s.delete(id))
  await reload()
  syncTick.value++
}

// ---- دمج العمليات المعلّقة فوق بيانات القراءة ----
function pendingFor(re: RegExp, method?: string) {
  return ops.value.filter((o) => o.status === 'pending' && re.test(o.path) && (!method || o.method === method))
}
function idOf(path: string) {
  return path.split('/')[3]
}
function applyList(list: any[], base: string, row: (o: Op) => any = (o) => ({ ...o.body, id: o.tmp, _pending: true })) {
  if (!Array.isArray(list)) return list
  const reBase = new RegExp('^' + base + '(/|$)')
  let out = list.slice()
  for (const o of pendingFor(reBase)) {
    const id = idOf(o.path)
    if (o.method === 'POST' && o.path === base) out.unshift(row(o))
    else if (o.method === 'PUT') out = out.map((x) => (String(x.id) === id ? { ...x, ...o.body, _pending: true } : x))
    else if (o.method === 'DELETE') out = out.filter((x) => String(x.id) !== id)
    else if (o.path.endsWith('/status')) out = out.map((x) => (String(x.id) === id ? { ...x, status: o.body?.status, _pending: true } : x))
  }
  return out
}

const ticketRow = (o: Op) => ({
  id: o.tmp, ticket_no: 'مؤقت-' + o.tmp!.slice(4, 8).toUpperCase(), customer_name: o.body?.customer_name,
  customer_phone: o.body?.customer_phone, device: [o.body?.make, o.body?.model].filter(Boolean).join(' '),
  repair_type: o.meta?.repair_type || null, price: o.body?.price || null, status: o.body?.status || 'received',
  status_label: null, technician: o.meta?.technician || null, created_at: o.created_at.slice(0, 16).replace('T', ' '),
  paid: 0, balance: o.body?.price || 0, promised_at: o.body?.promised_at || null, overdue: false, warranty_until: null,
  customer_id: null, _pending: true,
})

function overlay(path: string, data: any) {
  const [p, qs] = path.split('?')
  if (!ops.value.some((o) => o.status === 'pending')) return data
  if (p === '/dashboard/tickets' && data?.tickets) {
    const status = new URLSearchParams(qs || '').get('status')
    let rows = applyList(data.tickets, '/dashboard/tickets', ticketRow)
    rows = rows.map((r: any) => (r._pending ? { ...r, status_label: data.statuses?.find((s: any) => s.key === r.status)?.label || r.status } : r))
    if (status) rows = rows.filter((r: any) => r.status === status)
    const counts = { ...(data.counts || {}) }
    for (const o of pendingFor(/^\/dashboard\/tickets$/, 'POST')) counts[o.body?.status || 'received'] = Number(counts[o.body?.status || 'received'] || 0) + 1
    return { ...data, tickets: rows, counts }
  }
  const m = p.match(/^\/dashboard\/tickets\/([^/]+)$/)
  if (m && data?.ticket) {
    const st = pendingFor(new RegExp('^/dashboard/tickets/' + m[1] + '(/status)?$')).pop()
    if (st) return { ...data, ticket: { ...data.ticket, ...(st.path.endsWith('/status') ? { status: st.body?.status } : st.body) } }
    return data
  }
  if (p === '/dashboard/customers') return applyList(data, p)
  if (p === '/dashboard/inventory') return applyList(data, p)
  if (p === '/dashboard/technicians') return applyList(data, p)
  if (p === '/dashboard/bookings') return applyList(data, p)
  if (p === '/dashboard/settings') {
    const s = pendingFor(/^\/dashboard\/settings$/).pop()
    return s ? { ...data, ...s.body } : data
  }
  return data
}

// تذكرة أُنشئت دون اتصال ولم تُرفع: تفاصيلها من الصندوق نفسه
function localRecord(path: string) {
  const m = path.match(/^\/dashboard\/tickets\/(tmp_[a-z0-9]+)$/i)
  if (!m) return null
  const o = ops.value.find((x) => x.tmp === m[1])
  if (!o) return null
  return { ticket: { ...o.body, id: o.tmp, ticket_no: ticketRow(o).ticket_no, repair_type: o.meta?.repair_type ? { name: o.meta.repair_type } : null, events: [], created_at: ticketRow(o).created_at, _pending: true } }
}

// ---- تحميل مسبق للشاشات والبيانات (يُستدعى عند فتح اللوحة وهو متصل) ----
let warmedAt = 0
async function warmUp(api: (p: string) => Promise<any>, router: any, features: string[] = []) {
  if (!import.meta.client || !navigator.onLine || Date.now() - warmedAt < 10 * 60 * 1000) return
  // أول زيارة: ننتظر حتى يتولى الـ SW الصفحة وإلا لن يُخزَّن شيء
  const sw = navigator.serviceWorker
  if (!sw) return
  if (!sw.controller) await new Promise((r) => { sw.addEventListener('controllerchange', r, { once: true }); setTimeout(r, 20000) })
  if (!sw.controller) return
  warmedAt = Date.now()
  const list = [...WARM, ...(features.includes('inventory') ? ['/dashboard/inventory'] : [])]
  for (const p of list) { try { await api(p) } catch {} }
  // تفاصيل التذاكر المفتوحة (غير المسلّمة/الملغاة) حتى تفتح دون اتصال
  try {
    const r = await api('/dashboard/tickets')
    const open = (r?.tickets || []).filter((t: any) => !['delivered', 'cancelled'].includes(t.status) && !t._pending).slice(0, 60)
    for (const t of open) { try { await api('/dashboard/tickets/' + t.id) } catch {} }
  } catch {}
  // ملفات الصفحات تُحمَّل مسبقاً حتى تفتح أي صفحة دون اتصال
  try {
    for (const r of WARM_ROUTES) {
      const rec = router.resolve(r).matched
      for (const m of rec) { const c: any = m.components?.default; if (typeof c === 'function') await c().catch(() => {}) }
    }
  } catch {}
}

async function install() {
  if (!installEvt) return
  installEvt.prompt()
  try { await installEvt.userChoice } catch {}
  installEvt = null
  canInstall.value = false
}

let started = false
let readyP: Promise<void> = Promise.resolve()
// أول تحميل للصندوق بعد فتح الصفحة — القراءات تنتظره حتى تُدمج العمليات المعلّقة
function ready() {
  return readyP
}
function start(opts: { apiBase: string; getToken: () => string | null }) {
  if (!import.meta.client || started) return
  started = true
  apiBase = opts.apiBase
  getToken = opts.getToken
  // صندوق آخر مستخدم على هذا الجهاز (قبل أن يصل /me)
  try { curUser = (getToken() && localStorage.getItem('pr_uid')) || 'anon' } catch {}
  online.value = navigator.onLine
  window.addEventListener('online', () => { online.value = true; flush() })
  window.addEventListener('offline', () => { online.value = false })
  window.addEventListener('beforeinstallprompt', (e: any) => { e.preventDefault(); installEvt = e; canInstall.value = true })
  window.addEventListener('appinstalled', () => { canInstall.value = false })
  setInterval(() => { if (navigator.onLine) flush() }, 30000)
  readyP = reload().catch(() => {})
  readyP.then(flush)
}

function clearApiCache(onLogin = false) {
  if (import.meta.client) navigator.serviceWorker?.controller?.postMessage(onLogin ? 'RESET_API' : 'CLEAR_API')
}

export function useOffline() {
  const pending = computed(() => ops.value.filter((o) => o.status === 'pending').length)
  const failed = computed(() => ops.value.filter((o) => o.status === 'failed').length)
  return {
    online, ops, pending, failed, syncing, fromCache, syncTick, lastSync, canInstall,
    start, ready, setUser, reload, flush, retry, discard, enqueue, ruleFor, isNetworkError, overlay, localRecord,
    warmUp, install, clearApiCache,
  }
}
