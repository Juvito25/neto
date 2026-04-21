import { Button } from "@/components/ui/button"
import { ArrowRight, MessageCircle, Zap } from "lucide-react"

export function CTASection() {
  return (
    <section className="px-4 py-20 md:py-28">
      <div className="mx-auto max-w-7xl">
        <div className="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-[#0052CC] via-[#0052CC] to-[#003d99] px-8 py-16 text-center md:px-16 md:py-24">
          {/* Background decoration */}
          <div className="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2">
            <div className="h-96 w-96 rounded-full bg-white/10 blur-3xl" />
          </div>
          <div className="absolute bottom-0 right-0 translate-x-1/4 translate-y-1/4">
            <div className="h-64 w-64 rounded-full bg-white/10 blur-3xl" />
          </div>
          
          {/* Content */}
          <div className="relative">
            <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 backdrop-blur">
              <Zap className="h-8 w-8 text-white" />
            </div>
            
            <h2 className="mx-auto max-w-2xl text-balance text-3xl font-bold text-white md:text-4xl lg:text-5xl">
              Tu primer vendedor 24/7 en 5 minutos
            </h2>
            
            <p className="mx-auto mt-4 max-w-xl text-pretty text-lg text-white/80">
              Arrancás gratis hoy. Subís tu Excel de productos y NETO ya empieza a responder.
            </p>
            
            <div className="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
              <a href="https://app.netoia.cloud/register">
                <Button 
                  size="lg" 
                  className="group h-14 w-full rounded-2xl bg-white px-8 text-lg font-semibold text-[#0052CC] shadow-lg transition-all hover:scale-105 hover:bg-white/90 sm:w-auto"
                >
                  Quiero mi NETO gratis
                  <ArrowRight className="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1" />
                </Button>
              </a>
            </div>
            
            <p className="mt-6 text-sm text-white/60">
              7 días gratis • Sin tarjeta • Setup en 5 min
            </p>
          </div>
        </div>
      </div>
    </section>
  )
}