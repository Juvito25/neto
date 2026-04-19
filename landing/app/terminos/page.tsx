import { Header } from "@/components/landing/header"
import { Footer } from "@/components/landing/footer"

export default function TerminosPage() {
  return (
    <>
      <Header />
      <main className="pt-24 pb-16 px-4">
        <div className="mx-auto max-w-3xl">
          <h1 className="text-3xl font-bold text-gray-900 mb-8">Términos y Condiciones</h1>
          
          <div className="space-y-6 text-gray-600">
            <p>
              Al usar NETO, aceptás los siguientes términos y condiciones. Si no estás de acuerdo con estos términos, por favor no utilices nuestro servicio.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">1. Descripción del servicio</h2>
            <p>
              NETO es un asistente virtual con inteligencia artificial que se integra con WhatsApp Business para automatizar la atención al cliente, responder consultas sobre productos y cerrar ventas de forma automática.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">2. Registro y cuenta</h2>
            <p>
              Para usar NETO debés crear una cuenta proporcionando información veraz y actualizada. Sos responsable de mantener la confidencialidad de tu cuenta y contraseña, y de todas las actividades que ocurran bajo tu cuenta.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">3. Uso del servicio</h2>
            <p>
              Te comprometés a usar NETO de acuerdo con las leyes aplicables y de manera que no infrinja los derechos de terceros. No está permitido usar el servicio para actividades ilegales, spam o contenido perjudicial.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">4. Planes y pago</h2>
            <p>
              NETO ofrece un período de prueba gratuito de 7 días. Luego, podés elegir un plan de suscripción de $19 USD/mes. Los pagos se procesan mensualmente y podés cancelar en cualquier momento desde tu panel de control.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">5. Propiedad intelectual</h2>
            <p>
              NETO y todos sus componentes son propiedad de Juvi Web. No podés copiar, modificar o distribuir nuestro código, tecnología o contenido sin autorización expresa.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">6. Limitación de responsabilidad</h2>
            <p>
              NETO se proporciona "tal cual". No garantizamos que el servicio esté libre de errores o interrupciones. No seremos responsables por daños indirectos, incidentales o consecuenciales.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">7. Modificaciones</h2>
            <p>
              Podemos modificar estos términos en cualquier momento. Las modificaciones entrarán en vigor al publicarlas. El uso continuado del servicio después de los cambios implica aceptación.
            </p>

            <h2 className="text-xl font-semibold text-gray-900 mt-8">8. Contacto</h2>
            <p>
              Para preguntas sobre estos términos, contactanos en hola@neto.cloud.
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
