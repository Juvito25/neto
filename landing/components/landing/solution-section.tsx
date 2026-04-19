import { Bot, CreditCard, LayoutDashboard, Package, Brain, Clock, Zap, Smartphone, FileText, Megaphone } from "lucide-react"

const features = [
  {
    icon: Bot,
    title: "Tu vendedor que never sleeps",
    description: "NETO con IA Llama 3.1 responde como un vendedor con años de experiencia. Conoce cada producto, entiende lo que necesitan y cierra solo.",
    className: "md:col-span-2 md:row-span-2",
    preview: (
      <div className="mt-6 space-y-3">
        <div className="rounded-2xl bg-white/80 p-4 shadow-sm backdrop-blur">
          <div className="flex items-start gap-3">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#0052CC]/10">
              <Bot className="h-4 w-4 text-[#0052CC]" />
            </div>
            <div>
              <p className="text-sm text-gray-800">Hola! Necesitaba un shampoo para cabello graso</p>
              <p className="text-xs text-gray-500 mt-1">NETO responde</p>
            </div>
          </div>
        </div>
        <div className="ml-auto max-w-[80%] rounded-2xl bg-gray-100 p-4">
          <p className="text-sm text-gray-800">Tengo el cabello muy graso, qué me recomendás?</p>
        </div>
        <div className="rounded-2xl bg-white/80 p-4 shadow-sm backdrop-blur">
          <div className="flex items-start gap-3">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#0052CC]/10">
              <Bot className="h-4 w-4 text-[#0052CC]" />
            </div>
            <div>
              <p className="text-sm text-gray-800">Te recomiendo el Shampoo Purificante Net Plus! Es específico para cabello graso, regula el sebo sin secar. Llevando el pack de 2 lavados te sale $18.500 en vez de $24.000 🎯</p>
            </div>
          </div>
        </div>
      </div>
    ),
  },
  {
    icon: Package,
    title: "Tu catálogo siempre actualizado",
    description: "Subís tu Excel y NETO lo usa para responder. Precio, stock, descripción: todo sale de tu propia lista.",
    className: "md:col-span-1",
    preview: (
      <div className="mt-4 grid grid-cols-2 gap-2">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="overflow-hidden rounded-xl bg-white/80 shadow-sm backdrop-blur">
            <div className="aspect-square bg-gradient-to-br from-gray-100 to-gray-200" />
            <div className="p-2">
              <div className="h-2 w-16 rounded bg-gray-200" />
              <div className="mt-1 h-2 w-10 rounded bg-[#0052CC]/20" />
            </div>
          </div>
        ))}
      </div>
    ),
  },
  {
    icon: Smartphone,
    title: "Todo por WhatsApp",
    description: "No necesitás app, ni PC, ni nada raro. Tu cliente ya tiene WhatsApp y vos también.",
    className: "md:col-span-1",
    preview: (
      <div className="mt-4 overflow-hidden rounded-2xl bg-white/80 p-4 shadow-sm backdrop-blur">
        <div className="flex items-center gap-3">
          <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#008A45]">
            <svg className="h-6 w-6 text-white" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17.498 14.382c-.301-.15-1.767-.867-2.041-.966-.273-.101-.473-.15-.673.15-.197.295-. copper.964.241 1.2.275.226 1.027.6 1.272.72.275.136.463.151.673.045.301-.15 1.676-1.917 1.676-1.917s-.351-.302-.673-.453c-.321-.15-.601-.225-.673-.353-.074-.15-.074-.3-.052-.45.023-.151.226-.301.452-.45.301-.226.452-.226.601-.226s.351-.025.603.15c.25.176.476.451.476.451s-.351.226-.476.301z"/>
            </svg>
          </div>
          <div>
            <p className="text-sm font-semibold text-gray-900">WhatsApp es todo</p>
            <p className="text-xs text-gray-500">El cliente no instala nada</p>
          </div>
        </div>
      </div>
    ),
  },
  {
    icon: FileText,
    title: "Gestión simple",
    description: "Un panel simple para ver conversaciones, responder manually si querés, y ver tus métricas.",
    className: "md:col-span-2",
    preview: (
      <div className="mt-4 overflow-hidden rounded-2xl bg-white/80 p-4 shadow-sm backdrop-blur">
        <div className="grid grid-cols-3 gap-4">
          <div className="rounded-xl bg-[#0052CC]/5 p-3 text-center">
            <p className="text-2xl font-bold text-[#0052CC]">156</p>
            <p className="text-xs text-gray-500">Chats hoy</p>
          </div>
          <div className="rounded-xl bg-[#008A45]/5 p-3 text-center">
            <p className="text-2xl font-bold text-[#008A45]">$847k</p>
            <p className="text-xs text-gray-500">Vendido</p>
          </div>
          <div className="rounded-xl bg-orange-50 p-3 text-center">
            <p className="text-2xl font-bold text-orange-600">78%</p>
            <p className="text-xs text-gray-500">Auto-cierre</p>
          </div>
        </div>
      </div>
    ),
  },
]

export function SolutionSection() {
  return (
    <section className="px-4 py-20 md:py-28">
      <div className="mx-auto max-w-7xl">
        {/* Section header */}
        <div className="mx-auto max-w-2xl text-center">
          <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-[#0052CC]/10 px-4 py-1.5 text-sm font-medium text-[#0052CC]">
            <Brain className="h-4 w-4" />
            <span>Potenciado por IA</span>
          </div>
          <h2 className="mt-3 text-balance text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl">
            Un vendedor que conoce todo tu stock
          </h2>
          <p className="mt-4 text-pretty text-lg text-gray-600">
            NETO usa tu propio catálogo para responder. No busca en internet, busca en tus productos.
          </p>
        </div>

        {/* Bento Grid */}
        <div className="mt-16 grid gap-4 md:grid-cols-3 md:grid-rows-2">
          {features.map((feature, index) => (
            <div
              key={index}
              className={`group relative overflow-hidden rounded-3xl border border-gray-200 bg-white p-6 transition-all hover:border-[#0052CC]/20 hover:shadow-xl hover:shadow-[#0052CC]/5 ${feature.className}`}
            >
              {/* Content */}
              <div className="relative">
                {/* Icon */}
                <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0052CC]/10">
                  <feature.icon className="h-6 w-6 text-[#0052CC]" strokeWidth={1.5} />
                </div>
                
                <h3 className="text-xl font-semibold text-gray-900">
                  {feature.title}
                </h3>
                <p className="mt-2 text-gray-600">
                  {feature.description}
                </p>
                
                {/* Preview */}
                {feature.preview}
              </div>
              
              {/* Decorative gradient */}
              <div className="absolute -bottom-20 -right-20 h-40 w-40 rounded-full bg-[#0052CC]/5 blur-3xl transition-all group-hover:bg-[#0052CC]/10" />
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}