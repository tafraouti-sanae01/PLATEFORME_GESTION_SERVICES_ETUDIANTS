import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useApp } from "@/contexts/AppContext";
import { toast } from "sonner";
import { MessageSquare, CheckCircle, AlertCircle, Loader2 } from "lucide-react";

const complaintSchema = z.object({
  email: z.string().email("Format d'email invalide"),
  apogee: z.string().regex(/^\d{8}$/, "Le numéro Apogée doit contenir exactement 8 chiffres"),
  cin: z.string().min(1, "Le CIN est obligatoire"),
  subject: z.string().min(1, "L'objet est obligatoire").max(100, "L'objet ne doit pas dépasser 100 caractères"),
  description: z.string().min(10, "La description doit contenir au moins 10 caractères").max(1000, "La description ne doit pas dépasser 1000 caractères"),
});

type FormData = z.infer<typeof complaintSchema>;

export function ComplaintForm() {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitResult, setSubmitResult] = useState<{
    success: boolean;
    message: string;
  } | null>(null);
  const { addComplaint, validateStudent } = useApp();

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(complaintSchema),
  });

  const onSubmit = async (data: FormData) => {
    setIsSubmitting(true);
    setSubmitResult(null);

    try {
      const student = await validateStudent(data.email, data.apogee, data.cin);

      if (!student) {
        setSubmitResult({
          success: false,
          message:
            "Les informations saisies ne correspondent à aucun étudiant enregistré. Veuillez vérifier votre email, numéro Apogée et CIN.",
        });
        setIsSubmitting(false);
        return;
      }

      await addComplaint({
        id: crypto.randomUUID(),
        studentEmail: data.email,
        apogee: data.apogee,
        cin: data.cin,
        subject: data.subject,
        description: data.description,
        status: "pending",
        createdAt: new Date(),
      });

      setSubmitResult({
        success: true,
        message: "Votre réclamation a été enregistrée avec succès. Nous vous répondrons dans les plus brefs délais.",
      });
      
      toast.success("Réclamation envoyée avec succès!");
      reset();
    } catch (error) {
      setSubmitResult({
        success: false,
        message: "Une erreur est survenue lors de l'envoi de votre réclamation. Veuillez réessayer.",
      });
      toast.error("Erreur lors de l'envoi de la réclamation");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="w-full max-w-2xl mx-auto shadow-elegant animate-fade-in">
      <CardHeader className="space-y-1">
        <div className="flex items-center gap-3">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-secondary/20 text-secondary">
            <MessageSquare className="h-6 w-6" />
          </div>
          <div>
            <CardTitle className="font-display text-2xl">Espace Réclamation</CardTitle>
            <CardDescription>
              Soumettez votre réclamation et nous vous répondrons rapidement
            </CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="email">Adresse email *</Label>
              <Input
                id="email"
                type="email"
                placeholder="votre.email@etu.univ.ma"
                {...register("email")}
                className={errors.email ? "border-destructive" : ""}
              />
              {errors.email && (
                <p className="text-xs text-destructive">{errors.email.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="apogee">N° Apogée *</Label>
              <Input
                id="apogee"
                placeholder="12345678"
                {...register("apogee")}
                className={errors.apogee ? "border-destructive" : ""}
              />
              {errors.apogee && (
                <p className="text-xs text-destructive">{errors.apogee.message}</p>
              )}
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="cin">CIN *</Label>
            <Input
              id="cin"
              placeholder="AB123456"
              {...register("cin")}
              className={errors.cin ? "border-destructive" : ""}
            />
            {errors.cin && (
              <p className="text-xs text-destructive">{errors.cin.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="subject">Objet *</Label>
            <Input
              id="subject"
              placeholder="Ex: Retard dans le traitement de ma demande"
              {...register("subject")}
              className={errors.subject ? "border-destructive" : ""}
            />
            {errors.subject && (
              <p className="text-xs text-destructive">{errors.subject.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description *</Label>
            <Textarea
              id="description"
              placeholder="Décrivez votre réclamation en détail..."
              rows={5}
              {...register("description")}
              className={errors.description ? "border-destructive" : ""}
            />
            {errors.description && (
              <p className="text-xs text-destructive">{errors.description.message}</p>
            )}
          </div>

          {/* Submit result message */}
          {submitResult && (
            <div
              className={`flex items-start gap-3 rounded-lg p-4 animate-fade-in ${
                submitResult.success
                  ? "bg-success/10 text-success"
                  : "bg-destructive/10 text-destructive"
              }`}
            >
              {submitResult.success ? (
                <CheckCircle className="h-5 w-5 mt-0.5" />
              ) : (
                <AlertCircle className="h-5 w-5 mt-0.5" />
              )}
              <p className="font-medium">{submitResult.message}</p>
            </div>
          )}

          <Button
            type="submit"
            size="lg"
            className="w-full"
            disabled={isSubmitting}
          >
            {isSubmitting ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Envoi en cours...
              </>
            ) : (
              "Soumettre la réclamation"
            )}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
