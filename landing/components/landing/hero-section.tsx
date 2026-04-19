"use client"

import { Button } from "@/components/ui/button"
import { ArrowRight, MessageCircle, Sparkles, Zap } from "lucide-react"

export function HeroSection() {
  return (
    <section className="relative overflow-hidden px-4 pt-20 pb-24 md:pt-32 md:pb-32">
      {/* Subtle gradient background */}
      <div className="absolute inset-0 -z-10 bg-gradient-to-b from-[#0052CC]/5 via-transparent to-transparent" />
      
      <div className="mx-auto max-w-7xl">
        <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
          {/* Left content */}
          <div className="text-center lg:text-left">
            {/* Badge */}
            <div className="mb-6 inline-flex items-center gap-2 rounded-full bg-[#0052CC]/10 px-4 py-2 text-sm font-medium text-[#0052CC]">
              <Sparkles className="h-4 w-4" />
              <span>7 días gratis • Sin tarjeta</span>
            </div>
            
            {/* H1 - Massive and bold */}
            <h1 className="text-balance text-4xl font-bold leading-tight tracking-tight text-foreground md:text-5xl lg:text-6xl xl:text-7xl">
              Dejá de perder{" "}
              <span className="bg-gradient-to-r from-[#0052CC] to-[#0052CC]/70 bg-clip-text text-transparent">
                ventas mientras dormís
              </span>
            </h1>
            
            {/* Subtitle */}
            <p className="mt-6 text-pretty text-lg text-muted-foreground md:text-xl lg:max-w-xl">
              Tu vendedor virtual que nunca duerme, nunca se enferma y conoce cada producto de tu stock. 
              Responde en 30 segundos, cierra ventas y te hace cobrar.
            </p>
            
            {/* CTAs */}
            <div className="mt-10 flex flex-col items-center gap-4 sm:flex-row lg:justify-start">
              <a href="/signup">
                <Button 
                  size="lg" 
                  className="group h-14 w-full rounded-2xl bg-[#0052CC] px-8 text-lg font-semibold text-white shadow-lg shadow-[#0052CC]/25 transition-all hover:scale-105 hover:bg-[#0052CC]/90 hover:shadow-xl hover:shadow-[#0052CC]/30 sm:w-auto"
                >
                 Quiero mi asistente gratis
                  <ArrowRight className="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1" />
                </Button>
              </a>
              <a href="#funciones">
                <Button 
                  variant="outline" 
                  size="lg"
                  className="h-14 w-full rounded-2xl border-2 px-8 text-lg font-medium sm:w-auto"
                >
                  Ver cómo funciona
                </Button>
              </a>
            </div>
            
            {/* Trust indicators */}
            <div className="mt-10 flex flex-col items-center gap-4 text-sm text-muted-foreground sm:flex-row lg:justify-start">
              <div className="flex items-center gap-2">
                <svg className="h-5 w-5 text-[#008A45]" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                </svg>
                <span>7 días gratis</span>
              </div>
              <div className="flex items-center gap-2">
                <svg className="h-5 w-5 text-[#008A45]" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                </svg>
                <span>Setup en 5 minutos</span>
              </div>
              <div className="flex items-center gap-2">
                <svg className="h-5 w-5 text-[#008A45]" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                </svg>
                <span>Cancela cuando quieras</span>
              </div>
            </div>
          </div>
          
          {/* Right content - iPhone mockup with WhatsApp chat */}
          <div className="relative mx-auto max-w-sm lg:max-w-none">
            {/* Glow effect behind phone */}
            <div className="absolute -inset-4 -z-10 rounded-[3rem] bg-gradient-to-tr from-[#0052CC]/20 via-[#0052CC]/10 to-transparent blur-3xl" />
            
            {/* iPhone frame */}
            <div className="relative mx-auto w-[280px] rounded-[3rem] border-[14px] border-foreground/90 bg-foreground/90 p-1 shadow-2xl md:w-[320px]">
              {/* Notch */}
              <div className="absolute left-1/2 top-0 z-10 h-7 w-32 -translate-x-1/2 rounded-b-2xl bg-foreground/90" />
              
              {/* Screen */}
              <div className="relative overflow-hidden rounded-[2.2rem] bg-white">
                {/* WhatsApp header */}
                <div className="bg-[#075E54] px-4 py-3 pt-10">
                  <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white/20">
                      <MessageCircle className="h-5 w-5 text-white" />
                    </div>
                    <div>
                      <p className="font-semibold text-white">NETO</p>
                      <p className="text-xs text-white/70">asistente virtual</p>
                    </div>
                  </div>
                </div>
                
                {/* Chat messages */}
                <div className="space-y-3 bg-[#ECE5DD] p-4" style={{ backgroundImage: "url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\")" }}>
                  {/* User message */}
                  <div className="ml-auto max-w-[75%] rounded-2xl rounded-tr-md bg-[#DCF8C6] px-3 py-2 shadow-sm">
                    <p className="text-sm text-gray-800">Hola, tienen Milk Ops?</p>
                    <p className="mt-1 text-right text-[10px] text-gray-500">09:15</p>
                  </div>
                  
                  {/* AI response - quick */}
                  <div className="mr-auto max-w-[75%] rounded-2xl rounded-tl-md bg-white px-3 py-2 shadow-sm">
                    <div className="flex items-center gap-1">
                      <Zap className="h-3 w-3 text-[#0052CC]" />
                      <p className="text-xs text-[#0052CC] font-medium">respondió en 2s</p>
                    </div>
                    <p className="text-sm text-gray-800 mt-1">Sí, tenemos Milk Ops 500ml disponible! 🌿</p>
                    <p className="text-sm text-gray-800">Es un limpidor ecológico concentrado, rendí mucho y huele a lavanda.</p>
                  </div>
                  
                  {/* Product card */}
                  <div className="mr-auto max-w-[75%] overflow-hidden rounded-2xl rounded-tl-md bg-white shadow-sm">
                    <div className="flex aspect-video items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 p-4">
                      <div className="flex h-16 w-16 items-center justify-center rounded-xl bg-[#008A45]/10">
                        <span className="text-2xl">🌿</span>
                      </div>
                    </div>
                    <div className="p-3">
                      <p className="font-semibold text-sm text-gray-800">Milk Ops 500ml</p>
                      <p className="text-lg font-bold text-[#0052CC]">$4.500 ARS</p>
                      <p className="text-[10px] text-gray-500">Envío gratis</p>
                    </div>
                  </div>
                  
                  {/* AI closes sale */}
                  <div className="mr-auto max-w-[75%] rounded-2xl rounded-tl-md bg-white px-3 py-2 shadow-sm">
                    <p className="text-sm text-gray-800">Te lo envío ahora y pagás cuando te llega? 💚</p>
                    <p className="mt-1 text-right text-[10px] text-gray-500">09:16</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}