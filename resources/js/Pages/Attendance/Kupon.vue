<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { Paper, Result, Button, Icon } from '@varlet/ui'
import Vue3Lottie from 'vue3-lottie'
import bubbleAnimation from '../../Assets/bubble-explosion.json'

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
        props.participant.nomor_kupon === 'Tidak Dapat Undian'
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
                    <div class="kupon-badge" :class="{ 'no-kupon': props.participant.nomor_kupon === 'Tidak Dapat Undian' }">
                        <span class="kupon-label">{{ props.participant.nomor_kupon === 'Tidak Dapat Undian' ? 'STATUS UNDIAN' : 'NOMOR KUPON ANDA' }}</span>
                        <h1 v-if="props.participant.nomor_kupon !== 'Tidak Dapat Undian'" class="kupon-number">{{ props.participant.nomor_kupon }}</h1>
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
/* 1. CONTAINER UTAMA (Jangkar untuk Background & Absolutitas) */
.container-absensi {
    position: relative;
    max-width: 480px;
    margin: 0 auto;
    padding: 20px 15px;
    font-family: sans-serif;
    /* Background Cafe Gradient */
    background: linear-gradient(180deg, #e8dfd8 0%, #f5efe9 100%);
    min-height: 100vh;
    box-sizing: border-box;
    overflow: hidden; /* Mencegah scrollbar muncul akibat luapan animasi */
}

/* 2. LAYER ANIMASI LOTTIE GLOBAL (Berada di Belakang Konten) */
.lottie-bg-global {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;           /* Layer bawah */
    pointer-events: none; /* User tetap bisa klik tombol/input di atasnya */
    opacity: 0.6;         /* Transparansi gelembung agar tidak terlalu mencolok */
}

/* Memaksa elemen SVG Lottie untuk memenuhi layar */
.lottie-bg-global :deep(svg) {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
}

/* 3. HEADER BANNER (Merah & Oranye Khas Logo) */
.header-banner {
    position: relative;
    z-index: 2; /* Naik ke layer atas */
    text-align: center;
    margin-bottom: 25px;
    background: linear-gradient(135deg, #e63946 0%, #f35b04 100%);
    color: #ffffff;
    padding: 25px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(243, 91, 4, 0.25);
}

.header-banner h2 {
    margin: 0;
    font-size: 1.35rem;
    letter-spacing: 0.5px;
    font-weight: 700;
}

.header-banner p {
    margin: 8px 0 0;
    font-size: 0.85rem;
    opacity: 0.95;
    color: #ffffff;
    font-weight: 500;
}

/* 4. DESAIN KARTU TIKET (Setelah Check-in Sukses) */
.ticket-card {
    position: relative;
    z-index: 2; /* Naik ke layer atas */
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 25px;
    border: 1px solid rgba(218, 204, 193, 0.4);
    box-shadow: 0 10px 25px rgba(168, 150, 134, 0.15);
}

.ticket-header {
    padding: 25px 20px 10px;
    text-align: center;
    background: #fdfcfb;
}

.ticket-subtitle {
    font-size: 0.85rem;
    color: #666;
    margin: 5px 0 0;
}

/* Efek Potongan Tiket (Notch) */
.ticket-divider {
    position: relative;
    height: 2px;
    border-top: 2px dashed #e4ded9;
    margin: 10px 0;
}

.notch {
    position: absolute;
    width: 20px;
    height: 20px;
    background: #e8dfd8; /* Mengikuti warna dasar background kontainer */
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
    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
    border: 2px solid #f35b04;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 20px;
}

.kupon-label {
    font-size: 0.8rem;
    color: #e65100;
    font-weight: bold;
    letter-spacing: 1.5px;
}

.kupon-badge.no-kupon {
    background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
    border-color: #9333ea;
}

.no-kupon-text {
    font-size: 1.6rem !important;
    color: #7c3aed !important;
}

.kupon-number {
    font-size: 3.2rem;
    font-weight: 900;
    color: #d84315;
    margin: 5px 0 0;
    letter-spacing: 4px;
}

/* Detail Metadata Karyawan */
.meta-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #fafafa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #f0f0f0;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.95rem;
    border-bottom: 1px dashed #eaeaea;
    padding-bottom: 6px;
}

.meta-label { color: #7f8c8d; }
.meta-value { color: #2c3e50; text-align: right; }
.text-bold { font-weight: bold; color: #111; }

.ticket-note {
    font-size: 0.78rem;
    color: #95a5a6;
    text-align: center;
    margin-top: 15px;
    font-style: italic;
}

.action-wrapper {
    position: relative;
    z-index: 2; /* Naik ke layer atas */
    padding: 0 5px;
}

/* 5. FORM CARI & INPUT PIN (Langkah Awal) */
.step-wrapper {
    position: relative;
    z-index: 2; /* Naik ke layer atas */
}

.card-box {
    padding: 25px 20px;
    margin-bottom: 20px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid rgba(218, 204, 193, 0.4);
    box-shadow: 0 10px 25px rgba(168, 150, 134, 0.15);
}

.step-title {
    margin-top: 0;
    margin-bottom: 18px;
    color: #e63946;
    font-size: 1.1rem;
    font-weight: 700;
    border-left: 4px solid #f35b04;
    padding-left: 10px;
}

.info-karyawan {
    background: #fff3e0;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 0.95rem;
    border-left: 3px solid #f35b04;
}

.info-karyawan p {
    margin: 6px 0;
    color: #e65100;
}

.pin-instruction {
    font-size: 0.85rem;
    color: #555;
    margin-bottom: 10px;
    line-height: 1.4;
}

/* 6. UTILITY KUSTOMISASI VARLET & ANIMASI */
:deep(.var-input) {
    --input-focus-color: #f35b04;
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


