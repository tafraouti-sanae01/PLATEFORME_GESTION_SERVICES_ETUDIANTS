import { useState } from "react";
import { format } from "date-fns";
import { fr } from "date-fns/locale";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Complaint } from "@/types";
import { useApp } from "@/contexts/AppContext";
import { toast } from "sonner";
import { Eye, Send, Loader2 } from "lucide-react";

interface ComplaintsTableProps {
  complaints: Complaint[];
}

export function ComplaintsTable({ complaints }: ComplaintsTableProps) {
  const { respondToComplaint } = useApp();
  const [selectedComplaint, setSelectedComplaint] = useState<Complaint | null>(null);
  const [isResponding, setIsResponding] = useState(false);
  const [response, setResponse] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleViewDetails = (complaint: Complaint) => {
    setSelectedComplaint(complaint);
    setIsResponding(false);
    setResponse("");
  };

  const handleStartResponse = () => {
    setIsResponding(true);
  };

  const handleSubmitResponse = async () => {
    if (!selectedComplaint || !response.trim()) return;

    setIsSubmitting(true);
    try {
      await respondToComplaint(selectedComplaint.id, response);
      toast.success("Réponse envoyée avec succès");
      setSelectedComplaint(null);
      setIsResponding(false);
      setResponse("");
    } catch (error) {
      toast.error("Erreur lors de l'envoi de la réponse");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <>
      <div className="rounded-lg border border-border bg-card overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow className="bg-muted/50">
              <TableHead className="font-semibold">Date</TableHead>
              <TableHead className="font-semibold">N° Référence</TableHead>
              <TableHead className="font-semibold">Étudiant</TableHead>
              <TableHead className="font-semibold">Objet</TableHead>
              <TableHead className="font-semibold">Statut</TableHead>
              <TableHead className="text-right font-semibold">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {complaints.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="h-24 text-center text-muted-foreground">
                  Aucune réclamation trouvée
                </TableCell>
              </TableRow>
            ) : (
              complaints.map((complaint) => (
                <TableRow key={complaint.id} className="hover:bg-muted/30 transition-colors">
                  <TableCell className="text-muted-foreground">
                    {format(new Date(complaint.createdAt), "dd MMM yyyy", { locale: fr })}
                  </TableCell>
                  <TableCell className="font-mono text-sm">{complaint.referenceNumber}</TableCell>
                  <TableCell className="font-medium">{complaint.studentEmail}</TableCell>
                  <TableCell className="max-w-[200px] truncate">{complaint.subject}</TableCell>
                  <TableCell>
                    <Badge
                      variant="outline"
                      className={
                        complaint.status === "pending"
                          ? "bg-warning/10 text-warning border-warning/20"
                          : "bg-success/10 text-success border-success/20"
                      }
                    >
                      {complaint.status === "pending" ? "En attente" : "Résolue"}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-right">
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleViewDetails(complaint)}
                      className="gap-2"
                    >
                      <Eye className="h-4 w-4" />
                      Voir
                    </Button>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {/* Complaint Detail Dialog */}
      <Dialog open={!!selectedComplaint} onOpenChange={() => setSelectedComplaint(null)}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle className="font-display">Détails de la réclamation</DialogTitle>
            <DialogDescription>
              Soumise le {selectedComplaint && format(new Date(selectedComplaint.createdAt), "dd MMMM yyyy à HH:mm", { locale: fr })}
            </DialogDescription>
          </DialogHeader>
          {selectedComplaint && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-muted-foreground">Email</p>
                  <p className="font-medium">{selectedComplaint.studentEmail}</p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">N° Apogée</p>
                  <p className="font-mono">{selectedComplaint.apogee}</p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">CIN</p>
                  <p className="font-mono">{selectedComplaint.cin}</p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Statut</p>
                  <Badge
                    variant="outline"
                    className={
                      selectedComplaint.status === "pending"
                        ? "bg-warning/10 text-warning border-warning/20"
                        : "bg-success/10 text-success border-success/20"
                    }
                  >
                    {selectedComplaint.status === "pending" ? "En attente" : "Résolue"}
                  </Badge>
                </div>
              </div>

              <div>
                <p className="text-sm text-muted-foreground mb-1">Objet</p>
                <p className="font-medium">{selectedComplaint.subject}</p>
              </div>

              <div>
                <p className="text-sm text-muted-foreground mb-1">Description</p>
                <div className="rounded-lg bg-muted/50 p-3">
                  <p className="text-sm whitespace-pre-wrap">{selectedComplaint.description}</p>
                </div>
              </div>

              {selectedComplaint.response && (
                <div>
                  <p className="text-sm text-muted-foreground mb-1">Réponse</p>
                  <div className="rounded-lg bg-success/5 border border-success/20 p-3">
                    <p className="text-sm whitespace-pre-wrap">{selectedComplaint.response}</p>
                    {selectedComplaint.respondedAt && (
                      <p className="text-xs text-muted-foreground mt-2">
                        Répondu le {format(new Date(selectedComplaint.respondedAt), "dd MMMM yyyy à HH:mm", { locale: fr })}
                      </p>
                    )}
                  </div>
                </div>
              )}

              {selectedComplaint.status === "pending" && isResponding && (
                <div>
                  <p className="text-sm text-muted-foreground mb-1">Votre réponse</p>
                  <Textarea
                    placeholder="Rédigez votre réponse..."
                    rows={4}
                    value={response}
                    onChange={(e) => setResponse(e.target.value)}
                  />
                </div>
              )}
            </div>
          )}
          <DialogFooter>
            {selectedComplaint?.status === "pending" && !isResponding && (
              <Button onClick={handleStartResponse} className="gap-2">
                <Send className="h-4 w-4" />
                Répondre
              </Button>
            )}
            {isResponding && (
              <Button
                onClick={handleSubmitResponse}
                disabled={!response.trim() || isSubmitting}
                className="gap-2"
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    Envoi...
                  </>
                ) : (
                  <>
                    <Send className="h-4 w-4" />
                    Envoyer la réponse
                  </>
                )}
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
