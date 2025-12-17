import type { AdminUser, Complaint, DocumentRequest, Student } from "@/types";

/**
 * Configuration de l'URL de base de l'API
 * 
 * Priorité :
 * 1. Variable d'environnement VITE_API_URL (si définie)
 * 2. En mode dev : utilise le proxy Vite (chaîne vide)
 * 3. En production : URL par défaut du backend
 * 
 * Pour personnaliser l'URL, créez un fichier .env dans frontend/ avec :
 * VITE_API_URL=http://localhost/Service-scolarite/backend
 */
function getApiBase(): string {
  // Si VITE_API_URL est défini explicitement, l'utiliser
  if (import.meta.env.VITE_API_URL) {
    const url = import.meta.env.VITE_API_URL.trim();
    // S'assurer que l'URL ne se termine pas par /
    return url.endsWith('/') ? url.slice(0, -1) : url;
  }

  // En mode développement, utiliser le proxy Vite (chaîne vide)
  // Le proxy redirigera /api/* vers http://localhost/Service-scolarite/backend
  if (import.meta.env.DEV) {
    return "";
  }

  // En production, utiliser l'URL par défaut
  return "http://localhost/PLATEFORME_GESTION_SERVICES_ETUDIANTS/backend";
}

const API_BASE = getApiBase();

async function fetchJson<T>(path: string, options?: RequestInit): Promise<T> {
  try {
    const res = await fetch(`${API_BASE}${path}`, {
      headers: {
        "Content-Type": "application/json",
      },
      ...options,
    });

    if (!res.ok) {
      let errorMessage = `Erreur API (${res.status})`;
      try {
        const errorData = await res.json();
        errorMessage = errorData.error || errorMessage;
      } catch {
        // Si la réponse n'est pas du JSON, utiliser le texte
        try {
          const text = await res.text();
          if (text) errorMessage = text;
        } catch {
          // Ignorer les erreurs de lecture
        }
      }
      throw new Error(errorMessage);
    }

    const contentType = res.headers.get("content-type");
    if (contentType && contentType.includes("application/json")) {
      return res.json();
    } else {
      // Si ce n'est pas du JSON, retourner le texte
      const text = await res.text();
      return text as unknown as T;
    }
  } catch (error) {
    if (error instanceof Error) {
      throw error;
    }
    if (typeof error === "string") {
      throw new Error(error);
    }
    throw new Error("Erreur de connexion au serveur. Vérifiez votre connexion internet.");
  }
}

const toDate = (value?: string | null): Date | undefined => {
  if (!value) return undefined;
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? undefined : d;
};

const mapStudent = (s: any): Student => {
  if (!s || !s.id) {
    console.warn("Données étudiant incomplètes:", s);
    throw new Error("Données étudiant incomplètes");
  }

  return {
    id: String(s.id),
    email: s.email ? String(s.email) : '',
    apogee: s.apogee ? String(s.apogee) : '',
    cin: s.cin ? String(s.cin) : '',
    firstName: s.firstName ? String(s.firstName) : '',
    lastName: s.lastName ? String(s.lastName) : '',
  };
};

const mapRequest = (r: any): DocumentRequest => {
  // S'assurer que les champs obligatoires existent
  if (!r || !r.id || !r.referenceNumber || !r.studentId || !r.student) {
    console.warn("Données de demande incomplètes:", r);
    throw new Error("Données de demande incomplètes");
  }

  return {
    id: String(r.id),
    referenceNumber: String(r.referenceNumber),
    studentId: String(r.studentId),
    student: mapStudent(r.student),
    documentType: r.documentType || 'attestation_scolarite',
    status: r.status || 'pending',
    createdAt: toDate(r.createdAt) || new Date(),
    processedAt: toDate(r.processedAt),
    academicYear: r.academicYear ? String(r.academicYear) : undefined,
    semester: r.semester ? String(r.semester) : undefined,
    companyName: r.companyName ? String(r.companyName) : undefined,
    companyAddress: r.companyAddress ? String(r.companyAddress) : undefined,
    supervisorName: r.supervisorName ? String(r.supervisorName) : undefined,
    supervisorEmail: r.supervisorEmail ? String(r.supervisorEmail) : undefined,
    supervisorPhone: r.supervisorPhone ? String(r.supervisorPhone) : undefined,
    stageStartDate: toDate(r.stageStartDate),
    stageEndDate: toDate(r.stageEndDate),
    stageSubject: r.stageSubject ? String(r.stageSubject) : undefined,
    academicSupervisor: r.academicSupervisor ? String(r.academicSupervisor) : undefined,
  };
};

const mapComplaint = (c: any): Complaint => {
  if (!c || !c.id || !c.referenceNumber) {
    console.warn("Données réclamation incomplètes:", c);
    throw new Error("Données réclamation incomplètes");
  }

  return {
    id: String(c.id),
    referenceNumber: String(c.referenceNumber),
    studentEmail: c.studentEmail ? String(c.studentEmail) : '',
    apogee: c.apogee ? String(c.apogee) : '',
    cin: c.cin ? String(c.cin) : '',
    subject: c.subject ? String(c.subject) : '',
    description: c.description ? String(c.description) : '',
    status: c.status || 'pending',
    createdAt: toDate(c.createdAt) || new Date(),
    response: c.response ? String(c.response) : undefined,
    respondedAt: toDate(c.respondedAt),
    relatedRequestNumber: c.relatedRequestNumber ? String(c.relatedRequestNumber) : undefined,
  };
};

export async function getRequests(): Promise<DocumentRequest[]> {
  try {
    const data = await fetchJson<any[]>("/api/requests");
    if (!Array.isArray(data)) {
      console.error("La réponse API n'est pas un tableau:", data);
      return [];
    }
    const requests = data.map((r) => {
      try {
        return mapRequest(r);
      } catch (error) {
        console.error("Erreur lors du mapping d'une demande:", error, r);
        return null;
      }
    }).filter((r): r is DocumentRequest => r !== null);
    
    // Trier par date décroissante (les plus récentes en premier)
    return requests.sort((a, b) => {
      const dateA = a.createdAt.getTime();
      const dateB = b.createdAt.getTime();
      return dateB - dateA; // Décroissant
    });
  } catch (error) {
    console.error("Erreur lors de la récupération des demandes:", error);
    throw error;
  }
}

export async function updateRequestStatus(id: string, status: "accepted" | "rejected" | "pending", adminId?: string, rejectionReason?: string) {
  return fetchJson<{ ok: boolean }>(`/api/requests/${id}/status`, {
    method: "POST",
    body: JSON.stringify({ status, adminId, rejectionReason }),
  });
}

export async function getComplaints(): Promise<Complaint[]> {
  try {
    const data = await fetchJson<any[]>("/api/complaints");
    if (!Array.isArray(data)) {
      console.error("La réponse API n'est pas un tableau:", data);
      return [];
    }
    const complaints = data.map((c) => {
      try {
        return mapComplaint(c);
      } catch (error) {
        console.error("Erreur lors du mapping d'une réclamation:", error, c);
        return null;
      }
    }).filter((c): c is Complaint => c !== null);
    
    // Trier par date décroissante (les plus récentes en premier)
    return complaints.sort((a, b) => {
      const dateA = a.createdAt.getTime();
      const dateB = b.createdAt.getTime();
      return dateB - dateA; // Décroissant
    });
  } catch (error) {
    console.error("Erreur lors de la récupération des réclamations:", error);
    throw error;
  }
}

export async function loginAdmin(identifier: string, password: string): Promise<AdminUser> {
  return fetchJson<AdminUser>("/api/login", {
    method: "POST",
    body: JSON.stringify({ identifier, password }),
  });
}

export async function createRequest(request: {
  studentId: string;
  documentType: string;
  referenceNumber?: string;
  academicYear?: string;
  semester?: string;
  companyName?: string;
  companyAddress?: string;
  supervisorName?: string;
  supervisorEmail?: string;
  supervisorPhone?: string;
  stageStartDate?: string;
  stageEndDate?: string;
  stageSubject?: string;
  academicSupervisor?: string;
}): Promise<{ ok: boolean; id: string; referenceNumber: string }> {
  return fetchJson<{ ok: boolean; id: string; referenceNumber: string }>("/api/requests", {
    method: "POST",
    body: JSON.stringify(request),
  });
}

export async function createComplaint(complaint: {
  email: string;
  apogee: string;
  cin: string;
  subject: string;
  description: string;
  relatedRequestNumber?: string;
}): Promise<{ ok: boolean; id: string; referenceNumber: string }> {
  return fetchJson<{ ok: boolean; id: string; referenceNumber: string }>("/api/complaints", {
    method: "POST",
    body: JSON.stringify(complaint),
  });
}

export async function respondToComplaint(id: string, response: string, adminId?: string): Promise<{ ok: boolean }> {
  return fetchJson<{ ok: boolean }>(`/api/complaints/${id}/response`, {
    method: "POST",
    body: JSON.stringify({ response, adminId }),
  });
}

export async function validateStudent(email: string, apogee: string, cin: string): Promise<{
  valid: boolean;
  student: Student | null;
}> {
  return fetchJson<{ valid: boolean; student: Student | null }>("/api/students/validate", {
    method: "POST",
    body: JSON.stringify({ email, apogee, cin }),
  });
}

export async function sendEmailToStudent(
  requestId: string,
  subject?: string,
  message?: string
): Promise<{ ok: boolean; email: string; sent: boolean; message: string }> {
  return fetchJson<{ ok: boolean; email: string; sent: boolean; message: string }>(
    `/api/requests/${requestId}/send-email`,
    {
      method: "POST",
      body: JSON.stringify({ subject, message }),
    }
  );
}

export async function downloadDocument(requestId: string): Promise<Blob> {
  const API_BASE_DOWNLOAD = import.meta.env.VITE_API_URL || (import.meta.env.DEV ? "" : "http://localhost/PLATEFORME_GESTION_SERVICES_ETUDIANTS/backend");
  const res = await fetch(`${API_BASE_DOWNLOAD}/api/requests/${requestId}/download`, {
    method: "GET",
  });

  if (!res.ok) {
    const error = await res.json().catch(() => ({}));
    throw new Error(error.error || `API error (${res.status})`);
  }

  return res.blob();
}

export async function getAcademicYears(): Promise<string[]> {
  return fetchJson<string[]>("/api/academic-years");
}

export async function getSemesters(): Promise<string[]> {
  return fetchJson<string[]>("/api/semesters");
}

export async function getSupervisors(): Promise<string[]> {
  return fetchJson<string[]>("/api/supervisors");
}

export interface StudentDemand {
  id: string;
  referenceNumber: string;
  documentType: string;
  status: string;
  date: string;
  label: string;
}

export async function getStudentDemands(email: string, apogee: string, cin: string): Promise<StudentDemand[]> {
  const demands = await fetchJson<StudentDemand[]>("/api/students/demands", {
    method: "POST",
    body: JSON.stringify({ email, apogee, cin }),
  });
  
  // Trier par date décroissante (les plus récentes en premier)
  return demands.sort((a, b) => {
    const dateA = new Date(a.date).getTime();
    const dateB = new Date(b.date).getTime();
    return dateB - dateA; // Décroissant
  });
}

export interface ComplaintDetails extends Complaint {
  student: {
    id: string;
    email: string;
    apogee: string;
    cin: string;
    firstName: string;
    lastName: string;
    dateOfBirth?: string;
    placeOfBirth?: string;
    level?: string;
  };
  relatedRequest?: {
    referenceNumber: string;
    documentType: string;
    status: string;
    requestDate: string;
    academicYear?: string;
    semester?: string;
    companyName?: string;
    companyAddress?: string;
    stageSubject?: string;
    startDate?: string;
    endDate?: string;
    supervisorName?: string;
    supervisorEmail?: string;
    supervisorPhone?: string;
    academicSupervisor?: {
      id: string;
      name: string;
      email?: string;
      phone?: string;
    };
  };
}

export async function getComplaintDetails(id: string): Promise<ComplaintDetails> {
  return fetchJson<ComplaintDetails>(`/api/complaints/${id}`);
}

export type ApiStatus = "idle" | "loading" | "error" | "ready";


export interface StudentHistory {
  year: string;
  semesters: string[];
}

export async function getStudentAcademicHistory(studentId: string): Promise<StudentHistory[]> {
  return fetchJson<StudentHistory[]>("/api/students/history", {
    method: "POST",
    body: JSON.stringify({ studentId }),
  });
}
