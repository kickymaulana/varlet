<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useCelebration } from '../../composables/useCelebration'

const baseUrl = (usePage().props as any).app_url || ''

interface Winner {
    id: number
    nama_pemenang: string
    nomor_kupon: string
    departemen: string
    lokasi_kerja: string
    drawn_at: string
    prize: { id: number; nama: string; deskripsi: string | null }
}

const props = defineProps<{
    winners: { data: Winner[]; next_page_url: string | null; total: number; from: number; to: number }
    search: string | null
    totalWinners: number
}>()

const searchInput = ref(props.search || '')
const loadingMore = ref(false)
const { isPlaying, isMuted, needsInteraction, audioRef, fireworksCanvas, start, toggleMute } = useCelebration()

let searchTimeout: any = null

watch(searchInput, (val) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(`${baseUrl}/undian/winners`, { search: val || undefined }, { preserveState: true, replace: true })
    }, 400)
})

// Set audio source after mount (public folder asset)
onMounted(() => {
    if (audioRef.value) {
        audioRef.value.src = `${baseUrl}/audio/we-are-the-champions.mp3`
        audioRef.value.load()
    }
})

const loadMore = () => {
    if (!props.winners.next_page_url || loadingMore.value) return
    loadingMore.value = true
    router.get(props.winners.next_page_url, {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['winners'],
        onFinish: () => { loadingMore.value = false }
    })
}

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <canvas ref="fireworksCanvas" class="fireworks-canvas"></canvas>

    <audio ref="audioRef" loop preload="auto"></audio>

    <div class="overlay" v-if="needsInteraction">
        <div class="overlay-content">
            <div class="trophy">🏆</div>
            <h1>HUT PT Mark Dynamics Indonesia Tbk</h1>
            <h2>🎉 Selamat Kepada Semua Pemenang! 🎉</h2>
            <button class="start-btn" @click="start">
                🎵 Tap untuk Mulai Perayaan
            </button>
            <p class="hint">Sentuh tombol di atas untuk memutar musik & kembang api</p>
        </div>
    </div>

    <button class="mute-btn" v-else @click="toggleMute">
        {{ isMuted ? '🔇' : '🔊' }}
    </button>

    <div class="container" :class="{ dimmed: needsInteraction }">
        <header class="header">
            <h1>🎊 DAFTAR PEMENANG 🎊</h1>
            <p class="subtitle">HUT PT Mark Dynamics Indonesia Tbk</p>
            <div class="counter">
                <span class="counter-num">{{ totalWinners }}</span>
                <span class="counter-label">Pemenang Beruntung</span>
            </div>
        </header>

        <div class="search-box">
            <input
                v-model="searchInput"
                type="text"
                placeholder="🔍 Cari nama kamu di sini..."
                class="search-input"
            />
        </div>

        <div v-if="winners.data.length === 0" class="empty">
            <p>😔 Belum ada hasil untuk "{{ search }}"</p>
            <p class="empty-sub">Coba cari dengan nama lain</p>
        </div>

        <div v-else class="winners-grid">
            <div v-for="w in winners.data" :key="w.id" class="winner-card">
                <div class="prize-tag">🏆 {{ w.prize.nama }}</div>
                <div class="winner-name">{{ w.nama_pemenang }}</div>
                <div class="winner-meta">
                    <span class="kupon">{{ w.nomor_kupon }}</span>
                </div>
                <div class="winner-detail">
                    <p>📍 {{ w.departemen }} — {{ w.lokasi_kerja }}</p>
                    <p class="drawn-at">{{ formatDate(w.drawn_at) }}</p>
                </div>
            </div>
        </div>

        <div v-if="winners.next_page_url" class="load-more-wrap">
            <button class="load-more-btn" @click="loadMore" :disabled="loadingMore">
                {{ loadingMore ? '⏳ Memuat...' : '🎁 Muat Lebih Banyak' }}
            </button>
            <p class="page-info">{{ winners.from }}–{{ winners.to }} dari {{ winners.total }}</p>
        </div>
        <p v-else-if="winners.data.length > 0" class="end-info">
            🎉 Semua pemenang telah ditampilkan
        </p>
    </div>
</template>

<style scoped>
* { box-sizing: border-box; }

.fireworks-canvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
}

.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4f46e5 100%);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.overlay-content {
    text-align: center;
    color: white;
    max-width: 480px;
}

.trophy {
    font-size: 96px;
    animation: bounce 1.5s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

.overlay-content h1 {
    font-size: 1.5rem;
    margin: 16px 0 8px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.overlay-content h2 {
    font-size: 1.1rem;
    margin: 0 0 32px;
    color: #fbbf24;
}

.start-btn {
    background: linear-gradient(135deg, #fbbf24, #ef4444);
    color: white;
    border: none;
    padding: 20px 40px;
    border-radius: 50px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
    transition: transform 0.2s;
    animation: pulse 1.5s infinite;
}

.start-btn:active { transform: scale(0.95); }

@keyframes pulse {
    0%, 100% { box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4); }
    50% { box-shadow: 0 8px 40px rgba(239, 68, 68, 0.7); }
}

.hint {
    margin-top: 24px;
    font-size: 14px;
    opacity: 0.7;
}

.mute-btn {
    position: fixed;
    top: 16px;
    right: 16px;
    z-index: 50;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    width: 48px;
    height: 48px;
    font-size: 24px;
    cursor: pointer;
}

.container {
    position: relative;
    z-index: 1;
    max-width: 720px;
    margin: 0 auto;
    padding: 32px 16px;
    transition: opacity 0.5s;
}

.container.dimmed {
    opacity: 0.3;
    pointer-events: none;
}

.header {
    text-align: center;
    margin-bottom: 24px;
    color: white;
}

.header h1 {
    font-size: 2rem;
    margin: 0;
    text-shadow: 0 4px 20px rgba(0,0,0,0.5);
    background: linear-gradient(135deg, #fbbf24, #ef4444);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.subtitle {
    margin: 8px 0;
    opacity: 0.9;
    font-size: 14px;
}

.counter {
    margin-top: 16px;
    display: inline-flex;
    flex-direction: column;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    padding: 12px 24px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.2);
}

.counter-num {
    font-size: 32px;
    font-weight: 900;
    color: #fbbf24;
}

.counter-label {
    font-size: 11px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.search-box {
    margin-bottom: 20px;
}

.search-input {
    width: 100%;
    padding: 14px 20px;
    border-radius: 30px;
    border: 2px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    color: white;
    font-size: 15px;
    outline: none;
    transition: all 0.3s;
}

.search-input::placeholder {
    color: rgba(255,255,255,0.6);
}

.search-input:focus {
    border-color: #fbbf24;
    background: rgba(255,255,255,0.15);
}

.empty {
    text-align: center;
    color: white;
    padding: 48px 16px;
    background: rgba(255,255,255,0.05);
    border-radius: 16px;
}

.empty-sub {
    opacity: 0.6;
    font-size: 13px;
    margin-top: 8px;
}

.winners-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
}

.winner-card {
    background: linear-gradient(145deg, rgba(255,255,255,0.12), rgba(255,255,255,0.05));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 16px;
    padding: 16px;
    color: white;
    transition: transform 0.2s;
}

.winner-card:active {
    transform: scale(0.98);
}

.prize-tag {
    display: inline-block;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #1f2937;
    font-weight: 700;
    font-size: 12px;
    padding: 4px 12px;
    border-radius: 20px;
    margin-bottom: 8px;
}

.winner-name {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
}

.winner-meta {
    margin-bottom: 8px;
}

.kupon {
    font-family: monospace;
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
    padding: 2px 10px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
}

.winner-detail {
    font-size: 12px;
    opacity: 0.85;
    border-top: 1px solid rgba(255,255,255,0.1);
    padding-top: 8px;
}

.winner-detail p {
    margin: 4px 0;
}

.drawn-at {
    opacity: 0.6;
    font-size: 11px;
}

.load-more-wrap {
    text-align: center;
    margin-top: 24px;
}

.load-more-btn {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    padding: 14px 32px;
    border-radius: 30px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    width: 100%;
    max-width: 320px;
    transition: all 0.2s;
}

.load-more-btn:active {
    background: rgba(255,255,255,0.25);
}

.load-more-btn:disabled {
    opacity: 0.5;
}

.page-info {
    color: white;
    opacity: 0.6;
    font-size: 12px;
    margin-top: 12px;
}

.end-info {
    text-align: center;
    color: #fbbf24;
    margin-top: 24px;
    font-weight: 600;
}

@media (max-width: 600px) {
    .winners-grid {
        grid-template-columns: 1fr;
    }
    .header h1 { font-size: 1.5rem; }
    .container { padding: 20px 12px; }
}
</style>