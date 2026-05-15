let sections = Array.from(document.querySelectorAll(".form-section"));

function refreshReportesSections() {
  const root = document.querySelector(".sigtae-reportes-module");
  const found = root
    ? Array.from(root.querySelectorAll(".form-section"))
    : Array.from(document.querySelectorAll(".form-section"));
  if (found.length) {
    sections = found;
  }
}
const LOGIN_API_BASE_URLS = [
  "http://api.dvmc.cfemex.com/ad/validacion",
  "http://api.dvmc.cfemex.com/public/ad/validacion",
  "/ad/validacion",
  "/public/ad/validacion",
];
const RPE_PROFILE_API_URL_TEMPLATE = "http://10.4.157.20/api/consulta/{rpe}";
const LOCAL_RPE_DIRECTORY = {};
const EVALUATIONS_STORAGE_PREFIX = "reportes:evaluaciones:";
const PRUEBAS_STORAGE_KEY = "pruebas";
const USUARIOS_STORAGE_KEY = "usuarios";
const RESULTADOS_STORAGE_KEY = "resultados";
const WORKFLOW_STORAGE_KEY = "reportes:workflow";
const ADMIN_REVIEWERS_STORAGE_KEY = "reportes:admin-reviewers";
const MASTER_RPE = "G44BR";
const TP_PRIMARY_VOLTAGE_FIXED = "14400";
const TC_PRIMARY_CURRENT_10_5_FIXED = "10";
const TC_PRIMARY_CURRENT_50_5_FIXED = "50";
const TC_PRIMARY_CURRENT_200_5_FIXED = "200";
const FIXED_SIGNATURE_AREA = "PREPARACION DE MEDIDORES";
// TODO: Reemplazar con los 3 RPE reales iniciales de jefaturas.
const ADMIN_REVIEWER_DEFAULT_RPES = ["ADMIN1", "ADMIN2", "ADMIN3"];

/** Base URL de assets del módulo (integración SIGTAE o ruta standalone). */
function getReportesAssetBaseUrl() {
  const custom = window.__SIGTAE_REPORTES_BASE;
  if (custom) {
    const path = String(custom).replace(/\/?$/, "/");
    if (/^https?:\/\//i.test(path)) {
      return path;
    }
    return `${window.location.origin}${path.startsWith("/") ? path : `/${path}`}`;
  }
  return `${window.location.origin}${window.location.pathname.replace(/\/[^/]*$/, "/")}`;
}

function isSigtaeEmbeddedReportes() {
  return Boolean(window.__SIGTAE_REPORTES_USER && window.__SIGTAE_REPORTES_USER.rpe);
}
let currentStep = Math.max(
  0,
  sections.findIndex((section) => section.classList.contains("active"))
);

if (currentStep === -1) {
  currentStep = 0;
}

/* =============================================================================
   app.js — Lógica del formulario de evaluaciones y flujo de aprobación
   
   Flujo general:
   1) Constantes, pasos del DOM (`sections`) y estado de navegación (`currentStep`).
   2) Persistencia en localStorage/sessionStorage (evaluaciones por RPE, pruebas,
      usuarios, resultados, workflow pendiente/aprobado, lista de revisores).
   3) Funciones de negocio: fusión de listas, permisos (maestro / revisor / usuario),
      captura y restauración del formulario, validación por sección, cálculos TP/TC.
   4) Autenticación, consulta de RPE en API, PDF vía HTML + window.print().
   5) `DOMContentLoaded`: engancha botones, hub, guardado, bandeja de administración.
   ============================================================================= */

/** Lee `reportesAuth` desde sessionStorage; devuelve el objeto de sesión o null. */
function getAuthSession() {
  const raw = window.sessionStorage.getItem("reportesAuth");
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch (_err) {
    return null;
  }
}

/** RPE normalizado del usuario en sesión (vacío si no hay sesión). */
function getCurrentRpe() {
  const session = getAuthSession();
  return normalizeRpe(session?.rpe || "");
}

/** Clave localStorage para el arreglo de evaluaciones guardadas de un RPE. */
function getEvaluationsStorageKey(rpe) {
  return `${EVALUATIONS_STORAGE_PREFIX}${normalizeRpe(rpe)}`;
}

/** Devuelve el arreglo de evaluaciones guardadas de un usuario (o [] si no hay datos). */
function getSavedEvaluations(rpe) {
  const key = getEvaluationsStorageKey(rpe);
  const raw = window.localStorage.getItem(key);
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (_err) {
    return [];
  }
}

/** Persiste el arreglo completo de evaluaciones de un RPE en localStorage. */
function setSavedEvaluations(rpe, evaluations) {
  const key = getEvaluationsStorageKey(rpe);
  window.localStorage.setItem(key, JSON.stringify(evaluations));
}

/** Lee el almacén global de “pruebas” (historial técnico ligado a evaluaciones). */
function getPruebasStore() {
  const raw = window.localStorage.getItem(PRUEBAS_STORAGE_KEY);
  if (!raw) {
    return { pruebas: [] };
  }
  try {
    const parsed = JSON.parse(raw);
    if (parsed && Array.isArray(parsed.pruebas)) {
      return parsed;
    }
  } catch (_err) {
    // noop
  }
  return { pruebas: [] };
}

/** Guarda el objeto de pruebas en localStorage. */
function setPruebasStore(store) {
  window.localStorage.setItem(PRUEBAS_STORAGE_KEY, JSON.stringify(store));
}

/** Catálogo local de usuarios (RPE, zona, flags admin) usado para filtros y registro. */
function getUsuariosStore() {
  const raw = window.localStorage.getItem(USUARIOS_STORAGE_KEY);
  if (!raw) {
    return { usuarios: [] };
  }
  try {
    const parsed = JSON.parse(raw);
    if (parsed && Array.isArray(parsed.usuarios)) {
      return parsed;
    }
  } catch (_err) {
    // noop
  }
  return { usuarios: [] };
}

/** Persiste el catálogo de usuarios. */
function setUsuariosStore(store) {
  window.localStorage.setItem(USUARIOS_STORAGE_KEY, JSON.stringify(store));
}

/** Bitácora de resultados/acciones (alta, actualización, envíos a aprobación, etc.). */
function getResultadosStore() {
  const raw = window.localStorage.getItem(RESULTADOS_STORAGE_KEY);
  if (!raw) {
    return { resultados: [] };
  }
  try {
    const parsed = JSON.parse(raw);
    if (parsed && Array.isArray(parsed.resultados)) {
      return parsed;
    }
  } catch (_err) {
    // noop
  }
  return { resultados: [] };
}

/** Guarda la bitácora de resultados. */
function setResultadosStore(store) {
  window.localStorage.setItem(RESULTADOS_STORAGE_KEY, JSON.stringify(store));
}

/**
 * Flujo de aprobación: estructura `{ pending: [], approved: [] }` en localStorage.
 * Cada ítem incluye evaluationId, reviewerRpe, snapshot, fechas, etc.
 */
function getWorkflowStore() {
  const raw = window.localStorage.getItem(WORKFLOW_STORAGE_KEY);
  if (!raw) {
    return { pending: [], approved: [] };
  }
  try {
    const parsed = JSON.parse(raw);
    return {
      pending: Array.isArray(parsed?.pending) ? parsed.pending : [],
      approved: Array.isArray(parsed?.approved) ? parsed.approved : [],
    };
  } catch (_err) {
    return { pending: [], approved: [] };
  }
}

/** Escribe el almacén de workflow completo. */
function setWorkflowStore(store) {
  window.localStorage.setItem(WORKFLOW_STORAGE_KEY, JSON.stringify(store));
}

/**
 * Inserta o actualiza una entrada en `pending` por `evaluationId`.
 * Mantiene status "pending" y conserva datos previos mezclados con `entry`.
 */
function upsertPendingWorkflowEntry(entry) {
  const store = getWorkflowStore();
  const idx = store.pending.findIndex((x) => x.evaluationId === entry.evaluationId);
  if (idx >= 0) {
    store.pending[idx] = { ...store.pending[idx], ...entry, status: "pending" };
  } else {
    store.pending.unshift({ ...entry, status: "pending" });
  }
  setWorkflowStore(store);
}

/**
 * Propaga `snapshot` y `performedBy` al ítem correspondiente en workflow (pendiente o aprobado)
 * y actualiza la copia en guardadas del revisor vía `upsertEvaluationForUser`.
 * Se usa al guardar una evaluación ya vinculada al flujo para que PDF y bandejas muestren datos actuales.
 * @returns {boolean} true si hubo coincidencia en pending o approved.
 */
function syncWorkflowDataAfterEvaluationEdit(evaluationId, patch) {
  const id = String(evaluationId || "").trim();
  if (!id || !patch) {
    return false;
  }
  const snapshot = patch.snapshot || {};
  const performedBy = patch.performedBy || {};
  const now = Number(patch.updatedAt || Date.now());
  const nowIso = String(patch.updatedAtIso || formatNowIsoLocal());
  const ownerRpe = normalizeRpe(patch.ownerRpe || "");

  const store = getWorkflowStore();
  const pendingIdx = (store.pending || []).findIndex((x) => String(x.evaluationId || "") === id);
  if (pendingIdx >= 0) {
    const cur = store.pending[pendingIdx];
    const reviewerRpe = normalizeRpe(cur.reviewerRpe || "");
    store.pending[pendingIdx] = {
      ...cur,
      snapshot,
      performedBy,
      updatedAt: now,
      fechaHora: nowIso,
      updatedAtIso: nowIso,
      status: "pending",
    };
    setWorkflowStore(store);
    if (reviewerRpe) {
      upsertEvaluationForUser(reviewerRpe, {
        id,
        userId: cur.requestedByRpe || cur.userId || ownerRpe,
        reviewerRpe,
        timestamp: cur.timestamp || now,
        fechaHora: cur.fechaHora || nowIso,
        createdAt: cur.createdAt || cur.timestamp || now,
        updatedAt: now,
        createdAtIso: cur.createdAtIso || cur.fechaHora || nowIso,
        updatedAtIso: nowIso,
        snapshot,
        performedBy,
        workflowStatus: "pending",
      });
    }
    return true;
  }

  const approvedIdx = (store.approved || []).findIndex((x) => String(x.evaluationId || "") === id);
  if (approvedIdx >= 0) {
    const cur = store.approved[approvedIdx];
    const reviewerRpe = normalizeRpe(cur.reviewerRpe || "");
    store.approved[approvedIdx] = {
      ...cur,
      snapshot,
      performedBy,
      updatedAt: now,
      updatedAtIso: nowIso,
      status: "approved",
    };
    setWorkflowStore(store);
    if (reviewerRpe) {
      upsertEvaluationForUser(reviewerRpe, {
        id,
        userId: cur.requestedByRpe || cur.userId || ownerRpe,
        reviewerRpe,
        timestamp: cur.timestamp || now,
        fechaHora: cur.fechaHora || nowIso,
        createdAt: cur.createdAt || cur.timestamp || now,
        updatedAt: now,
        createdAtIso: cur.createdAtIso || cur.fechaHora || nowIso,
        updatedAtIso: nowIso,
        snapshot,
        performedBy,
        workflowStatus: "approved",
      });
    }
    return true;
  }

  return false;
}

/**
 * Mueve una evaluación de `pending` a `approved`, registrando quién aprobó y cuándo.
 * @returns {object|null} El objeto aprobado o null si no existía en pendientes.
 */
function approveWorkflowEntry(evaluationId, approvedByRpe) {
  const store = getWorkflowStore();
  const idx = store.pending.findIndex((x) => x.evaluationId === evaluationId);
  if (idx < 0) return null;
  const item = store.pending[idx];
  store.pending.splice(idx, 1);
  const approvedItem = {
    ...item,
    status: "approved",
    approvedAt: Date.now(),
    approvedAtIso: formatNowIsoLocal(),
    approvedBy: normalizeRpe(approvedByRpe),
  };
  store.approved.unshift(approvedItem);
  setWorkflowStore(store);
  return approvedItem;
}

/** Busca un pendiente por `evaluationId` o null. */
function getWorkflowPendingEntryByEvaluationId(evaluationId) {
  const id = String(evaluationId || "").trim();
  if (!id) return null;
  const store = getWorkflowStore();
  return (store.pending || []).find((x) => String(x.evaluationId || "") === id) || null;
}

/** Busca un aprobado por `evaluationId` o null. */
function getWorkflowApprovedEntryByEvaluationId(evaluationId) {
  const id = String(evaluationId || "").trim();
  if (!id) return null;
  const store = getWorkflowStore();
  return (store.approved || []).find((x) => String(x.evaluationId || "") === id) || null;
}

/**
 * Indica si la evaluación está solo en aprobados (no en pendientes): modo solo lectura + solo PDF.
 */
function isEvaluationApprovedOnlyInWorkflow(evaluationId) {
  const id = String(evaluationId || "").trim();
  if (!id) {
    return false;
  }
  if (getWorkflowPendingEntryByEvaluationId(id)) {
    return false;
  }
  return Boolean(getWorkflowApprovedEntryByEvaluationId(id));
}

/** Devuelve "pending", "approved" o cadena vacía según exista el id en el workflow. */
function resolveWorkflowUiStatusForEvaluationId(evaluationId) {
  if (getWorkflowPendingEntryByEvaluationId(evaluationId)) {
    return "pending";
  }
  if (getWorkflowApprovedEntryByEvaluationId(evaluationId)) {
    return "approved";
  }
  return "";
}

/**
 * Recorre todas las secciones del formulario y devuelve el primer campo requerido vacío
 * (objeto `{ section, missing }`) o null si todo está completo.
 */
function getFirstMissingRequiredFieldAnywhere() {
  for (const section of sections) {
    const missing = getFirstMissingRequiredField(section);
    if (missing) {
      return { section, missing };
    }
  }
  return null;
}

/**
 * Inserta o fusiona una evaluación en la lista guardada de `ownerRpe` (por ejemplo copia del revisor).
 */
function upsertEvaluationForUser(ownerRpe, evaluation) {
  const rpe = normalizeRpe(ownerRpe);
  if (!rpe || !evaluation?.id) return;
  const list = getSavedEvaluations(rpe);
  const idx = list.findIndex((x) => x.id === evaluation.id);
  if (idx >= 0) {
    list[idx] = { ...list[idx], ...evaluation };
  } else {
    list.unshift({ ...evaluation });
  }
  setSavedEvaluations(rpe, list);
}

/**
 * Convierte entradas del workflow (pendiente + aprobado) al formato de “evaluación guardada”.
 * Se listan todas para permitir consulta global en el hub.
 */
function getAssignedWorkflowEvaluations(reviewerRpe) {
  const store = getWorkflowStore();
  const assigned = [...(store.pending || []), ...(store.approved || [])];
  return assigned.map((item) => ({
    id: item.evaluationId,
    userId: item.requestedByRpe || item.userId || "",
    reviewerRpe: item.reviewerRpe || "",
    timestamp: item.timestamp || Date.now(),
    fechaHora: item.fechaHora || "",
    createdAt: item.createdAt || item.timestamp || Date.now(),
    updatedAt: item.updatedAt || item.timestamp || Date.now(),
    createdAtIso: item.createdAtIso || item.fechaHora || "",
    updatedAtIso: item.updatedAtIso || item.fechaHora || "",
    snapshot: item.snapshot || {},
    performedBy: item.performedBy || {},
    workflowStatus: item.status || "pending",
  }));
}

/**
 * Mezcla en las guardadas del revisor las evaluaciones asignadas por workflow
 * (sin pisar versiones más recientes por timestamp).
 */
function syncAssignedWorkflowToSavedEvaluations(reviewerRpe) {
  const assigned = getAssignedWorkflowEvaluations(reviewerRpe);
  if (!assigned.length) return;
  const current = getSavedEvaluations(reviewerRpe);
  const byId = new Map((current || []).map((item) => [item.id, item]));
  assigned.forEach((item) => {
    const prev = byId.get(item.id);
    if (!prev) {
      byId.set(item.id, item);
      return;
    }
    const prevTime = Number(prev.updatedAt || prev.timestamp || 0);
    const nextTime = Number(item.updatedAt || item.timestamp || 0);
    byId.set(item.id, nextTime >= prevTime ? { ...prev, ...item } : prev);
  });
  setSavedEvaluations(reviewerRpe, Array.from(byId.values()));
}

/**
 * Reconstruye candidatos “pendientes” a partir de la bitácora `resultados` cuando el snapshot
 * indica que `reviso_rpe` es el revisor buscado (compatibilidad con datos antiguos).
 */
function getReviewerEvaluationsFromResultados(reviewerRpe) {
  const target = normalizeRpe(reviewerRpe);
  if (!target) return [];
  const store = getResultadosStore();
  const latestByEvalId = new Map();
  (store.resultados || []).forEach((entry) => {
    const evalId = String(entry?.evaluationId || "").trim();
    if (!evalId) return;
    const snapshot = entry?.snapshot || {};
    const revisoRpe = normalizeRpe(snapshot?.reviso_rpe || "");
    if (revisoRpe !== target) return;
    const candidate = {
      id: evalId,
      userId: entry?.userId || "",
      reviewerRpe: revisoRpe,
      timestamp: entry?.timestamp || Date.now(),
      fechaHora: entry?.fechaHora || "",
      createdAt: entry?.timestamp || Date.now(),
      updatedAt: entry?.timestamp || Date.now(),
      createdAtIso: entry?.fechaHora || "",
      updatedAtIso: entry?.fechaHora || "",
      snapshot,
      performedBy: entry?.performedBy || {},
      workflowStatus: "pending",
    };
    const prev = latestByEvalId.get(evalId);
    if (!prev) {
      latestByEvalId.set(evalId, candidate);
      return;
    }
    const prevTime = Number(prev.updatedAt || prev.timestamp || 0);
    const nextTime = Number(candidate.updatedAt || candidate.timestamp || 0);
    if (nextTime >= prevTime) {
      latestByEvalId.set(evalId, candidate);
    }
  });
  return Array.from(latestByEvalId.values());
}

/**
 * Lista unificada para el hub: propias + asignadas por workflow + derivadas de resultados,
 * deduplicadas por id conservando la versión más reciente.
 */
function getMergedEvaluationsForUser(rpe) {
  const target = normalizeRpe(rpe);
  if (!target) return [];
  const ownEvaluations = getSavedEvaluations(target);
  const assignedWorkflow = getAssignedWorkflowEvaluations(target);
  const assignedFromResultados = getReviewerEvaluationsFromResultados(target);
  const mergedById = new Map();
  [...ownEvaluations, ...assignedWorkflow, ...assignedFromResultados].forEach((item) => {
    if (!item?.id) return;
    const prev = mergedById.get(item.id);
    if (!prev) {
      mergedById.set(item.id, item);
      return;
    }
    const prevTime = Number(prev.updatedAt || prev.timestamp || 0);
    const nextTime = Number(item.updatedAt || item.timestamp || 0);
    mergedById.set(item.id, nextTime >= prevTime ? item : prev);
  });
  return Array.from(mergedById.values()).sort(
    (a, b) => Number(b.updatedAt || b.timestamp || 0) - Number(a.updatedAt || a.timestamp || 0)
  );
}

/** Añade un registro al inicio de la bitácora `resultados`. */
function appendResultadoEntry(entry) {
  const store = getResultadosStore();
  store.resultados.unshift(entry);
  setResultadosStore(store);
}

/** Texto visible de la zona seleccionada en el select `#opciones` (para persistir en usuario). */
function getCurrentZonaText() {
  const zona = document.getElementById("opciones");
  if (!zona || zona.tagName !== "SELECT") {
    return "";
  }
  const opt = zona.options[zona.selectedIndex];
  return String(opt ? opt.text : "").trim();
}

/**
 * Convierte valor guardado (value o texto) en etiqueta legible usando las opciones del DOM de ZONA.
 */
function resolveZonaLabel(rawValue) {
  const raw = String(rawValue || "").trim();
  if (!raw) return "";
  const zona = document.getElementById("opciones");
  if (!zona || zona.tagName !== "SELECT") {
    return raw;
  }
  const exactByValue = Array.from(zona.options || []).find(
    (opt) => String(opt.value || "").trim() === raw
  );
  if (exactByValue) {
    return String(exactByValue.text || "").trim() || raw;
  }
  const exactByText = Array.from(zona.options || []).find(
    (opt) => String(opt.text || "").trim() === raw
  );
  if (exactByText) {
    return String(exactByText.text || "").trim() || raw;
  }
  return raw;
}

/** Interpreta strings/números típicos de APIs como booleano; si no coincide, usa `fallback`. */
function parseBooleanLike(value, fallback = false) {
  if (value === null || value === undefined || value === "") {
    return fallback;
  }
  if (typeof value === "boolean") {
    return value;
  }
  if (typeof value === "number") {
    return value !== 0;
  }
  const normalized = String(value).trim().toLowerCase();
  if (["true", "1", "yes", "si", "sí", "y"].includes(normalized)) {
    return true;
  }
  if (["false", "0", "no", "n"].includes(normalized)) {
    return false;
  }
  return fallback;
}

/** Etiqueta de perfil de negocio: MAESTRO, ADMIN o USUARIO. */
function resolveUserProfile(isAdmin, isMaster = false) {
  if (isMaster) return "MAESTRO";
  return isAdmin ? "ADMIN" : "USUARIO";
}

/** Fusiona o crea un registro en el catálogo local `usuarios` por RPE. */
function upsertUsuarioRecord(partial) {
  const rpe = normalizeRpe(partial?.RPE || partial?.rpe || "");
  if (!rpe) {
    return;
  }
  const usuariosStore = getUsuariosStore();
  const idx = usuariosStore.usuarios.findIndex((u) => normalizeRpe(u.RPE || "") === rpe);
  const prev = idx >= 0 ? usuariosStore.usuarios[idx] : {};
  const isMaster = normalizeRpe(rpe) === normalizeRpe(MASTER_RPE);
  const isAdmin = parseBooleanLike(partial?.isAdmin ?? prev.isAdmin, false) || isMaster;
  const activo = parseBooleanLike(partial?.activo ?? prev.activo, true);
  const next = {
    RPE: rpe,
    NOMBRE: String(partial?.NOMBRE ?? prev.NOMBRE ?? "").trim(),
    ZONA: String(partial?.ZONA ?? prev.ZONA ?? "").trim(),
    isAdmin,
    isMaster,
    perfil: resolveUserProfile(isAdmin, isMaster),
    activo,
    updatedAt: Date.now(),
  };
  if (idx >= 0) {
    usuariosStore.usuarios[idx] = next;
  } else {
    usuariosStore.usuarios.unshift(next);
  }
  setUsuariosStore(usuariosStore);
}

/** Marca de tiempo ISO UTC para guardados y bitácora. */
function formatNowIsoLocal() {
  return new Date().toISOString();
}

/** Captura bloque “Realizó” (rpe, nombre, área, fecha) para adjuntar a evaluación/resultados. */
function buildPerformedBySnapshot() {
  return {
    rpe: String(document.getElementById("realizo_rpe")?.value || "").trim(),
    nombre: String(document.getElementById("realizo_nom")?.value || "").trim(),
    area: String(document.getElementById("realizo_area")?.value || "").trim(),
    fecha: String(document.getElementById("realizo_fecha")?.value || "").trim(),
  };
}

/** Rellena campos “Realizó” desde un objeto guardado y revalida RPE. */
function applyPerformedBySnapshot(performedBy) {
  if (!performedBy || typeof performedBy !== "object") {
    return;
  }
  const set = (id, v) => {
    const el = document.getElementById(id);
    if (!el) return;
    if (id === "realizo_rpe") {
      el.value = normalizeRpe(v);
      return;
    }
    if (id === "realizo_fecha") {
      el.value = String(v || "");
      return;
    }
    el.value = normalizeUpperText(v);
  };
  set("realizo_rpe", performedBy.rpe);
  set("realizo_nom", performedBy.nombre);
  set("realizo_area", FIXED_SIGNATURE_AREA);
  set("realizo_fecha", performedBy.fecha);
  enforceFixedAreaFields();
  triggerRpeValidationById("realizo_rpe");
}

/**
 * Toma todos los inputs/selects con id dentro de `.app-shell`, excluyendo el hub de evaluaciones.
 * Devuelve un objeto plano id → value para persistir o enviar a PDF.
 */
function collectFormSnapshot() {
  const data = {};
  document.querySelectorAll(".app-shell input[id], .app-shell select[id], .app-shell textarea[id]").forEach((el) => {
    if (!el.id) return;
    if (el.closest("#evaluationHub")) return;
    if (el.id === "savedEvaluations") return;
    data[el.id] = el.value;
  });
  return data;
}

/**
 * Vuelca un snapshot guardado en el DOM (convierte fechas dd/mm/yyyy a ISO si aplica),
 * dispara eventos y recalcula series y progreso.
 */
function applyFormSnapshot(snapshot) {
  Object.entries(snapshot || {}).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (!el) return;
    if (el.type === "date") {
      const raw = String(value || "").trim();
      let normalizedDate = raw;
      const m = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
      if (m) {
        normalizedDate = `${m[3]}-${m[2]}-${m[1]}`;
      }
      el.value = normalizedDate;
    } else {
      el.value = value;
    }
    el.dispatchEvent(new Event("input", { bubbles: true }));
    el.dispatchEvent(new Event("change", { bubbles: true }));
  });
  // Reaplica defaults por categoría para evitar snapshots viejos con campos vacíos.
  const categoriaEl = document.getElementById("tp_categoria");
  const categoriaValue = String(categoriaEl?.value || "");
  applyTpCategoryDefaults();
  applyTcCategoryDefaults(categoriaValue);
  // Garantiza voltaje primario TP fijo (no editable) incluso al cargar snapshots viejos.
  enforceFixedTpPrimaryVoltage();
  enforceFixedAreaFields();
  updateElementoSerieVisuales();
  recalculateTpVoltSec();
  recalculateTcCorrSec();
  updateFormFillProgress();
}

/** Fuerza 14,400 V en TP primario para las 3 pruebas y mantiene bloqueo de edición. */
function enforceFixedTpPrimaryVoltage() {
  ["tp_vp1", "tp_vp2", "tp_vp3"].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = TP_PRIMARY_VOLTAGE_FIXED;
    el.readOnly = true;
    el.disabled = true;
  });
}

/** Fuerza área institucional en firmas y evita edición manual. */
function enforceFixedAreaFields() {
  ["realizo_area", "reviso_area", "recibe_area"].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = FIXED_SIGNATURE_AREA;
    el.readOnly = true;
    el.disabled = true;
  });
}

/** Normaliza mayúsculas/RPE en firmas y asegura que fechas de revisor/recibe no queden bloqueadas por error. */
function normalizeFirmasFields() {
  ["realizo_nom", "realizo_area", "reviso_nom", "reviso_area", "recibe_nom", "recibe_area"].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = normalizeUpperText(el.value);
  });
  ["realizo_rpe", "reviso_rpe", "recibe_rpe"].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = normalizeRpe(el.value);
  });
  ["reviso_fecha", "recibe_fecha"].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.disabled = false;
    el.readOnly = false;
  });
  enforceFixedAreaFields();
}

/** Limpia campos editables del formulario principal (nueva evaluación); preserva categoría TP. */
function clearEditableFormFields() {
  document.querySelectorAll(".app-shell input.editable, .app-shell select.field-input, .app-shell textarea.editable").forEach((el) => {
    if (el.id === "tp_categoria") return;
    el.value = "";
    el.dispatchEvent(new Event("input", { bubbles: true }));
    el.dispatchEvent(new Event("change", { bubbles: true }));
  });
  const marca = document.getElementById("opciones/marca");
  if (marca) {
    marca.value = "";
    marca.dispatchEvent(new Event("change", { bubbles: true }));
  }
}

/**
 * Autollenado TP según categoría.
 * Los campos de elemento `tp_serie_1..3` son captura manual y NO se sobreescriben aquí.
 */
function applyTpCategoryDefaults() {
  const setValue = (id, value) => {
    const el = document.getElementById(id);
    if (el) {
      el.value = value;
    }
  };

  const clearTpAutofillBlock = () => {
    setValue("tp_relacion", "");
    setValue("tp_constante", "");
    setValue("tp_pot_max", "");
    setValue("tp_clase", "");
    updateElementoSerieVisuales();
  };

  const fillTpAutofillBlock = () => {
    setValue("tp_relacion", "14400:120V");
    setValue("tp_constante", "120");
    setValue("tp_pot_max", "500");
    setValue("tp_clase", "0.2 V");
    updateElementoSerieVisuales();
  };

  const categoria = document.getElementById("tp_categoria");
  if (!categoria || !categoria.value) {
    clearTpAutofillBlock();
    return;
  }

  if (categoria.value === "10-5") {
    setValue("tp_relacion", "14400:120V");
    setValue("tp_constante", "120");
    setValue("tp_pot_max", "500");
    setValue("tp_clase", "0.2 V");
    updateElementoSerieVisuales();
    return;
  }

  if (categoria.value === "200-5") {
    fillTpAutofillBlock();
    return;
  }

  clearTpAutofillBlock();
}

/**
 * Autollenado TC según categoría TP.
 * Los campos de elemento `tc_serie_1..3` son captura manual y NO se sobreescriben aquí.
 */
function applyTcCategoryDefaults(categoryValue) {
  const setValue = (id, value) => {
    const el = document.getElementById(id);
    if (el) {
      el.value = value;
    }
  };

  // Coinciden con los value iniciales de los inputs deshabilitados en la sección TC (index.php).
  const tcSobrecorrienteDefault = "10 In";
  const tcClaseDefault = "0.2 A";

  const clearTcSeriesRelacionConstante = () => {
    setValue("tc_relacion", "");
    setValue("tc_constante", "");
  };

  const resetTcSobrecorrienteYClase = () => {
    setValue("tc_sobrecorriente", tcSobrecorrienteDefault);
    setValue("tc_clase", tcClaseDefault);
  };

  const syncTcPrimaryCurrentByCategory = (cat) => {
    const normalizedCat = String(cat || "");
    const lockAs200 = normalizedCat === "200-5";
    const lockAs10 = normalizedCat === "10-5";
    const lockAs50 = normalizedCat === "50-5";
    ["tc_cp1", "tc_cp2", "tc_cp3"].forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      if (lockAs200) {
        el.value = TC_PRIMARY_CURRENT_200_5_FIXED;
        el.readOnly = true;
        el.disabled = true;
      } else if (lockAs50) {
        el.value = TC_PRIMARY_CURRENT_50_5_FIXED;
        el.readOnly = true;
        el.disabled = true;
      } else if (lockAs10) {
        el.value = TC_PRIMARY_CURRENT_10_5_FIXED;
        el.readOnly = true;
        el.disabled = true;
      } else {
        el.readOnly = false;
        el.disabled = false;
        if (
          [TC_PRIMARY_CURRENT_200_5_FIXED, TC_PRIMARY_CURRENT_50_5_FIXED, TC_PRIMARY_CURRENT_10_5_FIXED].includes(
            String(el.value || "").trim()
          )
        ) {
          el.value = "";
        }
      }
    });
  };

  const applyTcSameAsEmptyOrTenFive = () => {
    clearTcSeriesRelacionConstante();
    resetTcSobrecorrienteYClase();
    updateElementoSerieVisuales();
  };

  if (!categoryValue) {
    syncTcPrimaryCurrentByCategory(categoryValue);
    applyTcSameAsEmptyOrTenFive();
    return;
  }

  if (categoryValue === "10-5") {
    syncTcPrimaryCurrentByCategory(categoryValue);
    setValue("tc_relacion", "10:5");
    setValue("tc_constante", "2");
    resetTcSobrecorrienteYClase();
    updateElementoSerieVisuales();
    return;
  }

  if (categoryValue === "200-5") {
    syncTcPrimaryCurrentByCategory(categoryValue);
    setValue("tc_relacion", "200:5");
    setValue("tc_constante", "40");
    setValue("tc_sobrecorriente", "10 In");
    setValue("tc_clase", "0.2 A");
    updateElementoSerieVisuales();
    return;
  }

  syncTcPrimaryCurrentByCategory(categoryValue);
  applyTcSameAsEmptyOrTenFive();
}

/** Copia los números de serie TP/TC a las etiquetas de aislamiento y conexión para lectura humana. */
function updateElementoSerieVisuales() {
  const dash = "—";
  for (let i = 1; i <= 3; i += 1) {
    const tp = document.getElementById(`tp_serie_${i}`);
    const tc = document.getElementById(`tc_serie_${i}`);
    const tpVal = tp ? String(tp.value || "").trim() : "";
    // TC muestra el mismo elemento capturado en TP (solo lectura).
    if (tc && String(tc.value || "").trim() !== tpVal) {
      tc.value = tpVal;
    }
    const tcVal = tc ? String(tc.value || "").trim() : "";
    const tpAis = document.getElementById(`tp_ais_row_label_${i}`);
    const tcAis = document.getElementById(`tc_ais_row_label_${i}`);
    if (tpAis) tpAis.textContent = tpVal || dash;
    if (tcAis) tcAis.textContent = tcVal || dash;
    const tpConn = document.getElementById(`tp_conn_serie_${i}`);
    const tcConn = document.getElementById(`tc_conn_serie_${i}`);
    if (tpConn) tpConn.textContent = tpVal || dash;
    if (tcConn) tcConn.textContent = tcVal || dash;
  }
}

/** Al salir de un porcentaje, limpia símbolos y deja un número razonablemente redondeado. */
function normalizePercentField(el) {
  if (!el || !el.classList.contains("td-input-percent")) {
    return;
  }
  let raw = String(el.value || "").trim().replace(",", ".").replace(/%/g, "");
  if (raw === "") {
    el.value = "";
    return;
  }
  // Permite negativos y decimales: -12.34, 0.5, 10
  raw = raw.replace(/[^0-9.\-]/g, "");
  if (raw.startsWith("--")) {
    raw = raw.replace(/^-+/, "-");
  }
  const minus = raw.startsWith("-") ? "-" : "";
  raw = minus + raw.replace(/-/g, "").replace(/^(-?)\./, "$10.");
  const firstDot = raw.indexOf(".");
  if (firstDot !== -1) {
    raw = raw.slice(0, firstDot + 1) + raw.slice(firstDot + 1).replace(/\./g, "");
  }
  const n = Number(raw);
  if (!Number.isFinite(n)) {
    return;
  }
  const normalized = n;
  el.value =
    Math.abs(normalized - Math.round(normalized)) < 1e-9
      ? String(Math.round(normalized))
      : String(Number(normalized.toFixed(4)));
}

/** Parseo tolerante de decimales con coma o punto. */
function parseDecimal(value) {
  if (typeof value !== "string") {
    return Number.NaN;
  }
  const normalized = value.replace(",", ".").trim();
  if (!normalized) {
    return Number.NaN;
  }
  return Number(normalized);
}

/** Formato compacto para celdas calculadas (hasta 4 decimales). */
function formatDecimal(value) {
  return Number(value.toFixed(4)).toString();
}

/** Calcula voltaje secundario TP (Vs) a partir de Vp y relación en cada elemento. */
function recalculateTpVoltSec() {
  for (let test = 1; test <= 3; test += 1) {
    const vpEl = document.getElementById(`tp_vp${test}`);
    const vp = parseDecimal(vpEl ? vpEl.value : "");
    const relEl = document.getElementById(`tp_rel${test}_e1`);
    const vsEl = document.getElementById(`tp_vs${test}_e1`);
    if (!relEl || !vsEl) {
      continue;
    }

    const rel = parseDecimal(relEl.value);
    if (!Number.isFinite(vp) || !Number.isFinite(rel) || rel === 0) {
      vsEl.value = "";
    } else {
      vsEl.value = formatDecimal(vp / rel);
    }
  }
}

/** Calcula corriente secundaria TC (Cs) a partir de Cp y relación en cada elemento. */
function recalculateTcCorrSec() {
  for (let test = 1; test <= 3; test += 1) {
    const cpEl = document.getElementById(`tc_cp${test}`);
    const cp = parseDecimal(cpEl ? cpEl.value : "");
    const relEl = document.getElementById(`tc_rel${test}_e1`);
    const csEl = document.getElementById(`tc_cs${test}_e1`);
    if (!relEl || !csEl) {
      continue;
    }

    const rel = parseDecimal(relEl.value);
    if (!Number.isFinite(cp) || !Number.isFinite(rel) || rel === 0) {
      csEl.value = "";
    } else {
      csEl.value = formatDecimal(cp / rel);
    }
  }
}

/** Indica si el control cuenta para el porcentaje de llenado del formulario (excluye hub, calc, etc.). */
function fieldCountsTowardFill(el) {
  if (!el || el.disabled || el.readOnly) {
    return false;
  }
  if (el.closest("#evaluationHub")) {
    return false;
  }
  const tag = el.tagName;
  const type = String(el.type || "").toLowerCase();
  if (type === "hidden" || type === "button" || type === "submit" || type === "reset") {
    return false;
  }
  if (el.classList.contains("calc")) {
    return false;
  }
  if (tag === "INPUT") {
    return (
      el.classList.contains("editable") ||
      el.classList.contains("meta-input") ||
      el.classList.contains("field-input")
    );
  }
  if (tag === "SELECT") {
    return (
      el.classList.contains("editable") ||
      el.classList.contains("field-input") ||
      el.id === "opciones" ||
      el.id === "opciones/marca"
    );
  }
  if (tag === "TEXTAREA") {
    return el.classList.contains("editable") || el.classList.contains("field-input");
  }
  return false;
}

/** Considera “lleno” un campo según tipo (select no vacío, marca_otro solo si marca=otro). */
function isFieldFilled(el) {
  const value = String(el.value || "").trim();
  if (el.id === "marca_otro") {
    const marca = document.getElementById("opciones/marca");
    if (!marca || marca.value !== "otro") {
      return true;
    }
    return value !== "";
  }
  if (el.tagName === "SELECT") {
    return value !== "";
  }
  return value !== "";
}

/** Porcentaje 0–100 de campos contables que tienen valor. */
function getFormFillPercent() {
  const candidates = Array.from(
    document.querySelectorAll("input, select, textarea")
  ).filter(fieldCountsTowardFill);

  if (candidates.length === 0) {
    return 0;
  }
  const filled = candidates.filter(isFieldFilled).length;
  return Math.min(100, Math.round((filled / candidates.length) * 100));
}

/** Actualiza barra y etiqueta de progreso de llenado. */
function updateFormFillProgress() {
  const pct = getFormFillPercent();
  const progressFill = document.getElementById("progressFill");
  const fillLabel = document.getElementById("fillProgressLabel");
  const text = `Formulario: ${pct}%`;
  if (progressFill) {
    progressFill.style.width = `${pct}%`;
  }
  if (fillLabel) {
    fillLabel.textContent = text;
  }
}

/**
 * Sincroniza la sección visible con `currentStep`, refresca firmas si aplica,
 * progreso, botones anterior/siguiente y visibilidad de acciones de aprobación.
 */
function updateNavigation() {
  const total = sections.length;

  sections.forEach((section, index) => {
    section.classList.toggle("active", index === currentStep);
  });
  if (sections[currentStep]?.id === "section-firmas") {
    fillRealizoFromSession();
  }

  updateFormFillProgress();

  const stepIndicator = document.getElementById("stepIndicator");
  if (stepIndicator) {
    stepIndicator.textContent = `${currentStep + 1} / ${total}`;
  }

  const prevBtn = document.getElementById("btn-prev");
  const nextBtn = document.getElementById("btn-next");
  if (prevBtn) {
    prevBtn.disabled = currentStep === 0;
  }
  if (nextBtn) {
    nextBtn.disabled = currentStep === total - 1;
  }

  if (typeof window.updateApprovalActionButtons === "function") {
    window.updateApprovalActionButtons();
  }
}

/** Mensaje breve no modal que desaparece solo (feedback inmediato). */
function showToast(message) {
  const toast = document.getElementById("toast");
  if (!toast) {
    return;
  }

  toast.textContent = message;
  toast.style.background = "rgba(26, 43, 68, 0.95)";
  toast.style.color = "#fff";
  toast.style.padding = "10px 12px";
  toast.style.borderRadius = "10px";
  toast.style.boxShadow = "0 8px 20px rgba(0, 0, 0, 0.2)";
  toast.style.opacity = "1";

  window.clearTimeout(showToast.timeoutId);
  showToast.timeoutId = window.setTimeout(() => {
    toast.style.opacity = "0";
  }, 2200);
}

/**
 * Heurística para decidir si la respuesta del API de login representa acceso válido
 * (boolean, número, string, objeto con success/ok/valid/status, etc.).
 */
function isLoginSuccessResponse(responseData) {
  if (!responseData) {
    return false;
  }
  if (typeof responseData === "boolean") {
    return responseData;
  }
  if (typeof responseData === "number") {
    return responseData > 0;
  }
  if (typeof responseData === "string") {
    const normalized = responseData.trim().toLowerCase();
    if (!normalized) {
      return false;
    }
    return !["false", "0", "error", "invalid", "unauthorized", "null"].includes(normalized);
  }
  if (Array.isArray(responseData)) {
    return responseData.length > 0;
  }
  if (typeof responseData === "object") {
    if ("success" in responseData) {
      return Boolean(responseData.success);
    }
    if ("ok" in responseData) {
      return Boolean(responseData.ok);
    }
    if ("valid" in responseData) {
      return Boolean(responseData.valid);
    }
    if ("status" in responseData) {
      const status = String(responseData.status).toLowerCase();
      if (["ok", "success", "valid", "true", "1"].includes(status)) {
        return true;
      }
      if (["error", "invalid", "false", "0"].includes(status)) {
        return false;
      }
    }
    return Object.keys(responseData).length > 0;
  }
  return false;
}

/** Intenta JSON.parse; si falla devuelve el texto crudo (para APIs que responden plano). */
function parseApiResponse(rawText) {
  const text = String(rawText || "").trim();
  if (!text) {
    return null;
  }
  try {
    return JSON.parse(text);
  } catch (_err) {
    return text;
  }
}

/** Detecta fallos de red tipo DNS no resuelto en los intentos de fetch del login. */
function hasDnsResolutionError(errors) {
  return (errors || []).some((item) =>
    String(item?.error || "").toUpperCase().includes("NAME_NOT_RESOLVED")
  );
}

/**
 * Prueba credenciales contra una lista de URLs de login (POST form-urlencoded).
 * Devuelve el primer éxito o agrega errores por cada intento fallido.
 */
async function validateCredentials(rpe, password) {
  const errors = [];
  for (const baseUrl of LOGIN_API_BASE_URLS) {
    const url = baseUrl;
    try {
      const response = await fetch(url, {
        method: "POST",
        headers: {
          Accept: "application/json, text/plain, */*",
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: new URLSearchParams({
          rpe: String(rpe || ""),
          psw: String(password || ""),
        }).toString(),
      });
      const responseText = await response.text();
      const parsed = parseApiResponse(responseText);
      const isValid = response.ok && isLoginSuccessResponse(parsed);
      if (isValid) {
        return { httpOk: true, responseText, parsed, isValid: true, status: response.status, url };
      }
      errors.push({ status: response.status, url });
      if (response.ok) {
        return { httpOk: true, responseText, parsed, isValid: false, status: response.status, url };
      }
    } catch (error) {
      errors.push({ status: 0, url, error: String(error?.message || error) });
    }
  }
  return {
    httpOk: false,
    responseText: "",
    parsed: null,
    isValid: false,
    status: errors[0]?.status || 0,
    errors,
  };
}

/** RPE en mayúsculas, sin caracteres no alfanuméricos, máximo 5 caracteres. */
function normalizeRpe(value) {
  return String(value || "")
    .toUpperCase()
    .replace(/[^A-Z0-9]/g, "")
    .slice(0, 5);
}

/** Lista de RPE dados de alta como administradores revisores (con valores por defecto si no hay datos). */
function getAdminReviewerRpes() {
  const raw = window.localStorage.getItem(ADMIN_REVIEWERS_STORAGE_KEY);
  if (!raw) {
    return ADMIN_REVIEWER_DEFAULT_RPES.map((x) => normalizeRpe(x)).filter(Boolean);
  }
  try {
    const parsed = JSON.parse(raw);
    const list = Array.isArray(parsed?.adminRpes) ? parsed.adminRpes : [];
    const normalized = list.map((x) => normalizeRpe(x)).filter(Boolean);
    return normalized.length
      ? normalized
      : ADMIN_REVIEWER_DEFAULT_RPES.map((x) => normalizeRpe(x)).filter(Boolean);
  } catch (_err) {
    return ADMIN_REVIEWER_DEFAULT_RPES.map((x) => normalizeRpe(x)).filter(Boolean);
  }
}

/** Persiste la lista completa de revisores (deduplicada y normalizada). */
function setAdminReviewerRpes(rpes) {
  const normalized = Array.from(
    new Set((rpes || []).map((x) => normalizeRpe(x)).filter(Boolean))
  );
  window.localStorage.setItem(
    ADMIN_REVIEWERS_STORAGE_KEY,
    JSON.stringify({ adminRpes: normalized, updatedAt: Date.now() })
  );
}

/** Agrega un RPE a la lista de revisores (no duplicados, no el maestro). Devuelve si hubo cambio. */
function addAdminReviewerRpe(rpe) {
  const target = normalizeRpe(rpe);
  if (!target) return false;
  if (target === normalizeRpe(MASTER_RPE)) return false;
  const list = getAdminReviewerRpes();
  if (list.includes(target)) return false;
  setAdminReviewerRpes([...list, target]);
  return true;
}

/** Indica si el RPE es maestro o está en la lista de administradores revisores. */
function isReviewerAdminRpe(rpe) {
  const target = normalizeRpe(rpe);
  if (!target) return false;
  if (target === normalizeRpe(MASTER_RPE)) return true;
  return getAdminReviewerRpes().includes(target);
}

/** Sesión actual con bandera de administrador (maestro, API o revisor de lista). */
function isCurrentUserAdmin() {
  const session = getAuthSession();
  return Boolean(session?.isAdmin);
}

/** Usuario actual es el RPE maestro (superusuario). */
function isCurrentUserMaster() {
  const session = getAuthSession();
  return normalizeRpe(session?.rpe || "") === normalizeRpe(MASTER_RPE);
}

/** Administrador solo por alta en lista de revisores (no incluye al maestro). */
function sessionIsReviewerAdmin() {
  const session = getAuthSession();
  if (!session?.rpe || session.isMaster) {
    return false;
  }
  if (typeof session.isReviewerAdmin === "boolean") {
    return session.isReviewerAdmin;
  }
  return getAdminReviewerRpes().includes(normalizeRpe(session.rpe));
}

/**
 * Quién puede editar datos del reporte: maestro, usuarios de campo (no admin),
 * o un revisor de lista únicamente cuando él es quien realizó la evaluación.
 */
function canCurrentUserEditReportForm() {
  const session = getAuthSession();
  if (!session?.rpe) {
    return false;
  }
  if (session.isMaster) {
    return true;
  }
  if (sessionIsReviewerAdmin()) {
    const realizo = normalizeRpe(document.getElementById("realizo_rpe")?.value || "");
    const me = normalizeRpe(session.rpe);
    return Boolean(realizo) && realizo === me;
  }
  return !isCurrentUserAdmin();
}

const REVISO_RECIBE_FIELD_IDS = new Set([
  "reviso_nom",
  "reviso_rpe",
  "reviso_area",
  "reviso_fecha",
  "recibe_nom",
  "recibe_rpe",
  "recibe_area",
  "recibe_fecha",
]);

const REALIZO_FIELD_IDS = ["realizo_nom", "realizo_rpe", "realizo_area", "realizo_fecha"];

/** Revisor asignado puede completar firmas aunque el resto del reporte sea solo lectura. */
function canEditRevisoAndRecibeFirmasOnly() {
  if (canCurrentUserEditReportForm()) {
    return false;
  }
  const evalId =
    typeof window.getReportesCurrentEvalId === "function"
      ? String(window.getReportesCurrentEvalId() || "").trim()
      : "";
  if (evalId && isEvaluationApprovedOnlyInWorkflow(evalId)) {
    return false;
  }
  const me = getCurrentRpe();
  const reviso = normalizeRpe(document.getElementById("reviso_rpe")?.value || "");
  return Boolean(me) && Boolean(reviso) && reviso === me;
}

/** Puede persistir cambios: editor completo del reporte o solo bloque de firmas de revisor. */
function canCurrentUserSaveReportChanges() {
  return canCurrentUserEditReportForm() || canEditRevisoAndRecibeFirmasOnly();
}

/**
 * Habilita o deshabilita inputs del formulario según rol, evaluación aprobada en flujo
 * o modo “solo firmas” para el revisor asignado.
 */
function applyReportFieldEditability() {
  const evalId =
    typeof window.getReportesCurrentEvalId === "function"
      ? String(window.getReportesCurrentEvalId() || "").trim()
      : "";
  if (evalId && isEvaluationApprovedOnlyInWorkflow(evalId)) {
    document
      .querySelectorAll(
        ".form-section input:not([type='hidden']):not([type='button']):not([type='submit']), .form-section textarea, .form-section select, .form-section .td-input"
      )
      .forEach((el) => {
        el.disabled = true;
      });
    ["opciones", "opciones/marca"].forEach((fid) => {
      const el = document.getElementById(fid);
      if (el && el.tagName === "SELECT") {
        el.disabled = true;
      }
    });
    REALIZO_FIELD_IDS.forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.disabled = true;
      }
    });
    return;
  }

  const sel =
    ".form-section input.editable, .form-section textarea.editable, .form-section .td-input.editable, .form-section select.field-input";
  const nodes = document.querySelectorAll(sel);
  const full = canCurrentUserEditReportForm();
  const partialFirmas = canEditRevisoAndRecibeFirmasOnly();

  nodes.forEach((el) => {
    const id = el.id || "";
    if (full) {
      el.disabled = false;
    } else if (partialFirmas && REVISO_RECIBE_FIELD_IDS.has(id)) {
      el.disabled = false;
    } else {
      el.disabled = true;
    }
  });

  // ZONA y MARCA: <select> sin clase .field-input no entraban en el selector anterior.
  ["opciones", "opciones/marca"].forEach((fid) => {
    const el = document.getElementById(fid);
    if (!el || el.tagName !== "SELECT") {
      return;
    }
    el.disabled = !full;
  });

  if (!full) {
    REALIZO_FIELD_IDS.forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.disabled = true;
      }
    });
  }
}

/**
 * Revisor con formulario bloqueado guarda solo cambios en firmas y sincroniza flujo / autor.
 */
function persistRevisorFirmasSnapshotSave() {
  const rpe = getCurrentRpe();
  const evaluationId =
    typeof window.getReportesCurrentEvalId === "function"
      ? String(window.getReportesCurrentEvalId() || "").trim()
      : "";
  if (!rpe || !evaluationId || !canEditRevisoAndRecibeFirmasOnly()) {
    return false;
  }
  const pending = getWorkflowPendingEntryByEvaluationId(evaluationId);
  if (!pending) {
    return false;
  }
  const wf = pending;
  const ownerRpe = normalizeRpe(wf.requestedByRpe || wf.userId || "");
  if (!ownerRpe) {
    return false;
  }

  const snapshot = collectFormSnapshot();
  const performedBy = buildPerformedBySnapshot();
  const now = Date.now();
  const nowIso = formatNowIsoLocal();

  const ownerList = getSavedEvaluations(ownerRpe);
  const oidx = ownerList.findIndex((x) => x.id === evaluationId);
  if (oidx >= 0) {
    ownerList[oidx] = {
      ...ownerList[oidx],
      snapshot,
      performedBy,
      updatedAt: now,
      updatedAtIso: nowIso,
    };
  } else {
    ownerList.unshift({
      id: evaluationId,
      userId: ownerRpe,
      timestamp: wf.timestamp || now,
      fechaHora: wf.fechaHora || nowIso,
      createdAt: wf.createdAt || wf.timestamp || now,
      updatedAt: now,
      createdAtIso: wf.createdAtIso || wf.fechaHora || nowIso,
      updatedAtIso: nowIso,
      snapshot,
      performedBy,
      reviewerRpe: normalizeRpe(wf.reviewerRpe || ""),
    });
  }
  setSavedEvaluations(ownerRpe, ownerList);

  const reviewerList = getSavedEvaluations(rpe);
  const rdx = reviewerList.findIndex((x) => x.id === evaluationId);
  if (rdx >= 0) {
    reviewerList[rdx] = {
      ...reviewerList[rdx],
      snapshot,
      performedBy,
      updatedAt: now,
      updatedAtIso: nowIso,
    };
    setSavedEvaluations(rpe, reviewerList);
  }

  const pruebasStore = getPruebasStore();
  const pidx = pruebasStore.pruebas.findIndex((p) => p.id === evaluationId);
  if (pidx >= 0) {
    pruebasStore.pruebas[pidx] = {
      ...pruebasStore.pruebas[pidx],
      snapshot,
      performedBy,
      updatedAt: now,
      updatedAtIso: nowIso,
    };
  }
  setPruebasStore(pruebasStore);

  appendResultadoEntry({
    resultadoId: `res_${now}_${Math.random().toString(36).slice(2, 8)}`,
    evaluationId,
    userId: rpe,
    accion: "actualizacion_firmas_revisor",
    timestamp: now,
    fechaHora: nowIso,
    snapshot,
    performedBy,
  });

  syncWorkflowDataAfterEvaluationEdit(evaluationId, {
    snapshot,
    performedBy,
    updatedAt: now,
    updatedAtIso: nowIso,
    ownerRpe,
  });
  return true;
}

/** Texto en mayúsculas recortado (nombres y áreas en formulario). */
function normalizeUpperText(value) {
  return String(value || "").toUpperCase()    .trim();
}

/**
 * Normaliza respuestas heterogéneas de directorio/API a { nombre, correo, area, isAdmin, activo }.
 * Devuelve null si no hay datos mínimos (nombre/correo).
 */
function parseRpeProfilePayload(payload) {
  if (!payload || typeof payload !== "object") {
    return null;
  }
  const nombreCompuestoFromParts = [payload.Nombre, payload.Apellidos]
    .map((x) => String(x || "").trim())
    .filter(Boolean)
    .join(" ")
    .trim();
  const nombre =
    String(
      payload.NombreCompleto ||
        payload.nombreCompleto ||
        payload.Nomina?.Nombre ||
        nombreCompuestoFromParts ||
      payload.nombre ||
        payload.Nombre ||
        payload.user?.nombre ||
        payload.data?.nombre ||
        payload.user?.name ||
        payload.data?.name ||
        payload.name ||
        payload.displayName ||
        payload.fullName ||
        ""
    ).trim();
  const correo =
    String(
      payload.correo ||
        payload.Correo ||
        payload.Email ||
        payload.Nomina?.EMail ||
        payload.user?.correo ||
        payload.data?.correo ||
        payload.user?.email ||
        payload.data?.email ||
        payload.email ||
        payload.mail ||
        payload.userPrincipalName ||
        ""
    ).trim();
  const area =
    String(
      payload.area ||
        payload.Area ||
        payload.Zona ||
        payload.Nomina?.Zona ||
        payload.Nomina?.Division ||
        payload.departamento ||
        payload.department ||
        payload.user?.area ||
        payload.data?.area ||
        payload.user?.department ||
        payload.data?.department ||
        ""
    ).trim();
  const isAdmin = parseBooleanLike(
    payload.isAdmin ??
      payload.admin ??
      payload.esAdmin ??
      payload.rolAdmin ??
      payload.user?.isAdmin ??
      payload.data?.isAdmin,
    false
  );
  const activo = parseBooleanLike(
    payload.activo ??
      payload.active ??
      payload.habilitado ??
      payload.Habilitado ??
      payload.user?.activo ??
      payload.data?.activo,
    true
  );
  if (!nombre && !correo) {
    return null;
  }
  return { nombre, correo, area, isAdmin, activo };
}

/**
 * Consulta perfil por RPE: primero diccionario local, luego plantilla de API (POST o GET si 405).
 */
async function fetchRpeProfile(rpe) {
  const normalizedRpe = normalizeRpe(rpe);
  if (!normalizedRpe) {
    return { status: "invalid", profile: null };
  }

  if (LOCAL_RPE_DIRECTORY[normalizedRpe]) {
    return { status: "found", profile: LOCAL_RPE_DIRECTORY[normalizedRpe], source: "local" };
  }

  if (!RPE_PROFILE_API_URL_TEMPLATE) {
    return { status: "error", profile: null, reason: "no-endpoint-template" };
  }

  const endpoint = RPE_PROFILE_API_URL_TEMPLATE.replace(
    "{rpe}",
    encodeURIComponent(normalizedRpe)
  );
  try {
    let response = await fetch(endpoint, {
      method: "POST",
      headers: {
        Accept: "application/json, text/plain, */*",
      },
    });
    // El endpoint de consulta puede responder 405 para POST; en ese caso reintenta con GET.
    if (response.status === 405) {
      response = await fetch(endpoint, {
        method: "GET",
        headers: {
          Accept: "application/json, text/plain, */*",
        },
      });
    }
    if (!response.ok) {
      return { status: "error", profile: null, reason: `http-${response.status}` };
    }
    const responseText = await response.text();
    const parsed = parseApiResponse(responseText);

    if (Array.isArray(parsed)) {
      for (const item of parsed) {
        const profile = parseRpeProfilePayload(item);
        if (profile) {
          return { status: "found", profile, source: endpoint };
        }
      }
      return { status: "not_found", profile: null, source: endpoint };
    }
    const profile = parseRpeProfilePayload(parsed);
    if (profile) {
      return { status: "found", profile, source: endpoint };
    }
    return { status: "not_found", profile: null, source: endpoint };
  } catch (_error) {
    return { status: "error", profile: null, reason: "api-failure" };
  }
}

/** Perfil ya conocido: sesión actual o catálogo local de usuarios (sin llamada de red). */
function getKnownProfileByRpe(rpe) {
  const target = normalizeRpe(rpe);
  if (!target) {
    return null;
  }
  const session = getAuthSession();
  const sessionRpe = normalizeRpe(session?.rpe || "");
  if (sessionRpe && sessionRpe === target) {
    return {
      nombre: String(session?.nombre || "").trim(),
      correo: String(session?.correo || "").trim(),
      area: String(session?.area || "").trim(),
    };
  }
  const usuariosStore = getUsuariosStore();
  const usuario = (usuariosStore.usuarios || []).find(
    (u) => normalizeRpe(u.RPE || "") === target
  );
  if (!usuario) {
    return null;
  }
  return {
    nombre: String(usuario.NOMBRE || "").trim(),
    correo: "",
    area: "",
  };
}

/** Actualiza clases y texto del elemento de ayuda junto a un campo RPE. */
function setRpeFeedback(feedbackEl, type, message) {
  if (!feedbackEl) {
    return;
  }
  feedbackEl.classList.remove("is-loading", "is-success", "is-error");
  if (type) {
    feedbackEl.classList.add(type);
  }
  feedbackEl.textContent = message || "";
}

/** Ejecuta la validación asociada a un input (blur o función interna `__runRpeValidation`). */
function triggerRpeValidationById(inputId) {
  const inputEl = document.getElementById(inputId);
  if (!inputEl) {
    return;
  }
  if (typeof inputEl.__runRpeValidation === "function") {
    inputEl.__runRpeValidation();
    return;
  }
  inputEl.dispatchEvent(new Event("blur", { bubbles: true }));
}

/**
 * Registra validación asíncrona de RPE en blur/change: normaliza, opcionalmente exige distinto a “Realizó”,
 * consulta caché/API y rellena nombre; `onAfterValidate` refresca botones de aprobación.
 */
function setupRpeValidation(inputId, feedbackId, options = {}) {
  const inputEl = document.getElementById(inputId);
  const feedbackEl = document.getElementById(feedbackId);
  const nameEl = options.nameId ? document.getElementById(options.nameId) : null;
  if (!inputEl || !feedbackEl) {
    return;
  }

  let activeRequestId = 0;
  const afterValidate =
    typeof options.onAfterValidate === "function" ? options.onAfterValidate : null;
  const validate = async () => {
    const normalized = normalizeRpe(inputEl.value);
    inputEl.value = normalized;
    const disallowSameAsId = options.disallowSameAsId || "";
    if (normalized && disallowSameAsId) {
      const baseEl = document.getElementById(disallowSameAsId);
      const baseRpe = normalizeRpe(baseEl?.value || "");
      if (baseRpe && normalized === baseRpe) {
        if (nameEl) {
          nameEl.value = "";
        }
        setRpeFeedback(
          feedbackEl,
          "is-error",
          "El RPE debe ser diferente al de Realizó."
        );
        if (afterValidate) afterValidate();
        return;
      }
    }
    if (!normalized) {
      setRpeFeedback(feedbackEl, "", "");
      if (afterValidate) afterValidate();
      return;
    }
    if (!/^[A-Z0-9]{1,5}$/.test(normalized)) {
      setRpeFeedback(feedbackEl, "is-error", "RPE inválido.");
      if (afterValidate) afterValidate();
      return;
    }

    const reqId = ++activeRequestId;
    setRpeFeedback(feedbackEl, "is-loading", "Validando RPE...");
    try {
      const knownProfile = options.apiOnly ? null : getKnownProfileByRpe(normalized);
      let profile = knownProfile;
      let lookupStatus = knownProfile ? "found" : "not_found";
      let lookupReason = "";
      if (!profile) {
        const lookup = await fetchRpeProfile(normalized);
        lookupStatus = lookup.status;
        lookupReason = String(lookup.reason || "");
        profile = lookup.profile;
      }
      if (reqId !== activeRequestId) {
        return;
      }
      if (profile) {
        const nombre = normalizeUpperText(profile.nombre || "Sin nombre");
        const correo = profile.correo || "Sin correo";
        const correoUi =
          correo && correo.length > 28 ? `${correo.slice(0, 28)}...` : correo;
        if (nameEl) {
          nameEl.value = nombre === "Sin nombre" ? "" : nombre;
        }
        setRpeFeedback(
          feedbackEl,
          "is-success",
          `Nombre: ${nombre} | Correo: ${correoUi}`
        );
        if (afterValidate) afterValidate();
      } else {
        if (nameEl) {
          nameEl.value = "";
        }
        if (lookupStatus === "error") {
          setRpeFeedback(
            feedbackEl,
            "is-error",
            `No se pudo consultar la API para validar el RPE.${lookupReason ? ` (${lookupReason})` : ""}`
          );
          if (afterValidate) afterValidate();
          return;
        }
        setRpeFeedback(
          feedbackEl,
          "is-error",
          "El RPE no existe."
        );
        if (afterValidate) afterValidate();
      }
    } catch (_error) {
      if (reqId !== activeRequestId) {
        return;
      }
      if (nameEl) {
        nameEl.value = "";
      }
      setRpeFeedback(
        feedbackEl,
        "is-error",
        "No se pudo validar el RPE en este momento."
      );
      if (afterValidate) afterValidate();
    }
  };

  inputEl.addEventListener("input", () => {
    inputEl.value = normalizeRpe(inputEl.value);
    setRpeFeedback(feedbackEl, "", "");
    if (afterValidate) afterValidate();
  });
  inputEl.addEventListener("change", validate);
  inputEl.addEventListener("blur", validate);
  inputEl.__runRpeValidation = validate;
}

/** Mensaje bajo el formulario de login (clases error/success). */
function setLoginFeedback(message, type) {
  const loginMessage = document.getElementById("loginMessage");
  if (!loginMessage) {
    return;
  }
  loginMessage.textContent = message;
  loginMessage.classList.remove("error", "success");
  if (type) {
    loginMessage.classList.add(type);
  }
}

/** Obtiene el primer perfil parseable de un objeto o arreglo devuelto por el login. */
function extractFirstProfileFromPayload(payload) {
  if (!payload) {
    return null;
  }
  if (Array.isArray(payload)) {
    for (const item of payload) {
      const profile = parseRpeProfilePayload(item);
      if (profile) return profile;
    }
    return null;
  }
  return parseRpeProfilePayload(payload);
}

/** Rellena bloque “Realizó” desde la sesión (y fecha de hoy si falta); dispara validación de RPE. */
function fillRealizoFromSession(options = {}) {
  const force = Boolean(options.force);
  const session = getAuthSession();
  if (!session) {
    return;
  }
  const realizoRpe = document.getElementById("realizo_rpe");
  const realizoNom = document.getElementById("realizo_nom");
  const realizoArea = document.getElementById("realizo_area");
  const realizoFecha = document.getElementById("realizo_fecha");
  if (realizoRpe && (force || !String(realizoRpe.value || "").trim())) {
    realizoRpe.value = normalizeRpe(session.rpe || "");
  }
  if (realizoNom && (force || !String(realizoNom.value || "").trim())) {
    realizoNom.value = normalizeUpperText(session.nombre || "");
  }
  if (realizoArea && (force || !String(realizoArea.value || "").trim())) {
    realizoArea.value = FIXED_SIGNATURE_AREA;
  }
  if (realizoFecha && (force || !String(realizoFecha.value || "").trim())) {
    const today = new Date();
    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, "0");
    const d = String(today.getDate()).padStart(2, "0");
    realizoFecha.value = `${y}-${m}-${d}`;
  }
  if (realizoRpe && String(realizoRpe.value || "").trim()) {
    triggerRpeValidationById("realizo_rpe");
  }
  enforceFixedAreaFields();
}

/** Muestra nombre o RPE del usuario en la barra superior. */
function renderCurrentUserName() {
  const el = document.getElementById("currentUserName");
  if (!el) return;
  const session = getAuthSession();
  const nombre = String(session?.nombre || "").trim();
  const rpe = normalizeRpe(session?.rpe || "");
  el.textContent = (nombre || rpe || "-").toUpperCase();
}

/**
 * Oculta login, muestra app, persiste sesión en sessionStorage, actualiza catálogo de usuario
 * y prepara formulario (realizó, nombre en UI).
 */
function unlockApplicationAfterLogin(rpe, profile = null) {
  const loginScreen = document.getElementById("loginScreen");
  const appShell = document.querySelector(".app-shell");
  if (loginScreen) {
    loginScreen.style.display = "none";
  }
  if (appShell) {
    appShell.classList.remove("is-hidden");
  }
  const isMaster = normalizeRpe(rpe) === normalizeRpe(MASTER_RPE);
  const onReviewerList = getAdminReviewerRpes().includes(normalizeRpe(rpe));
  const isReviewerAdmin = Boolean(!isMaster && onReviewerList);
  const isAdmin = isMaster || parseBooleanLike(profile?.isAdmin, false) || isReviewerAdminRpe(rpe);
  const activo = parseBooleanLike(profile?.activo, true);
  const perfil = resolveUserProfile(isAdmin, isMaster);
  window.sessionStorage.setItem(
    "reportesAuth",
    JSON.stringify({
      rpe,
      nombre: profile?.nombre || "",
      correo: profile?.correo || "",
      area: profile?.area || "",
      isAdmin,
      isMaster,
      isReviewerAdmin,
      perfil,
      activo,
      at: Date.now(),
    })
  );
  upsertUsuarioRecord({
    RPE: rpe,
    NOMBRE: profile?.nombre || "",
    ZONA: getCurrentZonaText(),
    isAdmin,
    activo,
  });
  fillRealizoFromSession({ force: true });
  renderCurrentUserName();
}

/** Inicializa sesión del módulo con el usuario ya autenticado en SIGTAE. */
function bootstrapSigtaeReportesSession() {
  const root = document.querySelector(".sigtae-reportes-module");
  const cfg = window.__SIGTAE_REPORTES_USER || {};
  if (root) {
    const dsRpe = root.getAttribute("data-sigtae-rpe") || "";
    const dsNombre = root.getAttribute("data-sigtae-nombre") || "";
    if (dsRpe && !cfg.rpe) cfg.rpe = dsRpe;
    if (dsNombre && !cfg.nombre) cfg.nombre = dsNombre;
  }
  if (!cfg || !normalizeRpe(cfg.rpe || "")) {
    return false;
  }
  const loginScreen = document.getElementById("loginScreen");
  if (loginScreen) {
    loginScreen.style.display = "none";
  }
  unlockApplicationAfterLogin(normalizeRpe(cfg.rpe), {
    nombre: cfg.nombre || "",
    correo: cfg.correo || "",
    area: cfg.area || FIXED_SIGNATURE_AREA,
    isAdmin: Boolean(cfg.isAdmin),
    activo: true,
  });
  return true;
}

/** Limpia sesión, muestra pantalla de login y resetea feedback/campos de acceso. */
function logoutApplication() {
  if (isSigtaeEmbeddedReportes()) {
    const basePath = window.SIGTAE_BASE_PATH || "";
    window.location.href = `${basePath}/dashboard.php`;
    return;
  }
  const loginScreen = document.getElementById("loginScreen");
  const appShell = document.querySelector(".app-shell");
  const loginForm = document.getElementById("loginForm");
  const loginRpe = document.getElementById("loginRpe");
  const loginPassword = document.getElementById("loginPassword");
  window.sessionStorage.removeItem("reportesAuth");
  if (appShell) {
    appShell.classList.add("is-hidden");
  }
  if (loginScreen) {
    loginScreen.style.display = "";
  }
  if (loginForm) {
    loginForm.reset();
  }
  setLoginFeedback("", "");
  if (loginRpe) {
    loginRpe.focus();
  }
  if (loginPassword) {
    loginPassword.value = "";
  }
  renderCurrentUserName();
}

/** IDs de campos obligatorios en la sección de aislamiento (equipos + matriz 3×3 TC/TP). */
function getAislamientoRequiredIds() {
  const ids = ["tc_equipo", "tp_equipo"];
  for (const p of ["tc_ais", "tp_ais"]) {
    for (let r = 1; r <= 3; r++) {
      for (let c = 1; c <= 3; c++) {
        ids.push(`${p}_e${r}_${c}`);
      }
    }
  }
  return ids;
}

/** Texto legible para toasts al faltar un campo (aria-label, reglas por id, label cercano). */
function getFieldLabelForToast(field) {
  const aria = field.getAttribute("aria-label");
  if (aria) {
    return aria;
  }
  if (field.id === "tc_equipo") {
    return "Equipo utilizado (TC)";
  }
  if (field.id === "tp_equipo") {
    return "Equipo utilizado (TP)";
  }
  const m = /^(tc|tp)_ais_e([1-3])_([1-3])$/.exec(field.id);
  if (m) {
    const dev = m[1] === "tc" ? "TC" : "TP";
    return `Aislamiento ${dev} — prueba ${m[2]} — MΩ columna ${m[3]}`;
  }
  return (
    field.closest(".field-group")?.querySelector("label")?.textContent?.trim() ||
    field.placeholder ||
    field.id ||
    "campo requerido"
  );
}

/**
 * Primer control vacío y requerido dentro de una sección del formulario (mapa por id de sección).
 * Respeta deshabilitados y el caso “marca otro” solo si aplica.
 */
function getFirstMissingRequiredField(section) {
  if (!section) {
    return null;
  }

  const requiredBySection = {
    "section-datos-placa": [
      "tp_categoria",
      "opciones",
      "opciones/marca",
      "marca_otro",
      "tipo",
      "no_serie",
    ],
    "section-tp-datos": [
      "tp_vp1",
      "tp_rel1_e1",
      "tp_vp2",
      "tp_rel2_e1",
      "tp_vp3",
      "tp_rel3_e1",
    ],
    "section-tc-datos": [
      "tc_cp1",
      "tc_rel1_e1",
      "tc_cp2",
      "tc_rel2_e1",
      "tc_cp3",
      "tc_rel3_e1",
    ],
    "section-conexion": [
      "tp_con1_a",
      "tp_con1_b",
      "tp_con2_a",
      "tp_con2_b",
      "tp_con3_a",
      "tp_con3_b",
      "tc_con1_a",
      "tc_con1_b",
      "tc_con2_a",
      "tc_con2_b",
      "tc_con3_a",
      "tc_con3_b",
    ],
    "section-firmas": [
      "realizo_nom",
      "realizo_rpe",
      "realizo_area",
      "realizo_fecha",
      "reviso_nom",
      "reviso_rpe",
      "reviso_area",
      "reviso_fecha",
      "recibe_nom",
      "recibe_rpe",
      "recibe_area",
      "recibe_fecha",
    ],
  };

  let requiredIds = requiredBySection[section.id] || [];
  if (section.id === "section-aislamiento") {
    requiredIds = getAislamientoRequiredIds();
  }
  const requiredFields = requiredIds
    .map((id) => document.getElementById(id))
    .filter((el) => !!el);

  return requiredFields.find((field) => {
    if (field.disabled || field.readOnly) {
      return false;
    }
    if (field.id === "marca_otro") {
      const marcaSel = document.getElementById("opciones/marca");
      if (!marcaSel || marcaSel.value !== "otro") {
        return false;
      }
    }
    const value = String(field.value || "").trim();
    // Para selects con placeholder de valor vacío
    if (field.tagName === "SELECT") {
      return value === "";
    }
    return value === "";
  });
}

/**
 * Avanza o retrocede sección; si el usuario debe validar, bloquea el avance con toast
 * hasta completar requeridos de la sección actual.
 */
window.navStep = function navStep(direction) {
  const move = Number(direction);
  const shouldValidateAdvance =
    !isCurrentUserAdmin() || isCurrentUserMaster() || sessionIsReviewerAdmin();
  if (move > 0 && shouldValidateAdvance) {
    const missingField = getFirstMissingRequiredField(sections[currentStep]);
    if (missingField) {
      const labelText = getFieldLabelForToast(missingField);
      missingField.focus();
      showToast(`Falta completar: ${labelText}`);
      return;
    }
  }

  const nextStep = currentStep + Number(direction);
  if (nextStep < 0 || nextStep >= sections.length) {
    return;
  }
  currentStep = nextStep;
  updateNavigation();
  window.scrollTo({ top: 0, behavior: "smooth" });
};

/**
 * Construye HTML de impresión con datos del DOM, carga logos como data URL, abre ventana
 * y al cerrar/volver el foco normaliza firmas, valida RPEs y refresca progreso/navegación.
 */
window.generarPDF = async function generarPDF() {
  const getValue = (id) => {
    const el = document.getElementById(id);
    if (!el) return "";
    if (el.tagName === "SELECT") {
      const opt = el.options[el.selectedIndex];
      const text = (opt ? opt.text : "").trim();
      if (el.id === "opciones/marca" && el.value === "otro") {
        return String(document.getElementById("marca_otro")?.value || "").trim() || text;
      }
      return text;
    }
    return String(el.value || "").trim();
  };
  const v = (id) => getValue(id) || "-";
  const vPct = (id) => {
    const raw = String(getValue(id) || "").trim();
    if (!raw) {
      return "-";
    }
    const cleaned = raw.replace(/%/g, "").trim();
    if (!cleaned) {
      return "-";
    }
    return `${cleaned}%`;
  };
  const esc = (s) =>
    String(s || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  const baseUrl = getReportesAssetBaseUrl();
  const cacheKey = Date.now();
  const toDataUrl = (blob) =>
    new Promise((resolve, reject) => {
      const fr = new FileReader();
      fr.onload = () => resolve(String(fr.result || ""));
      fr.onerror = reject;
      fr.readAsDataURL(blob);
    });
  const tryLoadImageAsDataUrl = async (urls) => {
    for (const url of urls) {
      try {
        const response = await fetch(url, { cache: "no-store" });
        if (!response.ok) continue;
        const blob = await response.blob();
        if (!blob || !String(blob.type || "").startsWith("image/")) continue;
        const dataUrl = await toDataUrl(blob);
        if (dataUrl) return dataUrl;
      } catch (_err) {
        // intenta siguiente ruta
      }
    }
    return "";
  };
  const titleImgDataUrl = await tryLoadImageAsDataUrl([
    `${baseUrl}assets/header-title.png?v=${cacheKey}`,
    `${baseUrl}assets/header-title.png.png?v=${cacheKey}`,
    `${baseUrl}assets/header-title.PNG?v=${cacheKey}`,
    `${window.location.origin}/reportes/assets/header-title.png?v=${cacheKey}`,
    `${window.location.origin}/reportes/assets/header-title.png.png?v=${cacheKey}`,
    `${window.location.origin}/reportes/assets/header-title.PNG?v=${cacheKey}`,
    `${baseUrl}assets/header-title.jpg?v=${cacheKey}`,
    `${baseUrl}assets/header-title.jpeg?v=${cacheKey}`,
    `${baseUrl}assets/header-title.webp?v=${cacheKey}`,
    `${baseUrl}assets/header-title.svg?v=${cacheKey}`,
    "assets/header-title.png",
  ]);
  const logoImgDataUrl = await tryLoadImageAsDataUrl([
    `${baseUrl}assets/header-cfe-logo.png?v=${cacheKey}`,
    `${baseUrl}assets/header-cfe-logo.png.png?v=${cacheKey}`,
    `${baseUrl}assets/header-cfe-logo.PNG?v=${cacheKey}`,
    `${window.location.origin}/reportes/assets/header-cfe-logo.png?v=${cacheKey}`,
    `${window.location.origin}/reportes/assets/header-cfe-logo.png.png?v=${cacheKey}`,
    `${window.location.origin}/reportes/assets/header-cfe-logo.PNG?v=${cacheKey}`,
    `${baseUrl}assets/header-cfe-logo,.png?v=${cacheKey}`,
    `${baseUrl}assets/header-cfe-logo.jpg?v=${cacheKey}`,
    `${baseUrl}assets/header-cfe-logo.jpeg?v=${cacheKey}`,
    `${baseUrl}assets/header-cfe-logo.webp?v=${cacheKey}`,
    `${baseUrl}assets/header-cfe-logo.svg?v=${cacheKey}`,
    "assets/header-cfe-logo.png",
  ]);

  const html = `
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Formato de Impresion</title>
  <style>
    @page { size: letter; margin: 8mm; }
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; }
    .sheet { width: 100%; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2mm; }
    /* Medidas fisicas para mantener proporcion estable en hoja carta */
    .header img.title { width: auto; height: 11mm; max-width: 65%; display:block; object-fit: contain; }
    .header img.logo { width: auto; height: 12mm; max-width: 24%; display:block; object-fit: contain; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #000; padding: 1px 3px; font-size: 10px; line-height: 1.2; vertical-align: middle; word-wrap: break-word; }
    .section { margin-top: 4px; }
    .center { text-align: center; }
    .right { text-align: right; }
    .title-row th { font-size: 11px; font-weight: 700; }
    .label { font-size: 9px; font-weight: 400; }
    .value { font-size: 11px; font-weight: 700; }
    .sign { margin-top: 16px; display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 12px; }
    .line { border-bottom: 1px solid #000; height: 20px; margin-bottom: 4px; }
    .meta-row { display: grid; grid-template-columns: 90px 1fr; align-items: end; gap: 6px; margin-bottom: 4px; }
    .meta-line { border-bottom: 1px solid #000; min-height: 16px; font-weight: 700; }
    @media print { .no-print { display:none; } }
  </style>
</head>
<body>
  <div class="sheet">
    <div class="header">
      ${
        titleImgDataUrl
          ? `<img class="title" src="${titleImgDataUrl}" alt="Titulo" />`
          : `<div class="value" style="font-size:16px;">LABORATORIO DE MEDICION DIVISIONAL</div>`
      }
      ${
        logoImgDataUrl
          ? `<img class="logo" src="${logoImgDataUrl}" alt="CFE" />`
          : `<div class="value" style="font-size:16px;">CFE</div>`
      }
    </div>

    <table>
      <tr class="title-row"><th colspan="5" class="center">PRUEBAS A TRANSFORMADORES DE INSTRUMENTO  (EQUIPOS COMBINADOS DE MEDICION SUBTERRANEOS)</th></tr>
      <tr>
        <td><span class="label">TIPO DE EQUIPO</span></td>
        <td colspan="2"><span class="label">EQUIPO COMBINADO DE MEDICION</span></td>
        <td colspan="2"><span class="label">TRANSFORMADORES DE CORRIENTE TIM</span></td>
      </tr>
      <tr class="title-row"><th colspan="6" class="center">DATOS DE PLACA DEL EQUIPO</th></tr>
    </table>

    <table class="section">
      <tr>
        <td><span class="label">ZONA</span><br><span class="value">${esc(v("opciones"))}</span></td>
        <td><span class="label">MARCA</span><br><span class="value">${esc(v("opciones/marca"))}</span></td>
        <td><span class="label">TIPO</span><br><span class="value">${esc(v("tipo"))}</span></td>
        <td><span class="label">NO. SERIE</span><br><span class="value">${esc(v("no_serie"))}</span></td>
        <td><span class="label">FRECUENCIA</span><br><span class="value">${esc(v("frecuencia"))}</span></td>
        <td><span class="label">NIVEL AISL.</span><br><span class="value">${esc(v("nivel_aislamiento"))} K</span></td>
      </tr>
    </table>

    <table class="section">
      <tr class="title-row"><th colspan="5" class="center">TRANSFORMADORES DE POTENCIAL</th></tr>
      <tr>
        <td class="label">Elemento 1<br><span class="value">${esc(v("tp_serie_1"))}</span></td>
        <td class="label">Elemento 2<br><span class="value">${esc(v("tp_serie_2"))}</span></td>
        <td class="label">Elemento 3<br><span class="value">${esc(v("tp_serie_3"))}</span></td>
        <td colspan="2"></td>
      </tr>
      <tr>
        <td class="label">RELACION<br><span class="value">${esc(v("tp_relacion"))}</span></td>
        <td class="label">CONSTANTE<br><span class="value">${esc(v("tp_constante"))}</span></td>
        <td class="label">POT. MAX<br><span class="value">${esc(v("tp_pot_max"))}</span></td>
        <td class="label">CLASE PRECISION<br><span class="value">${esc(v("tp_clase"))}</span></td>
        <td></td>
      </tr>
      ${[1,2,3].map(i => `
        <tr>
          <td class="label">PRUEBA EN VACIO ${i}E</td>
          <td class="label">VOLT. PRIM.<br><span class="value">${esc(v(`tp_vp${i}`))}</span></td>
          <td class="label">VOLT. SEC.<br><span class="value">${esc(v(`tp_vs${i}_e1`))}</span></td>
          <td class="label">RELACION OBTENIDA<br><span class="value">${esc(v(`tp_rel${i}_e1`))}</span></td>
          <td class="center value">${esc(v(`tp_rel${i}_e1`))}</td>
        </tr>
      `).join("")}
    </table>

    <table class="section">
      <tr class="title-row"><th colspan="5" class="center">TRANSFORMADORES DE CORRIENTE</th></tr>
      <tr>
        <td class="label">Elemento 1<br><span class="value">${esc(v("tc_serie_1"))}</span></td>
        <td class="label">Elemento 2<br><span class="value">${esc(v("tc_serie_2"))}</span></td>
        <td class="label">Elemento 3<br><span class="value">${esc(v("tc_serie_3"))}</span></td>
        <td colspan="2"></td>
      </tr>
      <tr>
        <td class="label">RELACION<br><span class="value">${esc(v("tc_relacion"))}</span></td>
        <td class="label">CONSTANTE<br><span class="value">${esc(v("tc_constante"))}</span></td>
        <td class="label">SOBRECORRIENTE MAX<br><span class="value">${esc(v("tc_sobrecorriente"))}</span></td>
        <td class="label">CLASE PRECISION<br><span class="value">${esc(v("tc_clase"))}</span></td>
        <td></td>
      </tr>
      ${[1,2,3].map(i => `
        <tr>
          <td class="label">PRUEBA EN VACIO ${i}E</td>
          <td class="label">CORR. PRIM.<br><span class="value">${esc(v(`tc_cp${i}`))}</span></td>
          <td class="label">CORR. SEC.<br><span class="value">${esc(v(`tc_cs${i}_e1`))}</span></td>
          <td class="label">RELACION OBTENIDA<br><span class="value">${esc(v(`tc_rel${i}_e1`))}</span></td>
          <td class="center value">${esc(v(`tc_rel${i}_e1`))}</td>
        </tr>
      `).join("")}
    </table>

    <table class="section">
      <tr class="title-row"><th colspan="6" class="center">DATOS DE CONEXION DEL EQUIPO</th></tr>
      <tr><th colspan="3" class="center">TP´S</th><th colspan="3" class="center">TC´S</th></tr>
      ${[1,2,3].map(i => `
        <tr>
          <td class="label">Elemento ${i}<br><span class="value">${esc(v(`tp_serie_${i}`))}</span></td>
          <td class="center value">${esc(vPct(`tp_con${i}_a`))}</td>
          <td class="center value">${esc(v(`tp_con${i}_b`))}</td>
          <td class="label">Elemento ${i}<br><span class="value">${esc(v(`tc_serie_${i}`))}</span></td>
          <td class="center value">${esc(vPct(`tc_con${i}_a`))}</td>
          <td class="center value">${esc(v(`tc_con${i}_b`))}</td>
        </tr>
      `).join("")}
    </table>

    <div class="sign">
      <div>
        <div class="center value" style="margin-bottom:8px;">REALIZO</div>
        <div class="line"></div>
        <div class="value">${esc(v("realizo_nom"))}</div>
      </div>
      <div>
        <div class="center value" style="margin-bottom:8px;">REVISO</div>
        <div class="line"></div>
        <div class="value">${esc(v("reviso_nom"))}</div>
      </div>
      <div>
        <div class="center value" style="margin-bottom:8px;">RECIBE</div>
        <div class="meta-row"><div class="value">NOMBRE:</div><div class="meta-line">${esc(v("recibe_nom"))}</div></div>
        <div class="meta-row"><div class="value">FIRMA Y RPE:</div><div class="meta-line">${esc(v("recibe_rpe"))}</div></div>
        <div class="meta-row"><div class="value">ZONA:</div><div class="meta-line">${esc(v("opciones"))}</div></div>
        <div class="meta-row"><div class="value">FECHA:</div><div class="meta-line">${esc(v("recibe_fecha"))}</div></div>
      </div>
    </div>
  </div>

  <script>
    window.onload = () => { window.print(); };
  </script>
</body>
</html>`;

  const printWin = window.open("", "_blank");
  if (!printWin) {
    showToast("Habilita ventanas emergentes para imprimir.");
    return;
  }
  let recoveredAfterPrint = false;
  const recoverAfterPrint = () => {
    if (recoveredAfterPrint) return;
    recoveredAfterPrint = true;
    normalizeFirmasFields();
    triggerRpeValidationById("realizo_rpe");
    triggerRpeValidationById("reviso_rpe");
    triggerRpeValidationById("recibe_rpe");
    updateFormFillProgress();
    updateNavigation();
  };
  const onFocusBack = () => {
    window.removeEventListener("focus", onFocusBack);
    recoverAfterPrint();
  };
  window.addEventListener("focus", onFocusBack);
  // Fallback por si el navegador no dispara focus al volver del diálogo.
  window.setTimeout(recoverAfterPrint, 1500);
  printWin.document.open();
  printWin.document.write(html);
  printWin.document.close();
};

/**
 * Arranque de la SPA: referencias a nodos, estado `currentEvaluationId`, helpers de UI/hub,
 * flujo login/logout, guardado/carga de evaluaciones, aprobaciones, alta de revisores y usuarios,
 * listeners de formulario (categorías, zona, cálculos TP/TC, RPE) e inicialización visual.
 */
document.addEventListener("DOMContentLoaded", () => {
  refreshReportesSections();
  if (currentStep === -1 || currentStep >= sections.length) {
    currentStep = 0;
  }

  const embeddedInSigtae = isSigtaeEmbeddedReportes();
  if (embeddedInSigtae) {
    bootstrapSigtaeReportesSession();
  }

  const loginForm = document.getElementById("loginForm");
  const loginSubmit = document.getElementById("loginSubmit");
  const loginRpe = document.getElementById("loginRpe");
  const loginPassword = document.getElementById("loginPassword");
  const loginScreen = document.getElementById("loginScreen");
  const appShell = document.querySelector(".app-shell");
  const evaluationHub = document.getElementById("evaluationHub");
  const saveEvaluationBtn = document.getElementById("saveEvaluationBtn");
  const submitApprovalBtn = document.getElementById("submitApprovalBtn");
  const approveReportBtn = document.getElementById("approveReportBtn");
  const savedEvaluationsSelect = document.getElementById("savedEvaluations");
  const savedZoneFilter = document.getElementById("savedZoneFilter");
  const loadEvaluationBtn = document.getElementById("loadEvaluationBtn");
  const newEvaluationBtn = document.getElementById("newEvaluationBtn");
  const adminReviewPanel = document.getElementById("adminReviewPanel");
  const masterAdminPanel = document.getElementById("masterAdminPanel");
  const adminReviewersSection = document.getElementById("adminReviewersSection");
  const normalUsersSection = document.getElementById("normalUsersSection");
  const pendingEvaluationsSelect = document.getElementById("pendingEvaluations");
  const approvedEvaluationsSelect = document.getElementById("approvedEvaluations");
  const adminZoneFilter = document.getElementById("adminZoneFilter");
  const loadPendingBtn = document.getElementById("loadPendingBtn");
  const approvePendingBtn = document.getElementById("approvePendingBtn");
  const loadApprovedBtn = document.getElementById("loadApprovedBtn");
  const newAdminRpeInput = document.getElementById("newAdminRpe");
  const newAdminRpeFeedback = document.getElementById("newAdminRpeFeedback");
  const validateAdminRpeBtn = document.getElementById("validateAdminRpeBtn");
  const addAdminRpeBtn = document.getElementById("addAdminRpeBtn");
  const adminRpeList = document.getElementById("adminRpeList");
  const newNormalRpeInput = document.getElementById("newNormalRpe");
  const newNormalRpeFeedback = document.getElementById("newNormalRpeFeedback");
  const validateNormalRpeBtn = document.getElementById("validateNormalRpeBtn");
  const addNormalRpeBtn = document.getElementById("addNormalRpeBtn");
  const normalRpeList = document.getElementById("normalRpeList");
  const evaluationStatus = document.getElementById("evaluationStatus");
  const logoutBtn = document.getElementById("logoutBtn");
  const backToHubBtn = document.getElementById("backToHubBtn");
  let currentEvaluationId = "";
  window.getReportesCurrentEvalId = () => currentEvaluationId;
  let pendingAdminCandidate = null;
  let pendingNormalCandidate = null;
  let lastSavedFilterRpe = "";
  renderCurrentUserName();

  const updateNormalAddBtnState = () => {
    if (!addNormalRpeBtn) return;
    const typed = normalizeRpe(newNormalRpeInput?.value || "");
    const validated = normalizeRpe(pendingNormalCandidate?.rpe || "");
    addNormalRpeBtn.disabled = !(typed && validated && typed === validated);
  };

  /** Muestra u oculta el botón “volver al hub” según si el shell está en modo pre-formulario. */
  const updateBackToHubVisibility = () => {
    if (!backToHubBtn || !appShell) return;
    const inHubMode = appShell.classList.contains("preform-mode");
    backToHubBtn.style.display = inHubMode ? "none" : "";
  };

  /** Mensaje de estado bajo el hub (color según error). */
  const setEvaluationStatus = (message, isError = false) => {
    if (!evaluationStatus) return;
    evaluationStatus.textContent = message || "";
    evaluationStatus.style.color = isError ? "#b42318" : "#5d6b82";
  };

  /** Modo menú vs formulario: clase `preform-mode` y oculta todas las secciones si entra al hub. */
  const setPreformMode = (enabled) => {
    if (!appShell) return;
    appShell.classList.toggle("preform-mode", Boolean(enabled));
    updateBackToHubVisibility();
    if (!enabled) {
      return;
    }
    sections.forEach((section) => section.classList.remove("active"));
  };

  /** Paneles maestro/revisor, botones nueva/guardar y `disabled` de campos según permisos y flujo. */
  const applyRoleMode = () => {
    const admin = isCurrentUserAdmin();
    const master = isCurrentUserMaster();
    const reviewerOnly = sessionIsReviewerAdmin();
    const me = getCurrentRpe();
    const workflowStore = getWorkflowStore();
    const hasAssignedReviewInbox =
      Boolean(me) &&
      [...(workflowStore.pending || []), ...(workflowStore.approved || [])].some(
        (item) => normalizeRpe(item?.reviewerRpe || "") === me
      );
    const canSeeReviewPanel = admin || master || reviewerOnly || hasAssignedReviewInbox;
    const isReviewerProfile = reviewerOnly || hasAssignedReviewInbox || (admin && !master);
    const canManageNormalUsers = master || isReviewerProfile;
    const showCreatorTools = master || !admin || reviewerOnly;
    const showSaveForFirmasOnly = canEditRevisoAndRecibeFirmasOnly();
    const approvedOnlyView = isEvaluationApprovedOnlyInWorkflow(currentEvaluationId);
    const pendingForCurrent = currentEvaluationId
      ? getWorkflowPendingEntryByEvaluationId(currentEvaluationId)
      : null;
    const currentRpe = getCurrentRpe();
    const isReviewerPendingView =
      Boolean(pendingForCurrent) &&
      Boolean(currentRpe) &&
      normalizeRpe(pendingForCurrent.reviewerRpe || "") === currentRpe &&
      !master;
    if (adminReviewPanel) {
      adminReviewPanel.style.display = canSeeReviewPanel ? "" : "none";
    }
    if (masterAdminPanel) {
      masterAdminPanel.style.display = canManageNormalUsers ? "" : "none";
    }
    if (adminReviewersSection) {
      adminReviewersSection.style.display = master ? "" : "none";
    }
    if (normalUsersSection) {
      normalUsersSection.style.display = canManageNormalUsers ? "" : "none";
    }
    updateNormalAddBtnState();
    if (newEvaluationBtn) {
      newEvaluationBtn.style.display = master || !isReviewerProfile ? "" : "none";
    }
    if (saveEvaluationBtn) {
      if (approvedOnlyView || isReviewerPendingView) {
        saveEvaluationBtn.style.display = "none";
      } else {
        saveEvaluationBtn.style.display = showCreatorTools || showSaveForFirmasOnly ? "" : "none";
      }
    }
    applyReportFieldEditability();

    if (typeof window.updateApprovalActionButtons === "function") {
      window.updateApprovalActionButtons();
    }
  };

  /** Lleva al usuario a la sección que contiene el campo y enfoca el control (tras validación fallida). */
  const focusSectionForField = (field) => {
    if (!field) return;
    const section = field.closest(".form-section");
    if (!section) return;
    const idx = sections.indexOf(section);
    if (idx >= 0) {
      currentStep = idx;
      updateNavigation();
    }
    window.setTimeout(() => {
      try {
        field.focus();
      } catch (_e) {
        // noop
      }
    }, 0);
  };

  /** Visibilidad de “Enviar a aprobación” y “Aprobar” según paso firmas, RPEs y estado en workflow. */
  window.updateApprovalActionButtons = function updateApprovalActionButtons() {
    if (!submitApprovalBtn && !approveReportBtn) return;

    const evalId =
      typeof window.getReportesCurrentEvalId === "function"
        ? String(window.getReportesCurrentEvalId() || "").trim()
        : "";
    if (evalId && isEvaluationApprovedOnlyInWorkflow(evalId)) {
      if (submitApprovalBtn) {
        submitApprovalBtn.style.display = "none";
      }
      if (approveReportBtn) {
        approveReportBtn.style.display = "none";
      }
      return;
    }

    const onFirmasStep = sections[currentStep]?.id === "section-firmas";
    const rpeSession = getCurrentRpe();
    const admin = isCurrentUserAdmin();
    const master = isCurrentUserMaster();
    const reviewerRpe = normalizeRpe(document.getElementById("reviso_rpe")?.value || "");
    const realizoRpe = normalizeRpe(document.getElementById("realizo_rpe")?.value || "");
    const pendingForCurrent = currentEvaluationId
      ? getWorkflowPendingEntryByEvaluationId(currentEvaluationId)
      : null;
    const approvedForCurrent = currentEvaluationId
      ? getWorkflowApprovedEntryByEvaluationId(currentEvaluationId)
      : null;
    const canSendToApproval =
      Boolean(rpeSession) &&
      onFirmasStep &&
      (!admin || master || sessionIsReviewerAdmin()) &&
      Boolean(reviewerRpe) &&
      reviewerRpe !== realizoRpe &&
      !pendingForCurrent;
    const canApproveFromForm =
      Boolean(rpeSession) &&
      onFirmasStep &&
      Boolean(currentEvaluationId) &&
      Boolean(pendingForCurrent);

    if (submitApprovalBtn) {
      const blockedByApproved = Boolean(currentEvaluationId) && Boolean(approvedForCurrent);
      submitApprovalBtn.style.display = canSendToApproval && !blockedByApproved ? "" : "none";
    }
    if (approveReportBtn) {
      approveReportBtn.style.display = canApproveFromForm ? "" : "none";
    }
  };

  if (submitApprovalBtn) {
    submitApprovalBtn.addEventListener("click", () => {
      if (!canCurrentUserEditReportForm()) {
        setEvaluationStatus("No tienes permiso para enviar este reporte a aprobación.", true);
        return;
      }
      const rpe = getCurrentRpe();
      if (!rpe) {
        setEvaluationStatus("Debes iniciar sesión para enviar a aprobación.", true);
        return;
      }
      const shouldValidate =
        !isCurrentUserAdmin() || isCurrentUserMaster() || sessionIsReviewerAdmin();
      if (shouldValidate) {
        const missingAnywhere = getFirstMissingRequiredFieldAnywhere();
        if (missingAnywhere?.missing) {
          const labelText = getFieldLabelForToast(missingAnywhere.missing);
          focusSectionForField(missingAnywhere.missing);
          showToast(`Falta completar: ${labelText}`);
          return;
        }
      }

      const reviewerRpe = normalizeRpe(document.getElementById("reviso_rpe")?.value || "");
      const realizoRpe = normalizeRpe(document.getElementById("realizo_rpe")?.value || "");
      if (!reviewerRpe || reviewerRpe === realizoRpe) {
        setEvaluationStatus("Define un revisor distinto a quien realizó la evaluación.", true);
        return;
      }
      if (currentEvaluationId && getWorkflowApprovedEntryByEvaluationId(currentEvaluationId)) {
        setEvaluationStatus("Esta evaluación ya está aprobada.", true);
        if (typeof window.updateApprovalActionButtons === "function") {
          window.updateApprovalActionButtons();
        }
        return;
      }

      const snapshot = collectFormSnapshot();
      const performedBy = buildPerformedBySnapshot();
      const now = Date.now();
      const nowIso = formatNowIsoLocal();
      const evaluations = getSavedEvaluations(rpe);

      /** Registra pendiente en workflow y deja copia en la lista del revisor asignado. */
      const pushWorkflowAndReviewerCopy = (evaluationId) => {
        upsertPendingWorkflowEntry({
          evaluationId,
          reviewerRpe,
          requestedByRpe: rpe,
          timestamp: now,
          fechaHora: nowIso,
          updatedAt: now,
          snapshot,
          performedBy,
        });
        upsertEvaluationForUser(reviewerRpe, {
          id: evaluationId,
          userId: rpe,
          reviewerRpe,
          timestamp: now,
          fechaHora: nowIso,
          updatedAt: now,
          updatedAtIso: nowIso,
          snapshot,
          performedBy,
          workflowStatus: "pending",
        });
      };

      if (currentEvaluationId) {
        const idx = evaluations.findIndex((x) => x.id === currentEvaluationId);
        if (idx < 0) {
          setEvaluationStatus("No se encontró la evaluación actual en tus guardadas.", true);
          return;
        }
        evaluations[idx] = {
          ...evaluations[idx],
          snapshot,
          performedBy,
          updatedAt: now,
          updatedAtIso: nowIso,
        };
        setSavedEvaluations(rpe, evaluations);

        const pruebasStore = getPruebasStore();
        const pidx = pruebasStore.pruebas.findIndex((p) => p.id === currentEvaluationId);
        if (pidx >= 0) {
          pruebasStore.pruebas[pidx] = {
            ...pruebasStore.pruebas[pidx],
            snapshot,
            performedBy,
            updatedAt: now,
            updatedAtIso: nowIso,
          };
        } else {
          pruebasStore.pruebas.unshift({
            id: currentEvaluationId,
            userId: rpe,
            timestamp: now,
            fechaHora: nowIso,
            snapshot,
            performedBy,
          });
        }
        setPruebasStore(pruebasStore);

        appendResultadoEntry({
          resultadoId: `res_${now}_${Math.random().toString(36).slice(2, 8)}`,
          evaluationId: currentEvaluationId,
          userId: rpe,
          accion: "envio_aprobacion",
          timestamp: now,
          fechaHora: nowIso,
          snapshot,
          performedBy,
        });

        pushWorkflowAndReviewerCopy(currentEvaluationId);
        refreshAdminReviewUI();
        refreshSavedEvaluationsUI();
        window.updateApprovalActionButtons();
        setEvaluationStatus("Evaluación enviada a pendientes de aprobación.");
        showToast("Enviada a aprobación");
        return;
      }

      const id = `ev_${now}`;
      evaluations.unshift({
        id,
        userId: rpe,
        timestamp: now,
        fechaHora: nowIso,
        createdAt: now,
        updatedAt: now,
        createdAtIso: nowIso,
        updatedAtIso: nowIso,
        snapshot,
        performedBy,
      });
      setSavedEvaluations(rpe, evaluations);

      const pruebasStore = getPruebasStore();
      pruebasStore.pruebas.unshift({
        id,
        userId: rpe,
        timestamp: now,
        fechaHora: nowIso,
        snapshot,
        performedBy,
      });
      setPruebasStore(pruebasStore);

      appendResultadoEntry({
        resultadoId: `res_${now}_${Math.random().toString(36).slice(2, 8)}`,
        evaluationId: id,
        userId: rpe,
        accion: "envio_aprobacion",
        timestamp: now,
        fechaHora: nowIso,
        snapshot,
        performedBy,
      });

      currentEvaluationId = id;
      pushWorkflowAndReviewerCopy(id);
      refreshAdminReviewUI();
      refreshSavedEvaluationsUI();
      window.updateApprovalActionButtons();
      setEvaluationStatus("Evaluación guardada y enviada a pendientes de aprobación.");
      showToast("Enviada a aprobación");
    });
  }

  if (approveReportBtn) {
    approveReportBtn.addEventListener("click", () => {
      const reviewerRpe = getCurrentRpe();
      const evaluationId = String(currentEvaluationId || "").trim();
      if (!reviewerRpe || !evaluationId) {
        setEvaluationStatus("Abre una evaluación pendiente para aprobarla.", true);
        return;
      }
      const pending = getWorkflowPendingEntryByEvaluationId(evaluationId);
      if (!pending) {
        setEvaluationStatus("Esta evaluación no está pendiente de aprobación.", true);
        window.updateApprovalActionButtons();
        return;
      }
      const assignedMatch = normalizeRpe(pending.reviewerRpe || "") === reviewerRpe;
      const canApproveByRole =
        isCurrentUserMaster() || isCurrentUserAdmin() || sessionIsReviewerAdmin() || assignedMatch;
      const puedeAprobar = Boolean(reviewerRpe) && canApproveByRole;
      if (!puedeAprobar) {
        setEvaluationStatus("No tienes permisos para aprobar este reporte.", true);
        return;
      }
      const approvedItem = approveWorkflowEntry(evaluationId, reviewerRpe);
      if (!approvedItem) {
        setEvaluationStatus("No se pudo aprobar: la evaluación ya no está pendiente.", true);
        refreshAdminReviewUI();
        window.updateApprovalActionButtons();
        return;
      }
      refreshAdminReviewUI();
      refreshSavedEvaluationsUI();
      window.updateApprovalActionButtons();
      setEvaluationStatus("Evaluación aprobada y movida a reportes aprobados.");
      showToast("Reporte aprobado");
    });
  }

  /** Texto de opción en select: tipo/serie y marca de tiempo localizada. */
  const buildEvaluationLabel = (item) => {
    const serie = String(item?.snapshot?.no_serie || "").trim();
    const tipo = String(item?.snapshot?.tipo || "").trim();
    const stamp = new Date(item.updatedAt || item.createdAt || Date.now()).toLocaleString("es-MX");
    const base = [tipo, serie].filter(Boolean).join(" / ");
    return `${base || "Evaluación"} - ${stamp}`;
  };

  /** Rellena select de evaluaciones del usuario y filtro por zona (incluye copias de workflow asignadas). */
  const refreshSavedEvaluationsUI = () => {
    if (!savedEvaluationsSelect) return;
    const rpe = getCurrentRpe();
    if (!rpe) {
      savedEvaluationsSelect.innerHTML = '<option value="">Inicia sesión para ver guardadas</option>';
      if (savedZoneFilter) {
        savedZoneFilter.innerHTML = '<option value="">Todas las zonas</option>';
      }
      return;
    }
    syncAssignedWorkflowToSavedEvaluations(rpe);
    const evaluations = getMergedEvaluationsForUser(rpe);
    const zones = Array.from(
      new Set(
        evaluations
          .map((item) => resolveZonaLabel(item?.snapshot?.opciones))
          .filter(Boolean)
      )
    ).sort((a, b) => a.localeCompare(b, "es"));
    if (savedZoneFilter) {
      const normalizedRpe = normalizeRpe(rpe);
      const previous = normalizedRpe !== lastSavedFilterRpe ? "" : String(savedZoneFilter.value || "");
      savedZoneFilter.innerHTML = '<option value="">Todas las zonas</option>';
      zones.forEach((zone) => {
        const opt = document.createElement("option");
        opt.value = zone;
        opt.textContent = zone;
        savedZoneFilter.appendChild(opt);
      });
      if (zones.includes(previous)) {
        savedZoneFilter.value = previous;
      } else {
        savedZoneFilter.value = "";
      }
      lastSavedFilterRpe = normalizedRpe;
    }
    const zoneFilter = String(savedZoneFilter?.value || "").trim();
    const filteredEvaluations = evaluations.filter((item) => {
      if (!zoneFilter) return true;
      return resolveZonaLabel(item?.snapshot?.opciones) === zoneFilter;
    });
    savedEvaluationsSelect.innerHTML = "";
    if (!filteredEvaluations.length) {
      savedEvaluationsSelect.innerHTML = '<option value="">Sin evaluaciones guardadas</option>';
      return;
    }
    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.textContent = "Selecciona una evaluación";
    savedEvaluationsSelect.appendChild(defaultOption);
    filteredEvaluations.forEach((item) => {
      const opt = document.createElement("option");
      opt.value = item.id;
      const label = buildEvaluationLabel(item);
      const wf =
        resolveWorkflowUiStatusForEvaluationId(item.id) ||
        (item.workflowStatus === "pending" || item.workflowStatus === "approved" ? item.workflowStatus : "");
      const workflowTag = wf === "pending" ? " [PENDIENTE]" : wf === "approved" ? " [APROBADA]" : "";
      opt.textContent = `${label}${workflowTag}`;
      savedEvaluationsSelect.appendChild(opt);
    });
    if (currentEvaluationId) {
      savedEvaluationsSelect.value = currentEvaluationId;
    }
  };
  if (savedZoneFilter) {
    savedZoneFilter.addEventListener("change", refreshSavedEvaluationsUI);
  }

  /** Bandejas pendientes/aprobadas del revisor (o todas si es maestro), con filtro por zona. */
  const refreshAdminReviewUI = () => {
    if (!pendingEvaluationsSelect || !approvedEvaluationsSelect) return;
    const store = getWorkflowStore();
    const masterSession = isCurrentUserMaster();
    // Todos los revisores ven la bandeja completa (pendientes y aprobadas).
    const pendingAll = store.pending || [];
    const approvedAll = store.approved || [];
    const pendingZones = Array.from(
      new Set(
        pendingAll
          .map((x) => resolveZonaLabel(x?.snapshot?.opciones))
          .filter(Boolean)
      )
    ).sort((a, b) => a.localeCompare(b, "es"));
    const zones = Array.from(
      new Set(
        [...pendingAll, ...approvedAll]
          .map((x) => resolveZonaLabel(x?.snapshot?.opciones))
          .filter(Boolean)
      )
    ).sort((a, b) => a.localeCompare(b, "es"));

    if (adminZoneFilter) {
      const previous = String(adminZoneFilter.value || "");
      adminZoneFilter.innerHTML = '<option value="">Todas las zonas</option>';
      // El filtro de esta bandeja se alimenta por zonas pendientes.
      pendingZones.forEach((zone) => {
        const opt = document.createElement("option");
        opt.value = zone;
        opt.textContent = zone;
        adminZoneFilter.appendChild(opt);
      });
      if (pendingZones.includes(previous)) {
        adminZoneFilter.value = previous;
      } else {
        adminZoneFilter.value = "";
      }
      adminZoneFilter.disabled = pendingZones.length === 0;
    }

    const zoneFilter = String(adminZoneFilter?.value || "").trim();
    const byZone = (item) =>
      !zoneFilter || resolveZonaLabel(item?.snapshot?.opciones) === zoneFilter;
    const pending = pendingAll.filter(byZone);
    const approved = approvedAll.filter(byZone);

    const fillSelect = (el, items, emptyText) => {
      el.innerHTML = "";
      if (!items.length) {
        el.innerHTML = `<option value="">${emptyText}</option>`;
        return;
      }
      const first = document.createElement("option");
      first.value = "";
      first.textContent = "Selecciona una evaluación";
      el.appendChild(first);
      items.forEach((item) => {
        const option = document.createElement("option");
        option.value = item.evaluationId;
        const tipo = String(item?.snapshot?.tipo || "").trim();
        const serie = String(item?.snapshot?.no_serie || "").trim();
        const zona = resolveZonaLabel(item?.snapshot?.opciones);
        const fecha = new Date(item.updatedAt || item.timestamp || Date.now()).toLocaleString("es-MX");
        const asignado = normalizeRpe(item.reviewerRpe || "");
        const asignadoTxt = masterSession && asignado ? ` — Rev: ${asignado}` : "";
        option.textContent = `${tipo || "Evaluación"}${serie ? ` / ${serie}` : ""}${zona ? ` / ${zona}` : ""}${asignadoTxt} - ${fecha}`;
        el.appendChild(option);
      });
    };

    fillSelect(pendingEvaluationsSelect, pending, "Sin pendientes");
    fillSelect(approvedEvaluationsSelect, approved, "Sin aprobadas");
  };

  if (adminZoneFilter) {
    adminZoneFilter.addEventListener("change", refreshAdminReviewUI);
  }

  /** Lista desplegable de RPE dados de alta como administradores revisores. */
  const refreshAdminRpeListUI = () => {
    if (!adminRpeList) return;
    const list = getAdminReviewerRpes();
    adminRpeList.innerHTML = "";
    if (!list.length) {
      adminRpeList.innerHTML = '<option value="">Sin administradores</option>';
      return;
    }
    list.forEach((rpe) => {
      const opt = document.createElement("option");
      opt.value = rpe;
      opt.textContent = rpe;
      adminRpeList.appendChild(opt);
    });
  };

  /** Lista de usuarios normales activos dados de alta por MAESTRO. */
  const refreshNormalUserListUI = () => {
    if (!normalRpeList) return;
    const usuarios = getUsuariosStore();
    const list = (usuarios.usuarios || [])
      .filter(
        (u) =>
          !parseBooleanLike(u?.isAdmin, false) &&
          !parseBooleanLike(u?.isMaster, false) &&
          parseBooleanLike(u?.activo, true)
      )
      .sort((a, b) =>
        normalizeRpe(a?.RPE || "").localeCompare(normalizeRpe(b?.RPE || ""), "es")
      );
    normalRpeList.innerHTML = "";
    if (!list.length) {
      normalRpeList.innerHTML = '<option value="">Sin usuarios normales</option>';
      return;
    }
    list.forEach((item) => {
      const rpe = normalizeRpe(item?.RPE || "");
      const nombre = normalizeUpperText(item?.NOMBRE || "");
      const opt = document.createElement("option");
      opt.value = rpe;
      opt.textContent = nombre ? `${rpe} - ${nombre}` : rpe;
      normalRpeList.appendChild(opt);
    });
  };
  /** Arranque standalone: pantalla de login hasta validar credenciales. */
  if (!embeddedInSigtae) {
    window.sessionStorage.removeItem("reportesAuth");
    if (appShell) {
      appShell.classList.add("is-hidden");
    }
    if (loginScreen) {
      loginScreen.style.display = "";
    }
  } else {
    refreshSavedEvaluationsUI();
    refreshAdminReviewUI();
    refreshAdminRpeListUI();
    refreshNormalUserListUI();
    applyRoleMode();
    setPreformMode(true);
    if (typeof window.updateApprovalActionButtons === "function") {
      window.updateApprovalActionButtons();
    }
  }

  if (loginForm && loginSubmit && loginRpe && loginPassword) {
    loginForm.addEventListener("submit", async (event) => {
      event.preventDefault();

      const rpe = String(loginRpe.value || "").trim();
      const password = String(loginPassword.value || "").trim();
      if (!rpe || !password) {
        setLoginFeedback("Captura RPE y contraseña.", "error");
        return;
      }

      loginSubmit.disabled = true;
      setLoginFeedback("Validando credenciales...", "");

      try {
        const validation = await validateCredentials(rpe, password);
        if (validation.isValid) {
          let profile = extractFirstProfileFromPayload(validation.parsed);
          if (!profile) {
            const lookup = await fetchRpeProfile(rpe);
            profile = lookup?.profile || null;
          }
          setLoginFeedback("Acceso correcto. Cargando formulario...", "success");
          unlockApplicationAfterLogin(rpe, profile);
          refreshSavedEvaluationsUI();
          refreshAdminReviewUI();
          refreshAdminRpeListUI();
          refreshNormalUserListUI();
          applyRoleMode();
          if (typeof window.updateApprovalActionButtons === "function") {
            window.updateApprovalActionButtons();
          }
          showToast(`Bienvenido ${rpe}`);
        } else if (!validation.httpOk) {
          if (hasDnsResolutionError(validation.errors)) {
            setLoginFeedback(
              "No se pudo validar: el dominio de autenticación no resuelve DNS.",
              "error"
            );
          } else if (validation.status === 404) {
            setLoginFeedback("No se pudo validar: endpoint de acceso no encontrado (404).", "error");
          } else {
            setLoginFeedback("No se pudo validar (error de servicio).", "error");
          }
        } else {
          setLoginFeedback("RPE o contraseña incorrectos.", "error");
        }
      } catch (_error) {
        setLoginFeedback("No fue posible conectar con la API de validación.", "error");
      } finally {
        loginSubmit.disabled = false;
      }
    });
  }

  if (logoutBtn && !embeddedInSigtae) {
    logoutBtn.addEventListener("click", () => {
      logoutApplication();
      applyRoleMode();
      if (typeof window.updateApprovalActionButtons === "function") {
        window.updateApprovalActionButtons();
      }
      showToast("Sesión cerrada.");
    });
  }

  if (backToHubBtn) {
    backToHubBtn.addEventListener("click", () => {
      setPreformMode(true);
      currentStep = 0;
      updateNavigation();
      refreshSavedEvaluationsUI();
      refreshAdminReviewUI();
      applyRoleMode();
      if (typeof window.updateApprovalActionButtons === "function") {
        window.updateApprovalActionButtons();
      }
      setEvaluationStatus("Regresaste al menú principal.");
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  if (saveEvaluationBtn) {
    saveEvaluationBtn.addEventListener("click", () => {
      const pendingForCurrent = currentEvaluationId
        ? getWorkflowPendingEntryByEvaluationId(currentEvaluationId)
        : null;
      const currentRpe = getCurrentRpe();
      const reviewerPendingLock =
        Boolean(pendingForCurrent) &&
        Boolean(currentRpe) &&
        normalizeRpe(pendingForCurrent.reviewerRpe || "") === currentRpe &&
        !isCurrentUserMaster();
      if (reviewerPendingLock) {
        setEvaluationStatus("Los reportes pendientes de aprobación no se pueden guardar por el revisor.", true);
        return;
      }
      if (!canCurrentUserSaveReportChanges()) {
        setEvaluationStatus("No tienes permiso para editar o guardar este reporte.", true);
        return;
      }
      if (isEvaluationApprovedOnlyInWorkflow(currentEvaluationId)) {
        setEvaluationStatus("Las evaluaciones aprobadas solo permiten generar el PDF.", true);
        return;
      }
      if (canEditRevisoAndRecibeFirmasOnly() && currentEvaluationId) {
        if (!persistRevisorFirmasSnapshotSave()) {
          setEvaluationStatus("No se pudieron guardar las firmas. Verifica que el reporte siga en pendientes.", true);
          return;
        }
        setEvaluationStatus("Firmas (Revisó / Recibe) guardadas correctamente.");
        refreshSavedEvaluationsUI();
        refreshAdminReviewUI();
        applyRoleMode();
        setPreformMode(true);
        currentStep = 0;
        updateNavigation();
        if (typeof window.updateApprovalActionButtons === "function") {
          window.updateApprovalActionButtons();
        }
        return;
      }
      const rpe = getCurrentRpe();
      if (!rpe) {
        setEvaluationStatus("Debes iniciar sesión para guardar.", true);
        return;
      }
      const evaluations = getSavedEvaluations(rpe);
      const snapshot = collectFormSnapshot();
      const now = Date.now();
      if (currentEvaluationId) {
        const idx = evaluations.findIndex((x) => x.id === currentEvaluationId);
        if (idx >= 0) {
          const performedBy = buildPerformedBySnapshot();
          const nowIso = formatNowIsoLocal();
          evaluations[idx] = {
            ...evaluations[idx],
            snapshot,
            performedBy,
            updatedAt: now,
            updatedAtIso: nowIso,
          };
          setSavedEvaluations(rpe, evaluations);
          const pruebasStore = getPruebasStore();
          const pidx = pruebasStore.pruebas.findIndex((p) => p.id === currentEvaluationId);
          if (pidx >= 0) {
            pruebasStore.pruebas[pidx] = {
              ...pruebasStore.pruebas[pidx],
              snapshot,
              performedBy,
              updatedAt: now,
              updatedAtIso: nowIso,
            };
          } else {
            pruebasStore.pruebas.unshift({
              id: currentEvaluationId,
              userId: rpe,
              timestamp: now,
              fechaHora: nowIso,
              snapshot,
              performedBy,
            });
          }
          setPruebasStore(pruebasStore);
          appendResultadoEntry({
            resultadoId: `res_${now}_${Math.random().toString(36).slice(2, 8)}`,
            evaluationId: currentEvaluationId,
            userId: rpe,
            accion: "actualizacion",
            timestamp: now,
            fechaHora: nowIso,
            snapshot,
            performedBy,
          });
          const syncedWorkflow = syncWorkflowDataAfterEvaluationEdit(currentEvaluationId, {
            snapshot,
            performedBy,
            updatedAt: now,
            updatedAtIso: nowIso,
            ownerRpe: rpe,
          });
          setEvaluationStatus(
            syncedWorkflow
              ? "Evaluación actualizada; también se actualizó el reporte en pendientes o aprobados."
              : "Evaluación actualizada."
          );
          refreshSavedEvaluationsUI();
          if (syncedWorkflow) {
            refreshAdminReviewUI();
          }
          setPreformMode(true);
          currentStep = 0;
          updateNavigation();
          if (typeof window.updateApprovalActionButtons === "function") {
            window.updateApprovalActionButtons();
          }
          return;
        }
      }
      const id = `ev_${now}`;
      const performedBy = buildPerformedBySnapshot();
      const nowIso = formatNowIsoLocal();
      evaluations.unshift({
        id,
        userId: rpe,
        timestamp: now,
        fechaHora: nowIso,
        createdAt: now,
        updatedAt: now,
        createdAtIso: nowIso,
        updatedAtIso: nowIso,
        snapshot,
        performedBy,
      });
      setSavedEvaluations(rpe, evaluations);
      const pruebasStore = getPruebasStore();
      pruebasStore.pruebas.unshift({
        id,
        userId: rpe,
        timestamp: now,
        fechaHora: nowIso,
        snapshot,
        performedBy,
      });
      setPruebasStore(pruebasStore);
      appendResultadoEntry({
        resultadoId: `res_${now}_${Math.random().toString(36).slice(2, 8)}`,
        evaluationId: id,
        userId: rpe,
        accion: "alta",
        timestamp: now,
        fechaHora: nowIso,
        snapshot,
        performedBy,
      });
      currentEvaluationId = id;
      setEvaluationStatus("Evaluación guardada.");
      refreshSavedEvaluationsUI();
      setPreformMode(true);
      currentStep = 0;
      updateNavigation();
      if (typeof window.updateApprovalActionButtons === "function") {
        window.updateApprovalActionButtons();
      }
    });
  }

  if (loadEvaluationBtn && savedEvaluationsSelect) {
    loadEvaluationBtn.addEventListener("click", () => {
      const rpe = getCurrentRpe();
      const id = String(savedEvaluationsSelect.value || "");
      if (!rpe || !id) {
        setEvaluationStatus("Selecciona una evaluación para cargar.", true);
        return;
      }
      const evaluations = getMergedEvaluationsForUser(rpe);
      const selected = evaluations.find((x) => x.id === id);
      if (!selected) {
        setEvaluationStatus("No se encontró la evaluación seleccionada.", true);
        return;
      }
      applyFormSnapshot(selected.snapshot || {});
      applyPerformedBySnapshot(selected.performedBy || {});
      normalizeFirmasFields();
      // Revalida RPEs al cargar para refrescar nombre/correo desde API.
      triggerRpeValidationById("reviso_rpe");
      triggerRpeValidationById("recibe_rpe");
      currentEvaluationId = id;
      setEvaluationStatus(
        canCurrentUserEditReportForm()
          ? "Evaluación cargada. Ya puedes editarla."
          : "Evaluación cargada en modo solo lectura."
      );
      setPreformMode(false);
      currentStep = 0;
      updateNavigation();
      applyRoleMode();
      if (typeof window.updateApprovalActionButtons === "function") {
        window.updateApprovalActionButtons();
      }
    });
  }

  if (loadPendingBtn && pendingEvaluationsSelect) {
    loadPendingBtn.addEventListener("click", () => {
      const evaluationId = String(pendingEvaluationsSelect.value || "");
      if (!evaluationId) {
        setEvaluationStatus("Selecciona una evaluación pendiente.", true);
        return;
      }
      const store = getWorkflowStore();
      const item = (store.pending || []).find((x) => x.evaluationId === evaluationId);
      if (!item) {
        setEvaluationStatus("La evaluación pendiente ya no existe.", true);
        refreshAdminReviewUI();
        return;
      }
      applyFormSnapshot(item.snapshot || {});
      applyPerformedBySnapshot(item.performedBy || {});
      normalizeFirmasFields();
      triggerRpeValidationById("reviso_rpe");
      triggerRpeValidationById("recibe_rpe");
      currentEvaluationId = evaluationId;
      setPreformMode(false);
      currentStep = 0;
      updateNavigation();
      applyRoleMode();
      if (typeof window.updateApprovalActionButtons === "function") {
        window.updateApprovalActionButtons();
      }
      setEvaluationStatus(
        canCurrentUserEditReportForm()
          ? "Evaluación pendiente cargada."
          : "Evaluación pendiente cargada en modo solo lectura."
      );
    });
  }

  if (approvePendingBtn && pendingEvaluationsSelect) {
    approvePendingBtn.addEventListener("click", () => {
      const evaluationId = String(pendingEvaluationsSelect.value || "");
      const reviewerRpe = getCurrentRpe();
      if (!evaluationId) {
        setEvaluationStatus("Selecciona una evaluación pendiente para aprobar.", true);
        return;
      }
      const approvedItem = approveWorkflowEntry(evaluationId, reviewerRpe);
      if (!approvedItem) {
        setEvaluationStatus("No se encontró la evaluación pendiente.", true);
        refreshAdminReviewUI();
        return;
      }
      refreshAdminReviewUI();
      refreshSavedEvaluationsUI();
      if (typeof window.updateApprovalActionButtons === "function") {
        window.updateApprovalActionButtons();
      }
      setEvaluationStatus("Evaluación aprobada y movida a reportes aprobados.");
    });
  }

  if (loadApprovedBtn && approvedEvaluationsSelect) {
    loadApprovedBtn.addEventListener("click", () => {
      const evaluationId = String(approvedEvaluationsSelect.value || "");
      if (!evaluationId) {
        setEvaluationStatus("Selecciona una evaluación aprobada.", true);
        return;
      }
      const store = getWorkflowStore();
      const item = (store.approved || []).find((x) => x.evaluationId === evaluationId);
      if (!item) {
        setEvaluationStatus("No se encontró la evaluación aprobada.", true);
        refreshAdminReviewUI();
        return;
      }
      applyFormSnapshot(item.snapshot || {});
      applyPerformedBySnapshot(item.performedBy || {});
      normalizeFirmasFields();
      triggerRpeValidationById("reviso_rpe");
      triggerRpeValidationById("recibe_rpe");
      currentEvaluationId = evaluationId;
      setPreformMode(false);
      currentStep = 0;
      updateNavigation();
      applyRoleMode();
      if (typeof window.updateApprovalActionButtons === "function") {
        window.updateApprovalActionButtons();
      }
      setEvaluationStatus(
        canCurrentUserEditReportForm()
          ? "Evaluación aprobada cargada."
          : "Evaluación aprobada cargada en modo solo lectura."
      );
    });
  }

  if (newEvaluationBtn) {
    newEvaluationBtn.addEventListener("click", () => {
      const me = getCurrentRpe();
      const workflowStore = getWorkflowStore();
      const hasAssignedReviewInbox =
        Boolean(me) &&
        [...(workflowStore.pending || []), ...(workflowStore.approved || [])].some(
          (item) => normalizeRpe(item?.reviewerRpe || "") === me
        );
      const isReviewerProfile =
        sessionIsReviewerAdmin() || hasAssignedReviewInbox || (isCurrentUserAdmin() && !isCurrentUserMaster());
      if (isReviewerProfile && !isCurrentUserMaster()) {
        setEvaluationStatus("Los revisores no pueden realizar evaluaciones nuevas.", true);
        return;
      }
      currentEvaluationId = "";
      clearEditableFormFields();
      applyTpCategoryDefaults();
      const categoriaActual = String(document.getElementById("tp_categoria")?.value || "");
      applyTcCategoryDefaults(categoriaActual);
      fillRealizoFromSession({ force: true });
      normalizeFirmasFields();
      setPreformMode(false);
      currentStep = 0;
      updateNavigation();
      refreshSavedEvaluationsUI();
      applyRoleMode();
      if (typeof window.updateApprovalActionButtons === "function") {
        window.updateApprovalActionButtons();
      }
      setEvaluationStatus("Formulario listo para nueva evaluación.");
    });
  }

  if (newAdminRpeInput) {
    newAdminRpeInput.addEventListener("input", () => {
      newAdminRpeInput.value = normalizeRpe(newAdminRpeInput.value);
      pendingAdminCandidate = null;
      if (addAdminRpeBtn) addAdminRpeBtn.disabled = true;
      if (newAdminRpeFeedback) setRpeFeedback(newAdminRpeFeedback, "", "");
    });
  }

  if (validateAdminRpeBtn && newAdminRpeInput) {
    validateAdminRpeBtn.addEventListener("click", async () => {
      if (!isCurrentUserMaster()) {
        setEvaluationStatus("Solo el perfil MAESTRO puede dar de alta administradores.", true);
        return;
      }
      const rpe = normalizeRpe(newAdminRpeInput.value);
      if (!rpe) {
        setEvaluationStatus("Captura un RPE válido para darlo de alta.", true);
        return;
      }
      validateAdminRpeBtn.disabled = true;
      pendingAdminCandidate = null;
      if (addAdminRpeBtn) addAdminRpeBtn.disabled = true;
      if (newAdminRpeFeedback) setRpeFeedback(newAdminRpeFeedback, "is-loading", "Validando RPE...");
      setEvaluationStatus("Validando RPE en directorio/API...");
      try {
        const lookup = await fetchRpeProfile(rpe);
        if (lookup.status !== "found" || !lookup.profile) {
          setEvaluationStatus("No se pudo dar de alta: el RPE no existe en directorio/API.", true);
          if (newAdminRpeFeedback) {
            setRpeFeedback(newAdminRpeFeedback, "is-error", "El RPE no existe en directorio/API.");
          }
          return;
        }
        const alreadyAdmin = getAdminReviewerRpes().includes(rpe);
        if (alreadyAdmin) {
          setEvaluationStatus("Ese RPE ya está registrado o no es válido.", true);
          if (newAdminRpeFeedback) {
            setRpeFeedback(newAdminRpeFeedback, "is-error", "Ese RPE ya está dado de alta.");
          }
          return;
        }
        pendingAdminCandidate = {
          rpe,
          nombre: lookup.profile?.nombre || "",
          correo: lookup.profile?.correo || "",
          area: lookup.profile?.area || "",
        };
        if (newAdminRpeFeedback) {
          const nombre = normalizeUpperText(pendingAdminCandidate.nombre || "SIN NOMBRE");
          const correo = pendingAdminCandidate.correo || "Sin correo";
          setRpeFeedback(newAdminRpeFeedback, "is-success", `Nombre: ${nombre} | Correo: ${correo}`);
        }
        if (addAdminRpeBtn) addAdminRpeBtn.disabled = false;
        setEvaluationStatus(`RPE ${rpe} validado. Ahora confirma el alta.`);
      } catch (_error) {
        setEvaluationStatus("No se pudo validar el RPE contra directorio/API.", true);
        if (newAdminRpeFeedback) {
          setRpeFeedback(newAdminRpeFeedback, "is-error", "No se pudo validar el RPE.");
        }
      } finally {
        validateAdminRpeBtn.disabled = false;
      }
    });
  }

  if (addAdminRpeBtn && newAdminRpeInput) {
    addAdminRpeBtn.addEventListener("click", () => {
      if (!isCurrentUserMaster()) {
        setEvaluationStatus("Solo el perfil MAESTRO puede dar de alta administradores.", true);
        return;
      }
      if (!pendingAdminCandidate?.rpe) {
        setEvaluationStatus("Primero valida el RPE en directorio/API.", true);
        return;
      }
      const currentTyped = normalizeRpe(newAdminRpeInput.value);
      if (currentTyped !== pendingAdminCandidate.rpe) {
        setEvaluationStatus("El RPE cambió. Vuelve a validar antes de dar de alta.", true);
        addAdminRpeBtn.disabled = true;
        return;
      }
      if (!addAdminReviewerRpe(pendingAdminCandidate.rpe)) {
        setEvaluationStatus("Ese RPE ya está registrado o no es válido.", true);
        return;
      }
      upsertUsuarioRecord({
        RPE: pendingAdminCandidate.rpe,
        NOMBRE: pendingAdminCandidate.nombre || "",
        ZONA: pendingAdminCandidate.area || "",
        isAdmin: true,
        activo: true,
      });
      newAdminRpeInput.value = "";
      pendingAdminCandidate = null;
      addAdminRpeBtn.disabled = true;
      if (newAdminRpeFeedback) setRpeFeedback(newAdminRpeFeedback, "", "");
      refreshAdminRpeListUI();
      setEvaluationStatus("Administrador dado de alta correctamente.");
    });
  }

  if (newNormalRpeInput) {
    newNormalRpeInput.addEventListener("input", () => {
      newNormalRpeInput.value = normalizeRpe(newNormalRpeInput.value);
      pendingNormalCandidate = null;
      updateNormalAddBtnState();
      if (newNormalRpeFeedback) setRpeFeedback(newNormalRpeFeedback, "", "");
    });
  }
  // Estado inicial: alta bloqueada hasta validar RPE correctamente.
  updateNormalAddBtnState();

  if (validateNormalRpeBtn && newNormalRpeInput) {
    validateNormalRpeBtn.addEventListener("click", async () => {
      const me = getCurrentRpe();
      const workflowStore = getWorkflowStore();
      const hasAssignedReviewInbox =
        Boolean(me) &&
        [...(workflowStore.pending || []), ...(workflowStore.approved || [])].some(
          (item) => normalizeRpe(item?.reviewerRpe || "") === me
        );
      const canManageNormalUsers =
        isCurrentUserMaster() || sessionIsReviewerAdmin() || hasAssignedReviewInbox || isCurrentUserAdmin();
      if (!canManageNormalUsers) {
        setEvaluationStatus("Solo revisores y MAESTRO pueden dar de alta usuarios normales.", true);
        return;
      }
      const rpe = normalizeRpe(newNormalRpeInput.value);
      if (!rpe) {
        setEvaluationStatus("Captura un RPE válido para darlo de alta.", true);
        return;
      }
      if (isReviewerAdminRpe(rpe)) {
        setEvaluationStatus("Ese RPE pertenece a un administrador/revisor. Usa su módulo correspondiente.", true);
        if (newNormalRpeFeedback) {
          setRpeFeedback(newNormalRpeFeedback, "is-error", "RPE de administrador/revisor.");
        }
        return;
      }
      const usuariosStore = getUsuariosStore();
      const existing = (usuariosStore.usuarios || []).find(
        (u) => normalizeRpe(u?.RPE || "") === rpe
      );
      if (existing && !parseBooleanLike(existing?.isAdmin, false)) {
        setEvaluationStatus("Ese usuario normal ya está dado de alta.", true);
        if (newNormalRpeFeedback) {
          setRpeFeedback(newNormalRpeFeedback, "is-error", "Ese RPE ya está dado de alta.");
        }
        return;
      }
      validateNormalRpeBtn.disabled = true;
      pendingNormalCandidate = null;
      updateNormalAddBtnState();
      if (newNormalRpeFeedback) setRpeFeedback(newNormalRpeFeedback, "is-loading", "Validando RPE...");
      setEvaluationStatus("Validando RPE de usuario normal en directorio/API...");
      try {
        const lookup = await fetchRpeProfile(rpe);
        if (lookup.status !== "found" || !lookup.profile) {
          setEvaluationStatus("No se pudo dar de alta: el RPE no existe en directorio/API.", true);
          if (newNormalRpeFeedback) {
            setRpeFeedback(newNormalRpeFeedback, "is-error", "El RPE no existe en directorio/API.");
          }
          return;
        }
        pendingNormalCandidate = {
          rpe,
          nombre: lookup.profile?.nombre || "",
          correo: lookup.profile?.correo || "",
          area: lookup.profile?.area || "",
        };
        if (newNormalRpeFeedback) {
          const nombre = normalizeUpperText(pendingNormalCandidate.nombre || "SIN NOMBRE");
          const correo = pendingNormalCandidate.correo || "Sin correo";
          setRpeFeedback(newNormalRpeFeedback, "is-success", `Nombre: ${nombre} | Correo: ${correo}`);
        }
        updateNormalAddBtnState();
        setEvaluationStatus(`RPE ${rpe} validado. Ahora confirma el alta como usuario normal.`);
      } catch (_error) {
        setEvaluationStatus("No se pudo validar el RPE contra directorio/API.", true);
        if (newNormalRpeFeedback) {
          setRpeFeedback(newNormalRpeFeedback, "is-error", "No se pudo validar el RPE.");
        }
      } finally {
        validateNormalRpeBtn.disabled = false;
      }
    });
  }

  if (addNormalRpeBtn && newNormalRpeInput) {
    addNormalRpeBtn.addEventListener("click", () => {
      if (addNormalRpeBtn.disabled) {
        setEvaluationStatus("Primero valida el RPE antes de dar de alta.", true);
        return;
      }
      const me = getCurrentRpe();
      const workflowStore = getWorkflowStore();
      const hasAssignedReviewInbox =
        Boolean(me) &&
        [...(workflowStore.pending || []), ...(workflowStore.approved || [])].some(
          (item) => normalizeRpe(item?.reviewerRpe || "") === me
        );
      const canManageNormalUsers =
        isCurrentUserMaster() || sessionIsReviewerAdmin() || hasAssignedReviewInbox || isCurrentUserAdmin();
      if (!canManageNormalUsers) {
        setEvaluationStatus("Solo revisores y MAESTRO pueden dar de alta usuarios normales.", true);
        return;
      }
      if (!pendingNormalCandidate?.rpe) {
        setEvaluationStatus("Primero valida el RPE en directorio/API.", true);
        return;
      }
      const currentTyped = normalizeRpe(newNormalRpeInput.value);
      if (currentTyped !== pendingNormalCandidate.rpe) {
        setEvaluationStatus("El RPE cambió. Vuelve a validar antes de dar de alta.", true);
        updateNormalAddBtnState();
        return;
      }
      if (isReviewerAdminRpe(pendingNormalCandidate.rpe)) {
        setEvaluationStatus("Ese RPE pertenece a un administrador/revisor.", true);
        return;
      }
      upsertUsuarioRecord({
        RPE: pendingNormalCandidate.rpe,
        NOMBRE: pendingNormalCandidate.nombre || "",
        ZONA: pendingNormalCandidate.area || "",
        isAdmin: false,
        activo: true,
      });
      newNormalRpeInput.value = "";
      pendingNormalCandidate = null;
      updateNormalAddBtnState();
      if (newNormalRpeFeedback) setRpeFeedback(newNormalRpeFeedback, "", "");
      refreshNormalUserListUI();
      setEvaluationStatus("Usuario normal dado de alta correctamente.");
    });
  }

  const marcaSel = document.getElementById("opciones/marca");
  const marcaOtroWrap = document.getElementById("marca_otro_wrap");
  const marcaOtro = document.getElementById("marca_otro");
  /** Muestra y habilita “marca otro” solo cuando el select de marca vale `otro`. */
  const syncMarcaOtro = () => {
    if (!marcaSel || !marcaOtro || !marcaOtroWrap) return;
    const isOtro = marcaSel.value === "otro";
    marcaOtroWrap.style.display = isOtro ? "" : "none";
    marcaOtro.disabled = !isOtro;
    if (!isOtro) {
      marcaOtro.value = "";
    }
  };
  if (marcaSel) {
    marcaSel.addEventListener("change", syncMarcaOtro);
    syncMarcaOtro();
  }

  const categoria = document.getElementById("tp_categoria");
  if (categoria) {
    categoria.addEventListener("change", () => {
      applyTpCategoryDefaults();
      applyTcCategoryDefaults(categoria.value);
    });
  }

  // Elementos se capturan en TP y se reflejan en TC + etiquetas posteriores.
  const syncManualElementSeries = (sourceId) => {
    const m = /^tp_serie_([1-3])$/.exec(String(sourceId || ""));
    if (!m) return;
    const idx = m[1];
    const sourceEl = document.getElementById(`tp_serie_${idx}`);
    const targetEl = document.getElementById(`tc_serie_${idx}`);
    if (!sourceEl || !targetEl) return;
    const nextValue = String(sourceEl.value || "");
    if (String(targetEl.value || "") !== nextValue) {
      targetEl.value = nextValue;
    }
  };

  ["tp_serie_1", "tp_serie_2", "tp_serie_3"].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    const onSeriesChange = () => {
      syncManualElementSeries(id);
      updateElementoSerieVisuales();
    };
    el.addEventListener("input", onSeriesChange);
    el.addEventListener("change", onSeriesChange);
  });
  ["tp_serie_1", "tp_serie_2", "tp_serie_3"].forEach(syncManualElementSeries);

  const zona = document.getElementById("opciones");
  if (zona) {
    zona.addEventListener("change", () => {
      const session = getAuthSession();
      if (!session?.rpe) return;
      upsertUsuarioRecord({
        RPE: session.rpe,
        NOMBRE: session.nombre || "",
        ZONA: getCurrentZonaText(),
        isAdmin: parseBooleanLike(session.isAdmin, false),
        activo: parseBooleanLike(session.activo, true),
      });
    });
  }

  for (let test = 1; test <= 3; test += 1) {
    const vpEl = document.getElementById(`tp_vp${test}`);
    if (vpEl) {
      vpEl.addEventListener("input", recalculateTpVoltSec);
    }
    const relEl = document.getElementById(`tp_rel${test}_e1`);
    if (relEl) {
      relEl.addEventListener("input", recalculateTpVoltSec);
    }
  }

  for (let test = 1; test <= 3; test += 1) {
    const cpEl = document.getElementById(`tc_cp${test}`);
    if (cpEl) {
      cpEl.addEventListener("input", recalculateTcCorrSec);
    }
    const relEl = document.getElementById(`tc_rel${test}_e1`);
    if (relEl) {
      relEl.addEventListener("input", recalculateTcCorrSec);
    }
  }

  document.querySelectorAll("input.td-input-percent").forEach((el) => {
    el.addEventListener("blur", () => normalizePercentField(el));
  });

  /** Tras validar RPE, recalcula `disabled` de campos y botones de aprobación en el siguiente tick. */
  const scheduleApprovalButtonsRefresh = () => {
    window.setTimeout(() => {
      applyReportFieldEditability();
      if (typeof window.updateApprovalActionButtons === "function") {
        window.updateApprovalActionButtons();
      }
    }, 0);
  };

  setupRpeValidation("realizo_rpe", "realizo_rpe_feedback", {
    nameId: "realizo_nom",
    apiOnly: false,
    onAfterValidate: scheduleApprovalButtonsRefresh,
  });
  setupRpeValidation("reviso_rpe", "reviso_rpe_feedback", {
    nameId: "reviso_nom",
    apiOnly: true,
    disallowSameAsId: "realizo_rpe",
    onAfterValidate: scheduleApprovalButtonsRefresh,
  });
  setupRpeValidation("recibe_rpe", "recibe_rpe_feedback", {
    nameId: "recibe_nom",
    apiOnly: true,
    disallowSameAsId: "realizo_rpe",
    onAfterValidate: scheduleApprovalButtonsRefresh,
  });
  triggerRpeValidationById("realizo_rpe");
  ["realizo_nom", "realizo_area", "reviso_nom", "reviso_area", "recibe_nom", "recibe_area"].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener("input", () => {
      el.value = normalizeUpperText(el.value);
    });
    el.value = normalizeUpperText(el.value);
  });

  applyTpCategoryDefaults();
  applyTcCategoryDefaults(categoria ? categoria.value : "");
  enforceFixedTpPrimaryVoltage();
  recalculateTpVoltSec();
  recalculateTcCorrSec();
  fillRealizoFromSession();
  refreshSavedEvaluationsUI();
  refreshAdminReviewUI();
  refreshAdminRpeListUI();
  refreshNormalUserListUI();
  applyRoleMode();
  updateBackToHubVisibility();
  if (evaluationHub) {
    setPreformMode(true);
  }

  document.querySelectorAll("input, select, textarea").forEach((el) => {
    el.addEventListener("input", updateFormFillProgress);
    el.addEventListener("change", updateFormFillProgress);
  });

  updateNavigation();
});
