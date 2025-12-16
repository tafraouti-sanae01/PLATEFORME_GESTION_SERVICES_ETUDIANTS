import { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useApp } from "@/contexts/AppContext";
import { toast } from "sonner";
import { MessageSquare, CheckCircle, AlertCircle, Loader2 } from "lucide-react";
import { getStudentDemands, type StudentDemand } from "@/lib/api";

const complaintSchema = z.object({
  email: z.string().email("Format d'email invalide"),
  apogee: z.string().regex(/^\d{8}$/, "Le numéro Apogée doit contenir exactement 8 chiffres"),
  cin: z.string().min(1, "Le CIN est obligatoire"),
  relatedRequestNumber: z.string().min(1, "Veuillez sélectionner un document"),
  subject: z.string().min(1, "L'objet est obligatoire").max(100, "L'objet ne doit pas dépasser 100 caractères"),
  description: z.string().min(10, "La description doit contenir au moins 10 caractères").max(1000, "La description ne doit pas dépasser 1000 caractères"),
});

type FormData = z.infer<typeof complaintSchema>;

export function ComplaintForm() {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isLoadingDemands, setIsLoadingDemands] = useState(false);
  const [submitResult, setSubmitResult] = useState<{
    success: boolean;
    message: string;
  } | null>(null);
  const [studentDemands, setStudentDemands] = useState<StudentDemand[]>([]);
  const [verifiedStudent, setVerifiedStudent] = useState<{ email: string; apogee: string; cin: string } | null>(null);
  const { addComplaint, validateStudent } = useApp();

  const {
    register,
    handleSubmit,
    reset,
    setValue,
    watch,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(complaintSchema),
  });

  const email = watch("email");
  const apogee = watch("apogee");
  const cin = watch("cin");

  // Charger les demandes de l'étudiant après validation
  useEffect(() => {
    const loadDemands = async () => {
      if (!verifiedStudent) {
        setStudentDemands([]);
        setValue("relatedRequestNumber", "");
        return;
      }

      setIsLoadingDemands(true);
      try {
        const demands = await getStudentDemands(
          verifiedStudent.email,
          verifiedStudent.apogee,
          verifiedStudent.cin
        );
        setStudentDemands(demands);
      } catch (error) {
        console.error("Erreur lors du chargement des demandes:", error);
        toast.error("Erreur lors du chargement de vos demandes");
        setStudentDemands([]);
      } finally {
        setIsLoadingDemands(false);
      }
    };

    loadDemands();
  }, [verifiedStudent, setValue]);

  // Valider l'étudiant lorsque les informations sont saisies
  useEffect(() => {
    const validate = async () => {
      const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email || "");
      const apogeeValid = /^\d{8}$/.test(apogee || "");
      const cinValid = !!cin && cin.length >= 1;

      if (!emailValid || !apogeeValid || !cinValid) {
        setVerifiedStudent(null);
        return;
      }

      try {
        const student = await validateStudent(email, apogee, cin);
        if (student) {
          setVerifiedStudent({ email, apogee, cin });
        } else {
          setVerifiedStudent(null);
        }
      } catch (error) {
        console.error("Erreur de validation:", error);
        setVerifiedStudent(null);
      }
    };

    // Debounce la validation
    const timeoutId = setTimeout(validate, 600);
    return () => clearTimeout(timeoutId);
  }, [email, apogee, cin, validateStudent]);

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

      if (!data.relatedRequestNumber) {
        setSubmitResult({
          success: false,
          message: "Veuillez sélectionner un document pour votre réclamation.",
        });
        setIsSubmitting(false);
        return;
      }

      await addComplaint({
        studentEmail: data.email,
        apogee: data.apogee,
        cin: data.cin,
        subject: data.subject,
        description: data.description,
        status: "pending",
        relatedRequestNumber: data.relatedRequestNumber,
      });

      setSubmitResult({
        success: true,
        message: "Votre réclamation a été enregistrée avec succès. Nous vous répondrons dans les plus brefs délais.",
      });
      
      toast.success("Réclamation envoyée avec succès!");
      
      // Attendre un peu pour que l'utilisateur voie le message de succès
      setTimeout(() => {
        reset();
        setSubmitResult(null);
        setVerifiedStudent(null);
        setStudentDemands([]);
      }, 2000);
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

          {/* Indicateur de validation */}
          {verifiedStudent && (
            <div className="flex items-center gap-2 rounded-lg bg-success/10 p-3 text-success">
              <CheckCircle className="h-4 w-4" />
              <p className="text-sm font-medium">Informations validées</p>
            </div>
          )}

          {/* Sélection du document */}
          {verifiedStudent && (
            <div className="space-y-2">
              <Label htmlFor="relatedRequestNumber">Document concerné *</Label>
              {isLoadingDemands ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  <span>Chargement de vos demandes...</span>
                </div>
              ) : studentDemands.length === 0 ? (
                <div className="rounded-lg border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive">
                  <p>Aucune demande trouvée. Vous devez d'abord faire une demande de document.</p>
                </div>
              ) : (
                <>
                  <Select
                    onValueChange={(value) => setValue("relatedRequestNumber", value)}
                  >
                    <SelectTrigger
                      id="relatedRequestNumber"
                      className={errors.relatedRequestNumber ? "border-destructive" : ""}
                    >
                      <SelectValue placeholder="Sélectionner un document" />
                    </SelectTrigger>
                    <SelectContent>
                      {studentDemands.map((demand) => (
                        <SelectItem key={demand.id} value={demand.referenceNumber}>
                          {demand.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.relatedRequestNumber && (
                    <p className="text-xs text-destructive">{errors.relatedRequestNumber.message}</p>
                  )}
                </>
              )}
            </div>
          )}

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
