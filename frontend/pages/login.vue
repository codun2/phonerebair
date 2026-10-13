<template>
  <div>
    <div class="topbar">
      <NuxtLink to="/" class="brand"><div class="logo"><i class="ti ti-device-mobile"></i></div><b>فيكس بلس</b></NuxtLink>
      <LangSwitch light />
    </div>
    <div class="auth">
      <div class="panel">
        <h1 class="center" style="font-size:22px; font-weight:800; margin-bottom:4px;">{{ t('تسجيل الدخول للوحة التحكم') }}</h1>
        <p class="center page-sub">{{ t('أدخل بيانات حسابك') }}</p>
        <div v-if="error" class="err">{{ error }}</div>
        <form @submit.prevent="submit">
          <div class="frow"><label>{{ t('البريد الإلكتروني') }}</label><input v-model="email" type="email" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" required /></div>
          <div class="frow"><label>{{ t('كلمة المرور') }}</label><input v-model="password" type="password" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" required /></div>
          <button class="btn btn-accent btn-block btn-lg" :disabled="busy"><i class="ti ti-login"></i> {{ busy ? '...' : t('دخول') }}</button>
        </form>
        <p class="center" style="margin-top:14px;font-size:12px;color:var(--muted)">{{ t('تجريبي:') }} admin@mobilefix.local / mobilefix1234</p>
      </div>
    </div>
  </div>
</template>

<script setup>
const { login } = useAuth()
const { t, isEn } = useI18n()
const email = ref('admin@mobilefix.local')
const password = ref('mobilefix1234')
const busy = ref(false)
const error = ref('')

async function submit() {
  busy.value = true
  error.value = ''
  try {
    const r = await login(email.value, password.value)
    await navigateTo(r?.user?.role === 'super_admin' ? '/super' : '/admin')
  } catch (e) {
    error.value = e?.data?.message || 'بيانات الدخول غير صحيحة'
  } finally { busy.value = false }
}
</script>

<style scoped>
.topbar { background: var(--ink); padding: 15px 28px; display: flex; align-items: center; justify-content: space-between; }
.brand { display: flex; align-items: center; gap: 10px; color: #fff; }
.logo { width: 38px; height: 38px; border-radius: 9px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.brand b { font-size: 18px; font-weight: 800; }
.auth { max-width: 420px; margin: 56px auto; padding: 0 16px; }
</style>
