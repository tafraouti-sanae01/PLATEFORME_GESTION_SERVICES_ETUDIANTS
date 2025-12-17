import { useState } from "react";
import { useApp } from "@/contexts/AppContext";
import { AdminLayout } from "@/components/layout/AdminLayout";
import { RequestsTable } from "@/components/admin/RequestsTable";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { DocumentType, documentTypeLabels, RequestStatus, statusLabels } from "@/types";
import { Search, Filter, RotateCcw } from "lucide-react";

// Only document types, exclude reclamation
const documentTypes: Exclude<DocumentType, "reclamation">[] = [
  "attestation_scolarite",
  "attestation_reussite",
  "releve_notes",
  "convention_stage"
];

export default function AdminHistorique() {
  const { requests } = useApp();
  const [searchTerm, setSearchTerm] = useState("");
  const [documentTypeFilter, setDocumentTypeFilter] = useState<string>("all");
  const [statusFilter, setStatusFilter] = useState<string>("all");

  // Filter out reclamation from requests and only show accepted and rejected requests in history, sorted by date (newest first)
  const processedRequests = requests
    .filter(
      (r) => r.documentType !== "reclamation" && (r.status === "accepted" || r.status === "rejected")
    )
    .sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime());

  const filteredRequests = processedRequests.filter((request) => {
    const matchesSearch =
      request.student.firstName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.student.lastName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.student.apogee.includes(searchTerm) ||
      request.student.cin.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.referenceNumber.toLowerCase().includes(searchTerm.toLowerCase());

    const matchesType =
      documentTypeFilter === "all" || request.documentType === documentTypeFilter;

    const matchesStatus = statusFilter === "all" || request.status === statusFilter;

    return matchesSearch && matchesType && matchesStatus;
  });

  const resetFilters = () => {
    setSearchTerm("");
    setDocumentTypeFilter("all");
    setStatusFilter("all");
  };

  const hasActiveFilters =
    searchTerm || documentTypeFilter !== "all" || statusFilter !== "all";

  return (
    <AdminLayout>
      <div className="space-y-6">
        {/* Header */}
        <div>
          <h1 className="font-display text-3xl font-bold text-foreground">Historique</h1>
          <p className="text-muted-foreground">
            Consultez l'historique complet des demandes traitées
          </p>
        </div>

        {/* Filters */}
        <Card className="shadow-elegant">
          <CardHeader className="pb-4">
            <div className="flex items-center gap-2">
              <Filter className="h-5 w-5 text-muted-foreground" />
              <CardTitle className="font-display text-lg">Filtres avancés</CardTitle>
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div className="space-y-2">
                <Label>Recherche</Label>
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                  <Input
                    placeholder="Nom, Apogée, CIN..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-10"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Type de document</Label>
                <Select
                  value={documentTypeFilter}
                  onValueChange={setDocumentTypeFilter}
                >
                  <SelectTrigger className="bg-background">
                    <SelectValue placeholder="Tous les types" />
                  </SelectTrigger>
                  <SelectContent className="bg-popover z-50">
                    <SelectItem value="all">Tous les types</SelectItem>
                    {documentTypes.map((type) => (
                      <SelectItem key={type} value={type}>
                        {documentTypeLabels[type]}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Statut</Label>
                <Select value={statusFilter} onValueChange={setStatusFilter}>
                  <SelectTrigger className="bg-background">
                    <SelectValue placeholder="Tous les statuts" />
                  </SelectTrigger>
                  <SelectContent className="bg-popover z-50">
                    <SelectItem value="all">Tous les statuts</SelectItem>
                    <SelectItem value="accepted">{statusLabels.accepted}</SelectItem>
                    <SelectItem value="rejected">{statusLabels.rejected}</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="flex items-end">
                <Button
                  variant="outline"
                  onClick={resetFilters}
                  disabled={!hasActiveFilters}
                  className="w-full gap-2"
                >
                  <RotateCcw className="h-4 w-4" />
                  Réinitialiser
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Results */}
        <Card className="shadow-elegant">
          <CardHeader>
            <CardTitle className="font-display">Demandes traitées</CardTitle>
            <CardDescription>
              {filteredRequests.length} résultat(s) sur {processedRequests.length} demandes
            </CardDescription>
          </CardHeader>
          <CardContent>
            <RequestsTable 
              requests={filteredRequests} 
              showActions={false}
              historyMode={true}
              enablePagination={true}
              itemsPerPage={6}
            />
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  );
}
