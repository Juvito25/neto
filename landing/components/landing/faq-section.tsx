"use client"

import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"

const faqs = [
  {
    question: "Cómo funciona?",
    answer: "NETO se conecta a tu WhatsApp Business. Cuando un cliente te escribe, la IA busca en tu catálogo y responde sola. Si necesita ajuda, te avisa.",
  },
  {
    question: "Necesito saber programar?",
    answer: "No. Subís tu Excel de productos, escaneás un QR y listo. En 5 minutos está andando.",
  },
  {
    question: "Es gratis?",
    answer: "Sí, 7 días gratis sin tarjeta. Después $19 USD/mes. Cancelás cuando quieras.",
  },
  {
    question: "Puedo usar mi número actual?",
    answer: "Sí, NETO usa tu número de WhatsApp Business. No necesitás cambiar de número.",
  },
  {
    question: "Qué pasa si no sabe responder?",
    answer: "Te llega una notificación y respondés vos desde el panel o por WhatsApp. Aprende de vos también.",
  },
  {
    question: "Cuántos productos puedo cargar?",
    answer: "Hasta 500 productos en el plan básico. Si necesitás más, hablamos.",
  },
]

export function FAQSection() {
  return (
    <section className="px-4 py-20 md:py-28">
      <div className="mx-auto max-w-3xl">
        {/* Section header */}
        <div className="text-center">
          <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-gray-100 px-4 py-1.5 text-sm font-medium text-gray-600">
            <span>Preguntas</span>
          </div>
          <h2 className="mt-3 text-balance text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl">
            Lo que tenés que saber
          </h2>
          <p className="mt-4 text-pretty text-lg text-gray-600">
            Respuestas cortas, sin vueltas.
          </p>
        </div>

        {/* Accordion */}
        <Accordion type="single" collapsible className="mt-12">
          {faqs.map((faq, index) => (
            <AccordionItem
              key={index}
              value={`item-${index}`}
              className="border-b border-gray-200 py-2"
            >
              <AccordionTrigger className="py-4 text-left text-lg font-medium text-gray-900 hover:text-[#0052CC] hover:no-underline [&[data-state=open]]:text-[#0052CC]">
                {faq.question}
              </AccordionTrigger>
              <AccordionContent className="pb-4 text-gray-600">
                {faq.answer}
              </AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>
      </div>
    </section>
  )
}