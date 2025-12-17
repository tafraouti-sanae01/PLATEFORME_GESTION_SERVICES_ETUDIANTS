import { useState } from "react";
import { useApp } from "@/contexts/AppContext";
import { AdminLayout } from "@/components/layout/AdminLayout";
import { RequestsTable } from "@/components/admin/RequestsTable";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Input } from "@/components/ui/input";
import { DocumentType, documentTypeLabels } from "@/types";
import { Search, FileText, GraduationCap, Award, BookOpen, Briefcase } from "lucide-react";

// Only document types, exclude reclamation
const documentTypes: Exclude<DocumentType, "reclamation">[] = [
  "attestation_scolarite",
  "attestation_reussite",
  "releve_notes",
  "convention_stage"
];

const typeIcons: Record<Exclude<DocumentType, "reclamation">, React.ElementType> = {
  attestation_scolarite: GraduationCap,
  attestation_reussite: Award,
  releve_notes: BookOpen,
  convention_stage: Briefcase,
};

export default function AdminDemandes() {
  const { requests } = useApp();
  const [searchTerm, setSearchTerm] = useState("");
  const [activeTab, setActiveTab] = useState<"all" | Exclude<DocumentType, "reclamation">>("all");

  // Filter out reclamation and only show pending requests, sorted by date (newest first)
  const pendingDocumentRequests = requests
    .filter((r) => r.documentType !== "reclamation" && r.status === "pending")
    .sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime());

  const filteredRequests = pendingDocumentRequests.filter((request) => {
    const matchesSearch =
      request.student.firstName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.student.lastName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      request.student.apogee.includes(searchTerm) ||
      request.referenceNumber.toLowerCase().includes(searchTerm.toLowerCase());

    const matchesType = activeTab === "all" || request.documentType === activeTab;

    return matchesSearch && matchesType;
  });

  return (
    <AdminLayout>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="font-display text-3xl font-bold text-foreground">Demandes</h1>
            <p className="text-muted-foreground">
              Gérez toutes les demandes de documents
            </p>
          </div>

          <div className="relative w-full sm:w-72">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              placeholder="Rechercher..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10"
            />
          </div>
        </div>

        {/* Tabs by document type */}
        <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as typeof activeTab)}>
          <TabsList className="flex-wrap h-auto gap-2 bg-transparent p-0">
            <TabsTrigger
              value="all"
              className="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground rounded-lg border border-border px-4 py-2"
            >
              <FileText className="mr-2 h-4 w-4" />
              Toutes ({pendingDocumentRequests.length})
            </TabsTrigger>
            {documentTypes.map((type) => {
              const Icon = typeIcons[type];
              const count = pendingDocumentRequests.filter((r) => r.documentType === type).length;
              return (
                <TabsTrigger
                  key={type}
                  value={type}
                  className="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground rounded-lg border border-border px-4 py-2"
                >
                  <Icon className="mr-2 h-4 w-4" />
                  {documentTypeLabels[type]} ({count})
                </TabsTrigger>
              );
            })}
          </TabsList>

          <TabsContent value={activeTab} className="mt-6">
            <Card className="shadow-elegant">
              <CardHeader>
                <CardTitle className="font-display">
                  {activeTab === "all"
                    ? "Toutes les demandes"
                    : documentTypeLabels[activeTab]}
                </CardTitle>
                <CardDescription>
                  {filteredRequests.length} demande(s) en attente
                </CardDescription>
              </CardHeader>
              <CardContent>
                <RequestsTable 
                  requests={filteredRequests} 
                  enablePagination={true}
                  itemsPerPage={6}
                />
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </AdminLayout>
  );
}
