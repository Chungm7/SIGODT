-- ==============================================================================
-- Migración: Actualización de regla de pago (1 Fase vs 2 Fases) en Transporte
-- Fecha: 2026-10-21
-- Contexto:
--   De acuerdo con el desglose técnico de trámites de Transporte, se identifican
--   9 procedimientos que deben liquidarse en 1 SOLA FASE (emisión conjunta de todas
--   las tasas en una única orden de giro desde el inicio), mientras que los otros 7
--   procedimientos de autorización/acceso inicial se mantienen en 2 FASES (costo
--   administrativo primero, y tras evaluación técnica se emiten las tasas complementarias).
--
-- Reglas de proced_tipocampo:
--   2 = 2 Fases (Costo administrativo previo)
--   3 = 1 Fase (Pago conjunto total de tasas)
-- ==============================================================================

BEGIN;

-- 1. Actualizar a Tipo 3 (1 Fase) los 9 procedimientos de la Subgerencia de Transporte (área 117)
UPDATE sc_giros.tm_procedimiento
SET proced_tipocampo = 3
WHERE proced_area = 117
  AND proced_cod IN (
    'PA14182B14', -- RENOVACION DE AUTORIZACION DE PERMANENCIA PARA TRANSPORTE ESPECIAL: TAXI, TURISTICO, ESTUDIANTES O TRABAJADORES
    'PA14185551', -- RENOVACION DE PERMISO DE OPERACION PARA TRANSPORTE ESPECIAL DE VEHICULOS MENORES MOTORIZADOS
    'PA141859D7', -- REGULARIZACION DE RECONOCIMIENTO DE EMPRESAS TRANSPORTE
    'PA1418704F', -- RENOVACION DE AUTORIZACION DE PERMANENCIA PARA TRANSPORTE ESPECIAL: TURISTICO, ESTUDIANTES O TRABAJADORES
    'PA1418A5E2', -- HABILITACION VEHICULAR POR SUSTITUCION DE VEHICULO
    'PA1418DAAC', -- ACTUALIZACION DE DATOS DE TARJETA UNICA DE CIRCULACION POR CAMBIO DE PROPIETARIO O DE CARACTERISTICAS TECNICAS DE LA UNIDAD
    'PA1418E325', -- RENOVACION DE AUTORIZACION DE PERMANENCIA PARA TRANSPORTE PUBLICO URBANO E INTERURBANO (OMNIBUS Y CAMIONETA RURAL) Y TRANSPORTE ESPECIAL (AUTO COLECTIVO)
    'PA1418E348', -- HABILITACION VEHICULAR POR CAUSAL DE VACANCIA EN FLOTA VEHICULAR (EXCLUSION, RENUNCIA, ABANDONO DE SERVICIO Y AQUELLOS QUE CUENTEN CON CERTIFICACION DE RESERVA DE SERVICIO)
    'PA1418FD42'  -- AUTORIZACION DE INCREMENTO DE FLOTA
  );

-- 2. Asegurar que los 7 procedimientos restantes de Transporte queden explícitamente en Tipo 2 (2 Fases)
UPDATE sc_giros.tm_procedimiento
SET proced_tipocampo = 2
WHERE proced_area = 117
  AND proced_cod IN (
    'PA14182194', -- AUTORIZACION DE ACCESO PARA TRANSPORTE PUBLICO ESPECIAL URBANO - INTERURBANO (AUTO COLECTIVO - 10 AÑOS)
    'PA14183B8C', -- AUTORIZACION DE ACCESO PARA TRANSPORTE PUBLICO URBANO E INTERURBANO (OMNIBUS - CAMIONETA RURAL - 10 AÑOS)
    'PA141854E1', -- PERMISO DE OPERACION PARA TRANSPORTE ESPECIAL DE VEHICULOS MENORES MOTORIZADOS (06 AÑOS)
    'PA14188C8C', -- AUTORIZACION DE ACCESO PARA TRANSPORTE ESPECIAL TURISTICO, ESTUDIANTES O TRABAJADORES (10 AÑOS) - PERSONA NATURAL
    'PA1418A141', -- CERTIFICADO DE HABILITACION TECNICA DE TERMINAL TERRESTRE Y ESTACION DE RUTA
    'PA1418B72E', -- AUTORIZACION DE ACCESO PARA TRANSPORTE ESPECIAL TURISTICO, ESTUDIANTES O TRABAJADORES (10 AÑOS) PERSONA JURIDICA (EMPRESA O ASOCIACION)
    'PA1418ED21'  -- MODIFICACION DE LA AUTORIZACION POR INCREMENTO O REDUCCION DE FRECUENCIAS
  );

COMMIT;

-- Verificación posterior
-- SELECT proced_cod, proced_tipocampo, proced_nom 
-- FROM sc_giros.tm_procedimiento 
-- WHERE proced_area = 117 AND proced_tupa = 10 
-- ORDER BY proced_tipocampo, proced_cod;
