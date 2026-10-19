-- Migración de optimización de índices para sc_giros y búsquedas frecuentes
-- SIGODT - 2026-10-19
-- 
-- Propósito:
-- 1. Eliminar escaneos secuenciales (Seq Scan) en tablas de alto volumen (td_giro_tasa_ciudadano, td_ordengirociud).
-- 2. Acelerar búsquedas por RUC y DNI en tb_empresa y tb_ciudadano.
-- 3. Acelerar el filtrado y ordenamiento de trámites por procedimiento y fecha.
--
-- Nota: En producción, se recomienda ejecutar con CONCURRENTLY para no bloquear operaciones concurrentes.

-- 1. Trámites por procedimiento y fecha
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_td_procedciudadano_proced_fecha 
    ON sc_giros.td_procedciudadano (proced_id, fechacrea DESC);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_td_procedciudadano_ciud 
    ON sc_giros.td_procedciudadano (ciud_id);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_td_procedciudadano_empr 
    ON sc_giros.td_procedciudadano (empr_id);

-- 2. Tasas del trámite
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_td_tasatciud_proced_est 
    ON sc_giros.td_tasatciud (tasatciud_procedciud, est);

-- 3. Vínculo entre giros y tasas (tabla de 86,000+ registros)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_td_giro_tasa_ciud_tasaciud 
    ON sc_giros.td_giro_tasa_ciudadano (tasaciud_id, girot_giro, est);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_td_giro_tasa_ciud_giro 
    ON sc_giros.td_giro_tasa_ciudadano (girot_giro);

-- 4. Órdenes de giro (tabla de 82,000+ registros)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_td_ordengirociud_est 
    ON sc_giros.td_ordengirociud (ogciud_id, est);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_td_ordengirociud_pers_fecha 
    ON sc_giros.td_ordengirociud (pers_id, fechacrea);

-- 5. Catálogos de búsqueda directa
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tb_empresa_ruc 
    ON public.tb_empresa (empr_ruc);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tb_ciudadano_numero_documento 
    ON public.tb_ciudadano (ciud_numero_documento);
