<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Snackbar, Dialog } from '@varlet/ui'

interface Prize { id: number; nama: string; urutan: number; is_drawn: boolean }
interface Winner { id: number; prize: { nama: string }; nama_pemenang: string; nomor_kupon: string; drawn_at: string }

const props = defineProps<{
  prizes: Prize[]; winners: Winner[]; total_participants: number; remaining_count: number; current_draw_prize_id: string
}>()

const page = usePage()
const pp = page.props as any
const baseUrl = pp.app_url || ''
const csrf = pp.csrf_token || ''

const loading = ref(false)

const startDraw = async (prizeId: number) => {
  loading.value = true
  const res = await fetch(`${baseUrl}/admin/lucky-draw/draw/start`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
    body: JSON.stringify({ prize_id: prizeId }),
  })
  const data = await res.json()
  loading.value = false
  if (res.ok) {
    Snackbar.info(`"${data.prize_name}" dikirim ke layar!`)
    window.location.reload()
  } else {
    Snackbar.warning(data.message || 'Gagal')
  }
}

const confirmReset = () => {
  Dialog({
    title: 'Reset Undian?',
    message: 'Semua pemenang akan dihapus. Hadiah akan diundi ulang.',
    onConfirm: async () => {
      const res = await fetch(`${baseUrl}/admin/lucky-draw/reset`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
      if (res.ok) window.location.reload()
    },
  })
}
</script>

<template>
  <Head title="Lucky Draw - Admin" />
  <div class="layout">
    <header class="top-bar">
      <a :href="baseUrl + '/admin/lucky-draw/prizes'" class="back"><var-icon name="chevron-left" :size="24" color="#0f172a" /></a>
      <h1>🎲 Lucky Draw</h1>
      <var-button text round @click="confirmReset"><var-icon name="refresh" :size="20" color="#ef4444" /></var-button>
    </header>
    <main class="content">
      <div class="stats">
        <div class="stat"><span>Hadir</span><b>{{ total_participants }}</b></div>
        <div class="stat"><span>Sisa</span><b class="warn">{{ remaining_count }}</b></div>
        <div class="stat"><span>Hadiah</span><b>{{ prizes.length }}</b></div>
      </div>

      <div v-if="current_draw_prize_id" class="alert">
        <var-icon name="television" :size="20" color="#f59e0b" />
        <span>Undian sedang berlangsung di layar! Buka <a :href="baseUrl + '/undian/show'" target="_blank">/undian/show</a></span>
      </div>

      <div class="card">
        <h3>Daftar Hadiah</h3>
        <div v-for="p in prizes" :key="p.id" class="row" :class="{ done: p.is_drawn }">
          <span class="name">{{ p.nama }}</span>
          <var-chip v-if="p.is_drawn" size="mini" type="success">Selesai</var-chip>
          <var-chip v-else-if="current_draw_prize_id == String(p.id)" size="mini" type="warning">Di Layar</var-chip>
          <var-button v-if="!p.is_drawn && current_draw_prize_id != String(p.id)" size="small" type="primary" :loading="loading" @click="startDraw(p.id)">Mulai</var-button>
        </div>
      </div>

      <div class="card">
        <h3>Riwayat Pemenang</h3>
        <div v-if="!winners.length" class="empty">Belum ada pemenang</div>
        <div v-for="w in winners" :key="w.id" class="row">
          <span class="prize-name">{{ w.prize.nama }}</span>
          <span class="flex-1">{{ w.nama_pemenang }}</span>
          <span class="mono">{{ w.nomor_kupon }}</span>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.layout { display: flex; flex-direction: column; min-height: 100vh; background: #f8fafc; font-family: Roboto, sans-serif; }
.top-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; background: #fff; border-bottom: 1px solid #f1f5f9; }
.top-bar h1 { margin: 0; font-size: 16px; }
.back { display: flex; text-decoration: none; }
.content { flex: 1; padding: 20px; display: flex; flex-direction: column; gap: 16px; max-width: 600px; margin: 0 auto; width: 100%; }
.stats { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
.stat { background: #fff; border-radius: 14px; padding: 14px; text-align: center; border: 1px solid #f1f5f9; }
.stat span { display: block; font-size: 11px; color: #94a3b8; }
.stat b { font-size: 22px; }
.warn { color: #f59e0b; }
.alert { background: #fef3c7; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #92400e; }
.alert a { color: #4f46e5; font-weight: 600; }
.card { background: #fff; border-radius: 20px; padding: 16px; border: 1px solid #f1f5f9; }
.card h3 { margin: 0 0 12px; font-size: 14px; }
.row { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f8fafc; }
.row:last-child { border-bottom: none; }
.row.done { opacity: 0.5; }
.name { flex: 1; font-size: 14px; font-weight: 500; }
.flex-1 { flex: 1; font-size: 13px; }
.mono { font-family: monospace; font-size: 12px; color: #64748b; }
.prize-name { min-width: 100px; font-weight: 600; color: #4f46e5; font-size: 13px; }
.empty { text-align: center; padding: 16px; color: #94a3b8; font-size: 13px; }
</style>
