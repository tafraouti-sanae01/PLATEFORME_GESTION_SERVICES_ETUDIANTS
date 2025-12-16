import { ComplaintForm } from "@/components/forms/ComplaintForm";
import { MessageSquare, HelpCircle, Clock, CheckCircle } from "lucide-react";

const steps = [
  {
    icon: MessageSquare,
    title: "Soumettez",
    description: "Décrivez votre problème",
  },
  {
    icon: Clock,
    title: "Traitement",
    description: "Nous analysons votre demande",
  },
  {
    icon: CheckCircle,
    title: "Réponse",
    description: "Recevez une réponse par email",
  },
];

export default function Reclamation() {
  return (
    <div className="min-h-screen bg-background">
      {/* Hero Section */}
      <section className="relative overflow-hidden bg-gradient-to-b from-secondary to-navy-medium py-16 lg:py-20">
        <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23ffffff%22%20fill-opacity%3D%220.03%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-50" />
        <div className="container relative">
          <div className="mx-auto max-w-2xl text-center">
            <div className="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-secondary-foreground/10 backdrop-blur-sm">
              <HelpCircle className="h-8 w-8 text-secondary-foreground" />
            </div>
            <h1 className="font-display text-3xl font-bold tracking-tight text-secondary-foreground sm:text-4xl lg:text-5xl animate-fade-in">
              Espace Réclamation
            </h1>
            <p className="mt-4 text-lg text-secondary-foreground/80 animate-slide-up" style={{ animationDelay: "0.2s" }}>
              Vous rencontrez un problème ? Nous sommes là pour vous aider.
            </p>
          </div>

          {/* Steps */}
          <div className="mt-10 flex justify-center gap-8 animate-slide-up" style={{ animationDelay: "0.4s" }}>
            {steps.map((step, index) => {
              const Icon = step.icon;
              return (
                <div key={index} className="flex flex-col items-center">
                  <div className="flex h-12 w-12 items-center justify-center rounded-full bg-secondary-foreground/10 backdrop-blur-sm">
                    <Icon className="h-6 w-6 text-secondary-foreground" />
                  </div>
                  <p className="mt-2 text-sm font-medium text-secondary-foreground">{step.title}</p>
                  <p className="text-xs text-secondary-foreground/70">{step.description}</p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Form Section */}
      <section className="py-16 lg:py-24">
        <div className="container">
          <ComplaintForm />
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
}
