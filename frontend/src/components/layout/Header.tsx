import { Link, useLocation } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { FileText, LogIn } from "lucide-react";
import logo from "@/assets/logo.png";

export function Header() {
  const location = useLocation();
  const isAdminRoute = location.pathname.startsWith("/admin");

  if (isAdminRoute) return null;

  return (
    <header className="sticky top-0 z-50 w-full border-b border-border/50 bg-card/80 backdrop-blur-md">
      <div className="container flex h-16 items-center justify-between">
        <Link to="/" className="flex items-center gap-3 group">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-transparent overflow-hidden transition-transform group-hover:scale-105">
            <img src={logo} alt="ENSA Tétouan" className="h-full w-full object-contain" />
          </div>
          <div className="flex flex-col">
            <span className="font-display text-lg font-semibold text-foreground">
              Espace Étudiant
            </span>
            <span className="text-xs text-muted-foreground">
              Portail des documents scolaires
            </span>
          </div>
        </Link>

        <nav className="hidden md:flex items-center gap-1">
          <Button
            variant={location.pathname === "/" ? "secondary" : "ghost"}
            asChild
            className="gap-2"
          >
            <Link 
              to="/#formulaire"
              onClick={(e) => {
                // Si on est déjà sur la page d'accueil, gérer le scroll manuellement
                if (location.pathname === "/") {
                  e.preventDefault();
                  const element = document.getElementById("formulaire");
                  if (element) {
                    element.scrollIntoView({ behavior: "smooth", block: "start" });
                  }
                }
              }}
            >
              <FileText className="h-4 w-4" />
              Demandes & Réclamations
            </Link>
          </Button>
        </nav>

        <Button variant="outline" asChild className="gap-2">
          <Link to="/admin/login">
            <LogIn className="h-4 w-4" />
            <span className="hidden sm:inline">Espace Admin</span>
          </Link>
        </Button>
      </div>
    </header>
  );
}
