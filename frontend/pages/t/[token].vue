<template>
  <div class="track-root">
    <div class="wrap">
      <div v-if="loading" class="card center" style="padding:60px">
        <i class="ti ti-loader-2 spin" style="font-size:28px;color:var(--accent)"></i>
        <div style="margin-top:12px;color:var(--muted)">{{ t('جارِ التحميل…') }}</div>
      </div>

      <div v-else-if="notFound" class="card center" style="padding:60px">
        <i class="ti ti-link-off" style="font-size:40px;color:var(--muted)"></i>
        <h2 style="margin:14px 0 6px">{{ t('رابط غير صالح') }}</h2>
        <p style="color:var(--muted)">{{ t('تأكد من الرابط أو تواصل مع المحل') }}</p>
      </div>

      <template v-else>
        <!-- ترويسة المحل -->
        <div class="shop-head">
          <div class="logo"><i class="ti ti-device-mobile"></i></div>
          <div>
            <h1>{{ shop.name || t('متابعة طلب الصيانة') }}</h1>
            <p v-if="shop.phone" dir="ltr">{{ shop.phone }}</p>
          </div>
          <div style="margin-inline-start:auto"><LangSwitch /></div>
        </div>

        <!-- بطاقة الحالة الحالية -->
        <div class="card status-card" :class="statusClass">
          <div class="st-icon"><i class="ti" :class="statusIcon"></i></div>
          <div class="st-no">{{ t('تذكرة') }} {{ tk.ticket_no }}</div>
          <div class="st-label">{{ t(tk.status_label) }}</div>
          <div class="st-msg">{{ statusMessage }}</div>
        </div>

        <!-- مؤشّر المراحل -->
        <div v-if="!tk.is_terminal" class="card">
          <div class="stepper">
            <div v-for="(s, i) in flow" :key="s.key" class="step" :class="{ done: s.done, current: s.current }">
              <div class="bullet"><i class="ti" :class="s.done ? 'ti-check' : 'ti-circle'"></i></div>
              <div class="slabel">{{ t(s.label) }}</div>
              <div v-if="i < flow.length - 1" class="bar" :class="{ fill: s.done }"></div>
            </div>
          </div>
        </div>

        <!-- بيانات الجهاز -->
        <div class="card">
          <div class="row"><span>{{ t('الجهاز') }}</span><b>{{ tk.device || '—' }}</b></div>
          <div class="row" v-if="tk.repair_type"><span>{{ t('العطل') }}</span><b>{{ tk.repair_type }}</b></div>
          <div class="row" v-if="tk.promised_at && !tk.is_ready"><span>{{ t('موعد التسليم المتوقع') }}</span><b>{{ tk.promised_at }}</b></div>
          <div class="row" v-if="tk.price"><span>{{ t('التكلفة') }}</span><b>{{ money(tk.price) }}</b></div>
          <div class="row" v-if="tk.warranty_until && tk.is_ready"><span>{{ t('الضمان حتى') }}</span><b style="color:var(--green)">{{ tk.warranty_until }}</b></div>
        </div>

        <!-- الخط الزمني -->
        <div class="card" v-if="tk.events?.length">
          <div class="sec-t">{{ t('سجل مراحل الصيانة') }}</div>
          <div class="tl">
            <div v-for="(e, i) in [...tk.events].reverse()" :key="i" class="ev">
              <span class="dot"></span>
              <div>
                <b>{{ t(e.status_label) }}</b>
                <span v-if="e.note && !isDefaultNote(e)" class="muted"> — {{ e.note }}</span>
                <div class="muted">{{ e.at }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="foot">
          <a v-if="shop.phone" :href="'tel:' + shop.phone" class="btn btn-out"><i class="ti ti-phone"></i> {{ t('اتصل بالمحل') }}</a>
          <a v-if="shop.phone" :href="waShop" target="_blank" class="btn btn-wa"><i class="ti ti-brand-whatsapp"></i> {{ t('واتساب') }}</a>
        </div>
        <p class="powered">{{ shop.name }}</p>
      </template>
    </div>
  </div>
</template>

<script setup>
const route = useRoute()
const config = useRuntimeConfig()
const { t, isEn } = useI18n()

const loading = ref(true)
const notFound = ref(false)
const shop = ref({})
const tk = ref({})
const flow = ref([])

const money = (n) => Number(n || 0).toLocaleString(isEn.value ? 'en-US' : 'ar-EG') + ' ' + t('ر.س')

const statusClass = computed(() => {
  if (tk.value.is_terminal) return 'bad'
  if (tk.value.is_ready) return 'good'
  return 'prog'
})
const statusIcon = computed(() => {
  if (tk.value.status === 'delivered') return 'ti-circle-check'
  if (tk.value.is_ready) return 'ti-package'
  if (tk.value.is_terminal) return 'ti-alert-circle'
  if (tk.value.status === 'waiting_part') return 'ti-clock-pause'
  if (tk.value.status === 'awaiting_approval') return 'ti-help-circle'
  return 'ti-tool'
})
const statusMessage = computed(() => {
  const m = {
    received: 'استلمنا جهازك وسنبدأ الفحص قريباً.',
    inspecting: 'جهازك قيد الفحص لتحديد العطل والتكلفة.',
    awaiting_approval: 'بانتظار موافقتك على التكلفة للبدء بالإصلاح.',
    waiting_part: 'بانتظار وصول قطعة الغيار، وسنكمل فور وصولها.',
    in_repair: 'جاري إصلاح جهازك الآن.',
    testing: 'انتهى الإصلاح، وجاري الاختبار النهائي للتأكد من الجودة.',
    ready: 'جهازك جاهز للاستلام! يمكنك المرور على المحل.',
    delivered: 'تم تسليم جهازك. شكراً لثقتك بنا 🙏',
    rejected: 'تم تسجيل عدم الموافقة على التكلفة. جهازك جاهز للاستلام.',
    unrepairable: 'نأسف، تعذّر إصلاح جهازك. يمكنك المرور لاستلامه.',
    cancelled: 'تم إلغاء الطلب. للاستفسار تواصل مع المحل.',
  }
  return t(m[tk.value.status] || m.received)
})
const waShop = computed(() => {
  let n = (shop.value.phone || '').replace(/\D/g, '')
  if (n.startsWith('00')) n = n.slice(2); else if (n.startsWith('0')) n = '966' + n.slice(1)
  return `https://wa.me/${n}?text=${encodeURIComponent('بخصوص تذكرة ' + tk.value.ticket_no)}`
})
function isDefaultNote(e) {
  return e.note === e.status_label || e.note === 'تم فتح التذكرة'
}

onMounted(async () => {
  try {
    const r = await $fetch(config.public.apiBase + '/track/' + route.params.token, { headers: { Accept: 'application/json' } })
    shop.value = r.shop; tk.value = r.ticket; flow.value = r.flow
  } catch { notFound.value = true } finally { loading.value = false }
})

useHead({ title: 'متابعة طلب الصيانة' })
</script>

<style scoped>
.track-root { min-height: 100vh; background: var(--bg); padding: 20px 16px 40px; }
.wrap { max-width: 560px; margin: 0 auto; }
.card { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 18px; margin-bottom: 14px; }
.center { text-align: center; }
.spin { animation: spin 1s linear infinite; } @keyframes spin { to { transform: rotate(360deg); } }
.shop-head { display: flex; align-items: center; gap: 12px; padding: 6px 2px 16px; }
.shop-head .logo { width: 46px; height: 46px; border-radius: 12px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.shop-head h1 { font-size: 19px; font-weight: 800; }
.shop-head p { font-size: 12.5px; color: var(--muted); }
.status-card { text-align: center; padding: 26px 18px; }
.status-card .st-icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 34px; margin: 0 auto 12px; }
.status-card.prog .st-icon { background: #E6F1FB; color: var(--accent); }
.status-card.good .st-icon { background: #E4F6EF; color: var(--green); }
.status-card.bad .st-icon { background: #FBE9E8; color: #A32020; }
.st-no { font-size: 12.5px; color: var(--muted); }
.st-label { font-size: 22px; font-weight: 800; margin: 4px 0 8px; }
.status-card.good .st-label { color: var(--green); }
.status-card.bad .st-label { color: #A32020; }
.st-msg { font-size: 14px; color: var(--ink); line-height: 1.7; }
.stepper { display: flex; flex-direction: column; gap: 0; }
.step { display: grid; grid-template-columns: 34px 1fr; align-items: center; position: relative; row-gap: 0; }
.step .bullet { width: 30px; height: 30px; border-radius: 50%; border: 2px solid var(--line); background: #fff; display: flex; align-items: center; justify-content: center; font-size: 15px; color: var(--muted); z-index: 1; }
.step.done .bullet { background: var(--green); border-color: var(--green); color: #fff; }
.step.current .bullet { background: var(--accent); border-color: var(--accent); color: #fff; box-shadow: 0 0 0 4px #E6F1FB; }
.step .slabel { font-size: 13.5px; color: var(--muted); padding: 7px 10px; }
.step.done .slabel, .step.current .slabel { color: var(--ink); font-weight: 600; }
.step .bar { position: absolute; inset-inline-start: 14px; top: 30px; width: 2px; height: calc(100% - 30px); background: var(--line); }
.step .bar.fill { background: var(--green); }
.row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--line-2); font-size: 14px; gap: 12px; }
.row:last-child { border: none; }
.row span { color: var(--muted); }
.sec-t { font-size: 15px; font-weight: 700; margin-bottom: 12px; }
.tl .ev { display: flex; gap: 10px; padding: 8px 0; }
.tl .dot { width: 9px; height: 9px; border-radius: 50%; background: var(--accent); margin-top: 7px; flex-shrink: 0; }
.muted { color: var(--muted); font-size: 12px; }
.foot { display: flex; gap: 10px; margin-top: 4px; }
.foot .btn { flex: 1; justify-content: center; }
.powered { text-align: center; color: var(--muted); font-size: 12px; margin-top: 18px; }
</style>
