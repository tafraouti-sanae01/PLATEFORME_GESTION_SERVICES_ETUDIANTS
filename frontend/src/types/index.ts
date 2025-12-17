export type DocumentType = 
  | "attestation_scolarite"
  | "attestation_reussite"
  | "releve_notes"
  | "convention_stage"
  | "reclamation";

export type RequestStatus = "pending" | "accepted" | "rejected";

export interface Student {
  id: string;
  email: string;
  apogee: string;
  cin: string;
  firstName: string;
  lastName: string;
  filiere?: string;
  niveau?: string;
  dateNaissance?: string;
  lieuNaissance?: string;
}

export interface DocumentRequest {
  id: string;
  referenceNumber: string;
  studentId: string;
  student: Student;
  documentType: DocumentType;
  status: RequestStatus;
  createdAt: Date;
  processedAt?: Date;
  
  // Additional fields based on document type
  academicYear?: string;
  semester?: string;
  
  // Convention de stage fields
  companyName?: string;
  companyAddress?: string;
  supervisorName?: string;
  supervisorEmail?: string;
  supervisorPhone?: string;
  stageStartDate?: Date;
  stageEndDate?: Date;
  stageSubject?: string;
  academicSupervisor?: string;

  // Reclamation fields
  reclamationSubject?: string;
  reclamationMessage?: string;
  relatedRequestNumber?: string;
}

export interface Complaint {
  id: string;
  referenceNumber: string;
  studentEmail: string;
  apogee: string;
  cin: string;
  subject: string;
  description: string;
  status: "pending" | "resolved";
  createdAt: Date;
  response?: string;
  respondedAt?: Date;
  relatedRequestNumber?: string;
}

export interface AdminUser {
  id: string;
  email: string;
  name: string;
}

export const documentTypeLabels: Record<DocumentType, string> = {
  attestation_scolarite: "Attestation de scolarité",
  attestation_reussite: "Attestation de réussite",
  releve_notes: "Relevé de notes",
  convention_stage: "Convention de stage",
  reclamation: "Réclamation",
};

export const statusLabels: Record<RequestStatus, string> = {
  pending: "En attente",
  accepted: "Acceptée",
  rejected: "Refusée",
};
