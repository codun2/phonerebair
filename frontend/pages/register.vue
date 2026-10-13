<template>
  <div class="reg">
    <div class="topbar">
      <NuxtLink to="/" class="brand"><div class="logo"><i class="ti ti-device-mobile"></i></div><b>فيكس بلس</b></NuxtLink>
      <LangSwitch light />
    </div>

    <div class="wrap">
      <div class="panel">
        <div v-if="done" class="ok-box" style="text-align:center">
          <i class="ti ti-circle-check" style="font-size:40px;color:var(--green)"></i>
          <h2 style="margin:12px 0 6px">{{ t('تم استلام طلبك!') }}</h2>
          <p style="color:var(--muted);line-height:1.8">{{ doneMsg }}</p>
          <NuxtLink to="/" class="btn btn-accent" style="margin-top:14px">{{ t('← رجوع للرئيسية') }}</NuxtLink>
        </div>

        <template v-else>
          <h1 style="font-size:24px;font-weight:800;margin-bottom:4px">{{ t('سجّل محلك') }}</h1>
          <p class="page-sub" style="margin-bottom:18px">{{ t('أنشئ حساب محلك، وسيتم تفعيله بعد مراجعة سريعة من الإدارة.') }}</p>
          <div v-if="err" class="err">{{ err }}</div>
          <form @submit.prevent="submit">
            <div class="frow"><label>{{ t('اسم المحل') }}</label><input v-model="f.shop_name" :placeholder="t('مثال: محل النور للجوالات')" required /></div>
            <div class="g2 frow">
              <div><label>{{ t('اسم المالك') }}</label><input v-model="f.owner_name" :placeholder="t('اسمك')" required /></div>
              <div><label>{{ t('رقم الجوال') }}</label><input v-model="f.phone" placeholder="05xxxxxxxx" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" required /></div>
            </div>
            <div class="frow"><label>{{ t('البريد الإلكتروني') }}</label><input v-model="f.email" type="email" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" placeholder="you@example.com" required /></div>
            <div class="frow"><label>{{ t('كلمة المرور') }}</label><input v-model="f.password" type="password" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" :placeholder="t('٦ أحرف على الأقل')" required /></div>
            <button class="btn btn-accent btn-block btn-lg" :disabled="busy"><i class="ti ti-rocket"></i> {{ busy ? '...' : t('إنشاء الحساب') }}</button>
          </form>
          <p class="center" style="margin-top:14px;font-size:13px;color:var(--muted)">{{ t('عندك حساب؟') }} <NuxtLink to="/login" class="link">{{ t('تسجيل الدخول') }}</NuxtLink></p>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
const { api } = useApi()
const { t, isEn } = useI18n()
const f = ref({ shop_name: '', owner_name: '', phone: '', email: '', password: '' })
const busy = ref(false)
const err = ref('')
const done = ref(false)
const doneMsg = ref('')

async function submit() {
  busy.value = true; err.value = ''
  try {
    const r = await api('/register-shop', { method: 'POST', body: f.value })
    doneMsg.value = r?.message || 'سيتم تفعيل محلك بعد مراجعة الإدارة.'
    done.value = true
  } catch (e) {
    err.value = e?.data?.message || Object.values(e?.data?.errors || {})?.[0]?.[0] || 'تعذّر إنشاء الحساب — تحقق من البيانات'
  } finally { busy.value = false }
}
</script>

<style scoped>
.reg { min-height: 100vh; background: var(--bg, #F4F6FA); }
.topbar { background: var(--ink); padding: 15px 28px; display: flex; align-items: center; justify-content: space-between; }
.brand { display: flex; align-items: center; gap: 10px; color: #fff; }
.logo { width: 38px; height: 38px; border-radius: 9px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.brand b { font-size: 18px; font-weight: 800; }
.wrap { max-width: 520px; margin: 44px auto; padding: 0 16px; }
</style>
