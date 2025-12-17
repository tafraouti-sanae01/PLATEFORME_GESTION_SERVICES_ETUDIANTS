import { ReactNode } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { cn } from "@/lib/utils";

interface StatsCardProps {
  title: string;
  value: string | number;
  icon: ReactNode;
  description?: string;
  trend?: {
    value: number;
    isPositive: boolean;
  };
  className?: string;
  variant?: "default" | "primary" | "success" | "warning" | "destructive";
}

const variantStyles = {
  default: "bg-card",
  primary: "bg-primary/5 border-primary/20",
  success: "bg-success/5 border-success/20",
  warning: "bg-warning/5 border-warning/20",
  destructive: "bg-destructive/5 border-destructive/20",
};

const iconVariantStyles = {
  default: "bg-muted text-muted-foreground",
  primary: "bg-primary/10 text-primary",
  success: "bg-success/10 text-success",
  warning: "bg-warning/10 text-warning",
  destructive: "bg-destructive/10 text-destructive",
};

export function StatsCard({
  title,
  value,
  icon,
  description,
  trend,
  className,
  variant = "default",
}: StatsCardProps) {
  return (
    <Card className={cn("shadow-elegant transition-all duration-300 hover:shadow-lg", variantStyles[variant], className)}>
      <CardContent className="p-6">
        <div className="flex items-start justify-between">
          <div className="space-y-2">
            <p className="text-sm font-medium text-muted-foreground">{title}</p>
            <p className="text-3xl font-bold text-foreground font-display">{value}</p>
            {description && (
              <p className="text-xs text-muted-foreground">{description}</p>
            )}
            {trend && (
              <div className={cn(
                "inline-flex items-center text-xs font-medium",
                trend.isPositive ? "text-success" : "text-destructive"
              )}>
                {trend.isPositive ? "↑" : "↓"} {Math.abs(trend.value)}%
              </div>
            )}
          </div>
          <div className={cn("flex h-12 w-12 items-center justify-center rounded-xl", iconVariantStyles[variant])}>
            {icon}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
