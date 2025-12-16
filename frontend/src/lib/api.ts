function getApiBase(): string {
  if (import.meta.env.VITE_API_URL) {
    const url = import.meta.env.VITE_API_URL.trim()
    return url.endsWith('/') ? url.slice(0, -1) : url
  }
  if (import.meta.env.DEV) return "" // proxy Vite
  return "http://localhost/PLATEFORME_GESTION_SERVICES_ETUDIANTS/backend"
}

const API_BASE = getApiBase()

async function fetchJson<T>(path: string, options?: RequestInit): Promise<T> {
  const res = await fetch(`${API_BASE}${path}`, {
    headers: { "Content-Type": "application/json" },
    ...options,
  })
  if (!res.ok) {
    let msg = `Erreur API (${res.status})`
    try { msg = (await res.json()).error || msg } catch {}
    throw new Error(msg)
  }
  const ct = res.headers.get("content-type")
  return ct && ct.includes("application/json") ? res.json() : (await res.text() as T)
}

// Exemples d’appels
export const getHealth = () => fetchJson<{ status: string }>("/api/health")
export const getRequests = () => fetchJson<any[]>("/api/requests")
export const createRequest = (data: any) =>
  fetchJson("/api/requests", { method: "POST", body: JSON.stringify(data) })