import { useState, useEffect, useCallback } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useApp } from "@/contexts/AppContext";
import { DocumentType, documentTypeLabels, Student } from "@/types";
import { academicYears as defaultAcademicYears, semesters as defaultSemesters, academicSupervisors as defaultAcademicSupervisors } from "@/data/mockData";
import { getAcademicYears, getSemesters, getSupervisors, getStudentAcademicHistory, type StudentHistory } from "@/lib/api";
import { toast } from "sonner";
import { FileText, CheckCircle, Loader2, ShieldCheck, ShieldX, AlertCircle } from "lucide-react";
import { debounce } from "@/lib/utils";

const baseSchema = z.object({
  email: z.string().email("Format d'email invalide"),
  apogee: z.string().regex(/^\d{8}$/, "Le numéro Apogée doit contenir exactement 8 chiffres"),
  cin: z.string().min(1, "Le CIN est obligatoire"),
  documentType: z.enum(["attestation_scolarite", "attestation_reussite", "releve_notes", "convention_stage", "reclamation"]),
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
  // Reclamation fields
  reclamationSubject: z.string().optional(),
  reclamationMessage: z.string().optional(),
  relatedRequestNumber: z.string().optional(),
}).refine((data) => {
  // Si c'est une réclamation, le numéro de référence de document est obligatoire
  if (data.documentType === "reclamation") {
    return !!data.relatedRequestNumber && data.relatedRequestNumber.trim().length > 0;
  }
  return true;
}, {
  message: "Le numéro de référence de document est obligatoire pour une réclamation",
  path: ["relatedRequestNumber"],
});

type FormData = z.infer<typeof extendedSchema>;

export function UnifiedRequestForm() {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isValidating, setIsValidating] = useState(false);
  const [verifiedStudent, setVerifiedStudent] = useState<Student | null>(null);
  const [validationError, setValidationError] = useState<string | null>(null);
  const [submitResult, setSubmitResult] = useState<{
    success: boolean;
    message: string;
    referenceNumber?: string;
  } | null>(null);
  const [academicYears, setAcademicYears] = useState<string[]>(defaultAcademicYears);
  const [semesters, setSemesters] = useState<string[]>(defaultSemesters);
  const [academicSupervisors, setAcademicSupervisors] = useState<string[]>(defaultAcademicSupervisors);
  const [studentHistory, setStudentHistory] = useState<StudentHistory[]>([]);
  const [availableSemesters, setAvailableSemesters] = useState<string[]>([]);

  const { validateStudent, addRequest, addComplaint } = useApp();

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    reset,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(extendedSchema),
  });

  const email = watch("email");
  const apogee = watch("apogee");
  const cin = watch("cin");
  const documentType = watch("documentType") as DocumentType;

  // Debounced validation function
  const debouncedValidate = useCallback(
    debounce(async (emailVal: string, apogeeVal: string, cinVal: string) => {
      const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal);
      const apogeeValid = /^\d{8}$/.test(apogeeVal);
      const cinValid = !!cinVal && cinVal.length >= 1;

      if (!emailValid || !apogeeValid || !cinValid) {
        setVerifiedStudent(null);
        setValidationError(null);
        setIsValidating(false);
        return;
      }

      setIsValidating(true);
      try {
        const student = await validateStudent(emailVal, apogeeVal, cinVal);
        if (student) {
          setVerifiedStudent(student);
          setValidationError(null);
        } else {
          setVerifiedStudent(null);
          setValidationError("Les informations saisies ne correspondent à aucun étudiant enregistré.");
        }
      } finally {
        setIsValidating(false);
      }
    }, 600),
    [validateStudent]
  );

  // Real-time validation effect
  useEffect(() => {
    if (email && apogee && cin) {
      debouncedValidate(email, apogee, cin);
    } else {
      setVerifiedStudent(null);
      setValidationError(null);
    }
  }, [email, apogee, cin, debouncedValidate]);



  // Load student history when verified
  useEffect(() => {
    if (verifiedStudent) {
      getStudentAcademicHistory(verifiedStudent.id)
        .then((history) => {
          setStudentHistory(history);
        })
        .catch((err) => {
          console.error("Failed to load student history", err);
          // Fallback to default years if history load fails
          setStudentHistory([]);
        });
    } else {
      setStudentHistory([]);
      setAvailableSemesters([]);
    }
  }, [verifiedStudent]);

  // Update available semesters when academic year changes
  const watchedAcademicYear = watch("academicYear");
  useEffect(() => {
    if (watchedAcademicYear && studentHistory.length > 0) {
      const selectedYearData = studentHistory.find(h => h.year === watchedAcademicYear);
      if (selectedYearData) {
        setAvailableSemesters(selectedYearData.semesters);
      } else {
        setAvailableSemesters([]);
      }
    } else {
      // If no history (fallback) or no year selected, show all default semesters
      setAvailableSemesters(defaultSemesters);
    }
  }, [watchedAcademicYear, studentHistory]);

  // Reset document type when student is not verified
  useEffect(() => {
    if (!verifiedStudent && documentType) {
      setValue("documentType", undefined as unknown as DocumentType);
    }
  }, [verifiedStudent, setValue]);

  // Load academic years, semesters, and supervisors from API
  useEffect(() => {
    const loadData = async () => {
      try {
        const [years, sems, sups] = await Promise.all([
          getAcademicYears().catch(() => defaultAcademicYears),
          getSemesters().catch(() => defaultSemesters),
          getSupervisors().catch(() => defaultAcademicSupervisors),
        ]);
        setAcademicYears(years);
        setSemesters(sems);
        setAcademicSupervisors(sups);
      } catch (error) {
        console.error("Erreur lors du chargement des données", error);
        // Keep default values
      }
    };
    loadData();
  }, []);

  const onSubmit = async (data: FormData) => {
    if (!verifiedStudent) {
      toast.error("Veuillez d'abord vérifier vos informations");
      return;
    }

    setIsSubmitting(true);
    setSubmitResult(null);

    try {
      if (data.documentType === "reclamation") {
        const result = await addComplaint({
          studentEmail: data.email,
          apogee: data.apogee,
          cin: data.cin,
          subject: data.reclamationSubject || "",
          description: data.reclamationMessage || "",
          status: "pending" as const,
          relatedRequestNumber: data.relatedRequestNumber || undefined,
        });

        setSubmitResult({
          success: true,
          message: "Votre réclamation a été enregistrée avec succès. Un email de confirmation avec tous les détails vous sera envoyé.",
        });
      } else {
        const result = await addRequest({
          studentId: verifiedStudent.id,
          student: verifiedStudent,
          documentType: data.documentType as DocumentType,
          status: "pending" as const,
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
      }

      toast.success("Demande envoyée avec succès!");
      
      // Attendre un peu pour que l'utilisateur voie le message de succès
      setTimeout(() => {
        // Réinitialiser le formulaire et tous les états
        reset();
        setVerifiedStudent(null);
        setValidationError(null);
        setStudentHistory([]);
        setAvailableSemesters([]);
        setSubmitResult(null);
      }, 2000);
    } catch (error) {
      console.error("Erreur lors de l'envoi de la demande", error);
      setSubmitResult({
        success: false,
        message: "Une erreur est survenue lors de l'envoi de votre demande. Veuillez réessayer.",
      });
      toast.error("Erreur lors de l'envoi de la demande");
    } finally {
      setIsSubmitting(false);
    }
  };

  const isStudentFieldsComplete = email && apogee && cin;

  return (
    <Card className="w-full max-w-2xl mx-auto shadow-elegant animate-fade-in">
      <CardHeader className="space-y-1">
        <div className="flex items-center gap-3">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <FileText className="h-6 w-6" />
          </div>
          <div>
            <CardTitle className="font-display text-2xl">Espace Étudiant</CardTitle>
            <CardDescription>
              Demandez un document ou soumettez une réclamation
            </CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          {/* Student identification fields */}
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                Identification
              </h3>
              {isValidating && (
                <div className="flex items-center gap-2 text-muted-foreground text-sm">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Vérification...
                </div>
              )}
              {!isValidating && verifiedStudent && (
                <div className="flex items-center gap-2 text-success text-sm">
                  <ShieldCheck className="h-4 w-4" />
                  Étudiant vérifié
                </div>
              )}
              {!isValidating && validationError && isStudentFieldsComplete && (
                <div className="flex items-center gap-2 text-destructive text-sm">
                  <ShieldX className="h-4 w-4" />
                  Non vérifié
                </div>
              )}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="email">Adresse email *</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="votre.email@etu.univ.ma"
                  {...register("email")}
                  className={errors.email ? "border-destructive" : verifiedStudent ? "border-success" : ""}
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
                  className={errors.apogee ? "border-destructive" : verifiedStudent ? "border-success" : ""}
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
                className={errors.cin ? "border-destructive" : verifiedStudent ? "border-success" : ""}
              />
              {errors.cin && (
                <p className="text-xs text-destructive">{errors.cin.message}</p>
              )}
            </div>

            {/* Verification status message */}
            {validationError && isStudentFieldsComplete && !isValidating && (
              <div className="flex items-start gap-3 rounded-lg p-3 bg-destructive/10 text-destructive animate-fade-in">
                <AlertCircle className="h-5 w-5 mt-0.5 flex-shrink-0" />
                <p className="text-sm">{validationError}</p>
              </div>
            )}

            {verifiedStudent && (
              <div className="flex items-start gap-3 rounded-lg p-3 bg-success/10 text-success animate-fade-in">
                <CheckCircle className="h-5 w-5 mt-0.5 flex-shrink-0" />
                <div className="text-sm">
                  <p className="font-medium">Bienvenue, {verifiedStudent.firstName} {verifiedStudent.lastName}</p>
                  <p className="text-success/80">Vous pouvez maintenant sélectionner le type de demande.</p>
                </div>
              </div>
            )}
          </div>

          {/* Document type selection - only enabled when student is verified */}
          <div className="space-y-2">
            <Label>Type de demande *</Label>
            <Select
              value={documentType}
              onValueChange={(value) => setValue("documentType", value as DocumentType)}
              disabled={!verifiedStudent}
            >
              <SelectTrigger className={`bg-background ${!verifiedStudent ? "opacity-50 cursor-not-allowed" : ""}`}>
                <SelectValue placeholder={verifiedStudent ? "Sélectionner un type" : "Vérifiez d'abord vos informations"} />
              </SelectTrigger>
              <SelectContent className="bg-popover z-50">
                {Object.entries(documentTypeLabels).map(([value, label]) => (
                  <SelectItem key={value} value={value}>
                    {label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {!verifiedStudent && (
              <p className="text-xs text-muted-foreground">
                Remplissez et vérifiez vos informations pour accéder aux options
              </p>
            )}
          </div>

          {/* Reclamation fields */}
          {documentType === "reclamation" && (
            <div className="space-y-4 animate-fade-in rounded-lg border border-border bg-muted/30 p-4">
              <h4 className="font-medium text-foreground">Détails de la réclamation</h4>

              <div className="space-y-2">
                <Label htmlFor="relatedRequestNumber">Numéro de référence de document *</Label>
                <Input
                  id="relatedRequestNumber"
                  placeholder="REQ-XXX-XXX"
                  {...register("relatedRequestNumber")}
                  className={errors.relatedRequestNumber ? "border-destructive" : ""}
                />
                {errors.relatedRequestNumber && (
                  <p className="text-xs text-destructive">{errors.relatedRequestNumber.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label>Objet *</Label>
                <Input
                  placeholder="Objet de votre réclamation"
                  {...register("reclamationSubject")}
                />
              </div>

              <div className="space-y-2">
                <Label>Message *</Label>
                <Textarea
                  placeholder="Décrivez votre réclamation en détail..."
                  rows={5}
                  {...register("reclamationMessage")}
                />
              </div>
            </div>
          )}

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
                    {studentHistory.length > 0
                      ? studentHistory.map((h) => (
                        <SelectItem key={h.year} value={h.year}>
                          {h.year}
                        </SelectItem>
                      ))
                      : academicYears.map((year) => (
                        <SelectItem key={year} value={year}>
                          {year}
                        </SelectItem>
                      ))
                    }
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
                      {availableSemesters.map((sem) => (
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
              className={`flex items-start gap-3 rounded-lg p-4 animate-fade-in ${submitResult.success
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
            disabled={isSubmitting || !verifiedStudent || !documentType}
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
