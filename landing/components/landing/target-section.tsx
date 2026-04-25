import { Scissors, ShoppingBag, Utensils, Dumbbell, Stethoscope, GraduationCap } from "lucide-react"

const industries = [
  {
    icon: ShoppingBag,
    name: "Retail",
    description: "Tiendas de ropa, electrónicos y más",
  },
  {
    icon: Utensils,
    name: "Gastronomía",
    description: "Restaurantes, cafeterías, delivery",
  },
  {
    icon: Scissors,
    name: "Belleza",
    description: "Salones, spas, estéticas",
  },
  {
    icon: Dumbbell,
    name: "Fitness",
    description: "Gimnasios, entrenadores, nutrición",
  },
  {
    icon: Stethoscope,
    name: "Salud",
    description: "Clínicas, consultorios, farmacias",
  },
  {
    icon: GraduationCap,
    name: "Educación",
    description: "Cursos, tutorías, academias",
  },
]

export function TargetSection() {
  return (
    <section className="px-4 py-16 md:py-20">
      <div className="mx-auto max-w-7xl">
        {/* Section header */}
        <div className="mx-auto max-w-2xl text-center">
          <p className="text-sm font-semibold uppercase tracking-wider text-primary">
            Para tu negocio
          </p>
          <h2 className="mt-3 text-balance text-3xl font-bold text-foreground md:text-4xl lg:text-5xl">
            Diseñado para cualquier industria
          </h2>
          <p className="mt-4 text-pretty text-lg text-muted-foreground">
            NETO se adapta a tu negocio, sin importar el rubro.
          </p>
        </div>

        {/* Industries grid */}
        <div className="mt-16 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
          {industries.map((industry, index) => (
            <div
              key={index}
              className="group flex flex-col items-center rounded-3xl border border-border/50 bg-card p-6 text-center transition-all hover:border-primary/20 hover:bg-primary/5 hover:shadow-lg"
            >
              <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted transition-colors group-hover:bg-primary/10">
                <industry.icon className="h-7 w-7 text-muted-foreground transition-colors group-hover:text-primary" strokeWidth={1.5} />
              </div>
              <h3 className="font-semibold text-foreground">{industry.name}</h3>
              <p className="mt-1 text-xs text-muted-foreground">{industry.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
