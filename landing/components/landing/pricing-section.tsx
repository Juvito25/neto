import { Button } from "@/components/ui/button"
import { Check, ArrowRight, Sparkles, Zap, Award } from "lucide-react"

const benefits = [
  "Respuestas IA ilimitadas 24/7",
  "Catálogo de hasta 500 productos",
  "Link de pago automático",
  "Dashboard con métricas",
  "Gestión por WhatsApp o panel web",
  "Setup en 5 minutos",
  "Sin contratos, cancelás cuando quieras",
]

export function PricingSection() {
  return (
    <section className="px-4 py-20 md:py-28">
      <div className="mx-auto max-w-7xl">
        {/* Section header */}
        <div className="mx-auto max-w-2xl text-center">
          <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-[#008A45]/10 px-4 py-1.5 text-sm font-medium text-[#008A45]">
            <Award className="h-4 w-4" />
            <span>Mejor precio del mercado</span>
          </div>
          <h2 className="mt-3 text-balance text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl">
            $19 USD/mes = 1 venta recuperada
          </h2>
          <p className="mt-4 text-pretty text-lg text-gray-600">
            Es lo que costaría un vendedor junior 4 horas. NETO trabaja 24 horas, los 365 días del año.
          </p>
        </div>

        {/* Pricing card */}
        <div className="relative mx-auto mt-16 max-w-lg">
          {/* Glow effect */}
          <div className="absolute -inset-4 -z-10 rounded-[3rem] bg-gradient-to-r from-[#0052CC]/20 via-[#0052CC]/10 to-[#0052CC]/20 blur-2xl" />
          
          {/* Card */}
          <div className="relative overflow-hidden rounded-3xl border-2 border-[#0052CC]/20 bg-white p-8 shadow-2xl shadow-[#0052CC]/10 md:p-10">
            {/* Popular badge */}
            <div className="absolute -right-16 top-8 rotate-45 bg-[#0052CC] px-16 py-1 text-xs font-semibold uppercase text-white">
             Mejor valor
            </div>
            
            {/* Content */}
            <div className="relative">
              {/* Plan name */}
              <div className="inline-flex items-center gap-2 rounded-full bg-[#0052CC]/10 px-4 py-2 text-sm font-medium text-[#0052CC]">
                <Sparkles className="h-4 w-4" />
                <span>NETO Pro</span>
              </div>
              
              {/* Price */}
              <div className="mt-6 flex items-baseline gap-2">
                <span className="text-5xl font-bold tracking-tight text-gray-900 md:text-6xl">
                  $19
                </span>
                <span className="text-xl text-gray-500">USD/mes</span>
              </div>
              
              <p className="mt-3 text-gray-600">
                Todo para automatizar tus ventas. Sin sorpresa.
              </p>
              
              {/* Benefits list */}
              <ul className="mt-8 space-y-4">
                {benefits.map((benefit, index) => (
                  <li key={index} className="flex items-start gap-3">
                    <div className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#008A45]/10">
                      <Check className="h-3 w-3 text-[#008A45]" strokeWidth={3} />
                    </div>
                    <span className="text-gray-700">{benefit}</span>
                  </li>
                ))}
              </ul>
              
              {/* CTA */}
              <a href="https://app.netoia.cloud/register" className="block mt-10">
                <Button 
                  size="lg" 
                  className="group h-14 w-full rounded-2xl bg-[#0052CC] text-lg font-semibold text-white shadow-lg shadow-[#0052CC]/25 transition-all hover:scale-[1.02] hover:bg-[#0052CC]/90 hover:shadow-xl hover:shadow-[#0052CC]/30"
                >
                  Empezar gratis 7 días
                  <ArrowRight className="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1" />
                </Button>
              </a>
              
              <p className="mt-4 text-center text-sm text-gray-500">
                Sin tarjeta • Sin compromiso
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}