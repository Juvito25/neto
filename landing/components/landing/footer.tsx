import { MessageCircle } from "lucide-react"

const footerLinks = {
  Producto: [
    { label: "Cómo funciona", href: "#funciones" },
    { label: "Precio", href: "#precio" },
    { label: "Preguntas frecuentes", href: "#faq" },
  ],
  Empresa: [
    { label: "Contacto", href: "mailto:hola@neto.cloud" },
  ],
  Legal: [
    { label: "Privacidad", href: "/privacidad" },
    { label: "Términos", href: "/terminos" },
  ],
}

export function Footer() {
  return (
    <footer className="border-t border-gray-200 px-4 py-16">
      <div className="mx-auto max-w-7xl">
        <div className="grid gap-12 md:grid-cols-2 lg:grid-cols-5">
          {/* Brand */}
          <div className="lg:col-span-2">
            <a href="#" className="flex items-center gap-2">
              <img src="/logo-horizontal.svg" alt="NETO" className="h-7 w-auto" />
            </a>
            <p className="mt-4 max-w-xs text-sm text-gray-600">
              Tu vendedor virtual con IA para WhatsApp. Vende 24/7 mientras dormís.
            </p>
          </div>

          {/* Links */}
          {Object.entries(footerLinks).map(([category, links]) => (
            <div key={category}>
              <h3 className="mb-4 text-sm font-semibold text-gray-900">{category}</h3>
              <ul className="space-y-3">
                {links.map((link) => (
                  <li key={link.label}>
                    <a
                      href={link.href}
                      className="text-sm text-gray-600 transition-colors hover:text-gray-900"
                    >
                      {link.label}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        {/* Bottom */}
        <div className="mt-16 flex flex-col items-center justify-between gap-4 border-t border-gray-200 pt-8 md:flex-row">
          <p className="text-sm text-gray-500">
            © 2026 NETO. Todos los derechos reservados.
          </p>
          <a 
            href="https://juviweb.com" 
            target="_blank" 
            rel="noopener noreferrer"
            className="text-sm text-gray-500 hover:text-gray-900 transition-colors"
          >
            Desarrollado por <span className="font-medium text-gray-700">Juvi Web</span>
          </a>
        </div>
      </div>
    </footer>
  )
}