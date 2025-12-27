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

import { DocumentRequest, documentTypeLabels, statusLabels } from "@/types";
import { useApp } from "@/contexts/AppContext";
import { sendEmailToStudent, downloadDocument } from "@/lib/api";
import { toast } from "sonner";
import {
  CheckCircle,
  XCircle,
  Download,
  Eye,
  Loader2,
  Mail,
} from "lucide-react";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { TablePagination } from "./TablePagination";
import { usePagination } from "@/hooks/usePagination";

interface RequestsTableProps {
  requests: DocumentRequest[];
  showActions?: boolean;
  enablePagination?: boolean;
  itemsPerPage?: number;
  historyMode?: boolean;
}

const statusColors = {
  pending: "bg-warning/10 text-warning border-warning/20",
  accepted: "bg-success/10 text-success border-success/20",
  rejected: "bg-destructive/10 text-destructive border-destructive/20",
};

export function RequestsTable({
  requests,
  showActions = true,
  enablePagination = false,
  itemsPerPage = 10,
  historyMode = false,
}: RequestsTableProps) {
  const { updateRequestStatus } = useApp();
  const [selectedRequest, setSelectedRequest] = useState<DocumentRequest | null>(null);
  const [sendingEmail, setSendingEmail] = useState<string | null>(null);
  const [downloading, setDownloading] = useState<string | null>(null);
  const [rejectDialogOpen, setRejectDialogOpen] = useState(false);
  const [requestToReject, setRequestToReject] = useState<DocumentRequest | null>(null);
  const [rejectionReason, setRejectionReason] = useState("");
  const [isRejecting, setIsRejecting] = useState(false);

  const pagination = usePagination({
    data: requests,
    itemsPerPage: enablePagination ? itemsPerPage : requests.length,
  });

  const displayRequests = enablePagination ? pagination.paginatedData : requests;

  const handleAccept = async (id: string) => {
    try {
      await updateRequestStatus(id, "accepted");
      toast.success("Demande acceptée avec succès. L'email avec le document a été envoyé automatiquement à l'étudiant.");
    } catch (error) {
      console.error("Erreur lors de l'acceptation:", error);
      toast.error("Erreur lors de l'acceptation de la demande");
    }
  };

  const handleRejectClick = (request: DocumentRequest) => {
    setRequestToReject(request);
    setRejectionReason("");
    setRejectDialogOpen(true);
  };

  const handleReject = async () => {
    if (!requestToReject) return;

    setIsRejecting(true);
    try {
      await updateRequestStatus(requestToReject.id, "rejected", undefined, rejectionReason.trim() || undefined);
      toast.success("Demande refusée. L'email avec les raisons du refus a été envoyé automatiquement à l'étudiant.");
      setRejectDialogOpen(false);
      setRequestToReject(null);
      setRejectionReason("");
    } catch (error) {
      console.error("Erreur lors du refus:", error);
      toast.error("Erreur lors du refus de la demande");
    } finally {
      setIsRejecting(false);
    }
  };

  const handleSendEmail = async (request: DocumentRequest) => {
    setSendingEmail(request.id);
    try {
      const result = await sendEmailToStudent(request.id);
      if (result.sent) {
        toast.success(`Email envoyé avec succès à ${request.student.email}`);
      } else {
        toast.warning(`Email non envoyé: ${result.message}`);
      }
    } catch (error) {
      console.error("Erreur lors de l'envoi de l'email:", error);
      toast.error("Erreur lors de l'envoi de l'email");
    } finally {
      setSendingEmail(null);
    }
  };

  const handleDownload = async (request: DocumentRequest) => {
    // Check removed to allow download for all statuses as requested
    /*
    if (request.status !== "accepted") {
      toast.error("Le document n'est disponible qu'après acceptation de la demande");
      return;
    }
    */

    setDownloading(request.id);
    try {
      const blob = await downloadDocument(request.id);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `document_${request.referenceNumber}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
      toast.success("Document téléchargé avec succès");
    } catch (error) {
      console.error("Erreur lors du téléchargement:", error);
      toast.error("Erreur lors du téléchargement du document");
    } finally {
      setDownloading(null);
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
              <TableHead className="font-semibold">N° Apogée</TableHead>
              <TableHead className="font-semibold">Type de document</TableHead>
              <TableHead className="font-semibold">Statut</TableHead>
              {(showActions || historyMode) && <TableHead className="text-right font-semibold">Actions</TableHead>}
            </TableRow>
          </TableHeader>
          <TableBody>
            {displayRequests.length === 0 ? (
              <TableRow>
                <TableCell colSpan={(showActions || historyMode) ? 7 : 6} className="h-24 text-center text-muted-foreground">
                  Aucune demande trouvée
                </TableCell>
              </TableRow>
            ) : (
              displayRequests.map((request) => (
                <TableRow key={request.id} className="hover:bg-muted/30 transition-colors">
                  <TableCell className="text-muted-foreground">
                    {format(new Date(request.createdAt), "dd MMM yyyy", { locale: fr })}
                  </TableCell>
                  <TableCell className="font-mono text-sm">{request.referenceNumber}</TableCell>
                  <TableCell className="font-medium">
                    {request.student.firstName} {request.student.lastName}
                  </TableCell>
                  <TableCell className="font-mono text-sm">{request.student.apogee}</TableCell>
                  <TableCell>{documentTypeLabels[request.documentType]}</TableCell>
                  <TableCell>
                    <Badge variant="outline" className={statusColors[request.status]}>
                      {statusLabels[request.status]}
                    </Badge>
                  </TableCell>
                  {(showActions || historyMode) && (
                    <TableCell className="text-right">
                      <TooltipProvider delayDuration={0}>
                        <div className="flex items-center justify-end gap-1">
                          {historyMode ? (
                            <>
                              {(request.status === "rejected" || request.status === "accepted") && (
                                <Tooltip>
                                  <TooltipTrigger asChild>
                                    <Button
                                      variant="ghost"
                                      size="icon"
                                      onClick={() => setSelectedRequest(request)}
                                      className="h-8 w-8 text-muted-foreground hover:text-foreground"
                                    >
                                      <Eye className="h-4 w-4" />
                                      <span className="sr-only">Voir détails</span>
                                    </Button>
                                  </TooltipTrigger>
                                  <TooltipContent>Voir détails</TooltipContent>
                                </Tooltip>
                              )}

                              {request.status === "accepted" && (
                                <>
                                  <Tooltip>
                                    <TooltipTrigger asChild>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleDownload(request)}
                                        disabled={downloading === request.id}
                                        className="h-8 w-8 text-muted-foreground hover:text-foreground"
                                      >
                                        {downloading === request.id ? (
                                          <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : (
                                          <Download className="h-4 w-4" />
                                        )}
                                        <span className="sr-only">Télécharger</span>
                                      </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Télécharger</TooltipContent>
                                  </Tooltip>

                                  <Tooltip>
                                    <TooltipTrigger asChild>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleSendEmail(request)}
                                        disabled={sendingEmail === request.id}
                                        className="h-8 w-8 text-muted-foreground hover:text-foreground"
                                      >
                                        {sendingEmail === request.id ? (
                                          <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : (
                                          <Mail className="h-4 w-4" />
                                        )}
                                        <span className="sr-only">Envoyer le document</span>
                                      </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Envoyer le document</TooltipContent>
                                  </Tooltip>
                                </>
                              )}
                            </>
                          ) : (
                            <>
                              <Tooltip>
                                <TooltipTrigger asChild>
                                  <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => setSelectedRequest(request)}
                                    className="h-8 w-8 text-muted-foreground hover:text-foreground"
                                  >
                                    <Eye className="h-4 w-4" />
                                    <span className="sr-only">Voir détails</span>
                                  </Button>
                                </TooltipTrigger>
                                <TooltipContent>Voir détails</TooltipContent>
                              </Tooltip>

                              {request.status === "pending" && (
                                <>
                                  <Tooltip>
                                    <TooltipTrigger asChild>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleAccept(request.id)}
                                        className="h-8 w-8 text-success hover:text-success hover:bg-success/10"
                                      >
                                        <CheckCircle className="h-4 w-4" />
                                        <span className="sr-only">Valider</span>
                                      </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Valider</TooltipContent>
                                  </Tooltip>

                                  <Tooltip>
                                    <TooltipTrigger asChild>
                                      <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleRejectClick(request)}
                                        className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                      >
                                        <XCircle className="h-4 w-4" />
                                        <span className="sr-only">Refuser</span>
                                      </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Refuser</TooltipContent>
                                  </Tooltip>
                                </>
                              )}

                              <Tooltip>
                                <TooltipTrigger asChild>
                                  <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => handleDownload(request)}
                                    disabled={downloading === request.id}
                                    className="h-8 w-8 text-muted-foreground hover:text-foreground"
                                  >
                                    {downloading === request.id ? (
                                      <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : (
                                      <Download className="h-4 w-4" />
                                    )}
                                    <span className="sr-only">Télécharger</span>
                                  </Button>
                                </TooltipTrigger>
                                <TooltipContent>Télécharger</TooltipContent>
                              </Tooltip>

                              {request.status === "accepted" && (
                                <Tooltip>
                                  <TooltipTrigger asChild>
                                    <Button
                                      variant="ghost"
                                      size="icon"
                                      onClick={() => handleDownload(request)}
                                      disabled={downloading === request.id}
                                      className="h-8 w-8 text-muted-foreground hover:text-foreground"
                                    >
                                      {downloading === request.id ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                      ) : (
                                        <Download className="h-4 w-4" />
                                      )}
                                      <span className="sr-only">Télécharger</span>
                                    </Button>
                                  </TooltipTrigger>
                                  <TooltipContent>Télécharger (Copie)</TooltipContent>
                                </Tooltip>
                              )}
                            </>
                          )}
                        </div>
                      </TooltipProvider>
                    </TableCell>
                  )}
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

      {/* Request Detail Dialog */}
      <Dialog open={!!selectedRequest} onOpenChange={() => setSelectedRequest(null)}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle className="font-display">Détails de la demande</DialogTitle>
            <DialogDescription>
              Référence: {selectedRequest?.referenceNumber}
            </DialogDescription>
          </DialogHeader>
          {selectedRequest && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-muted-foreground">Étudiant</p>
                  <p className="font-medium">
                    {selectedRequest.student.firstName} {selectedRequest.student.lastName}
                  </p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Email</p>
                  <p className="font-medium">{selectedRequest.student.email}</p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">N° Apogée</p>
                  <p className="font-mono">{selectedRequest.student.apogee}</p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">CIN</p>
                  <p className="font-mono">{selectedRequest.student.cin}</p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Type de document</p>
                  <p className="font-medium">{documentTypeLabels[selectedRequest.documentType]}</p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Statut</p>
                  <Badge variant="outline" className={statusColors[selectedRequest.status]}>
                    {statusLabels[selectedRequest.status]}
                  </Badge>
                </div>
              </div>

              {/* Informations spécifiques selon le type de document */}
              {selectedRequest.documentType === "releve_notes" && (
                <div className="rounded-lg border border-border p-4 space-y-3">
                  <h4 className="font-medium">Informations du relevé de notes</h4>
                  <div className="grid grid-cols-2 gap-3 text-sm">
                    {selectedRequest.academicYear && (
                      <div>
                        <p className="text-muted-foreground">Année universitaire</p>
                        <p className="font-medium">{selectedRequest.academicYear}</p>
                      </div>
                    )}
                    {selectedRequest.semester && (
                      <div>
                        <p className="text-muted-foreground">Semestre</p>
                        <p className="font-medium">{selectedRequest.semester}</p>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {selectedRequest.documentType === "attestation_reussite" && (
                <div className="rounded-lg border border-border p-4 space-y-3">
                  <h4 className="font-medium">Informations de l'attestation de réussite</h4>
                  <div className="grid grid-cols-2 gap-3 text-sm">
                    {selectedRequest.academicYear && (
                      <div>
                        <p className="text-muted-foreground">Année universitaire</p>
                        <p className="font-medium">{selectedRequest.academicYear}</p>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {selectedRequest.documentType === "convention_stage" && (
                <div className="rounded-lg border border-border p-4 space-y-3">
                  <h4 className="font-medium">Informations du stage</h4>
                  <div className="grid grid-cols-2 gap-3 text-sm">
                    {selectedRequest.companyName && (
                      <div>
                        <p className="text-muted-foreground">Entreprise</p>
                        <p className="font-medium">{selectedRequest.companyName}</p>
                      </div>
                    )}
                    {selectedRequest.companyAddress && (
                      <div>
                        <p className="text-muted-foreground">Adresse de l'entreprise</p>
                        <p className="font-medium">{selectedRequest.companyAddress}</p>
                      </div>
                    )}
                    {selectedRequest.supervisorName && (
                      <div>
                        <p className="text-muted-foreground">Responsable entreprise</p>
                        <p className="font-medium">{selectedRequest.supervisorName}</p>
                      </div>
                    )}
                    {selectedRequest.supervisorEmail && (
                      <div>
                        <p className="text-muted-foreground">Email responsable</p>
                        <p className="font-medium">{selectedRequest.supervisorEmail}</p>
                      </div>
                    )}
                    {selectedRequest.supervisorPhone && (
                      <div>
                        <p className="text-muted-foreground">Téléphone responsable</p>
                        <p className="font-medium">{selectedRequest.supervisorPhone}</p>
                      </div>
                    )}
                    {selectedRequest.academicSupervisor && (
                      <div>
                        <p className="text-muted-foreground">Encadrant académique</p>
                        <p className="font-medium">{selectedRequest.academicSupervisor}</p>
                      </div>
                    )}
                    {(selectedRequest.stageStartDate || selectedRequest.stageEndDate) && (
                      <div>
                        <p className="text-muted-foreground">Période du stage</p>
                        <p className="font-medium">
                          {selectedRequest.stageStartDate && format(new Date(selectedRequest.stageStartDate), "dd/MM/yyyy")} 
                          {selectedRequest.stageStartDate && selectedRequest.stageEndDate && " - "}
                          {selectedRequest.stageEndDate && format(new Date(selectedRequest.stageEndDate), "dd/MM/yyyy")}
                        </p>
                      </div>
                    )}
                    {selectedRequest.stageSubject && (
                      <div className="col-span-2">
                        <p className="text-muted-foreground">Sujet du stage</p>
                        <p className="font-medium">{selectedRequest.stageSubject}</p>
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* Reject Request Dialog */}
      <Dialog open={rejectDialogOpen} onOpenChange={setRejectDialogOpen}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle className="font-display">Refuser la demande</DialogTitle>
            <DialogDescription>
              Veuillez indiquer les raisons du refus. Un email sera envoyé automatiquement à l'étudiant.
            </DialogDescription>
          </DialogHeader>
          {requestToReject && (
            <div className="space-y-4">
              <div className="rounded-lg border border-border bg-muted/30 p-4">
                <div className="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <p className="text-muted-foreground">Étudiant</p>
                    <p className="font-medium">
                      {requestToReject.student.firstName} {requestToReject.student.lastName}
                    </p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">N° Référence</p>
                    <p className="font-mono">{requestToReject.referenceNumber}</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">Type de document</p>
                    <p className="font-medium">{documentTypeLabels[requestToReject.documentType]}</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">Email</p>
                    <p className="font-medium">{requestToReject.student.email}</p>
                  </div>
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="rejection-reason">
                  Raisons du refus <span className="text-muted-foreground"></span>
                </Label>
                <Textarea
                  id="rejection-reason"
                  placeholder="Ex: Document incomplet, informations manquantes, non conforme aux exigences..."
                  rows={6}
                  value={rejectionReason}
                  onChange={(e) => setRejectionReason(e.target.value)}
                  className="resize-none"
                />
                <p className="text-xs text-muted-foreground">
                  Ce message sera inclus dans l'email envoyé à l'étudiant pour justifier le refus de sa demande.
                </p>
              </div>
            </div>
          )}
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setRejectDialogOpen(false);
                setRequestToReject(null);
                setRejectionReason("");
              }}
              disabled={isRejecting}
            >
              Annuler
            </Button>
            <Button
              variant="destructive"
              onClick={handleReject}
              disabled={isRejecting}
              className="gap-2"
            >
              {isRejecting ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Refus en cours...
                </>
              ) : (
                <>
                  <XCircle className="h-4 w-4" />
                  Confirmer le refus
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}