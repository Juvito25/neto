import { Clock, MessageSquareOff, TrendingDown, AlertTriangle } from "lucide-react"

const painPoints = [
  {
    icon: MessageSquareOff,
    title: "Mensajes que nunca respondés",
    description: "El cliente te escribe a la noche, te ve offline y se fue a la competencia. Perdiste una venta que ya era tuya.",
  },
  {
    icon: Clock,
    title: "Tu negocio cierra a las 9pm",
    description: "Mientras dormís, tu WhatsApp sigue sonando. Y los clientes se van con quien responde primero.",
  },
  {
    icon: TrendingDown,
    title: "El cliente se aburre de esperar",
    description: "Respondés 3 horas después: 'sorry, estaba en una reunion'. Ya compré en otro lado.",
  },
]

export function PainSection() {
  return (
    <section className="px-4 py-20 md:py-28">
      <div className="mx-auto max-w-7xl">
        {/* Section header */}
        <div className="mx-auto max-w-2xl text-center">
          <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-red-50 px-4 py-1.5 text-sm font-medium text-red-600">
            <AlertTriangle className="h-4 w-4" />
            <span>La realidad del comerciante</span>
          </div>
          <h2 className="mt-3 text-balance text-3xl font-bold text-foreground md:text-4xl lg:text-5xl">
            ¿Cuántas ventas perdiste esta semana?
          </h2>
          <p className="mt-4 text-pretty text-lg text-muted-foreground">
            El que no responde cuando le escriben, pierde. Es simple.
          </p>
        </div>

        {/* Pain cards - Bento Grid Style */}
        <div className="mt-16 grid gap-6 md:grid-cols-3">
          {painPoints.map((pain, index) => (
            <div
              key={index}
              className="group relative overflow-hidden rounded-3xl border border-gray-200 bg-gradient-to-b from-gray-50 to-white p-8 transition-all hover:border-red-200 hover:shadow-xl hover:shadow-red-500/5"
            >
              {/* Icon */}
              <div className="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50">
                <pain.icon className="h-7 w-7 text-red-500" strokeWidth={1.5} />
              </div>
              
              {/* Content */}
              <h3 className="text-xl font-semibold text-gray-900">
                {pain.title}
              </h3>
              <p className="mt-3 text-gray-600">
                {pain.description}
              </p>
              
              {/* Decorative gradient */}
              <div className="absolute -bottom-20 -right-20 h-40 w-40 rounded-full bg-red-100 blur-3xl transition-all group-hover:bg-red-200" />
            </div>
          ))}
        </div>

        {/* Impact stat */}
        <div className="mt-16 text-center">
          <p className="text-2xl font-bold text-gray-900">
            <span className="text-[#0052CC]">El 67%</span> de los comerciantes pierde al menos 1 venta por día por no responder a tiempo.
          </p>
        </div>
      </div>
    </section>
  )
}