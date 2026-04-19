import { Header } from "@/components/landing/header"
import { HeroSection } from "@/components/landing/hero-section"
import { PainSection } from "@/components/landing/pain-section"
import { SolutionSection } from "@/components/landing/solution-section"
import { TargetSection } from "@/components/landing/target-section"
import { PricingSection } from "@/components/landing/pricing-section"
import { FAQSection } from "@/components/landing/faq-section"
import { CTASection } from "@/components/landing/cta-section"
import { Footer } from "@/components/landing/footer"

export default function Home() {
  return (
    <div className="min-h-screen bg-background">
      <Header />
      
      <main>
        <HeroSection />
        
        <PainSection />
        
        <section id="funciones">
          <SolutionSection />
        </section>
        
        <TargetSection />
        
        <section id="precio">
          <PricingSection />
        </section>
        
        <section id="faq">
          <FAQSection />
        </section>
        
        <CTASection />
      </main>
      
      <Footer />
    </div>
  )
}
