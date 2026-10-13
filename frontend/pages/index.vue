<template>
  <div class="lp">
    <header class="nav">
      <div class="brand">
        <div class="logo"><i class="ti ti-device-mobile"></i></div>
        <div><div class="bt">فيكس بلس</div><div class="bs">FIX PLUS</div></div>
      </div>
      <div class="right">
        <LangSwitch light />
        <NuxtLink to="/login" class="ghost">{{ t('دخول') }}</NuxtLink>
        <NuxtLink to="/register" class="btn btn-accent"><i class="ti ti-rocket"></i> {{ t('سجّل محلك') }}</NuxtLink>
      </div>
    </header>

    <section class="hero">
      <div class="hero-in">
        <span class="tag"><i class="ti ti-sparkles"></i> {{ t('نظام متكامل لإدارة محلات صيانة الجوالات') }}</span>
        <h1>{{ t('أدِر محل صيانة جوالاتك باحتراف — من مكان واحد') }}</h1>
        <p>{{ t('تذاكر تصليح، حاسبة أسعار فورية، حجوزات أونلاين، تتبّع للزبائن، مخزون، وفريق — كل شي بنظام واحد سهل.') }}</p>
        <div class="cta">
          <NuxtLink to="/register" class="btn btn-accent btn-lg"><i class="ti ti-rocket"></i> {{ t('ابدأ الآن مجانًا') }}</NuxtLink>
          <a href="#plans" class="btn btn-ghost btn-lg">{{ t('شوف الباقات') }}</a>
        </div>
        <div class="trust"><i class="ti ti-circle-check"></i> {{ t('تفعيل خلال دقائق') }} · <i class="ti ti-circle-check"></i> {{ t('موقع حجز خاص بمحلك') }} · <i class="ti ti-circle-check"></i> {{ t('دعم بالعربي') }}</div>
      </div>
    </section>

    <section class="sec">
      <div class="wrap">
        <h2 class="center">{{ t('كل اللي يحتاجه محلك') }}</h2>
        <p class="sub center">{{ t('مصمَّم خصيصًا لمحلات صيانة الجوالات') }}</p>
        <div class="feats">
          <div class="feat"><div class="ic"><i class="ti ti-ticket"></i></div><h4>{{ t('نظام تذاكر') }}</h4><p>{{ t('استقبل الأجهزة، عيّن الفني، وتابع الحالة من الاستلام للتسليم.') }}</p></div>
          <div class="feat"><div class="ic"><i class="ti ti-calculator"></i></div><h4>{{ t('حاسبة أسعار فورية') }}</h4><p>{{ t('الزبون يعرف سعر التصليح والمدة قبل لا يجي المحل.') }}</p></div>
          <div class="feat"><div class="ic"><i class="ti ti-calendar-check"></i></div><h4>{{ t('حجوزات أونلاين') }}</h4><p>{{ t('موقع حجز خاص بمحلك يستقبل الطلبات ٢٤ ساعة.') }}</p></div>
          <div class="feat"><div class="ic"><i class="ti ti-map-pin-search"></i></div><h4>{{ t('تتبّع للزبون') }}</h4><p>{{ t('الزبون يتابع حالة جهازه برقم التذكرة لحظة بلحظة.') }}</p></div>
          <div class="feat"><div class="ic"><i class="ti ti-packages"></i></div><h4>{{ t('إدارة المخزون') }}</h4><p>{{ t('تابع قطع الغيار وتنبيهات النفاد تلقائيًا.') }}</p></div>
          <div class="feat"><div class="ic"><i class="ti ti-users"></i></div><h4>{{ t('فريق وفنيون') }}</h4><p>{{ t('وزّع التذاكر على الفنيين وتابع أداءهم.') }}</p></div>
        </div>
      </div>
    </section>

    <section class="sec plans" id="plans">
      <div class="wrap">
        <h2 class="center">{{ t('باقات بسيطة وواضحة') }}</h2>
        <p class="sub center">{{ t('اختر الباقة المناسبة لمحلك — فعّلها وابدأ فورًا') }}</p>
        <div class="plan-grid">
          <div v-for="(p, i) in plans" :key="p.id" class="plan" :class="{ pop: i === 1 }">
            <div v-if="i === 1" class="badge">{{ t('الأكثر طلبًا') }}</div>
            <h3>{{ p.name }}</h3>
            <div class="price">{{ Number(p.price).toLocaleString(isEn ? 'en-US' : 'ar-EG') }} <span>{{ t('ر.س') }} / {{ p.duration_days }} {{ t('يوم') }}</span></div>
            <p class="pdesc">{{ p.description || '' }}</p>
            <ul>
              <li><i class="ti ti-check"></i> {{ t('تذاكر وحجوزات غير محدودة') }}</li>
              <li><i class="ti ti-check"></i> {{ p.max_technicians ? p.max_technicians + ' ' + t('فنيين') : t('فنيون غير محدودين') }}</li>
              <li><i class="ti ti-check"></i> {{ t('موقع حجز وتتبّع خاص بمحلك') }}</li>
            </ul>
            <NuxtLink to="/register" class="btn btn-block" :class="i === 1 ? 'btn-accent' : 'btn-ghost'">{{ t('اختر الباقة') }}</NuxtLink>
          </div>
          <div v-if="!plans.length" class="center" style="grid-column:1/-1;color:var(--muted)">{{ t('سيتم عرض الباقات قريبًا') }}</div>
        </div>
      </div>
    </section>

    <section class="cta-band">
      <div class="wrap center">
        <h2 style="color:#fff">{{ t('جاهز تطوّر محلك؟') }}</h2>
        <p>{{ t('سجّل محلك اليوم وابدأ باستقبال الحجوزات خلال دقائق.') }}</p>
        <NuxtLink to="/register" class="btn btn-lg" style="background:#fff;color:var(--accent-d)"><i class="ti ti-rocket"></i> {{ t('سجّل محلك الآن') }}</NuxtLink>
      </div>
    </section>

    <footer class="foot">
      <div class="wrap fin">
        <div class="brand"><div class="logo sm"><i class="ti ti-device-mobile"></i></div><b style="color:#fff">فيكس بلس</b></div>
        <span>© 2026 فيكس بلس — {{ t('نظام إدارة محلات صيانة الجوالات') }}</span>
      </div>
    </footer>
  </div>
</template>

<script setup>
const { api } = useApi()
const { t, isEn } = useI18n()
const plans = ref([])
onMounted(async () => { try { plans.value = await api('/plans') } catch {} })
</script>

<style scoped>
.lp { background: var(--bg, #F4F6FA); }
.nav { display: flex; align-items: center; justify-content: space-between; padding: 16px 32px; background: var(--ink); }
.brand { display: flex; align-items: center; gap: 11px; }
.logo { width: 40px; height: 40px; border-radius: 10px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.logo.sm { width: 32px; height: 32px; font-size: 18px; }
.bt { font-size: 18px; font-weight: 800; color: #fff; }
.bs { font-size: 10px; color: #8b98ad; letter-spacing: 3px; }
.right { display: flex; align-items: center; gap: 14px; }
.ghost { color: #cdd6e6; font-size: 14px; }
.ghost:hover { color: #fff; }
.hero { background: linear-gradient(180deg, var(--ink), var(--ink-2, #1E2E4E)); color: #fff; text-align: center; padding: 72px 22px 80px; }
.hero-in { max-width: 720px; margin: 0 auto; }
.tag { display: inline-block; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14); color: #cfe0f5; font-size: 13px; padding: 7px 16px; border-radius: 20px; margin-bottom: 20px; }
.hero h1 { font-size: 42px; font-weight: 800; line-height: 1.3; margin-bottom: 16px; color: #fff; }
.hero p { font-size: 18px; color: #c3ccdb; margin-bottom: 26px; line-height: 1.8; }
.cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-bottom: 20px; }
.btn-ghost { background: rgba(255,255,255,.1); color: #fff; }
.trust { font-size: 13px; color: #8fa1bb; }
.trust i { color: var(--green); }
.wrap { max-width: 1080px; margin: 0 auto; padding: 0 22px; }
.sec { padding: 60px 0; }
.sec h2 { font-size: 30px; font-weight: 800; margin-bottom: 8px; }
.sub { color: var(--muted); margin-bottom: 36px; }
.feats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.feat { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 26px 22px; }
.feat .ic { width: 52px; height: 52px; border-radius: 13px; background: #E6F1FB; color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 25px; margin-bottom: 14px; }
.feat h4 { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
.feat p { font-size: 14px; color: var(--muted); line-height: 1.7; }
.plans { background: #fff; border-top: 1px solid var(--line); }
.plan-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; align-items: stretch; }
.plan { border: 1px solid var(--line); border-radius: 18px; padding: 28px 24px; position: relative; background: #fff; display: flex; flex-direction: column; }
.plan.pop { border-color: var(--accent); box-shadow: 0 14px 40px rgba(24,95,165,.14); }
.badge { position: absolute; top: -12px; right: 24px; background: var(--accent); color: #fff; font-size: 12px; padding: 4px 12px; border-radius: 20px; }
.plan h3 { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
.price { font-size: 32px; font-weight: 800; color: var(--accent-d); margin-bottom: 4px; }
.price span { font-size: 14px; font-weight: 500; color: var(--muted); }
.pdesc { font-size: 13px; color: var(--muted); margin-bottom: 16px; min-height: 18px; }
.plan ul { list-style: none; padding: 0; margin: 0 0 20px; flex: 1; }
.plan li { display: flex; align-items: center; gap: 8px; font-size: 14px; padding: 7px 0; }
.plan li i { color: var(--green); }
.cta-band { background: var(--accent-d); padding: 56px 22px; text-align: center; }
.cta-band p { color: #B5D4F4; margin: 8px 0 22px; }
.foot { background: #0E1526; padding: 26px 0; }
.fin { display: flex; align-items: center; justify-content: space-between; color: #8A909C; font-size: 13px; }
@media (max-width: 860px) { .hero h1 { font-size: 30px; } .feats, .plan-grid { grid-template-columns: 1fr; } .fin { flex-direction: column; gap: 12px; } }
</style>
