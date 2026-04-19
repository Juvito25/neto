import { Header } from "@/components/landing/header"
import { Footer } from "@/components/landing/footer"

export default function PrivacidadPage() {
  return (
    <>
      <Header />
      <main className="pt-24 pb-16 px-4">
        <div className="mx-auto max-w-3xl">
          <h1 className="text-3xl font-bold text-gray-900 mb-8">Política de Privacidad</h1>
          
          <div className="space-y-6 text-gray-600">
            <p>
              En NETO valoramos tu privacidad y nos comprometemos a proteger tus datos personales. Esta política de privacidad describe cómo recopilamos, usamos y protegemos tu información.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">1. Información que recopilamos</h2>
            <p>
              Recopilamos información que nos proporcionas directamente, como tu nombre, correo electrónico y datos de tu negocio al registrarte. También recopilamos datos de tus conversaciones con clientes a través de WhatsApp para mejorar nuestro servicio.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">2. Cómo usamos tu información</h2>
            <p>
              Usamos tu información para: proporcionarte el servicio de asistente virtual, mejorar nuestros productos y servicios, comunicarnos contigo sobre actualizaciones y soporte técnico, y cumplir con nuestras obligaciones legales.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">3. Almacenamiento y seguridad</h2>
            <p>
              Tus datos se almacenan en servidores seguros y adoptamos medidas técnicas y organizativas apropiadas para proteger tu información contra accesos no autorizados, pérdida o alteración.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">4. Compartir información</h2>
            <p>
              No vendemos tu información personal. Solo compartimos datos con proveedores de servicios que nos ayudan a operar nuestra plataforma, quienes están obligados a mantener la confidencialidad.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">5. Tus derechos</h2>
            <p>
              Tienes derecho a acceder, corregir o eliminar tus datos personales. Para ejercer estos derechos, contactanos en hola@neto.cloud.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">6. Contacto</h2>
            <p>
              Si tenés preguntas sobre esta política de privacidad, podés contactarnos en hola@neto.cloud.
            </p>

            <p className="text-sm text-gray-500 mt-12">
              Última actualización: Abril 2026
            </p>
          </div>
        </div>
      </main>
      <Footer />
    </>
  )
}
