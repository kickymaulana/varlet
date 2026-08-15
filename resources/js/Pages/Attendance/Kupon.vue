<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { Paper, Result, Button, Icon } from '@varlet/ui'
import Vue3Lottie from 'vue3-lottie'
import bubbleAnimation from '../../Assets/bubble-explosion.json'
import { useDarkTheme } from '../../composables/useDarkTheme'

useDarkTheme()

// Menerima data participant langsung sebagai props
const props = defineProps<{
    participant: {
        id: number;
        nomor_induk: string;
        nama_lengkap: string;
        departemen: string;
        lokasi_kerja: string;
        is_present: boolean;
        nomor_kupon: string;
    }
}>()

const bagikanKeWhatsApp = () => {
    const nama = props.participant.nama_lengkap
    const kupon = props.participant.nomor_kupon
    const dept = props.participant.departemen

    const teksPesan = encodeURIComponent(
        !props.participant.nomor_kupon
            ? `*BUKTI E-ABSENSI - HUT MARK DYNAMICS*\n\n` +
              `Saya telah melakukan E-Absensi!\n\n` +
              `👤 *Nama:* ${nama}\n` +
              `🏭 *Departemen:* ${dept}\n\n` +
              `Terima kasih. 🙏`
            : `*KUPON LUCKY DRAW - HUT MARK DYNAMICS*\n\n` +
              `Halo rekan-rekan, saya telah sukses melakukan E-Absensi!\n\n` +
              `👤 *Nama:* ${nama}\n` +
              `🏭 *Departemen:* ${dept}\n` +
              `🎟️ *NOMOR UNDIAN:* ${kupon}\n\n` +
              `Semoga beruntung di acara Door Prize nanti! 🎉`
    )
    window.open(`https://api.whatsapp.com/send?text=${teksPesan}`, '_blank')
}

const page = usePage()

const kembaliUtama = () => {
    // Mengambil base URL dinamis dari Laravel (http://localhost/varlet/public)
    const baseUrl = page.props.app_url

    // Gabungkan dengan endpoint absensi
    router.get(`${baseUrl}/absensi`)
}
</script>

<template>
    <div class="container-absensi">
        <div class="lottie-bg-global">
            <Vue3Lottie :animationData="bubbleAnimation" :loop="true" :autoPlay="true" />
        </div>

        <div class="header-banner">
            <h2>HUT PT Mark Dynamics Indonesia Tbk</h2>
            <p>Sistem E-Absensi &amp; Kupon Undian Mandiri</p>
        </div>

        <div class="animasi-muncul">
            <var-paper :elevation="4" class="ticket-card">
                <div class="ticket-header">
                    <var-result type="success" title="CHECK-IN BERHASIL" />
                    <p class="ticket-subtitle">Bukti Kehadiran &amp; Kupon Door Prize Resmi</p>
                </div>

                <div class="ticket-divider">
                    <div class="notch notch-left"></div>
                    <div class="notch notch-right"></div>
                </div>

                <div class="ticket-body">
                    <div class="kupon-badge" :class="{ 'no-kupon': !props.participant.nomor_kupon }">
                        <span class="kupon-label">{{ props.participant.nomor_kupon ? 'NOMOR KUPON ANDA' : 'STATUS UNDIAN' }}</span>
                        <h1 v-if="props.participant.nomor_kupon" class="kupon-number">{{ props.participant.nomor_kupon }}</h1>
                        <h1 v-else class="kupon-number no-kupon-text">🙏 Tidak Dapat Undian</h1>
                    </div>

                    <div class="meta-details">
                        <div class="meta-row">
                            <span class="meta-label">Nama Karyawan</span>
                            <span class="meta-value text-bold">{{ props.participant.nama_lengkap }}</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Nomor Induk</span>
                            <span class="meta-value">{{ props.participant.nomor_induk }}</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Departemen</span>
                            <span class="meta-value">{{ props.participant.departemen }}</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Lokasi Kerja</span>
                            <span class="meta-value">{{ props.participant.lokasi_kerja }}</span>
                        </div>
                    </div>
                    <p class="ticket-note">*Silakan screenshot halaman ini sebagai cadangan bukti fisik.</p>
                </div>
            </var-paper>

            <div class="action-wrapper">
                <var-button type="success" block size="large" @click="bagikanKeWhatsApp">
                    <template #left-icon><var-icon name="whatsapp" /></template>
                    Bagikan Nomor Kupon ke WhatsApp
                </var-button>

                <var-button type="warning" block style="margin-top: 10px;" @click="kembaliUtama">
                    Kembali ke Halaman Utama
                </var-button>
            </div>
        </div>
    </div>
</template>


<style scoped>
/* 1. CONTAINER UTAMA (Dark Theme) */
.container-absensi {
    position: relative;
    max-width: 480px;
    margin: 0 auto;
    padding: 20px 15px;
    font-family: sans-serif;
    background: linear-gradient(180deg, #0f0f23 0%, #1a1a3e 50%, #16213e 100%);
    min-height: 100vh;
    box-sizing: border-box;
    overflow: hidden;
    color: #e4e4e7;
}

/* 2. LAYER ANIMASI LOTTIE GLOBAL */
.lottie-bg-global {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: none;
    opacity: 0.1;
}

.lottie-bg-global :deep(svg) {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
    filter: invert(1) hue-rotate(180deg);
}

/* 3. HEADER BANNER */
.header-banner {
    position: relative;
    z-index: 2;
    text-align: center;
    margin-bottom: 25px;
    background: linear-gradient(135deg, #1e1e3f 0%, #2a2a5e 100%);
    color: #ffffff;
    padding: 25px 20px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.header-banner h2 {
    margin: 0;
    font-size: 1.35rem;
    letter-spacing: 0.5px;
    font-weight: 700;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.header-banner p {
    margin: 8px 0 0;
    font-size: 0.85rem;
    opacity: 0.85;
    color: #d1d5db;
    font-weight: 500;
}

/* 4. DESAIN KARTU TIKET (Dark) */
.ticket-card {
    position: relative;
    z-index: 2;
    background: linear-gradient(145deg, #1e1e3f 0%, #16213e 100%);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 25px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.ticket-header {
    padding: 25px 20px 10px;
    text-align: center;
    background: linear-gradient(145deg, #1e1e3f 0%, #25254a 100%);
}

.ticket-subtitle {
    font-size: 0.85rem;
    color: #9ca3af;
    margin: 5px 0 0;
}

/* Efek Potongan Tiket (Notch) */
.ticket-divider {
    position: relative;
    height: 2px;
    border-top: 2px dashed rgba(255, 255, 255, 0.1);
    margin: 10px 0;
}

.notch {
    position: absolute;
    width: 20px;
    height: 20px;
    background: #0f0f23;
    border-radius: 50%;
    top: -11px;
}

.notch-left { left: -11px; }
.notch-right { right: -11px; }

.ticket-body {
    padding: 15px 25px 25px;
}

/* Badge Nomor Kupon */
.kupon-badge {
    text-align: center;
    background: linear-gradient(135deg, #1e3a5f 0%, #1e4d7a 100%);
    border: 2px solid #3b82f6;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 20px;
}

.kupon-label {
    font-size: 0.8rem;
    color: #bfdbfe;
    font-weight: bold;
    letter-spacing: 1.5px;
}

.kupon-badge.no-kupon {
    background: linear-gradient(135deg, #2e1065 0%, #4c1d95 100%);
    border-color: #a855f7;
}

.no-kupon-text {
    font-size: 1.6rem !important;
    color: #c084fc !important;
}

.kupon-number {
    font-size: 3.2rem;
    font-weight: 900;
    color: #60a5fa;
    margin: 5px 0 0;
    letter-spacing: 4px;
    text-shadow: 0 0 20px rgba(96, 165, 250, 0.5);
}

/* Detail Metadata Karyawan */
.meta-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: rgba(30, 30, 63, 0.8);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.95rem;
    border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
    padding-bottom: 6px;
}

.meta-label { color: #9ca3af; }
.meta-value { color: #e4e4e7; text-align: right; }
.text-bold { font-weight: bold; color: #fbbf24; }

.ticket-note {
    font-size: 0.78rem;
    color: #6b7280;
    text-align: center;
    margin-top: 15px;
    font-style: italic;
}

.action-wrapper {
    position: relative;
    z-index: 2;
    padding: 0 5px;
}

/* 5. UTILITY KUSTOMISASI VARLET & ANIMASI */
:deep(.var-input) {
    --input-focus-color: #f59e0b;
}

.animasi-muncul {
    animation: fadeIn 0.45s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>


