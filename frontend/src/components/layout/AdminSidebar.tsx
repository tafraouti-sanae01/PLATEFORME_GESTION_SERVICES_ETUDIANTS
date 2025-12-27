import { Link, useLocation, useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { useApp } from "@/contexts/AppContext";
import {
  LayoutDashboard,
  FileText,
  History,
  MessageSquare,
  LogOut,
} from "lucide-react";
import { cn } from "@/lib/utils";
import logo from "@/assets/logo.png";

const navItems = [
  { path: "/admin/dashboard", label: "Dashboard", icon: LayoutDashboard },
  { path: "/admin/demandes", label: "Demandes", icon: FileText },
  { path: "/admin/historique", label: "Historique", icon: History },
  { path: "/admin/reclamations", label: "Réclamations", icon: MessageSquare },
];

export function AdminSidebar() {
  const location = useLocation();
  const navigate = useNavigate();
  const { logoutAdmin, currentAdmin } = useApp();

  const handleLogout = () => {
    logoutAdmin();
    navigate("/");
  };

  return (
    <aside className="fixed left-0 top-0 z-40 h-screen w-64 border-r border-border bg-card">
      <div className="flex h-full flex-col">
        {/* Header */}
        <div className="flex h-16 items-center gap-3 border-b border-border px-4">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-transparent overflow-hidden">
            <img src={logo} alt="ENSA Tétouan" className="h-full w-full object-contain" />
          </div>
          <div className="flex flex-col">
            <span className="font-display text-sm font-semibold text-foreground">
              Administration
            </span>
            <span className="text-xs text-muted-foreground">
              Gestion des documents
            </span>
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex-1 space-y-1 p-4">
          {navItems.map((item) => {
            const Icon = item.icon;
            const isActive = location.pathname === item.path;
            return (
              <Link
                key={item.path}
                to={item.path}
                className={cn(
                  "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200",
                  isActive
                    ? "bg-primary text-primary-foreground shadow-elegant"
                    : "text-muted-foreground hover:bg-muted hover:text-foreground"
                )}
              >
                <Icon className="h-5 w-5" />
                {item.label}
              </Link>
            );
          })}
        </nav>

        {/* Footer */}
        <div className="border-t border-border p-4">
          <div className="mb-3 rounded-lg bg-muted/50 p-3">
            <p className="text-xs text-muted-foreground">Connecté en tant que</p>
            <p className="text-sm font-medium text-foreground">
              {currentAdmin?.name || "Administrateur"}
            </p>
          </div>
          <Button
            variant="outline"
            className="w-full gap-2"
            onClick={handleLogout}
          >
            <LogOut className="h-4 w-4" />
            Déconnexion
          </Button>
        </div>
      </div>
    </aside>
  );
}
