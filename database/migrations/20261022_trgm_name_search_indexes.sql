-- Migración de optimización de índices trigrama (pg_trgm) para búsqueda por nombre y razón social
-- SIGODT - 2026-10-22
--
-- Propósito:
-- 1. Acelerar búsquedas de coincidencia parcial (ILIKE '%termino%') en consultas interactivas.
-- 2. Habilitar escaneos Bitmap Index Scan en lugar de Seq Scan sobre tb_ciudadano y tb_empresa.
-- 3. Reducir los tiempos de respuesta del live search de ~1,400ms a ~200-300ms.
--
-- Nota: En producción, se recomienda ejecutar con CONCURRENTLY para evitar bloqueos de tabla.
-- Si se ejecuta dentro de un bloque de transacción (BEGIN/COMMIT), retirar la palabra CONCURRENTLY.

-- 1. Habilitar la extensión pg_trgm en el esquema public
CREATE EXTENSION IF NOT EXISTS pg_trgm SCHEMA public;

-- En caso de que pg_trgm se encuentre instalado en un esquema secundario, moverlo a public
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 
        FROM pg_extension 
        WHERE extname = 'pg_trgm' 
          AND extnamespace != 'public'::regnamespace
    ) THEN
        ALTER EXTENSION pg_trgm SET SCHEMA public;
    END IF;
END $$;

-- 2. Índice trigrama GIN sobre el nombre completo concatenado del ciudadano
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tb_ciudadano_trgm_concat 
    ON public.tb_ciudadano USING gin ((ciud_nombre || ' ' || ciud_primer_apellido || ' ' || COALESCE(ciud_segundo_apellido, '')) gin_trgm_ops);

-- 3. Índices trigrama GIN sobre razón social y nombre comercial de empresas registradas
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tb_empresa_trgm_razon 
    ON public.tb_empresa USING gin (empr_razon_social gin_trgm_ops);

CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tb_empresa_trgm_comercial 
    ON public.tb_empresa USING gin (empr_nombre_comercial gin_trgm_ops);

-- 4. Índice trigrama GIN sobre la razón social histórica en trámites de procedimientos
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_td_proced_trgm_razon 
    ON sc_giros.td_procedciudadano USING gin (empresa_razon_social gin_trgm_ops);
