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
import { Complaint, documentTypeLabels } from "@/types";
import { useApp } from "@/contexts/AppContext";
import { toast } from "sonner";
import { Eye, Send, Loader2 } from "lucide-react";
import { getComplaintDetails, type ComplaintDetails } from "@/lib/api";
import { useEffect } from "react";
import { TablePagination } from "./TablePagination";
import { usePagination } from "@/hooks/usePagination";

interface ComplaintsTableProps {
  complaints: Complaint[];
  enablePagination?: boolean;
  itemsPerPage?: number;
}

export function ComplaintsTable({ 
  complaints,
  enablePagination = false,
  itemsPerPage = 10,
}: ComplaintsTableProps) {
  const { respondToComplaint } = useApp();
  const [selectedComplaint, setSelectedComplaint] = useState<Complaint | null>(null);
  const [complaintDetails, setComplaintDetails] = useState<ComplaintDetails | null>(null);
  const [isLoadingDetails, setIsLoadingDetails] = useState(false);
  const [isResponding, setIsResponding] = useState(false);
  const [response, setResponse] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const pagination = usePagination({
    data: complaints,
    itemsPerPage: enablePagination ? itemsPerPage : complaints.length,
  });

  const displayComplaints = enablePagination ? pagination.paginatedData : complaints;

  // Fetch complaint details when a complaint is selected
  useEffect(() => {
    if (selectedComplaint) {
      setIsLoadingDetails(true);
      getComplaintDetails(selectedComplaint.id)
        .then((details) => {
          setComplaintDetails(details);
        })
        .catch((error) => {
          console.error("Erreur lors du chargement des détails:", error);
          toast.error("Impossible de charger les détails de la réclamation");
          setComplaintDetails(null);
        })
        .finally(() => {
          setIsLoadingDetails(false);
        });
    } else {
      setComplaintDetails(null);
    }
  }, [selectedComplaint]);

  const handleViewDetails = (complaint: Complaint) => {
    setSelectedComplaint(complaint);
    setIsResponding(false);
    setResponse("");
  };

  const handleStartResponse = () => {
    setIsResponding(true);
  };

  const handleSubmitResponse = async () => {
    if (!complaintDetails || !response.trim()) return;

    setIsSubmitting(true);
    try {
      await respondToComplaint(complaintDetails.id, response);
      toast.success("Réponse envoyée avec succès");
      setSelectedComplaint(null);
      setComplaintDetails(null);
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
            {displayComplaints.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="h-24 text-center text-muted-foreground">
                  Aucune réclamation trouvée
                </TableCell>
              </TableRow>
            ) : (
              displayComplaints.map((complaint) => (
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

      {/* Pagination */}
      {enablePagination && (
        <TablePagination
          currentPage={pagination.currentPage}
          totalPages={pagination.totalPages}
          onPageChange={pagination.goToPage}
          onPrevious={pagination.previousPage}
          onNext={pagination.nextPage}
          canGoPrevious={pagination.canGoPrevious}
          canGoNext={pagination.canGoNext}
          startIndex={pagination.startIndex}
          endIndex={pagination.endIndex}
          totalItems={pagination.totalItems}
        />
      )}

      {/* Complaint Detail Dialog */}
      <Dialog open={!!selectedComplaint} onOpenChange={() => setSelectedComplaint(null)}>
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="font-display">Détails de la réclamation</DialogTitle>
            <DialogDescription>
              Soumise le {selectedComplaint && format(new Date(selectedComplaint.createdAt), "dd MMMM yyyy à HH:mm", { locale: fr })}
            </DialogDescription>
          </DialogHeader>
          {isLoadingDetails ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
              <span className="ml-2 text-muted-foreground">Chargement des détails...</span>
            </div>
          ) : complaintDetails ? (
            <div className="space-y-4">
              {/* Student Information */}
              <div className="rounded-lg border border-border bg-card p-4">
                <h3 className="font-semibold mb-3">Informations de l'étudiant</h3>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <p className="text-sm text-muted-foreground">Nom complet</p>
                    <p className="font-medium">{complaintDetails.student.firstName} {complaintDetails.student.lastName}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Email</p>
                    <p className="font-medium">{complaintDetails.student.email}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">N° Apogée</p>
                    <p className="font-mono">{complaintDetails.student.apogee}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">CIN</p>
                    <p className="font-mono">{complaintDetails.student.cin}</p>
                  </div>
                  {complaintDetails.student.level && (
                    <div>
                      <p className="text-sm text-muted-foreground">Niveau</p>
                      <p className="font-medium">{complaintDetails.student.level}</p>
                    </div>
                  )}
                </div>
              </div>

              {/* Related Document Information */}
              {complaintDetails.relatedRequest && (
                <div className="rounded-lg border border-primary/20 bg-primary/5 p-4">
                  <h3 className="font-semibold mb-3">Document concerné par la réclamation</h3>
                  <div className="space-y-3">
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <p className="text-sm text-muted-foreground">N° de référence</p>
                        <p className="font-mono font-medium">{complaintDetails.relatedRequest.referenceNumber}</p>
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">Type de document</p>
                        <p className="font-medium">{documentTypeLabels[complaintDetails.relatedRequest.documentType as keyof typeof documentTypeLabels]}</p>
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">Statut</p>
                        <Badge
                          variant="outline"
                          className={
                            complaintDetails.relatedRequest.status === "pending"
                              ? "bg-warning/10 text-warning border-warning/20"
                              : complaintDetails.relatedRequest.status === "accepted"
                              ? "bg-success/10 text-success border-success/20"
                              : "bg-muted"
                          }
                        >
                          {complaintDetails.relatedRequest.status === "pending"
                            ? "En attente"
                            : complaintDetails.relatedRequest.status === "accepted"
                            ? "Traitée"
                            : complaintDetails.relatedRequest.status}
                        </Badge>
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground">Date de demande</p>
                        <p className="font-medium">
                          {format(new Date(complaintDetails.relatedRequest.requestDate), "dd MMMM yyyy", { locale: fr })}
                        </p>
                      </div>
                    </div>

                    {/* Document-specific details */}
                    {complaintDetails.relatedRequest.academicYear && (
                      <div>
                        <p className="text-sm text-muted-foreground">Année universitaire</p>
                        <p className="font-medium">{complaintDetails.relatedRequest.academicYear}</p>
                      </div>
                    )}
                    {complaintDetails.relatedRequest.semester && (
                      <div>
                        <p className="text-sm text-muted-foreground">Semestre</p>
                        <p className="font-medium">{complaintDetails.relatedRequest.semester}</p>
                      </div>
                    )}
                    {complaintDetails.relatedRequest.companyName && (
                      <>
                        <div>
                          <p className="text-sm text-muted-foreground">Entreprise</p>
                          <p className="font-medium">{complaintDetails.relatedRequest.companyName}</p>
                        </div>
                        {complaintDetails.relatedRequest.companyAddress && (
                          <div>
                            <p className="text-sm text-muted-foreground">Adresse de l'entreprise</p>
                            <p className="font-medium">{complaintDetails.relatedRequest.companyAddress}</p>
                          </div>
                        )}
                        {complaintDetails.relatedRequest.stageSubject && (
                          <div>
                            <p className="text-sm text-muted-foreground">Sujet du stage</p>
                            <p className="font-medium">{complaintDetails.relatedRequest.stageSubject}</p>
                          </div>
                        )}
                        {complaintDetails.relatedRequest.startDate && complaintDetails.relatedRequest.endDate && (
                          <div className="grid grid-cols-2 gap-4">
                            <div>
                              <p className="text-sm text-muted-foreground">Date de début</p>
                              <p className="font-medium">
                                {format(new Date(complaintDetails.relatedRequest.startDate), "dd MMMM yyyy", { locale: fr })}
                              </p>
                            </div>
                            <div>
                              <p className="text-sm text-muted-foreground">Date de fin</p>
                              <p className="font-medium">
                                {format(new Date(complaintDetails.relatedRequest.endDate), "dd MMMM yyyy", { locale: fr })}
                              </p>
                            </div>
                          </div>
                        )}
                      </>
                    )}
                  </div>
                </div>
              )}

              {/* Complaint Details */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-muted-foreground">Statut de la réclamation</p>
                  <Badge
                    variant="outline"
                    className={
                      complaintDetails.status === "pending"
                        ? "bg-warning/10 text-warning border-warning/20"
                        : "bg-success/10 text-success border-success/20"
                    }
                  >
                    {complaintDetails.status === "pending" ? "En attente" : "Résolue"}
                  </Badge>
                </div>
              </div>

              <div>
                <p className="text-sm text-muted-foreground mb-1">Objet</p>
                <p className="font-medium">{complaintDetails.subject}</p>
              </div>

              <div>
                <p className="text-sm text-muted-foreground mb-1">Description</p>
                <div className="rounded-lg bg-muted/50 p-3">
                  <p className="text-sm whitespace-pre-wrap">{complaintDetails.description}</p>
                </div>
              </div>

              {complaintDetails.response && (
                <div>
                  <p className="text-sm text-muted-foreground mb-1">Réponse</p>
                  <div className="rounded-lg bg-success/5 border border-success/20 p-3">
                    <p className="text-sm whitespace-pre-wrap">{complaintDetails.response}</p>
                    {complaintDetails.respondedAt && (
                      <p className="text-xs text-muted-foreground mt-2">
                        Répondu le {format(new Date(complaintDetails.respondedAt), "dd MMMM yyyy à HH:mm", { locale: fr })}
                      </p>
                    )}
                  </div>
                </div>
              )}

              {complaintDetails.status === "pending" && isResponding && (
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
          ) : selectedComplaint ? (
            <div className="text-center py-4 text-muted-foreground">
              Impossible de charger les détails
            </div>
          ) : null}
          <DialogFooter>
            {complaintDetails?.status === "pending" && !isResponding && (
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
