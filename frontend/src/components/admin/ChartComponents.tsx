import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from "recharts";
import { documentTypeLabels, DocumentType } from "@/types";

// Color palettes - Using same colors as stats cards
const REQUEST_STATUS_COLORS = {
    pending: "#f59e0b", // warning/orange
    accepted: "#10b981", // success/green
    rejected: "#ef4444", // destructive/red
};

const COMPLAINT_STATUS_COLORS = {
    pending: "#f59e0b", // warning/orange
    resolved: "#10b981", // success/green
};

const DOCUMENT_TYPE_COLORS = [
    "#3b82f6", // blue
    "#8b5cf6", // purple
    "#ec4899", // pink
    "#f59e0b", // orange
    "#10b981", // green
];

interface DocumentTypeData {
    type: string;
    label: string;
    count: number;
}

interface StatusData {
    name: string;
    value: number;
    color: string;
}

interface DocumentTypeBarChartProps {
    data: Record<Exclude<DocumentType, "reclamation">, number>;
}

interface StatusPieChartProps {
    pending: number;
    accepted: number;
    rejected: number;
}

interface ComplaintsStatusPieChartProps {
    pending: number;
    resolved: number;
}

// Custom label for pie chart
const renderCustomLabel = (entry: any) => {
    return `${entry.value}`;
};

export function DocumentTypeBarChart({ data }: DocumentTypeBarChartProps) {
    const chartData: DocumentTypeData[] = Object.entries(documentTypeLabels)
        .filter(([type]) => type !== "reclamation")
        .map(([type, label], index) => ({
            type,
            label,
            count: data[type as Exclude<DocumentType, "reclamation">] || 0,
        }));

    return (
        <Card className="shadow-elegant">
            <CardHeader>
                <CardTitle className="font-display">Distribution par type de document</CardTitle>
                <CardDescription>Nombre de demandes par catégorie</CardDescription>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                    <BarChart data={chartData}>
                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                        <XAxis
                            dataKey="label"
                            className="text-xs"
                            angle={-45}
                            textAnchor="end"
                            height={100}
                        />
                        <YAxis className="text-xs" />
                        <Tooltip
                            contentStyle={{
                                backgroundColor: "hsl(var(--card))",
                                border: "1px solid hsl(var(--border))",
                                borderRadius: "6px"
                            }}
                        />
                        <Bar
                            dataKey="count"
                            fill="hsl(var(--primary))"
                            radius={[8, 8, 0, 0]}
                        />
                    </BarChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}

export function StatusPieChart({ pending, accepted, rejected }: StatusPieChartProps) {
    const total = pending + accepted + rejected;

    const chartData: StatusData[] = [
        { name: "En attente", value: pending, color: REQUEST_STATUS_COLORS.pending },
        { name: "Acceptées", value: accepted, color: REQUEST_STATUS_COLORS.accepted },
        { name: "Refusées", value: rejected, color: REQUEST_STATUS_COLORS.rejected },
    ].filter(item => item.value > 0); // Only show non-zero values

    return (
        <Card className="shadow-elegant">
            <CardHeader>
                <CardTitle className="font-display">Statuts des demandes</CardTitle>
                <CardDescription>Répartition par statut</CardDescription>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                    <PieChart>
                        <Pie
                            data={chartData}
                            cx="50%"
                            cy="50%"
                            labelLine={false}
                            label={renderCustomLabel}
                            outerRadius={80}
                            fill="#8884d8"
                            dataKey="value"
                        >
                            {chartData.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={entry.color} />
                            ))}
                        </Pie>
                        <Tooltip
                            contentStyle={{
                                backgroundColor: "hsl(var(--card))",
                                border: "1px solid hsl(var(--border))",
                                borderRadius: "6px"
                            }}
                        />
                        <Legend
                            verticalAlign="bottom"
                            height={36}
                            iconType="circle"
                        />
                    </PieChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}

export function ComplaintsStatusPieChart({ pending, resolved }: ComplaintsStatusPieChartProps) {
    const total = pending + resolved;

    const chartData: StatusData[] = [
        { name: "En attente", value: pending, color: COMPLAINT_STATUS_COLORS.pending },
        { name: "Résolu", value: resolved, color: COMPLAINT_STATUS_COLORS.resolved },
    ].filter(item => item.value > 0); // Only show non-zero values

    return (
        <Card className="shadow-elegant">
            <CardHeader>
                <CardTitle className="font-display">Statuts des réclamations</CardTitle>
                <CardDescription>Répartition par statut</CardDescription>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                    <PieChart>
                        <Pie
                            data={chartData}
                            cx="50%"
                            cy="50%"
                            labelLine={false}
                            label={renderCustomLabel}
                            outerRadius={80}
                            fill="#8884d8"
                            dataKey="value"
                        >
                            {chartData.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={entry.color} />
                            ))}
                        </Pie>
                        <Tooltip
                            contentStyle={{
                                backgroundColor: "hsl(var(--card))",
                                border: "1px solid hsl(var(--border))",
                                borderRadius: "6px"
                            }}
                        />
                        <Legend
                            verticalAlign="bottom"
                            height={36}
                            iconType="circle"
                        />
                    </PieChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}
