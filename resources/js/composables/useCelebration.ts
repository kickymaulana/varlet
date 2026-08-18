import { ref, onMounted, onUnmounted } from 'vue'

export function useCelebration() {
    const isPlaying = ref(false)
    const isMuted = ref(false)
    const needsInteraction = ref(true)
    const audioRef = ref<HTMLAudioElement | null>(null)
    const fireworksCanvas = ref<HTMLCanvasElement | null>(null)
    let animFrame: number | null = null
    let particles: any[] = []

    const COLORS = ['#fbbf24', '#ef4444', '#3b82f6', '#10b981', '#a855f7', '#ec4899', '#fff']

    class Particle {
        x: number
        y: number
        vx: number
        vy: number
        color: string
        life: number
        size: number
        gravity: number

        constructor(canvas: HTMLCanvasElement) {
            this.x = Math.random() * canvas.width * 0.8 + canvas.width * 0.1
            this.y = Math.random() * canvas.height * 0.5
            const angle = Math.random() * Math.PI * 2
            const speed = 3 + Math.random() * 5
            this.vx = Math.cos(angle) * speed
            this.vy = Math.sin(angle) * speed
            this.color = COLORS[Math.floor(Math.random() * COLORS.length)]
            this.life = 1
            this.size = 2 + Math.random() * 3
            this.gravity = 0.08
        }

        update() {
            this.x += this.vx
            this.y += this.vy
            this.vy += this.gravity
            this.vx *= 0.98
            this.vy *= 0.98
            this.life -= 0.01
        }

        draw(ctx: CanvasRenderingContext2D) {
            ctx.globalAlpha = Math.max(0, this.life)
            ctx.fillStyle = this.color
            ctx.beginPath()
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2)
            ctx.fill()
        }
    }

    const launchFirework = (canvas: HTMLCanvasElement) => {
        for (let i = 0; i < 60; i++) {
            particles.push(new Particle(canvas))
        }
    }

    const animate = () => {
        if (!fireworksCanvas.value) return
        const canvas = fireworksCanvas.value
        const ctx = canvas.getContext('2d')
        if (!ctx) return

        ctx.fillStyle = 'rgba(15, 15, 35, 0.15)'
        ctx.fillRect(0, 0, canvas.width, canvas.height)

        particles = particles.filter(p => p.life > 0)
        particles.forEach(p => {
            p.update()
            p.draw(ctx)
        })

        ctx.globalAlpha = 1

        if (Math.random() < 0.05) {
            launchFirework(canvas)
        }

        animFrame = requestAnimationFrame(animate)
    }

    const resizeCanvas = () => {
        if (!fireworksCanvas.value) return
        const canvas = fireworksCanvas.value
        canvas.width = window.innerWidth
        canvas.height = window.innerHeight
    }

    const start = () => {
        needsInteraction.value = false
        isPlaying.value = true

        if (audioRef.value && !isMuted.value) {
            audioRef.value.play().catch(() => {})
        }

        if (fireworksCanvas.value) {
            resizeCanvas()
            animate()
            setInterval(() => {
                if (fireworksCanvas.value && isPlaying.value) {
                    launchFirework(fireworksCanvas.value)
                }
            }, 1200)
        }
    }

    const toggleMute = () => {
        isMuted.value = !isMuted.value
        if (audioRef.value) {
            audioRef.value.muted = isMuted.value
            if (!isMuted.value && audioRef.value.paused) {
                audioRef.value.play().catch(() => {})
            }
        }
    }

    onMounted(() => {
        resizeCanvas()
        window.addEventListener('resize', resizeCanvas)
    })

    onUnmounted(() => {
        if (animFrame) cancelAnimationFrame(animFrame)
        window.removeEventListener('resize', resizeCanvas)
        if (audioRef.value) {
            audioRef.value.pause()
        }
    })

    return {
        isPlaying,
        isMuted,
        needsInteraction,
        audioRef,
        fireworksCanvas,
        start,
        toggleMute,
    }
}
