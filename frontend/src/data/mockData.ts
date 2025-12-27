import { Student, DocumentRequest, Complaint, AdminUser } from "@/types";

export const mockStudents: Student[] = [
  {
    id: "1",
    email: "ahmed.benali@etu.univ.ma",
    apogee: "12345678",
    cin: "AB123456",
    firstName: "Ahmed",
    lastName: "Benali",
  },
  {
    id: "2",
    email: "fatima.alami@etu.univ.ma",
    apogee: "87654321",
    cin: "CD789012",
    firstName: "Fatima",
    lastName: "Alami",
  },
  {
    id: "3",
    email: "youssef.el-mansouri@etu.univ.ma",
    apogee: "11223344",
    cin: "EF345678",
    firstName: "Youssef",
    lastName: "El Mansouri",
  },
];

export const mockRequests: DocumentRequest[] = [
  {
    id: "1",
    referenceNumber: "REQ-2024-001",
    studentId: "1",
    student: mockStudents[0],
    documentType: "attestation_scolarite",
    status: "pending",
    createdAt: new Date("2024-01-15"),
  },
  {
    id: "2",
    referenceNumber: "REQ-2024-002",
    studentId: "2",
    student: mockStudents[1],
    documentType: "attestation_reussite",
    status: "accepted",
    createdAt: new Date("2024-01-12"),
    processedAt: new Date("2024-01-14"),
    academicYear: "2023-2024",
  },
  {
    id: "3",
    referenceNumber: "REQ-2024-003",
    studentId: "3",
    student: mockStudents[2],
    documentType: "releve_notes",
    status: "pending",
    createdAt: new Date("2024-01-16"),
    academicYear: "2023-2024",
    semester: "S1",
  },
  {
    id: "4",
    referenceNumber: "REQ-2024-004",
    studentId: "1",
    student: mockStudents[0],
    documentType: "convention_stage",
    status: "rejected",
    createdAt: new Date("2024-01-10"),
    processedAt: new Date("2024-01-11"),
    companyName: "Tech Solutions SARL",
    companyAddress: "123 Avenue Mohammed V, Casablanca",
    supervisorName: "Karim Ouazzani",
    supervisorEmail: "k.ouazzani@techsolutions.ma",
    supervisorPhone: "0612345678",
    stageStartDate: new Date("2024-02-01"),
    stageEndDate: new Date("2024-04-30"),
    stageSubject: "Développement d'une application web",
    academicSupervisor: "Dr. Hassan Moussaoui",
  },
];

export const mockComplaints: Complaint[] = [
  {
    id: "1",
    referenceNumber: "REC-2024-001",
    studentEmail: "ahmed.benali@etu.univ.ma",
    apogee: "12345678",
    cin: "AB123456",
    subject: "Demande de document urgent",
    description: "J'ai besoin de mon attestation de scolarité rapidement pour une inscription.",
    status: "pending",
    createdAt: new Date("2024-01-20"),
  },
  {
    id: "2",
    referenceNumber: "REC-2024-002",
    studentEmail: "fatima.alami@etu.univ.ma",
    apogee: "87654321",
    cin: "CD789012",
    subject: "Relevé de notes incomplet",
    description: "Mon relevé de notes ne contient pas toutes les notes du semestre.",
    status: "resolved",
    createdAt: new Date("2024-01-18"),
    respondedAt: new Date("2024-01-19"),
    response: "Nous avons vérifié et votre relevé de notes est complet. Toutes les notes sont présentes.",
  },
];

export const mockAdmin: AdminUser = {
  id: "admin1",
  email: "admin@univ.ma",
  name: "Administrateur",
};

export const academicYears = [
  "2023-2024",
  "2022-2023",
  "2021-2022",
  "2020-2021",
];

export const semesters = ["S1", "S2", "S3", "S4", "S5", "S6", "S7", "S8", "S9", "S10"];

export const academicSupervisors = [
  "Dr. Hassan Moussaoui",
  "Dr. Leila Benkirane",
  "Dr. Mohammed Tazi",
  "Dr. Amina El Ouafi",
  "Dr. Rachid Bennani",
];
