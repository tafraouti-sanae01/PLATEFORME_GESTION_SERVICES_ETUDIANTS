import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Search, Clock, CheckCircle, XCircle } from "lucide-react";
import { useApp } from "@/contexts/AppContext";

const RequestTracker = () => {
  const { requests, complaints } = useApp();
  const [referenceNumber, setReferenceNumber] = useState("");
  const [searchResult, setSearchResult] = useState<{
    found: boolean;
    status?: string;
    type?: string;
  } | null>(null);

  const handleSearch = () => {
    if (!referenceNumber.trim()) return;

    // Search in requests (includes reclamations)
    const request = requests.find(r => r.referenceNumber === referenceNumber.trim());
    if (request) {
      const isReclamation = request.documentType === "reclamation";
      setSearchResult({
        found: true,
        status: request.status,
        type: isReclamation ? "réclamation" : "demande"
      });
      return;
    }

    // Search in old complaints format (for backwards compatibility)
    const complaint = complaints.find(c => c.id === referenceNumber.trim());
    if (complaint) {
      setSearchResult({
        found: true,
        status: complaint.status,
        type: "réclamation"
      });
      return;
    }

    setSearchResult({ found: false });
  };

  const getStatusDisplay = (status: string) => {
    switch (status) {
      case "pending":
        return {
          label: "En attente",
          variant: "secondary" as const,
          icon: Clock,
          color: "text-yellow-600"
        };
      case "approved":
      case "resolved":
        return {
          label: "Traité",
          variant: "default" as const,
          icon: CheckCircle,
          color: "text-green-600"
        };
      case "rejected":
        return {
          label: "Refusé",
          variant: "destructive" as const,
          icon: XCircle,
          color: "text-red-600"
        };
      default:
        return {
          label: status,
          variant: "outline" as const,
          icon: Clock,
          color: "text-muted-foreground"
        };
    }
  };

  return (
    <Card className="border-border/50 shadow-lg">
      <CardHeader className="text-center pb-4">
        <CardTitle className="text-xl font-semibold flex items-center justify-center gap-2">
          <Search className="h-5 w-5 text-primary" />
          Suivi de demande
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex gap-2">
          <Input
            placeholder="Entrez votre numéro de demande (ex: REQ-XXXXXX)"
            value={referenceNumber}
            onChange={(e) => {
              setReferenceNumber(e.target.value);
              setSearchResult(null);
            }}
            onKeyDown={(e) => e.key === "Enter" && handleSearch()}
            className="flex-1"
          />
          <Button onClick={handleSearch} className="px-6">
            OK
          </Button>
        </div>

        {searchResult && (
          <div className="mt-4 p-4 rounded-lg bg-muted/50 border border-border">
            {searchResult.found ? (
              <div className="flex items-center justify-between">
                <div className="space-y-1">
                  <p className="text-sm text-muted-foreground">
                    Type: <span className="font-medium text-foreground capitalize">{searchResult.type}</span>
                  </p>
                  <p className="text-sm text-muted-foreground">
                    Numéro: <span className="font-medium text-foreground">{referenceNumber}</span>
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  {(() => {
                    const statusInfo = getStatusDisplay(searchResult.status!);
                    const Icon = statusInfo.icon;
                    return (
                      <>
                        <Icon className={`h-5 w-5 ${statusInfo.color}`} />
                        <Badge variant={statusInfo.variant} className="text-sm px-3 py-1">
                          {statusInfo.label}
                        </Badge>
                      </>
                    );
                  })()}
                </div>
              </div>
            ) : (
              <p className="text-center text-destructive">
                Aucune demande trouvée avec ce numéro.
              </p>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default RequestTracker;
