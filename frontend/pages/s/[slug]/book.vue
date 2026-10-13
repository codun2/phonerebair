<template>
  <div :style="themeVars">
    <SiteHeader :slug="slug" />
    <section class="phead">
      <h1 class="page-title" style="color:#fff">{{ t('احجز موعد تصليح') }}</h1>
      <p style="color:#B5D4F4">{{ t('عبّي بياناتك وبنتواصل معك لتأكيد الموعد') }}</p>
    </section>

    <div class="box">
      <div class="panel">
        <div v-if="done" class="ok-box">
          <i class="ti ti-circle-check" style="font-size:24px"></i>
          <div style="margin-top:8px">{{ t('تم استلام حجزك! بنراسلك على واتساب قريباً.') }}</div>
          <NuxtLink :to="base" class="link" style="display:inline-block;margin-top:10px">{{ t('← رجوع للرئيسية') }}</NuxtLink>
        </div>

        <template v-else>
          <div class="g2 frow">
            <div><label>{{ t('الاسم الكامل') }}</label><input v-model="f.name" :placeholder="t('اسمك')" /></div>
            <div><label>{{ t('رقم الجوال') }}</label><input v-model="f.phone" placeholder="05xxxxxxxx" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          </div>
          <div class="g2 frow">
            <div><label>{{ t('الماركة') }}</label>
              <select v-model="f.make">
                <option v-for="m in catalog.makes" :key="m.id" :value="m.name">{{ m.name }}</option>
              </select>
            </div>
            <div><label>{{ t('نوع العطل') }}</label>
              <select v-model="f.repair_type">
                <option v-for="rt in catalog.repair_types" :key="rt.id" :value="rt.name">{{ rt.name }}</option>
              </select>
            </div>
          </div>
          <div class="g2 frow">
            <div><label>{{ t('طريقة الخدمة') }}</label>
              <select v-model="f.service_type">
                <option value="visit">{{ t('أزور المحل') }}</option>
                <option value="pickup">{{ t('استلام الجهاز من عندي') }}</option>
              </select>
            </div>
            <div><label>{{ t('الوقت المفضّل') }}</label>
              <select v-model="f.preferred_time">
                <option value="اليوم مساءً">{{ t('اليوم مساءً') }}</option><option value="غداً صباحاً">{{ t('غداً صباحاً') }}</option><option value="غداً مساءً">{{ t('غداً مساءً') }}</option>
              </select>
            </div>
          </div>
          <div class="frow"><label>{{ t('ملاحظات') }}</label><textarea v-model="f.note" rows="3" :placeholder="t('اكتب أي تفاصيل عن العطل...')"></textarea></div>
          <div v-if="err" class="err">{{ err }}</div>
          <button class="btn btn-accent btn-lg btn-block" :disabled="loading" @click="submit"><i class="ti ti-calendar-check"></i> {{ t('تأكيد الحجز') }}</button>
        </template>
      </div>
    </div>
    <SiteFooter :slug="slug" />
  </div>
</template>

<script setup>
const route = useRoute()
const slug = route.params.slug
const { shopApi } = useApi()
const { base, themeVars, loadSettings } = useShop(slug)
const { t, isEn } = useI18n()
onMounted(loadSettings)
const catalog = ref({ makes: [], repair_types: [] })
const f = ref({ name: '', phone: '', make: '', repair_type: '', service_type: 'visit', preferred_time: 'اليوم مساءً', note: '' })
const done = ref(false)
const err = ref('')
const loading = ref(false)

async function submit() {
  err.value = ''
  if (!f.value.name || !f.value.phone) { err.value = t('الاسم ورقم الجوال مطلوبان'); return }
  loading.value = true
  try {
    await shopApi(slug, '/booking', { method: 'POST', body: f.value })
    done.value = true
  } catch (e) {
    err.value = t('تعذّر إرسال الحجز — حاول مجدداً')
  } finally { loading.value = false }
}

onMounted(async () => {
  try { catalog.value = await shopApi(slug, '/catalog') } catch {}
  f.value.make = catalog.value.makes[0]?.name || ''
  f.value.repair_type = catalog.value.repair_types[0]?.name || ''
})
</script>

<style scoped>
.phead { background: var(--accent-d); padding: 40px 22px; text-align: center; }
.box { max-width: 700px; margin: 30px auto; padding: 0 22px; }
</style>
