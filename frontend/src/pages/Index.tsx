import { UnifiedRequestForm } from "@/components/forms/UnifiedRequestForm";
import RequestTracker from "@/components/forms/RequestTracker";
import { FileText, MessageSquare, Shield, Clock } from "lucide-react";
import logo from "@/assets/logo.png";

const features = [
  {
    icon: FileText,
    title: "Documents variés",
    description: "Attestations, relevés de notes, conventions de stage",
  },
  {
    icon: Clock,
    title: "Traitement rapide",
    description: "Suivi en temps réel de vos demandes",
  },
  {
    icon: Shield,
    title: "Sécurisé",
    description: "Vos données sont protégées et vérifiées",
  },
  {
    icon: MessageSquare,
    title: "Support réactif",
    description: "Espace réclamation disponible 24/7",
  },
];

const Index = () => {
  return (
    <div className="min-h-screen bg-background">
      {/* Hero Section */}
      <section className="relative overflow-hidden bg-gradient-to-b from-primary to-navy-dark py-20 lg:py-28">
        <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23ffffff%22%20fill-opacity%3D%220.03%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-50" />
        <div className="container relative">
          <div className="mx-auto max-w-3xl text-center">
            {/* Logo */}
            <h1 className="font-display text-4xl font-bold tracking-tight text-primary-foreground sm:text-5xl lg:text-6xl animate-fade-in">
              Portail des Documents
              <span className="block text-navy-light">Scolaires</span>
            </h1>
            <p className="mt-6 text-lg text-primary-foreground/80 animate-slide-up" style={{ animationDelay: "0.2s" }}>
              Demandez vos attestations, relevés de notes et conventions de stage
              en quelques clics. Service rapide et sécurisé pour tous les étudiants.
            </p>
          </div>

          {/* Feature cards */}
          <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 animate-slide-up" style={{ animationDelay: "0.4s" }}>
            {features.map((feature, index) => {
              const Icon = feature.icon;
              return (
                <div
                  key={index}
                  className="group rounded-xl bg-primary-foreground/10 backdrop-blur-sm border border-primary-foreground/10 p-5 transition-all duration-300 hover:bg-primary-foreground/15"
                >
                  <Icon className="h-8 w-8 text-navy-light mb-3" />
                  <h3 className="font-semibold text-primary-foreground">{feature.title}</h3>
                  <p className="mt-1 text-sm text-primary-foreground/70">{feature.description}</p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Tracking Section */}
      <section className="py-12 lg:py-16 bg-muted/30">
        <div className="container max-w-2xl">
          <RequestTracker />
        </div>
      </section>

      {/* Form Section */}
      <section id="formulaire" className="py-16 lg:py-24 scroll-mt-20">
        <div className="container">
          <UnifiedRequestForm />
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-border bg-muted/30 py-8">
        <div className="container text-center">
          <p className="text-sm text-muted-foreground">
            © 2025 Espace Étudiant - Portail des documents scolaires. Tous droits réservés.
          </p>
        </div>
      </footer>
    </div>
  );
};

export default Index;
