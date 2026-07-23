<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const pp = page.props as any
const baseUrl = pp.app_url || ''

const phase = ref<'idle' | 'ready' | 'animating' | 'revealed'>('idle')
const pendingPrize = ref<{ id: number; nama: string; deskripsi: string | null } | null>(null)
const currentWinner = ref<any>(null)
const animatingName = ref('')
const winners = ref<any[]>([])
const loading = ref(false)
const darkMode = ref(false)
let pollTimer: ReturnType<typeof setInterval> | null = null
let animTimer: ReturnType<typeof setInterval> | null = null

const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'

const fetchState = async () => {
  if (phase.value === 'animating') return
  try {
    const res = await fetch(`${baseUrl}/undian/data`)
    const data = await res.json()
    winners.value = data.winners || []

    if (data.pending_draw) {
      pendingPrize.value = data.pending_draw
      currentWinner.value = null
      if (phase.value === 'idle') phase.value = 'ready'
    } else {
      pendingPrize.value = null
      if (data.current_winner && phase.value !== 'animating') {
        currentWinner.value = data.current_winner
        phase.value = 'revealed'
        animatingName.value = currentWinner.value?.nama_pemenang || currentWinner.value?.winner?.nama || '???'
      } else if (!data.current_winner && phase.value !== 'animating') {
        currentWinner.value = null
        phase.value = 'idle'
      }
    }
  } catch {}
}

const startDraw = async () => {
  if (!pendingPrize.value) return
  loading.value = true
  try {
    const res = await fetch(`${baseUrl}/undian/draw`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pp.csrf_token || '' },
      body: JSON.stringify({ prize_id: pendingPrize.value.id }),
    })
    const data = await res.json()
    if (res.ok) {
      currentWinner.value = data
      pendingPrize.value = null
      phase.value = 'animating'
      startAnimation()
    }
  } catch {}
  finally { loading.value = false }
}

const startAnimation = () => {
  animTimer = setInterval(() => {
    let name = ''
    for (let i = 0; i < 14; i++) name += chars[Math.floor(Math.random() * chars.length)]
    animatingName.value = name
  }, 60)
}

const stopAnimation = () => {
  if (animTimer) clearInterval(animTimer)
  animTimer = null
  animatingName.value = currentWinner.value?.winner?.nama || '???'
  phase.value = 'revealed'
}

const toggleDark = () => { darkMode.value = !darkMode.value }

onMounted(() => {
  fetchState()
  pollTimer = setInterval(fetchState, 3000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (animTimer) clearInterval(animTimer)
})
</script>

<template>
  <div class="screen" :class="{ dark: darkMode }">
    <div class="bg-decor"></div>

    <button class="dark-btn" @click="toggleDark">
      <span>{{ darkMode ? '☀️' : '🌙' }}</span>
    </button>

    <div class="header">
      <h1>🎉 LUCKY DRAW 🎉</h1>
      <p>HUT Mark Dynamics Indonesia Tbk</p>
    </div>

    <!-- IDLE -->
    <div v-if="phase === 'idle'" class="center">
      <div class="icon">🎰</div>
      <h2>Menunggu Undian...</h2>
      <p>Admin akan memulai undian dari panel kontrol.</p>
    </div>

    <!-- READY: Ada hadiah, MC klik "Mulai Undi" -->
    <div v-else-if="phase === 'ready' && pendingPrize" class="center">
      <div class="prize-label">HADIAH</div>
      <div class="prize-name-big">{{ pendingPrize.nama }}</div>
      <div v-if="pendingPrize.deskripsi" class="sponsor">Sponsor: {{ pendingPrize.deskripsi }}</div>
      <button class="draw-btn" @click="startDraw" :disabled="loading">
        <span v-if="!loading">🎲 Mulai Undi</span>
        <span v-else>⏳ Memproses...</span>
      </button>
      <p class="hint">Klik tombol untuk mulai mengundi</p>
    </div>

    <!-- ANIMATING: Nama random, klik "STOP" -->
    <div v-else-if="phase === 'animating'" class="center">
      <div class="prize-label">PEMENANG</div>
      <div class="prize-name-big" v-if="currentWinner">{{ currentWinner.prize.nama }}</div>
      <div v-if="currentWinner?.prize?.deskripsi" class="sponsor">Sponsor: {{ currentWinner.prize.deskripsi }}</div>
      <div class="anim-box">
        <span class="anim-name">{{ animatingName }}</span>
      </div>
      <button class="stop-btn" @click="stopAnimation">🛑 STOP!</button>
      <p class="hint">Klik STOP! untuk menghentikan animasi</p>
    </div>

    <!-- REVEALED: Pemenang terpilih -->
    <div v-else-if="phase === 'revealed' && currentWinner" class="center">
      <div class="prize-label">🏆 PEMENANG 🏆</div>
      <div class="prize-name-big">{{ currentWinner.prize?.nama || currentWinner.prize_name }}</div>
      <div v-if="currentWinner.prize?.deskripsi" class="sponsor">Sponsor: {{ currentWinner.prize.deskripsi }}</div>
      <div class="winner-box">
        <span class="winner-name">{{ currentWinner.nama_pemenang || currentWinner.winner?.nama }}</span>
      </div>
      <div class="winner-kupon">{{ currentWinner.nomor_kupon || currentWinner.winner?.nomor_kupon }}</div>
      <div v-if="currentWinner.departemen || currentWinner.winner?.departemen" class="winner-dept">{{ currentWinner.departemen || currentWinner.winner?.departemen }}</div>
    </div>

    <div class="footer">
      <div class="recent">
        <h4>📋 Pemenang</h4>
        <div class="scroll">
          <div v-for="w in winners.slice(0, 8)" :key="w.id" class="mini">
            <span class="m-prize">{{ w.prize?.nama }}</span>
            <span class="m-name">{{ w.nama_pemenang }}</span>
            <span class="m-kupon">{{ w.nomor_kupon }}</span>
          </div>
          <div v-if="!winners.length" class="mini empty">Belum ada pemenang</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
* { margin: 0; padding: 0; box-sizing: border-box; }
.screen {
  min-height: 100vh;
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 30%, #4f46e5 70%, #667eea 100%);
  color: #fff;
  font-family: 'Segoe UI', Roboto, sans-serif;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 20px;
  position: relative;
  overflow: hidden;
  transition: background 0.5s;
}
.screen.dark { background: linear-gradient(135deg, #020617 0%, #0f172a 50%, #1e293b 100%); }
.bg-decor {
  position: absolute; top: -50%; left: -50%;
  width: 200%; height: 200%;
  background: radial-gradient(circle at 30% 40%, rgba(99,102,241,0.1) 0%, transparent 60%),
              radial-gradient(circle at 70% 60%, rgba(167,139,250,0.08) 0%, transparent 50%);
  pointer-events: none;
}
.dark-btn {
  position: fixed; top: 16px; right: 16px; z-index: 100;
  background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
  border-radius: 50%; width: 40px; height: 40px;
  cursor: pointer; font-size: 18px;
  display: flex; align-items: center; justify-content: center;
}
.header { text-align: center; margin-bottom: 40px; position: relative; z-index: 1; }
.header h1 { font-size: 52px; font-weight: 900; letter-spacing: 3px; text-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.header p { font-size: 18px; opacity: 0.7; margin-top: 8px; }
.center { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; position: relative; z-index: 1; }
.icon { font-size: 72px; margin-bottom: 20px; }
.icon ~ h2 { font-size: 32px; margin-bottom: 8px; }
.icon ~ p { font-size: 16px; opacity: 0.6; }
.prize-label { font-size: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 6px; opacity: 0.6; margin-bottom: 10px; }
.prize-name-big { font-size: 42px; font-weight: 800; margin-bottom: 10px; text-shadow: 0 2px 15px rgba(0,0,0,0.2); }
.sponsor { font-size: 20px; font-weight: 600; margin-bottom: 20px; padding: 8px 24px; background: rgba(255,255,255,0.1); border-radius: 12px; }
.draw-btn {
  background: linear-gradient(135deg, #f59e0b, #ef4444);
  border: none; border-radius: 20px;
  padding: 20px 60px;
  font-size: 24px; font-weight: 800;
  color: #fff; cursor: pointer;
  box-shadow: 0 10px 30px rgba(239,68,68,0.4);
  transition: transform 0.2s;
}
.draw-btn:hover { transform: scale(1.05); }
.draw-btn:disabled { opacity: 0.6; cursor: wait; }
.hint { margin-top: 16px; font-size: 14px; opacity: 0.4; }
.anim-box {
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255,255,255,0.3);
  border-radius: 24px;
  padding: 30px 60px;
  margin-bottom: 20px;
}
.anim-name {
  font-size: 54px;
  font-weight: 900;
  font-family: monospace;
  color: #fbbf24;
  text-shadow: 0 0 30px rgba(251,191,36,0.5);
  letter-spacing: 4px;
}
.stop-btn {
  background: #ef4444;
  border: none; border-radius: 20px;
  padding: 16px 48px;
  font-size: 22px; font-weight: 800;
  color: #fff; cursor: pointer;
  box-shadow: 0 8px 25px rgba(239,68,68,0.5);
  transition: transform 0.2s;
  animation: pulse 1.5s infinite;
}
.stop-btn:hover { transform: scale(1.08); }
@keyframes pulse {
  0%, 100% { box-shadow: 0 8px 25px rgba(239,68,68,0.5); }
  50% { box-shadow: 0 8px 40px rgba(239,68,68,0.8); }
}
.winner-box {
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(10px);
  border: 3px solid rgba(255,255,255,0.4);
  border-radius: 24px;
  padding: 30px 60px;
  margin-bottom: 16px;
}
.winner-name { font-size: 56px; font-weight: 900; letter-spacing: 2px; }
.winner-kupon { font-size: 28px; font-weight: 700; font-family: monospace; background: rgba(255,255,255,0.1); padding: 8px 32px; border-radius: 12px; margin-bottom: 8px; }
.winner-dept { font-size: 18px; opacity: 0.7; }
.footer { width: 100%; max-width: 800px; margin-top: 40px; position: relative; z-index: 1; }
.recent { background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border-radius: 20px; padding: 20px; }
.recent h4 { font-size: 14px; font-weight: 600; margin-bottom: 12px; opacity: 0.8; }
.scroll { display: flex; flex-direction: column; gap: 4px; max-height: 180px; overflow-y: auto; }
.mini { display: flex; gap: 14px; padding: 4px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 13px; opacity: 0.7; }
.mini:last-child { border-bottom: none; }
.m-prize { min-width: 120px; font-weight: 600; }
.m-name { flex: 1; }
.m-kupon { font-family: monospace; }
.empty { justify-content: center; opacity: 0.5; }
</style>
