-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 10-10-2025 a las 03:28:26
-- Versión del servidor: 8.0.36
-- Versión de PHP: 8.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gmi_erp`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `area`
--

CREATE TABLE `area` (
  `id` int NOT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `area`
--

INSERT INTO `area` (`id`, `nombre`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 'Dirección', 1, '2025-09-28 19:59:41', NULL),
(2, 'Contabilidad', 1, '2025-09-28 19:59:41', NULL),
(3, 'Nómina', 1, '2025-09-28 19:59:41', NULL),
(4, 'Fiscal', 1, '2025-09-28 19:59:41', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `entidad` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `entidad_id` bigint DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auth_token`
--

CREATE TABLE `auth_token` (
  `id` bigint NOT NULL,
  `usuario_id` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expira_en` datetime NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa`
--

CREATE TABLE `empresa` (
  `id` int NOT NULL,
  `cliente_grupo` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `rfc` varchar(13) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contrato_servicios` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nombre_facturacion` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono_facturacion` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correo_facturacion` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_regimen` varchar(160) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `actividad_principal` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estatus_domicilio` enum('LOCALIZADO','NO_LOCALIZADO') COLLATE utf8mb4_general_ci DEFAULT 'LOCALIZADO',
  `origen_captura` enum('MANUAL','AUTOMATICO') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `constancia_doc_id` int DEFAULT NULL,
  `tipo_persona` enum('FISICA','MORAL') COLLATE utf8mb4_general_ci NOT NULL,
  `regimenes` json DEFAULT NULL,
  `responsable_id` int DEFAULT NULL,
  `area_id` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresa`
--

INSERT INTO `empresa` (`id`, `cliente_grupo`, `nombre`, `rfc`, `contrato_servicios`, `nombre_facturacion`, `telefono_facturacion`, `correo_facturacion`, `tipo_regimen`, `actividad_principal`, `estatus_domicilio`, `origen_captura`, `constancia_doc_id`, `tipo_persona`, `regimenes`, `responsable_id`, `area_id`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 'Cliente Demo', 'Empresa Demo SA de CV', 'DEM010101AA1', 'txt', 'txt', '123456789', 'txt@txt.com', 'asdas', 'asdasd', 'LOCALIZADO', NULL, NULL, 'MORAL', '[\"601\"]', 2, 4, 1, '2025-09-28 19:59:41', '2025-10-07 00:09:11'),
(2, 'Cliente 1', 'Empresa 1', 'CIAA960628PE7', 'asd', 'asdasd', 'asdasd', 'asdasd', 'asdasd', 'asdasd', 'LOCALIZADO', NULL, NULL, 'MORAL', NULL, 4, 2, 1, '2025-10-06 23:46:43', NULL),
(3, 'Cliente 1', 'Empresa 1', 'CIAA960628PE8', 'wrw', 'erwerewe', 'rwerwe', 'rwer', 'werwe', 'werwe', 'NO_LOCALIZADO', NULL, NULL, 'FISICA', NULL, 2, 4, 1, '2025-10-08 00:53:54', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_documento`
--

CREATE TABLE `empresa_documento` (
  `id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `tipo_id` int NOT NULL,
  `version` int NOT NULL DEFAULT '1',
  `archivo_nombre` varchar(255) NOT NULL,
  `archivo_path` varchar(255) NOT NULL,
  `mime` varchar(120) NOT NULL,
  `size_bytes` bigint NOT NULL,
  `metadata` json DEFAULT NULL,
  `subido_por` int DEFAULT NULL,
  `subido_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `empresa_documento`
--

INSERT INTO `empresa_documento` (`id`, `empresa_id`, `tipo_id`, `version`, `archivo_nombre`, `archivo_path`, `mime`, `size_bytes`, `metadata`, `subido_por`, `subido_en`) VALUES
(1, 1, 3, 1, '14221633.docx', '/uploads/empresas/1/acta_constitutiva/19dd2d4365a502f553143963a8e1f29a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 475049, NULL, 2, '2025-10-07 23:26:18'),
(2, 1, 9, 1, '14221633.pdf', '/uploads/empresas/1/asamblea_ordinaria/9c4bbaed811d95bae6735384b31c3648.pdf', 'application/pdf', 391227, NULL, 2, '2025-10-07 23:26:18'),
(3, 1, 6, 1, '14221633_.docx', '/uploads/empresas/1/comprobante_domicilio/bb5377e392e9947d36f3ff796eec00f3.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 688788, NULL, 2, '2025-10-07 23:26:18'),
(4, 1, 11, 1, '14221633_.pdf', '/uploads/empresas/1/socios/77a25a039c6d5d39fcb7255db0b91aa5.pdf', 'application/pdf', 295885, NULL, 2, '2025-10-07 23:26:18'),
(5, 1, 10, 1, 'cedula conalep.pdf', '/uploads/empresas/1/asamblea_extraordinaria/5d986c09b81214071e1e1a9ec02ff3ad.pdf', 'application/pdf', 176363, NULL, 2, '2025-10-07 23:35:39'),
(6, 1, 4, 1, 'comprobanteNSS.pdf', '/uploads/empresas/1/cif/c973ad1f1f6d0f603bd012702509ae89.pdf', 'application/pdf', 69995, NULL, 2, '2025-10-07 23:35:39'),
(7, 3, 9, 1, 'tarjetaNSS.pdf', '/uploads/empresas/3/asamblea_ordinaria/6cfcd02641270604b4eb891d9af56f3f.pdf', 'application/pdf', 80259, NULL, 2, '2025-10-08 00:54:19'),
(8, 3, 9, 2, 'udemy certificado desarrolo web.pdf', '/uploads/empresas/3/asamblea_ordinaria/5f44683ffa0241fc64886f1cc453b483.pdf', 'application/pdf', 238043, NULL, 2, '2025-10-08 00:54:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_documento_tipo`
--

CREATE TABLE `empresa_documento_tipo` (
  `id` int NOT NULL,
  `clave` varchar(64) NOT NULL,
  `nombre` varchar(160) NOT NULL,
  `requerido_pf` tinyint(1) NOT NULL DEFAULT '0',
  `requerido_pm` tinyint(1) NOT NULL DEFAULT '0',
  `multiple` tinyint(1) NOT NULL DEFAULT '0',
  `acepta_ext` json NOT NULL,
  `max_mb` smallint DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `empresa_documento_tipo`
--

INSERT INTO `empresa_documento_tipo` (`id`, `clave`, `nombre`, `requerido_pf`, `requerido_pm`, `multiple`, `acepta_ext`, `max_mb`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 'convenio_confidencialidad', 'Convenio de confidencialidad', 0, 0, 0, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(2, 'propuesta', 'Propuesta/Alcance', 0, 0, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(3, 'acta_constitutiva', 'Acta constitutiva', 0, 1, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(4, 'cif', 'Constancia de situación fiscal', 1, 1, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(5, 'id_rl', 'Identificación del representante legal', 0, 1, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(6, 'comprobante_domicilio', 'Comprobante de domicilio', 1, 1, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(7, 'aviso_privacidad', 'Aviso de privacidad', 0, 0, 0, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(8, 'consiliacion_social', 'Conciliación social', 0, 0, 0, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(9, 'asamblea_ordinaria', 'Acta de asamblea ordinaria', 0, 1, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(10, 'asamblea_extraordinaria', 'Acta de asamblea extraordinaria', 0, 1, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(11, 'socios', 'Relación de socios/accionistas', 0, 1, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(12, 'fiel', 'e.firma (cer/key)', 1, 1, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(13, 'sellos', 'Sellos digitales', 1, 1, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(14, 'licencia_funcionamiento', 'Licencia de funcionamiento', 0, 0, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(15, 'proteccion_civil', 'Dictamen de protección civil', 0, 0, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(16, 'uso_suelo', 'Uso de suelo', 0, 0, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(17, 'permisos', 'Otros permisos/licencias', 0, 0, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00'),
(18, 'vigencia_marca', 'Título/Vigencia de marca', 0, 0, 1, '[\"pdf\", \"xls\", \"xlsx\", \"doc\", \"docx\", \"jpg\", \"jpeg\", \"png\"]', NULL, 1, '2025-10-05 23:40:57', '2025-10-07 22:29:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa_obligacion`
--

CREATE TABLE `empresa_obligacion` (
  `id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `obligacion_id` int NOT NULL,
  `periodicidad` enum('MENSUAL','BIMESTRAL','TRIMESTRAL','SEMESTRAL','ANUAL','EVENTUAL') COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_dias` enum('NATURALES','HABILES','INHABILES') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NATURALES',
  `dia_vencimiento` tinyint DEFAULT NULL,
  `offset_dias` int NOT NULL DEFAULT '0',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `responsable_id` int DEFAULT NULL,
  `area_id` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresa_obligacion`
--

INSERT INTO `empresa_obligacion` (`id`, `empresa_id`, `obligacion_id`, `periodicidad`, `tipo_dias`, `dia_vencimiento`, `offset_dias`, `fecha_inicio`, `fecha_fin`, `responsable_id`, `area_id`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 1, 1, 'MENSUAL', 'HABILES', 17, 0, '2025-01-01', NULL, 2, 4, 1, '2025-09-28 19:59:41', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencia`
--

CREATE TABLE `evidencia` (
  `id` bigint NOT NULL,
  `rutina_id` bigint NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_relativa` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `extension` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `peso_bytes` bigint DEFAULT NULL,
  `subido_por` int DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `feriado`
--

CREATE TABLE `feriado` (
  `fecha` date NOT NULL,
  `descripcion` varchar(120) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu`
--

CREATE TABLE `menu` (
  `id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `etiqueta` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` enum('item','header','divider','external') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'item',
  `icono` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vista` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url_externa` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target` enum('_self','_blank') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '_self',
  `orden` int NOT NULL DEFAULT '0',
  `namespace` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `badge_text` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `badge_variant` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requiere_permiso` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menu`
--

INSERT INTO `menu` (`id`, `parent_id`, `etiqueta`, `slug`, `tipo`, `icono`, `vista`, `url_externa`, `target`, `orden`, `namespace`, `visible`, `badge_text`, `badge_variant`, `requiere_permiso`, `created_at`, `updated_at`, `created_by`, `updated_by`, `deleted_at`) VALUES
(2, NULL, 'Obligaciones', 'obligaciones', 'item', 'fas fa-file-contract', 'obligaciones', NULL, '_self', 2, 'sidebar', 1, NULL, NULL, 'menu.obligaciones', NULL, NULL, NULL, NULL, NULL),
(3, 2, 'Rutinas', 'rutinas', 'item', 'fas fa-calendar-check', 'rutinas', NULL, '_self', 0, 'sidebar', 1, NULL, NULL, 'menu.rutinas', NULL, NULL, NULL, NULL, NULL),
(4, NULL, 'Seguridad', 'seguridad', 'item', 'fas fa-shield-alt', NULL, NULL, '_self', 1, 'sidebar', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 4, 'Usuarios', 'usuarios', 'item', 'fas fa-users', 'admin/usuarios', NULL, '_self', 0, 'sidebar', 1, NULL, NULL, 'admin.usuarios.ver', NULL, NULL, NULL, NULL, NULL),
(6, 4, 'Roles', 'roles', 'item', 'fas fa-user-tag', 'admin/roles', NULL, '_self', 1, 'sidebar', 1, NULL, NULL, 'admin.roles.ver', NULL, NULL, NULL, NULL, NULL),
(8, 4, 'Menú', 'men', 'item', 'fas fa-sitemap', 'admin/menu', NULL, '_self', 2, 'sidebar', 1, NULL, NULL, 'admin.menu.ver', NULL, NULL, NULL, NULL, NULL),
(20, NULL, 'Administrar Empresas', 'admon_empresas', 'item', 'fas fa-building', NULL, NULL, '_self', 0, 'sidebar', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 20, 'Empresas', 'empresas', 'item', 'fas fa-building', 'empresas/', NULL, '_self', 0, 'sidebar', 1, NULL, NULL, 'empresa.ver', NULL, NULL, NULL, NULL, NULL),
(23, 20, 'Expediente', 'expediente', 'item', 'fas fa-building', 'empresas/expediente', NULL, '_self', 1, 'sidebar', 1, NULL, NULL, 'empresa.expediente', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu_backup_20251003`
--

CREATE TABLE `menu_backup_20251003` (
  `id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `etiqueta` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` enum('item','header','divider','external') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'item',
  `icono` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vista` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url_externa` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target` enum('_self','_blank') COLLATE utf8mb4_general_ci NOT NULL DEFAULT '_self',
  `orden` int NOT NULL DEFAULT '0',
  `namespace` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `badge_text` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `badge_variant` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requiere_permiso` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menu_backup_20251003`
--

INSERT INTO `menu_backup_20251003` (`id`, `parent_id`, `etiqueta`, `slug`, `tipo`, `icono`, `vista`, `url_externa`, `target`, `orden`, `namespace`, `visible`, `badge_text`, `badge_variant`, `requiere_permiso`, `created_at`, `updated_at`, `created_by`, `updated_by`, `deleted_at`) VALUES
(1, NULL, 'Empresas', NULL, 'item', 'fas fa-building', 'empresas', NULL, '_self', 10, NULL, 1, NULL, NULL, 'menu.empresas', NULL, NULL, NULL, NULL, NULL),
(2, NULL, 'Obligaciones', NULL, 'item', 'fas fa-file-contract', 'obligaciones', NULL, '_self', 20, NULL, 1, NULL, NULL, 'menu.obligaciones', NULL, NULL, NULL, NULL, NULL),
(3, NULL, 'Rutinas', NULL, 'item', 'fas fa-calendar-check', 'rutinas', NULL, '_self', 30, NULL, 1, NULL, NULL, 'menu.rutinas', NULL, NULL, NULL, NULL, NULL),
(4, NULL, 'Seguridad', NULL, 'item', 'fas fa-shield-alt', NULL, NULL, '_self', 10, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 4, 'Usuarios', NULL, 'item', 'fas fa-users', 'admin/usuarios', NULL, '_self', 1, NULL, 1, NULL, NULL, 'admin.usuarios.ver', NULL, NULL, NULL, NULL, NULL),
(6, 4, 'Roles', NULL, 'item', 'fas fa-user-tag', 'admin/roles', NULL, '_self', 20, NULL, 1, NULL, NULL, 'admin.roles.ver', NULL, NULL, NULL, NULL, NULL),
(7, 4, 'Permisos', NULL, 'item', 'fas fa-key', 'admin/permisos', NULL, '_self', 3, NULL, 1, NULL, NULL, 'admin.permisos.ver', NULL, NULL, NULL, NULL, NULL),
(8, 4, 'Menú', NULL, 'item', 'fas fa-bars', 'admin/menu', NULL, '_self', 4, NULL, 1, NULL, NULL, 'admin.menu.ver', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu_rol`
--

CREATE TABLE `menu_rol` (
  `menu_id` int NOT NULL,
  `rol_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menu_rol`
--

INSERT INTO `menu_rol` (`menu_id`, `rol_id`) VALUES
(2, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(8, 2),
(20, 2),
(21, 2),
(23, 2),
(2, 3),
(3, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `obligacion`
--

CREATE TABLE `obligacion` (
  `id` int NOT NULL,
  `clave` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `organismo` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `obligacion`
--

INSERT INTO `obligacion` (`id`, `clave`, `descripcion`, `organismo`, `activo`) VALUES
(1, 'ISR_MENSUAL', 'Declaración ISR mensual', 'SAT', 1),
(2, 'IVA_MENSUAL', 'Declaración IVA mensual', 'SAT', 1),
(3, 'NOMINA_IMSS', 'Pago IMSS mensual', 'IMSS', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset`
--

CREATE TABLE `password_reset` (
  `id` bigint NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expira_en` datetime NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `id` int NOT NULL,
  `clave` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permiso`
--

INSERT INTO `permiso` (`id`, `clave`, `descripcion`) VALUES
(1, 'menu.empresas', 'Ver menú de Empresas'),
(2, 'menu.obligaciones', 'Ver menú de Obligaciones'),
(3, 'menu.rutinas', 'Ver menú de Rutinas'),
(4, 'empresa.ver', 'Ver listado y detalle de empresas'),
(5, 'empresa.crear', 'Crear empresa'),
(6, 'empresa.editar', 'Editar empresa'),
(7, 'empresa.borrar', 'Baja lógica de empresa'),
(8, 'obligacion.ver', 'Listar y ver obligaciones'),
(9, 'obligacion.crear', 'Crear obligación'),
(10, 'obligacion.editar', 'Editar obligación'),
(11, 'obligacion.borrar', 'Borrar obligación'),
(12, 'rutina.ver', 'Listar y ver rutinas'),
(13, 'rutina.generar', 'Generar rutinas por periodo'),
(14, 'rutina.editar', 'Editar rutina'),
(15, 'rutina.revisar', 'Marcar como en revisión/completa'),
(16, 'rutina.borrar', 'Cancelar rutina'),
(17, 'evidencia.subir', 'Subir evidencias'),
(18, 'evidencia.descargar', 'Descargar evidencias'),
(19, 'admin.usuarios.ver', 'Ver listado de usuarios'),
(20, 'admin.usuarios.crear', 'Crear usuarios'),
(21, 'admin.usuarios.editar', 'Editar usuarios'),
(22, 'admin.usuarios.borrar', 'Borrar usuarios (baja lógica)'),
(23, 'admin.usuarios.roles', 'Asignar roles a usuarios'),
(24, 'admin.roles.ver', 'Ver listado de roles'),
(25, 'admin.roles.crear', 'Crear roles'),
(26, 'admin.roles.editar', 'Editar roles'),
(27, 'admin.roles.borrar', 'Borrar roles'),
(28, 'admin.roles.permisos', 'Asignar permisos a roles'),
(29, 'admin.permisos.ver', 'Ver listado de permisos'),
(30, 'admin.permisos.crear', 'Crear permisos'),
(31, 'admin.permisos.editar', 'Editar permisos'),
(32, 'admin.permisos.borrar', 'Borrar permisos'),
(33, 'admin.menu.ver', 'Ver administración de menú'),
(34, 'admin.menu.crear', 'Crear elementos de menú'),
(35, 'admin.menu.editar', 'Editar elementos de menú'),
(36, 'admin.menu.borrar', 'Eliminar elementos de menú'),
(37, 'admin.menu.roles', 'Asignar roles a un nodo del menú'),
(38, 'usuarios.ver', 'Puede listar usuarios'),
(39, 'usuarios.crear', 'Puede crear usuarios'),
(40, 'usuarios.editar', 'Puede editar usuarios'),
(41, 'usuarios.eliminar', 'Puede eliminar/baja lógica'),
(42, 'usuarios.password.cambiar', 'Puede cambiar contraseña de usuario'),
(43, 'tareas.ver', 'Puede ver tareas'),
(44, 'tareas.crear', 'Puede crear tareas'),
(45, 'tareas.editar', 'Puede editar tareas'),
(46, 'tareas.revisar', 'Puede revisar/validar tareas'),
(47, 'tareas.eliminar', 'Puede eliminar tareas'),
(48, 'expediente.ver', 'Puede ver documentos'),
(49, 'expediente.subir', 'Puede subir archivos'),
(50, 'expediente.descargar', 'Puede descargar archivos'),
(51, 'expediente.eliminar', 'Puede eliminar archivos'),
(52, 'dashboard.ver', 'Puede ver dashboards y métricas'),
(53, 'menu.administrar', 'Puede administrar el menú'),
(65, 'admin.menu.reordenar', 'Reordenar elementos de menú'),
(74, 'admin.users.ver', 'Ver usuarios'),
(75, 'admin.users.crear', 'Crear usuarios'),
(76, 'admin.users.editar', 'Editar usuarios'),
(77, 'admin.users.borrar', 'Borrar usuarios'),
(78, 'admin.users.*', 'Todos los permisos de Usuarios'),
(79, 'admin.users.roles', 'Gestionar roles de usuarios'),
(84, 'empresa.expediente', 'Gestionar expediente (subir/listar documentos)'),
(85, 'empresa.cif', 'Cargar/parsear/aplicar CIF'),
(90, 'empresa.expediente.versions', 'Listar versiones de un documento'),
(91, 'empresa.expediente.download', 'Descargar documento'),
(92, 'empresa.obligacion.ver', 'Ver obligaciones asignadas por empresa'),
(93, 'empresa.obligacion.asignar', 'Asignar nuevas obligaciones a una empresa'),
(94, 'empresa.obligacion.editar', 'Editar configuración de obligaciones asignadas'),
(95, 'empresa.obligacion.borrar', 'Desasignar obligaciones de una empresa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rate_limit`
--

CREATE TABLE `rate_limit` (
  `id` bigint NOT NULL,
  `key_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `ventana_inicio` datetime NOT NULL,
  `contador` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id` int NOT NULL,
  `nombre` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prioridad` int NOT NULL DEFAULT '100',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id`, `nombre`, `slug`, `descripcion`, `prioridad`, `activo`, `created_at`, `updated_at`) VALUES
(2, 'Gerencia', 'gerencia', NULL, 2, 1, '2025-10-02 02:58:38', '2025-10-02 02:58:38'),
(3, 'Auxiliar', 'auxiliar', NULL, 3, 1, '2025-10-02 02:58:38', '2025-10-02 02:58:38'),
(4, 'Admin General', 'admin_general', 'Acceso total', 1000, 1, '2025-10-02 03:06:06', '2025-10-02 03:06:06'),
(10, 'Admin', 'admin', NULL, 100, 1, '2025-10-06 01:23:12', '2025-10-07 01:10:07'),
(11, 'Supervisor', 'supervisor', NULL, 100, 1, '2025-10-06 01:23:12', '2025-10-07 01:09:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permiso`
--

CREATE TABLE `rol_permiso` (
  `rol_id` int NOT NULL,
  `permiso_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol_permiso`
--

INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 14),
(2, 15),
(2, 16),
(2, 17),
(2, 18),
(2, 19),
(2, 23),
(2, 24),
(2, 25),
(2, 26),
(2, 27),
(2, 28),
(2, 29),
(2, 30),
(2, 31),
(2, 32),
(2, 33),
(2, 34),
(2, 35),
(2, 36),
(2, 37),
(2, 38),
(2, 39),
(2, 40),
(2, 41),
(2, 42),
(2, 43),
(2, 44),
(2, 45),
(2, 46),
(2, 47),
(2, 48),
(2, 49),
(2, 50),
(2, 51),
(2, 52),
(2, 53),
(2, 65),
(2, 74),
(2, 75),
(2, 76),
(2, 77),
(2, 79),
(2, 84),
(2, 85),
(2, 90),
(2, 91),
(2, 92),
(2, 93),
(2, 94),
(2, 95),
(3, 4),
(3, 5),
(3, 6),
(3, 8),
(3, 9),
(3, 10),
(3, 11),
(3, 12),
(3, 13),
(3, 14),
(3, 15),
(3, 17),
(3, 18),
(3, 43),
(3, 44),
(3, 45),
(3, 46),
(3, 48),
(3, 49),
(3, 50),
(3, 52),
(3, 84),
(3, 90),
(3, 91),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 6),
(4, 7),
(4, 8),
(4, 9),
(4, 10),
(4, 11),
(4, 12),
(4, 13),
(4, 14),
(4, 15),
(4, 16),
(4, 17),
(4, 18),
(4, 19),
(4, 20),
(4, 21),
(4, 22),
(4, 23),
(4, 24),
(4, 25),
(4, 26),
(4, 27),
(4, 28),
(4, 29),
(4, 30),
(4, 31),
(4, 32),
(4, 33),
(4, 34),
(4, 35),
(4, 36),
(4, 37),
(4, 38),
(4, 39),
(4, 40),
(4, 41),
(4, 42),
(4, 43),
(4, 44),
(4, 45),
(4, 46),
(4, 47),
(4, 48),
(4, 49),
(4, 50),
(4, 51),
(4, 52),
(4, 53),
(4, 65),
(10, 4),
(10, 5),
(10, 6),
(10, 7),
(10, 38),
(10, 39),
(10, 40),
(10, 41),
(10, 42),
(10, 43),
(10, 44),
(10, 45),
(10, 46),
(10, 47),
(10, 84),
(10, 85),
(10, 90),
(10, 91),
(11, 4),
(11, 6),
(11, 84),
(11, 85),
(11, 90),
(11, 91);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rutina`
--

CREATE TABLE `rutina` (
  `id` bigint NOT NULL,
  `empresa_obligacion_id` int NOT NULL,
  `periodo_inicio` date NOT NULL,
  `periodo_fin` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('PENDIENTE','EN_REVISION','COMPLETA','CANCELADA','BLOQUEADA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDIENTE',
  `progreso` decimal(5,2) NOT NULL DEFAULT '0.00',
  `responsable_id` int DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_general_ci,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `pass_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `area_id` int DEFAULT NULL,
  `jefe_id` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `nombre`, `email`, `pass_hash`, `area_id`, `jefe_id`, `activo`, `creado_en`, `actualizado_en`) VALUES
(1, 'Directora General', 'dir@gmi.local', '$2y$10$0dPMU8GZ9tsd/rL1tKz8zuEN9eTgL9yRrRnv5S2nDhdYXK0eXW0ni', 1, NULL, 1, '2025-09-28 19:59:41', '2025-09-28 21:28:15'),
(2, 'Gerente Fiscal', 'gerencia@gmi.local', '$2y$10$d3q2Thfym/KgvbyppJLsfOyqZHNRbo7IrnA8wNVXx5Rbu9sEH3/z2', 4, NULL, 1, '2025-09-28 19:59:41', '2025-09-28 22:53:11'),
(3, 'Auxiliar Contable', 'auxiliar@gmi.local', '$2y$10$0dPMU8GZ9tsd/rL1tKz8zuEN9eTgL9yRrRnv5S2nDhdYXK0eXW0ni', 2, NULL, 1, '2025-09-28 19:59:41', '2025-09-28 21:28:15'),
(4, 'Aldo', 'acristobal@dcsoluciones.net', '$2y$10$UyStw6oWuB8e8rMo76NhzeVm7uc47fevv0GII5K7Xz.JX3FVZQxCG', 2, NULL, 1, '2025-10-03 00:17:44', '2025-10-03 00:18:02'),
(5, 'Usuario Prueba', 'prueba@prueba.com', '$2y$10$WxjlAXtpSMhHf/Kj9gzH2Oqev9kzP6HPXYjAvcIlWCjlTBlQEMztm', 2, NULL, 1, '2025-10-05 18:29:30', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_rol`
--

CREATE TABLE `usuario_rol` (
  `usuario_id` int NOT NULL,
  `rol_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_rol`
--

INSERT INTO `usuario_rol` (`usuario_id`, `rol_id`) VALUES
(2, 2),
(3, 3),
(4, 4);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_permiso`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_permiso` (
`clave` varchar(120)
,`descripcion` varchar(200)
,`id` int
,`modulo` varchar(120)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_rol_permisos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_rol_permisos` (
`modulo` varchar(120)
,`permiso_clave` varchar(120)
,`rol_id` int
,`rol_nombre` varchar(60)
,`rol_slug` varchar(80)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_permiso`
--
DROP TABLE IF EXISTS `v_permiso`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_permiso`  AS SELECT `permiso`.`id` AS `id`, `permiso`.`clave` AS `clave`, substring_index(`permiso`.`clave`,'.',1) AS `modulo`, `permiso`.`descripcion` AS `descripcion` FROM `permiso` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_rol_permisos`
--
DROP TABLE IF EXISTS `v_rol_permisos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_rol_permisos`  AS SELECT `r`.`id` AS `rol_id`, `r`.`nombre` AS `rol_nombre`, `r`.`slug` AS `rol_slug`, `p`.`clave` AS `permiso_clave`, substring_index(`p`.`clave`,'.',1) AS `modulo` FROM ((`rol` `r` join `rol_permiso` `rp` on((`rp`.`rol_id` = `r`.`id`))) join `permiso` `p` on((`p`.`id` = `rp`.`permiso_id`))) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `area`
--
ALTER TABLE `area`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_area_nombre` (`nombre`);

--
-- Indices de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_entidad` (`entidad`,`entidad_id`);

--
-- Indices de la tabla `auth_token`
--
ALTER TABLE `auth_token`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token`),
  ADD KEY `idx_auth_usuario` (`usuario_id`);

--
-- Indices de la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_empresa_rfc` (`rfc`),
  ADD KEY `idx_empresa_area` (`area_id`),
  ADD KEY `idx_empresa_responsable` (`responsable_id`),
  ADD KEY `idx_empresa_activo` (`activo`),
  ADD KEY `fk_empresa_constancia` (`constancia_doc_id`);

--
-- Indices de la tabla `empresa_documento`
--
ALTER TABLE `empresa_documento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_empdoc_tipo` (`tipo_id`),
  ADD KEY `idx_empdoc_empresa_tipo_ver` (`empresa_id`,`tipo_id`,`version`),
  ADD KEY `idx_empdoc_empresa_fecha` (`empresa_id`,`subido_en`),
  ADD KEY `idx_emp_tipo_ver` (`empresa_id`,`tipo_id`,`version`);

--
-- Indices de la tabla `empresa_documento_tipo`
--
ALTER TABLE `empresa_documento_tipo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `empresa_obligacion`
--
ALTER TABLE `empresa_obligacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_empresa_obligacion` (`empresa_id`,`obligacion_id`),
  ADD KEY `fk_eo_obligacion` (`obligacion_id`),
  ADD KEY `fk_eo_responsable` (`responsable_id`),
  ADD KEY `fk_eo_area` (`area_id`);

--
-- Indices de la tabla `evidencia`
--
ALTER TABLE `evidencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_evidencia_usuario` (`subido_por`),
  ADD KEY `idx_evidencia_rutina` (`rutina_id`);

--
-- Indices de la tabla `feriado`
--
ALTER TABLE `feriado`
  ADD PRIMARY KEY (`fecha`);

--
-- Indices de la tabla `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_menu_vista` (`vista`),
  ADD UNIQUE KEY `uq_menu_parent_slug` (`parent_id`,`slug`),
  ADD KEY `idx_menu_parent` (`parent_id`),
  ADD KEY `idx_menu_parent_orden` (`parent_id`,`orden`),
  ADD KEY `idx_menu_permiso` (`requiere_permiso`),
  ADD KEY `idx_menu_namespace_parent_orden` (`namespace`,`parent_id`,`orden`);

--
-- Indices de la tabla `menu_backup_20251003`
--
ALTER TABLE `menu_backup_20251003`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_menu_vista` (`vista`),
  ADD UNIQUE KEY `uq_menu_parent_slug` (`parent_id`,`slug`),
  ADD KEY `idx_menu_parent` (`parent_id`),
  ADD KEY `idx_menu_parent_orden` (`parent_id`,`orden`),
  ADD KEY `idx_menu_permiso` (`requiere_permiso`),
  ADD KEY `idx_menu_namespace_parent_orden` (`namespace`,`parent_id`,`orden`);

--
-- Indices de la tabla `menu_rol`
--
ALTER TABLE `menu_rol`
  ADD PRIMARY KEY (`menu_id`,`rol_id`),
  ADD KEY `idx_menurol_rol` (`rol_id`);

--
-- Indices de la tabla `obligacion`
--
ALTER TABLE `obligacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_obligacion_clave` (`clave`);

--
-- Indices de la tabla `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reset_token` (`token`),
  ADD KEY `idx_reset_email` (`email`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permiso_clave` (`clave`),
  ADD KEY `idx_permiso_clave` (`clave`);

--
-- Indices de la tabla `rate_limit`
--
ALTER TABLE `rate_limit`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rl` (`key_hash`,`ventana_inicio`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rol_nombre` (`nombre`);

--
-- Indices de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD PRIMARY KEY (`rol_id`,`permiso_id`),
  ADD KEY `idx_rp_rol` (`rol_id`),
  ADD KEY `idx_rp_permiso` (`permiso_id`);

--
-- Indices de la tabla `rutina`
--
ALTER TABLE `rutina`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rutina_periodo` (`empresa_obligacion_id`,`periodo_inicio`,`periodo_fin`),
  ADD KEY `fk_rutina_responsable` (`responsable_id`),
  ADD KEY `idx_rutina_eo` (`empresa_obligacion_id`),
  ADD KEY `idx_rutina_venc` (`fecha_vencimiento`),
  ADD KEY `idx_rutina_estado` (`estado`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario_email` (`email`),
  ADD KEY `idx_usuario_area` (`area_id`),
  ADD KEY `idx_usuario_jefe` (`jefe_id`);

--
-- Indices de la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD PRIMARY KEY (`usuario_id`,`rol_id`),
  ADD KEY `idx_ur_usuario` (`usuario_id`),
  ADD KEY `idx_ur_rol` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `area`
--
ALTER TABLE `area`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auth_token`
--
ALTER TABLE `auth_token`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresa`
--
ALTER TABLE `empresa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `empresa_documento`
--
ALTER TABLE `empresa_documento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `empresa_documento_tipo`
--
ALTER TABLE `empresa_documento_tipo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `empresa_obligacion`
--
ALTER TABLE `empresa_obligacion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `evidencia`
--
ALTER TABLE `evidencia`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `menu_backup_20251003`
--
ALTER TABLE `menu_backup_20251003`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `obligacion`
--
ALTER TABLE `obligacion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT de la tabla `rate_limit`
--
ALTER TABLE `rate_limit`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `rutina`
--
ALTER TABLE `rutina`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auth_token`
--
ALTER TABLE `auth_token`
  ADD CONSTRAINT `fk_auth_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD CONSTRAINT `fk_empresa_area` FOREIGN KEY (`area_id`) REFERENCES `area` (`id`),
  ADD CONSTRAINT `fk_empresa_constancia` FOREIGN KEY (`constancia_doc_id`) REFERENCES `empresa_documento` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_empresa_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `empresa_documento`
--
ALTER TABLE `empresa_documento`
  ADD CONSTRAINT `fk_empdoc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_empdoc_tipo` FOREIGN KEY (`tipo_id`) REFERENCES `empresa_documento_tipo` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `empresa_obligacion`
--
ALTER TABLE `empresa_obligacion`
  ADD CONSTRAINT `fk_eo_area` FOREIGN KEY (`area_id`) REFERENCES `area` (`id`),
  ADD CONSTRAINT `fk_eo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eo_obligacion` FOREIGN KEY (`obligacion_id`) REFERENCES `obligacion` (`id`),
  ADD CONSTRAINT `fk_eo_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `evidencia`
--
ALTER TABLE `evidencia`
  ADD CONSTRAINT `fk_evidencia_rutina` FOREIGN KEY (`rutina_id`) REFERENCES `rutina` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_evidencia_usuario` FOREIGN KEY (`subido_por`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `fk_menu_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `menu_rol`
--
ALTER TABLE `menu_rol`
  ADD CONSTRAINT `fk_menurol_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_menurol_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mr_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mr_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permiso` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `rutina`
--
ALTER TABLE `rutina`
  ADD CONSTRAINT `fk_rutina_eo` FOREIGN KEY (`empresa_obligacion_id`) REFERENCES `empresa_obligacion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rutina_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_area` FOREIGN KEY (`area_id`) REFERENCES `area` (`id`),
  ADD CONSTRAINT `fk_usuario_jefe` FOREIGN KEY (`jefe_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD CONSTRAINT `fk_ur_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ur_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
