import { useApp } from "@/contexts/AppContext";
import { AdminLayout } from "@/components/layout/AdminLayout";
import { StatsCard } from "@/components/admin/StatsCard";
import { RequestsTable } from "@/components/admin/RequestsTable";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { FileText, Clock, CheckCircle, XCircle, MessageSquare } from "lucide-react";
import { documentTypeLabels, DocumentType } from "@/types";

export default function AdminDashboard() {
  const { requests, complaints } = useApp();

  const stats = {
    total: requests.length,
    pending: requests.filter((r) => r.status === "pending").length,
    accepted: requests.filter((r) => r.status === "accepted").length,
    rejected: requests.filter((r) => r.status === "rejected").length,
    pendingComplaints: complaints.filter((c) => c.status === "pending").length,
  };

  const pendingPercentage = stats.total > 0 ? Math.round((stats.pending / stats.total) * 100) : 0;
  const acceptedPercentage = stats.total > 0 ? Math.round((stats.accepted / stats.total) * 100) : 0;
  const rejectedPercentage = stats.total > 0 ? Math.round((stats.rejected / stats.total) * 100) : 0;

  const recentPendingRequests = requests
    .filter((r) => r.documentType !== "reclamation" && r.status === "pending")
    .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
    .slice(0, 5);

  // Filter out reclamations from document requests
  const documentRequests = requests.filter((r) => r.documentType !== "reclamation");
  const totalDocumentRequests = documentRequests.length;

  // Group by document type (excluding reclamation)
  const byType = Object.keys(documentTypeLabels)
    .filter((type) => type !== "reclamation")
    .reduce((acc, type) => {
      acc[type as DocumentType] = documentRequests.filter((r) => r.documentType === type).length;
      return acc;
    }, {} as Record<Exclude<DocumentType, "reclamation">, number>);

  return (
    <AdminLayout>
      <div className="space-y-6">
        {/* Header */}
        <div>
          <h1 className="font-display text-3xl font-bold text-foreground">Dashboard</h1>
          <p className="text-muted-foreground">
            Vue d'ensemble des demandes et réclamations
          </p>
        </div>

        {/* Stats Grid */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
          <StatsCard
            title="Total Demandes"
            value={stats.total}
            icon={<FileText className="h-6 w-6" />}
            variant="primary"
          />
          <StatsCard
            title="En attente"
            value={stats.pending}
            description={`${pendingPercentage}%`}
            icon={<Clock className="h-6 w-6" />}
            variant="warning"
          />
          <StatsCard
            title="Acceptées"
            value={stats.accepted}
            description={`${acceptedPercentage}%`}
            icon={<CheckCircle className="h-6 w-6" />}
            variant="success"
          />
          <StatsCard
            title="Refusées"
            value={stats.rejected}
            description={`${rejectedPercentage}%`}
            icon={<XCircle className="h-6 w-6" />}
            variant="destructive"
          />
          <StatsCard
            title="Réclamations"
            value={stats.pendingComplaints}
            description="en attente"
            icon={<MessageSquare className="h-6 w-6" />}
            variant="default"
          />
        </div>

        {/* Content Grid */}
        <div className="grid gap-6 lg:grid-cols-3">
          {/* Recent Pending Requests */}
          <div className="lg:col-span-2">
            <Card className="shadow-elegant">
              <CardHeader>
                <CardTitle className="font-display">Demandes en attente</CardTitle>
                <CardDescription>
                  Les dernières demandes nécessitant une action
                </CardDescription>
              </CardHeader>
              <CardContent>
                <RequestsTable requests={recentPendingRequests} />
              </CardContent>
            </Card>
          </div>

          {/* Document Type Distribution */}
          <div>
            <Card className="shadow-elegant">
              <CardHeader>
                <CardTitle className="font-display">Par type de document</CardTitle>
                <CardDescription>Répartition des demandes</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {Object.entries(documentTypeLabels)
                  .filter(([type]) => type !== "reclamation")
                  .map(([type, label]) => {
                    const count = byType[type as Exclude<DocumentType, "reclamation">] || 0;
                    const percentage = totalDocumentRequests > 0 ? Math.round((count / totalDocumentRequests) * 100) : 0;
                    return (
                      <div key={type} className="space-y-2">
                        <div className="flex justify-between text-sm">
                          <span className="text-muted-foreground">{label}</span>
                          <span className="font-medium">{count}</span>
                        </div>
                        <div className="h-2 rounded-full bg-muted overflow-hidden">
                          <div
                            className="h-full bg-gradient-to-r from-primary to-secondary transition-all duration-500"
                            style={{ width: `${percentage}%` }}
                          />
                        </div>
                      </div>
                    );
                  })}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
