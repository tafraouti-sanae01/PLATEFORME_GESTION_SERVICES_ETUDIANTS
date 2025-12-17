import React, { createContext, useContext, useState, ReactNode, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { DocumentRequest, Complaint, Student, AdminUser } from "@/types";
import { 
  getRequests, 
  getComplaints, 
  validateStudent as validateStudentAPI,
  createRequest as createRequestAPI,
  createComplaint as createComplaintAPI,
  updateRequestStatus as updateRequestStatusAPI,
  respondToComplaint as respondToComplaintAPI,
  loginAdmin as loginAdminAPI
} from "@/lib/api";
import { mockStudents } from "@/data/mockData";

interface AppContextType {
  // Data
  requests: DocumentRequest[];
  complaints: Complaint[];
  students: Student[];
  
  // Loading states
  isLoadingRequests: boolean;
  isLoadingComplaints: boolean;
  
  // Auth
  isAdminLoggedIn: boolean;
  currentAdmin: AdminUser | null;
  
  // Actions
  addRequest: (request: Omit<DocumentRequest, "id" | "referenceNumber" | "createdAt">) => Promise<{ id: string; referenceNumber: string }>;
  updateRequestStatus: (id: string, status: "accepted" | "rejected" | "pending", adminId?: string, rejectionReason?: string) => Promise<void>;
  addComplaint: (complaint: Omit<Complaint, "id" | "referenceNumber" | "createdAt">) => Promise<{ id: string; referenceNumber: string }>;
  respondToComplaint: (id: string, response: string, adminId?: string) => Promise<void>;
  loginAdmin: (identifier: string, password: string) => Promise<boolean>;
  logoutAdmin: () => void;
  validateStudent: (email: string, apogee: string, cin: string) => Promise<Student | null>;
  refetchRequests: () => void;
  refetchComplaints: () => void;
}

const AppContext = createContext<AppContextType | undefined>(undefined);

export function AppProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const [isAdminLoggedIn, setIsAdminLoggedIn] = useState(false);
  const [currentAdmin, setCurrentAdmin] = useState<AdminUser | null>(null);
  const [students] = useState<Student[]>(mockStudents);

  // Load admin from localStorage on mount
  useEffect(() => {
    const savedAdmin = localStorage.getItem("admin");
    if (savedAdmin) {
      try {
        const admin = JSON.parse(savedAdmin);
        setCurrentAdmin(admin);
        setIsAdminLoggedIn(true);
      } catch (e) {
        localStorage.removeItem("admin");
      }
    }
  }, []);

  // Fetch requests from API
  const { 
    data: requests = [], 
    isLoading: isLoadingRequests,
    refetch: refetchRequests,
    error: requestsError
  } = useQuery({
    queryKey: ["requests"],
    queryFn: getRequests,
    refetchInterval: 30000, 
    retry: 2,
    retryDelay: 1000,
  });

  // Log errors if they occur
  useEffect(() => {
    if (requestsError) {
      console.error("Erreur lors du chargement des demandes:", requestsError);
    }
  }, [requestsError]);

  // Fetch complaints from API
  const { 
    data: complaints = [], 
    isLoading: isLoadingComplaints,
    refetch: refetchComplaints,
    error: complaintsError
  } = useQuery({
    queryKey: ["complaints"],
    queryFn: getComplaints,
    refetchInterval: 30000, // Refetch every 30 seconds
    retry: 2,
    retryDelay: 1000,
  });

  // Log errors if they occur
  useEffect(() => {
    if (complaintsError) {
      console.error("Erreur lors du chargement des réclamations:", complaintsError);
    }
  }, [complaintsError]);

  // Create request mutation
  const createRequestMutation = useMutation({
    mutationFn: createRequestAPI,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["requests"] });
      await queryClient.refetchQueries({ queryKey: ["requests"] });
    },
  });

  // Create complaint mutation
  const createComplaintMutation = useMutation({
    mutationFn: createComplaintAPI,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["complaints"] });
      await queryClient.refetchQueries({ queryKey: ["complaints"] });
    },
  });

  // Update request status mutation
  const updateRequestStatusMutation = useMutation({
    mutationFn: ({ id, status, adminId, rejectionReason }: { id: string; status: "accepted" | "rejected" | "pending"; adminId?: string; rejectionReason?: string }) =>
      updateRequestStatusAPI(id, status, adminId, rejectionReason),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["requests"] });
      await queryClient.refetchQueries({ queryKey: ["requests"] });
    },
  });

  // Respond to complaint mutation
  const respondToComplaintMutation = useMutation({
    mutationFn: ({ id, response, adminId }: { id: string; response: string; adminId?: string }) =>
      respondToComplaintAPI(id, response, adminId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["complaints"] });
      await queryClient.refetchQueries({ queryKey: ["complaints"] });
    },
  });

  const addRequest = async (requestData: Omit<DocumentRequest, "id" | "referenceNumber" | "createdAt">): Promise<{ id: string; referenceNumber: string }> => {
    try {
      const result = await createRequestMutation.mutateAsync({
        studentId: requestData.studentId,
        documentType: requestData.documentType,
        academicYear: requestData.academicYear,
        semester: requestData.semester,
        companyName: requestData.companyName,
        companyAddress: requestData.companyAddress,
        supervisorName: requestData.supervisorName,
        supervisorEmail: requestData.supervisorEmail,
        supervisorPhone: requestData.supervisorPhone,
        stageStartDate: requestData.stageStartDate?.toISOString().split("T")[0],
        stageEndDate: requestData.stageEndDate?.toISOString().split("T")[0],
        stageSubject: requestData.stageSubject,
        academicSupervisor: requestData.academicSupervisor,
      });
      return { id: result.id, referenceNumber: result.referenceNumber };
    } catch (error) {
      console.error("Erreur lors de la création de la demande:", error);
      throw error;
    }
  };

  const updateRequestStatus = async (id: string, status: "accepted" | "rejected" | "pending", adminId?: string, rejectionReason?: string) => {
    try {
      await updateRequestStatusMutation.mutateAsync({ id, status, adminId: adminId || currentAdmin?.id, rejectionReason });
    } catch (error) {
      console.error("Erreur lors de la mise à jour du statut:", error);
      throw error;
    }
  };

  const addComplaint = async (complaintData: Omit<Complaint, "id" | "referenceNumber" | "createdAt">): Promise<{ id: string; referenceNumber: string }> => {
    try {
      const result = await createComplaintMutation.mutateAsync({
        email: complaintData.studentEmail,
        apogee: complaintData.apogee,
        cin: complaintData.cin,
        subject: complaintData.subject,
        description: complaintData.description,
        relatedRequestNumber: complaintData.relatedRequestNumber,
      });
      return { id: result.id, referenceNumber: result.referenceNumber };
    } catch (error) {
      console.error("Erreur lors de la création de la réclamation:", error);
      throw error;
    }
  };

  const respondToComplaint = async (id: string, response: string, adminId?: string) => {
    try {
      await respondToComplaintMutation.mutateAsync({ 
        id, 
        response, 
        adminId: adminId || currentAdmin?.id 
      });
    } catch (error) {
      console.error("Erreur lors de la réponse à la réclamation:", error);
      throw error;
    }
  };

  const loginAdmin = async (identifier: string, password: string): Promise<boolean> => {
    try {
      const admin = await loginAdminAPI(identifier, password);
      setIsAdminLoggedIn(true);
      setCurrentAdmin(admin);
      localStorage.setItem("admin", JSON.stringify(admin));
      return true;
    } catch (error) {
      console.error("Login error:", error);
      return false;
    }
  };

  const logoutAdmin = () => {
    setIsAdminLoggedIn(false);
    setCurrentAdmin(null);
    localStorage.removeItem("admin");
  };

  const validateStudent = async (email: string, apogee: string, cin: string): Promise<Student | null> => {
    try {
      const result = await validateStudentAPI(email, apogee, cin);
      return result.valid ? result.student : null;
    } catch (error) {
      console.error("Validation error:", error);
      return null;
    }
  };

  return (
    <AppContext.Provider
      value={{
        requests,
        complaints,
        students,
        isLoadingRequests,
        isLoadingComplaints,
        isAdminLoggedIn,
        currentAdmin,
        addRequest,
        updateRequestStatus,
        addComplaint,
        respondToComplaint,
        loginAdmin,
        logoutAdmin,
        validateStudent,
        refetchRequests: () => refetchRequests(),
        refetchComplaints: () => refetchComplaints(),
      }}
    >
      {children}
    </AppContext.Provider>
  );
}

export function useApp() {
  const context = useContext(AppContext);
  if (context === undefined) {
    throw new Error("useApp must be used within an AppProvider");
  }
  return context;
}
