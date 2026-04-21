"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Menu, X } from "lucide-react"

const navLinks = [
  { label: "Cómo funciona", href: "#funciones" },
  { label: "Precio", href: "#precio" },
  { label: "Preguntas", href: "#faq" },
]

export function Header() {
  const [isMenuOpen, setIsMenuOpen] = useState(false)

  return (
    <header className="fixed left-0 right-0 top-0 z-50 px-4 pt-4">
      <nav className="mx-auto flex max-w-7xl items-center justify-between rounded-2xl border border-gray-200 bg-white/80 px-4 py-3 shadow-lg shadow-black/5 backdrop-blur-xl md:px-6">
        {/* Logo */}
        <a href="#" className="flex items-center gap-2">
          <img src="/logo-horizontal.svg" alt="NETO" className="h-8 w-auto" />
        </a>

        {/* Desktop nav */}
        <div className="hidden items-center gap-8 md:flex">
          {navLinks.map((link) => (
            <a
              key={link.label}
              href={link.href}
              className="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900"
            >
              {link.label}
            </a>
          ))}
        </div>

        {/* Desktop CTA */}
        <div className="hidden items-center gap-3 md:flex">
          <a href="https://app.netoia.cloud/login">
            <Button variant="ghost" className="text-sm font-medium text-gray-600">
              Ingresar
            </Button>
          </a>
          <a href="https://app.netoia.cloud/register">
            <Button className="rounded-xl bg-[#0052CC] px-5 font-semibold text-white shadow-md shadow-[#0052CC]/20">
              Empezar gratis
            </Button>
          </a>
        </div>

        {/* Mobile menu button */}
        <button
          onClick={() => setIsMenuOpen(!isMenuOpen)}
          className="flex h-10 w-10 items-center justify-center rounded-xl text-gray-900 md:hidden"
        >
          {isMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
        </button>
      </nav>

      {/* Mobile menu */}
      {isMenuOpen && (
        <div className="mx-auto mt-2 max-w-7xl rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur-xl md:hidden">
          <div className="flex flex-col gap-2">
            {navLinks.map((link) => (
              <a
                key={link.label}
                href={link.href}
                onClick={() => setIsMenuOpen(false)}
                className="rounded-xl px-4 py-3 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900"
              >
                {link.label}
              </a>
            ))}
            <hr className="my-2 border-gray-200" />
            <a href="https://app.netoia.cloud/login" className="w-full">
              <Button variant="ghost" className="w-full justify-start px-4 text-sm font-medium">
                Ingresar
              </Button>
            </a>
            <a href="https://app.netoia.cloud/register" className="w-full">
              <Button className="w-full rounded-xl bg-[#0052CC] font-semibold text-white shadow-md shadow-[#0052CC]/20">
                Empezar gratis
              </Button>
            </a>
          </div>
        </div>
      )}
    </header>
  )
}