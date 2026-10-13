<template>
  <DashShell active="bookings">
    <div class="page-title">{{ t('الحجوزات') }}</div>
    <p class="page-sub">{{ t('طلبات الحجز والتسعير الواردة من الموقع') }}</p>

    <table>
      <thead><tr><th>{{ t('الزبون') }}</th><th>{{ t('الجهاز') }}</th><th>{{ t('العطل') }}</th><th>{{ t('الخدمة') }}</th><th>{{ t('الوقت') }}</th><th>{{ t('الحالة') }}</th><th></th></tr></thead>
      <tbody>
        <tr v-for="b in bookings" :key="b.id">
          <td><b>{{ b.name }}</b><div class="muted" dir="ltr" style="text-align:right">{{ b.phone }}</div></td>
          <td>{{ [b.make, b.model].filter(Boolean).join(' ') || '—' }}</td>
          <td>{{ b.repair_type || '—' }}</td>
          <td>{{ b.service_type === 'pickup' ? t('استلام') : t('زيارة') }}</td>
          <td class="muted">{{ b.preferred_time || '—' }}</td>
          <td>
            <select :value="b.status" @change="setStatus(b, $event.target.value)" style="width:auto;padding:6px 10px;font-size:12px">
              <option value="new">{{ t('جديد') }}</option><option value="contacted">{{ t('تم التواصل') }}</option>
              <option value="converted">{{ t('حُوّل لتذكرة') }}</option><option value="closed">{{ t('مغلق') }}</option>
            </select>
          </td>
          <td><a v-if="waLink(b.phone)" :href="waLink(b.phone)" target="_blank" class="act"><i class="ti ti-brand-whatsapp" style="color:var(--green)"></i></a></td>
        </tr>
        <tr v-if="!bookings.length"><td colspan="7" class="empty">{{ t('لا توجد حجوزات') }}</td></tr>
      </tbody>
    </table>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t } = useI18n()
const bookings = ref([])
async function load() { bookings.value = await api('/dashboard/bookings') }
async function setStatus(b, status) { await api('/dashboard/bookings/' + b.id + '/status', { method: 'POST', body: { status } }); b.status = status }
const waLink = (p) => { const n = (p || '').replace(/\D/g, ''); return n ? 'https://wa.me/' + n : null }
onMounted(load)
</script>

<style scoped>
.muted { color: var(--muted); font-size: 12px; }
</style>
