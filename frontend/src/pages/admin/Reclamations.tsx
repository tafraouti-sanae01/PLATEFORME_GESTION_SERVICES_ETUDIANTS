import { useState } from "react";
import { useApp } from "@/contexts/AppContext";
import { AdminLayout } from "@/components/layout/AdminLayout";
import { ComplaintsTable } from "@/components/admin/ComplaintsTable";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatsCard } from "@/components/admin/StatsCard";
import { MessageSquare, Clock, CheckCircle } from "lucide-react";

export default function AdminReclamations() {
  const { complaints } = useApp();
  const [activeTab, setActiveTab] = useState<"all" | "pending" | "resolved">("all");

  const stats = {
    total: complaints.length,
    pending: complaints.filter((c) => c.status === "pending").length,
    resolved: complaints.filter((c) => c.status === "resolved").length,
  };

  const filteredComplaints = complaints.filter((complaint) => {
    if (activeTab === "all") return true;
    return complaint.status === activeTab;
  });

  return (
    <AdminLayout>
      <div className="space-y-6">
        {/* Header */}
        <div>
          <h1 className="font-display text-3xl font-bold text-foreground">Réclamations</h1>
          <p className="text-muted-foreground">
            Gérez les réclamations des étudiants
          </p>
        </div>

        {/* Stats */}
        <div className="grid gap-4 sm:grid-cols-3">
          <StatsCard
            title="Total réclamations"
            value={stats.total}
            icon={<MessageSquare className="h-6 w-6" />}
            variant="primary"
          />
          <StatsCard
            title="En attente"
            value={stats.pending}
            icon={<Clock className="h-6 w-6" />}
            variant="warning"
          />
          <StatsCard
            title="Résolues"
            value={stats.resolved}
            icon={<CheckCircle className="h-6 w-6" />}
            variant="success"
          />
        </div>

        {/* Tabs */}
        <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as typeof activeTab)}>
          <TabsList className="bg-muted/50">
            <TabsTrigger value="all">
              Toutes ({stats.total})
            </TabsTrigger>
            <TabsTrigger value="pending">
              En attente ({stats.pending})
            </TabsTrigger>
            <TabsTrigger value="resolved">
              Résolues ({stats.resolved})
            </TabsTrigger>
          </TabsList>

          <TabsContent value={activeTab} className="mt-6">
            <Card className="shadow-elegant">
              <CardHeader>
                <CardTitle className="font-display">
                  {activeTab === "all"
                    ? "Toutes les réclamations"
                    : activeTab === "pending"
                    ? "Réclamations en attente"
                    : "Réclamations résolues"}
                </CardTitle>
                <CardDescription>
                  {filteredComplaints.length} réclamation(s)
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ComplaintsTable complaints={filteredComplaints} />
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </AdminLayout>
  );
}
