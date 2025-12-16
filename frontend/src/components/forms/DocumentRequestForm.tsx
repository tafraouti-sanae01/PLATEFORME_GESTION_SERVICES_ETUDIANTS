import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useApp } from "@/contexts/AppContext";
import type { DocumentType } from "@/types";
import { documentTypeLabels } from "@/types";
import { academicYears, semesters, academicSupervisors } from "@/data/mockData";
import { toast } from "sonner";
import { FileText, CheckCircle, AlertCircle, Loader2 } from "lucide-react";

const baseSchema = z.object({
  email: z.string().email("Format d'email invalide"),
  apogee: z.string().regex(/^\d{8}$/, "Le numéro Apogée doit contenir exactement 8 chiffres"),
  cin: z.string().min(1, "Le CIN est obligatoire"),
  documentType: z.enum(["attestation_scolarite", "attestation_reussite", "releve_notes", "convention_stage"]),
});

const extendedSchema = baseSchema.extend({
  academicYear: z.string().optional(),
  semester: z.string().optional(),
  companyName: z.string().optional(),
  companyAddress: z.string().optional(),
  supervisorName: z.string().optional(),
  supervisorEmail: z.string().email("Format d'email invalide").optional().or(z.literal("")),
  supervisorPhone: z.string().optional(),
  stageStartDate: z.string().optional(),
  stageEndDate: z.string().optional(),
  stageSubject: z.string().optional(),
  academicSupervisor: z.string().optional(),
});

type FormData = z.infer<typeof extendedSchema>;

export function DocumentRequestForm() {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitResult, setSubmitResult] = useState<{
    success: boolean;
    message: string;
    referenceNumber?: string;
  } | null>(null);
  const { validateStudent, addRequest } = useApp();

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    reset,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(extendedSchema),
    defaultValues: {
      documentType: "attestation_scolarite",
    },
  });

  const documentType = watch("documentType") as DocumentType;

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

      const result = await addRequest({
        studentId: student.id,
        student,
        documentType: data.documentType as DocumentType,
        status: "pending",
        academicYear: data.academicYear,
        semester: data.semester,
        companyName: data.companyName,
        companyAddress: data.companyAddress,
        supervisorName: data.supervisorName,
        supervisorEmail: data.supervisorEmail,
        supervisorPhone: data.supervisorPhone,
        stageStartDate: data.stageStartDate ? new Date(data.stageStartDate) : undefined,
        stageEndDate: data.stageEndDate ? new Date(data.stageEndDate) : undefined,
        stageSubject: data.stageSubject,
        academicSupervisor: data.academicSupervisor,
      });

      setSubmitResult({
        success: true,
        message: "Votre demande a été enregistrée avec succès. Un email de confirmation avec tous les détails vous sera envoyé.",
      });
      
      toast.success("Demande envoyée avec succès!");
      reset();
    } catch (error) {
      setSubmitResult({
        success: false,
        message: "Une erreur est survenue lors de l'envoi de votre demande. Veuillez réessayer.",
      });
      toast.error("Erreur lors de l'envoi de la demande");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="w-full max-w-2xl mx-auto shadow-elegant animate-fade-in">
      <CardHeader className="space-y-1">
        <div className="flex items-center gap-3">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <FileText className="h-6 w-6" />
          </div>
          <div>
            <CardTitle className="font-display text-2xl">Demande de document</CardTitle>
            <CardDescription>
              Remplissez le formulaire pour demander un document scolaire
            </CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          {/* Base fields */}
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

          <div className="grid gap-4 sm:grid-cols-2">
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
              <Label>Type de document *</Label>
              <Select
                value={documentType}
                onValueChange={(value) => setValue("documentType", value as DocumentType)}
              >
                <SelectTrigger className="bg-background">
                  <SelectValue placeholder="Sélectionner un type" />
                </SelectTrigger>
                <SelectContent className="bg-popover z-50">
                  {Object.entries(documentTypeLabels).map(([value, label]) => (
                    <SelectItem key={value} value={value}>
                      {label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          {/* Conditional fields based on document type */}
          {(documentType === "attestation_reussite" || documentType === "releve_notes") && (
            <div className="grid gap-4 sm:grid-cols-2 animate-fade-in">
              <div className="space-y-2">
                <Label>Année universitaire *</Label>
                <Select
                  onValueChange={(value) => setValue("academicYear", value)}
                >
                  <SelectTrigger className="bg-background">
                    <SelectValue placeholder="Sélectionner l'année" />
                  </SelectTrigger>
                  <SelectContent className="bg-popover z-50">
                    {academicYears.map((year) => (
                      <SelectItem key={year} value={year}>
                        {year}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {documentType === "releve_notes" && (
                <div className="space-y-2">
                  <Label>Semestre(s) concerné(s) *</Label>
                  <Select
                    onValueChange={(value) => setValue("semester", value)}
                  >
                    <SelectTrigger className="bg-background">
                      <SelectValue placeholder="Sélectionner le semestre" />
                    </SelectTrigger>
                    <SelectContent className="bg-popover z-50">
                      {semesters.map((sem) => (
                        <SelectItem key={sem} value={sem}>
                          {sem}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}
            </div>
          )}

          {documentType === "convention_stage" && (
            <div className="space-y-4 animate-fade-in rounded-lg border border-border bg-muted/30 p-4">
              <h4 className="font-medium text-foreground">Informations du stage</h4>
              
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>Nom de l'entreprise *</Label>
                  <Input
                    placeholder="Tech Solutions SARL"
                    {...register("companyName")}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Adresse de l'entreprise *</Label>
                  <Input
                    placeholder="123 Avenue Mohammed V, Casablanca"
                    {...register("companyAddress")}
                  />
                </div>
              </div>

              <div className="grid gap-4 sm:grid-cols-3">
                <div className="space-y-2">
                  <Label>Nom du responsable *</Label>
                  <Input
                    placeholder="Nom complet"
                    {...register("supervisorName")}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Email du responsable *</Label>
                  <Input
                    type="email"
                    placeholder="email@entreprise.ma"
                    {...register("supervisorEmail")}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Téléphone *</Label>
                  <Input
                    placeholder="0612345678"
                    {...register("supervisorPhone")}
                  />
                </div>
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>Date de début *</Label>
                  <Input
                    type="date"
                    {...register("stageStartDate")}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Date de fin *</Label>
                  <Input
                    type="date"
                    {...register("stageEndDate")}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Sujet/Thème du stage *</Label>
                <Input
                  placeholder="Développement d'une application web"
                  {...register("stageSubject")}
                />
              </div>

              <div className="space-y-2">
                <Label>Encadrant pédagogique souhaité</Label>
                <Select
                  onValueChange={(value) => setValue("academicSupervisor", value)}
                >
                  <SelectTrigger className="bg-background">
                    <SelectValue placeholder="Sélectionner un encadrant" />
                  </SelectTrigger>
                  <SelectContent className="bg-popover z-50">
                    {academicSupervisors.map((sup) => (
                      <SelectItem key={sup} value={sup}>
                        {sup}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
          )}

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
              <div>
                <p className="font-medium">{submitResult.message}</p>
              </div>
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
              "Soumettre la demande"
            )}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
