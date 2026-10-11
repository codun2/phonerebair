<template>
  <div>
    <SiteHeader />

    <!-- Hero -->
    <section class="hero">
      <div class="hero-in">
        <span class="tag"><i class="ti ti-shield-check"></i> {{ settings.hero_badge || 'صيانة معتمدة · ضمان على كل تصليح' }}</span>
        <h1>{{ settings.hero_title || 'جهازك بأمان — تصليح احترافي بنفس اليوم' }}</h1>
        <p>{{ settings.hero_subtitle || 'اعرف سعر التصليح فوراً، احجز موعدك، وتابع حالة جهازك لحظة بلحظة.' }}</p>
        <div class="cta">
          <a href="#quote" class="btn btn-accent btn-lg"><i class="ti ti-calculator"></i> احسب سعر التصليح</a>
          <a v-if="waLink" :href="waLink" target="_blank" class="btn btn-wa btn-lg"><i class="ti ti-brand-whatsapp"></i> تواصل واتساب</a>
        </div>
      </div>
    </section>

    <!-- Instant quote calculator -->
    <div class="quote-wrap" id="quote">
      <div class="quote panel">
        <div class="sec-t"><i class="ti ti-calculator" style="color:var(--accent)"></i> حاسبة السعر الفوري</div>
        <p class="page-sub" style="margin-bottom:16px">اختر جهازك ونوع العطل ليظهر السعر ومدة التصليح فوراً</p>
        <div class="g3">
          <div><label>الماركة</label>
            <select v-model="sel.make" @change="onMake">
              <option v-for="m in catalog.makes" :key="m.id" :value="m">{{ m.name }}</option>
            </select>
          </div>
          <div><label>الموديل</label>
            <select v-model="sel.model" @change="calc">
              <option v-for="md in (sel.make?.models || [])" :key="md.id" :value="md">{{ md.name }}</option>
            </select>
          </div>
          <div><label>نوع العطل</label>
            <select v-model="sel.type" @change="calc">
              <option v-for="t in catalog.repair_types" :key="t.id" :value="t">{{ t.name }}</option>
            </select>
          </div>
        </div>
        <div class="result" :class="{ empty: !result?.found }">
          <template v-if="result?.found">
            <div><div class="rl">السعر التقديري</div><div class="price">{{ fmt(result.price) }} <span>ر.س</span></div></div>
            <div class="center"><div class="rl">مدة التصليح</div><div class="rv">{{ result.duration_minutes }} دقيقة</div></div>
            <div class="center"><div class="rl">الضمان</div><div class="rv">{{ result.warranty_months }} أشهر</div></div>
            <div class="spacer"></div>
            <NuxtLink to="/book" class="btn btn-accent btn-lg">احجز التصليح ←</NuxtLink>
          </template>
          <template v-else>
            <span style="color:var(--muted)"><i class="ti ti-info-circle"></i> {{ result?.message || 'اختر الماركة والموديل ونوع العطل' }}</span>
          </template>
        </div>
      </div>
    </div>

    <!-- Services -->
    <section class="sec" id="services">
      <div class="wrap">
        <h2 class="center">خدماتنا</h2>
        <p class="page-sub center">نصلّح كل أعطال الجوالات بقطع أصلية وضمان</p>
        <div class="svc-grid">
          <div v-for="s in catalog.repair_types" :key="s.id" class="svc">
            <div class="ic"><i :class="'ti ' + (s.icon || 'ti-tool')"></i></div>
            <h4>{{ s.name }}</h4>
          </div>
        </div>
      </div>
    </section>

    <!-- Why us -->
    <section class="sec why">
      <div class="wrap">
        <h2 class="center">ليش موبايل فيكس؟</h2>
        <div class="why-grid">
          <div class="why-item"><div class="ic"><i class="ti ti-clock-bolt"></i></div><div><h4>تصليح سريع</h4><p>أغلب التصليحات تخلص بنفس اليوم.</p></div></div>
          <div class="why-item"><div class="ic"><i class="ti ti-shield-check"></i></div><div><h4>ضمان مكتوب</h4><p>ضمان حتى ٦ أشهر على القطع والصيانة.</p></div></div>
          <div class="why-item"><div class="ic"><i class="ti ti-eye"></i></div><div><h4>شفافية كاملة</h4><p>تعرف السعر قبل التصليح وتتابع الحالة.</p></div></div>
          <div class="why-item"><div class="ic"><i class="ti ti-certificate"></i></div><div><h4>فنيون معتمدون</h4><p>فريق متخصص بكل أنواع الأجهزة.</p></div></div>
          <div class="why-item"><div class="ic"><i class="ti ti-cpu"></i></div><div><h4>قطع أصلية</h4><p>نستخدم قطع أصلية بمصدر موثوق.</p></div></div>
          <div class="why-item"><div class="ic"><i class="ti ti-message-2"></i></div><div><h4>تحديثات فورية</h4><p>رسائل واتساب بكل خطوة.</p></div></div>
        </div>
      </div>
    </section>

    <!-- Track CTA -->
    <section class="sec" style="padding-top:0">
      <div class="wrap">
        <div class="track-cta">
          <h2>{{ settings.cta_title || 'تابع حالة تصليحك' }}</h2>
          <p>{{ settings.cta_text || 'أدخل رقم التذكرة أو رقم جوالك وشوف وين وصل جهازك' }}</p>
          <div class="track-in">
            <input v-model="trackQ" placeholder="RX-10248" @keyup.enter="goTrack" />
            <button class="btn btn-lg" style="background:#fff;color:var(--accent-d)" @click="goTrack">تتبّع</button>
          </div>
        </div>
      </div>
    </section>

    <SiteFooter />
  </div>
</template>

<script setup>
const { api } = useApi()
const { settings, loadSettings, waLink } = useSettings()

const catalog = ref({ makes: [], repair_types: [] })
const sel = ref({ make: null, model: null, type: null })
const result = ref(null)
const trackQ = ref('')

const fmt = (n) => Number(n).toLocaleString('ar-EG')

function onMake() {
  sel.value.model = sel.value.make?.models?.[0] || null
  calc()
}

async function calc() {
  if (!sel.value.make || !sel.value.type) { result.value = null; return }
  try {
    result.value = await api('/site/quote', {
      method: 'POST',
      body: {
        make_id: sel.value.make.id,
        car_model_id: sel.value.model?.id || null,
        repair_type_id: sel.value.type.id,
      },
    })
  } catch { result.value = { found: false } }
}

function goTrack() {
  navigateTo('/track?q=' + encodeURIComponent(trackQ.value || ''))
}

onMounted(async () => {
  await loadSettings()
  catalog.value = await api('/site/catalog')
  sel.value.make = catalog.value.makes.find(m => m.name === 'Apple') || catalog.value.makes[0]
  sel.value.model = sel.value.make?.models?.find(x => x.name === 'iPhone 14') || sel.value.make?.models?.[0]
  sel.value.type = catalog.value.repair_types[0]
  calc()
})
</script>

<style scoped>
.hero { background: linear-gradient(180deg, #E6F1FB, var(--bg)); padding: 60px 22px 44px; text-align: center; }
.hero-in { max-width: 640px; margin: 0 auto; }
.tag { display: inline-block; background: #fff; border: 1px solid var(--line); color: var(--accent-d); font-size: 13px; padding: 6px 14px; border-radius: 20px; margin-bottom: 18px; font-weight: 500; }
.hero h1 { font-size: 40px; font-weight: 800; line-height: 1.3; margin-bottom: 14px; }
.hero p { font-size: 17px; color: #4A4F5A; margin-bottom: 24px; }
.cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.quote-wrap { max-width: 780px; margin: -28px auto 0; padding: 0 22px; position: relative; z-index: 5; }
.quote { border-radius: 18px; box-shadow: 0 12px 40px rgba(20,50,90,.10); }
.result { display: flex; align-items: center; gap: 20px; background: #E1F5EE; border-radius: 14px; padding: 18px 22px; margin-top: 16px; flex-wrap: wrap; }
.result.empty { background: var(--line-2); }
.rl { font-size: 12px; color: var(--green); }
.result.empty .rl { color: var(--muted); }
.price { font-size: 30px; font-weight: 800; color: #04342C; }
.price span { font-size: 15px; }
.rv { font-size: 16px; font-weight: 700; color: #04342C; }
.spacer { flex: 1; }
.wrap { max-width: 1080px; margin: 0 auto; padding: 0 22px; }
.sec { padding: 54px 0; }
.sec h2 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
.svc-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 26px; }
.svc { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px 16px; text-align: center; transition: .2s; }
.svc:hover { transform: translateY(-4px); border-color: var(--accent); box-shadow: 0 10px 30px rgba(20,50,90,.08); }
.svc .ic { width: 54px; height: 54px; border-radius: 14px; background: #E6F1FB; color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 12px; }
.svc h4 { font-size: 15px; font-weight: 700; }
.why { background: #fff; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
.why-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; margin-top: 26px; }
.why-item { display: flex; gap: 14px; }
.why-item .ic { width: 46px; height: 46px; border-radius: 12px; background: #E1F5EE; color: var(--green); display: flex; align-items: center; justify-content: center; font-size: 23px; flex-shrink: 0; }
.why-item h4 { font-size: 15px; font-weight: 700; margin-bottom: 3px; }
.why-item p { font-size: 13px; color: var(--muted); }
.track-cta { background: var(--accent-d); border-radius: 20px; padding: 40px; text-align: center; color: #fff; }
.track-cta h2 { color: #fff; }
.track-cta p { color: #B5D4F4; margin-bottom: 20px; }
.track-in { display: flex; gap: 10px; max-width: 440px; margin: 0 auto; }
.track-in input { flex: 1; }
@media (max-width: 820px) { .hero h1 { font-size: 30px; } .svc-grid { grid-template-columns: 1fr 1fr; } .why-grid { grid-template-columns: 1fr; } }
</style>
