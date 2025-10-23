-- Revisión (registro maestro)
CREATE TABLE revision (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  nombre            VARCHAR(180)        NOT NULL,
  numero_orden      VARCHAR(80)         NULL,
  numero_oficio     VARCHAR(80)         NULL,
  ejercicio         VARCHAR(9)          NULL,             -- p. ej. "2024"
  fecha_notificacion DATE               NOT NULL,
  fecha_vencimiento  DATE               NOT NULL,

  tipo_revision_id  INT                 NOT NULL,         -- FK catálogo (7 opciones)
  antecedente_id    INT                 NULL,             -- FK opcional
  antecedente_texto VARCHAR(200)        NULL,             -- si antecedente = Otro

  tipo_impuesto_id  INT                 NULL,             -- FK catálogo
  dependencia_id    INT                 NULL,             -- FK catálogo

  estatus           ENUM('en_proceso','completa') DEFAULT 'en_proceso',
  riesgo            ENUM('bajo','medio','alto')   DEFAULT 'medio',
  observaciones     TEXT                NULL,

  area_id           INT                 NOT NULL,         -- (UI: Departamento)
  responsable_id    INT                 NOT NULL,         -- (UI: Asesor)
  created_by        INT                 NOT NULL,

  created_at        TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX ix_rev_venc (fecha_vencimiento),
  INDEX ix_rev_est  (estatus),
  INDEX ix_rev_area (area_id),
  INDEX ix_rev_resp (responsable_id),
  INDEX ix_rev_tipo (tipo_revision_id),

  CONSTRAINT fk_rev_tipo_rev  FOREIGN KEY (tipo_revision_id) REFERENCES revision_tipo(id),
  CONSTRAINT fk_rev_ant       FOREIGN KEY (antecedente_id)   REFERENCES revision_antecedente(id),
  CONSTRAINT fk_rev_imp       FOREIGN KEY (tipo_impuesto_id) REFERENCES revision_tipo_impuesto(id),
  CONSTRAINT fk_rev_dep       FOREIGN KEY (dependencia_id)   REFERENCES revision_dependencia(id)
  -- area_id, responsable_id, created_by → referencian tus tablas existentes (areas/usuarios)
);


-- Tipo de revisión (catálogo fijo con las 7 opciones)
CREATE TABLE revision_tipo (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(60) UNIQUE NOT NULL,
  nombre VARCHAR(120)      NOT NULL,
  activo TINYINT(1)        DEFAULT 1
);

INSERT INTO revision_tipo (clave, nombre) VALUES
('requerimiento',       'Requerimiento'),
('carta_invitacion',    'Carta-Invitación'),
('pt_dictamen',         'PT-Dictamen'),
('revision_gabinete',   'Revisión-Gabinete'),
('visita_domiciliaria', 'Visita-Domiciliaria'),
('compulsa',            'Compulsa'),
('otro',                'Otro');

-- Antecedente de la revisión (opcional; deja abierta tu semántica)
CREATE TABLE revision_antecedente (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(160) NOT NULL,
  activo TINYINT(1) DEFAULT 1
);

-- Tipo de impuesto
CREATE TABLE revision_tipo_impuesto (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave  VARCHAR(40) UNIQUE NOT NULL,
  nombre VARCHAR(160)       NOT NULL
);
INSERT INTO revision_tipo_impuesto (clave, nombre) VALUES
('iva','IVA'),('isr','ISR'),('ieps','IEPS'),('otros','Otros');

-- Dependencia emisora (SAT/otra)
CREATE TABLE revision_dependencia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(160) NOT NULL
);


-- Documentos de la revisión
CREATE TABLE revision_documento (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  revision_id      INT          NOT NULL,
  version          INT          NOT NULL DEFAULT 1,
  is_inicial       TINYINT(1)   NOT NULL DEFAULT 0,  -- ← marca la evidencia inicial
  nombre_original  VARCHAR(255) NOT NULL,
  archivo_path     VARCHAR(255) NOT NULL,            -- ruta relativa: /uploads/revisiones/{revisionId}/...
  mime             VARCHAR(120) NULL,
  size_bytes       INT          NULL,
  uploaded_by      INT          NOT NULL,
  created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

  INDEX ix_revdoc_rev (revision_id),
  CONSTRAINT fk_revdoc_rev FOREIGN KEY (revision_id) REFERENCES revision(id) ON DELETE CASCADE
);

-- En MySQL 8.0: asegura a lo sumo un archivo inicial por revisión (truco de columna generada)
ALTER TABLE revision_documento
  ADD COLUMN inicial_key INT GENERATED ALWAYS AS (CASE WHEN is_inicial = 1 THEN revision_id ELSE NULL END) VIRTUAL,
  ADD UNIQUE KEY uq_revdoc_inicial (inicial_key);


CREATE TABLE revision_bitacora (
  id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  revision_id   INT       NOT NULL,
  evento        VARCHAR(60) NOT NULL,  -- e.g.: 'creacion','subida_doc','elim_doc','cambio_estatus','reasignacion','alerta_proximo','alerta_vencida'
  detalle       JSON      NULL,        -- payload libre {archivo:'x.pdf', estatus:'completa', de:12, a:18, ...}
  actor_id      INT       NULL,        -- usuario o NULL si 'Sistema'
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX ix_rbit_rev (revision_id),
  CONSTRAINT fk_rbit_rev FOREIGN KEY (revision_id) REFERENCES revision(id) ON DELETE CASCADE
);


-- Log de notificaciones enviadas (para no spamear)
CREATE TABLE revision_notificacion (
  id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  revision_id   INT          NOT NULL,
  tipo          ENUM('proxima','vencida') NOT NULL,
  enviado_a     VARCHAR(180) NOT NULL,  -- correo del responsable (y/o supervisor si CC)
  dias_antes    INT          NULL,      -- solo para 'proxima' (p. ej. 5)
  enviado_en    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_notif_unica (revision_id, tipo, enviado_a, DATE(enviado_en)),
  INDEX ix_notif_rev (revision_id),
  CONSTRAINT fk_notif_rev FOREIGN KEY (revision_id) REFERENCES revision(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS app_parametro (
  k VARCHAR(120) PRIMARY KEY,
  v VARCHAR(255) NOT NULL
);

INSERT IGNORE INTO app_parametro (k, v) VALUES
('revisiones.alerta_dias', '5'),     -- umbral próximas a vencer
('revisiones.hora_cron',   '08:00'), -- referencia documental
('zona_horaria_sistema',   'America/Mexico_City');


CREATE OR REPLACE VIEW v_revisiones_listado AS
SELECT
  r.id,
  r.nombre,
  r.numero_orden,
  
  r.numero_oficio,
  r.ejercicio,
  r.fecha_notificacion,
  r.fecha_vencimiento,
  DATEDIFF(r.fecha_vencimiento, CURRENT_DATE()) AS dias_restantes,
  r.estatus,
  r.riesgo,
  r.area_id,
  r.responsable_id,
  rt.nombre AS tipo_revision,
  di.nombre AS dependencia,
  ti.nombre AS tipo_impuesto
FROM revision r
LEFT JOIN revision_tipo rt          ON rt.id = r.tipo_revision_id
LEFT JOIN revision_dependencia di   ON di.id = r.dependencia_id
LEFT JOIN revision_tipo_impuesto ti ON ti.id = r.tipo_impuesto_id;
