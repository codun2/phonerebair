<template>
  <Teleport to="body">
    <Transition name="cd">
      <div v-if="state.open" class="cd-ov" @click.self="answer(false)">
        <div class="cd-box">
          <div class="cd-ic"><i class="ti ti-alert-triangle"></i></div>
          <h3>{{ state.title }}</h3>
          <p v-if="state.text">{{ state.text }}</p>
          <div class="cd-acts">
            <button class="btn btn-out" @click="answer(false)">إلغاء</button>
            <button class="btn btn-accent" @click="answer(true)">تأكيد الحذف</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const { state, answer } = useConfirm()
</script>

<style scoped>
.cd-ov { position: fixed; inset: 0; background: rgba(14, 15, 19, .55); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }
.cd-box { background: #fff; border-radius: 18px; padding: 30px 28px 24px; width: min(400px, 100%); text-align: center; box-shadow: 0 24px 60px rgba(0,0,0,.25); }
.cd-ic { width: 62px; height: 62px; border-radius: 50%; background: #FBE9E8; color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 16px; }
h3 { font-size: 17px; font-weight: 800; margin-bottom: 6px; }
p { font-size: 13.5px; color: var(--muted); margin-bottom: 4px; }
.cd-acts { display: flex; gap: 10px; margin-top: 20px; }
.cd-acts .btn { flex: 1; padding: 11px; }
.cd-enter-active, .cd-leave-active { transition: opacity .18s; }
.cd-enter-active .cd-box, .cd-leave-active .cd-box { transition: transform .18s; }
.cd-enter-from, .cd-leave-to { opacity: 0; }
.cd-enter-from .cd-box, .cd-leave-to .cd-box { transform: scale(.94); }
</style>
