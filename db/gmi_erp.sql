-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 19-11-2025 a las 11:22:38
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
-- Estructura de tabla para la tabla `app_parametro`
--

CREATE TABLE `app_parametro` (
  `k` varchar(120) NOT NULL,
  `v` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `app_parametro`
--

INSERT INTO `app_parametro` (`k`, `v`) VALUES
('revisiones.alerta_dias', '5'),
('revisiones.hora_cron', '08:00'),
('zona_horaria_sistema', 'America/Mexico_City');

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
(1, 'Cliente Demo', 'Empresa Demo SA de CV', 'DEM010101AA1', 'txt', 'txt', '123456789', 'txt@txt.com', 'asdas', 'asdasd', 'LOCALIZADO', NULL, NULL, 'MORAL', '[\"601\"]', 2, 4, 1, '2025-09-28 19:59:41', '2025-10-30 22:41:44'),
(2, 'Cliente 1', 'Empresa 1', 'CIAA960628PE7', 'asd', 'asdasd', 'asdasd', 'asdasd', 'asdasd', 'asdasd', 'LOCALIZADO', NULL, NULL, 'MORAL', NULL, 4, 2, 1, '2025-10-06 23:46:43', NULL),
(3, 'Cliente 1', 'Empresa 1', 'CIAA960628PE8', 'tst', 'erwerewe', 'rwerwe', 'rwer', 'werwe', 'werwe', 'NO_LOCALIZADO', NULL, NULL, 'MORAL', NULL, 2, 4, 1, '2025-10-08 00:53:54', '2025-11-15 18:06:35'),
(4, 'Clinte teeest', 'test', 'CIAA960628PE2', 'Seervicios', 'dfasdf', 'fasd', 'sdfasd', 'asdfa', 'asdf', 'NO_LOCALIZADO', NULL, NULL, 'MORAL', NULL, 2, 4, 1, '2025-10-21 14:54:52', '2025-10-30 22:42:42');

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
(9, 4, 3, 1, '14221633.docx', '/uploads/empresas/4/acta_constitutiva/ef57003719a098108c931282be58589b.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 475049, NULL, 2, '2025-10-21 15:34:16'),
(10, 4, 10, 1, '14221633.pdf', '/uploads/empresas/4/asamblea_extraordinaria/1caab4cdf2f2591c4656066caf8c9753.pdf', 'application/pdf', 391227, NULL, 2, '2025-10-21 15:34:16'),
(11, 4, 9, 1, '14221633_.docx', '/uploads/empresas/4/asamblea_ordinaria/8f2941d63d3dd849dfa859d364a4bb02.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 688788, NULL, 2, '2025-10-21 15:34:16'),
(12, 4, 7, 1, 'CV.pdf', '/uploads/empresas/4/aviso_privacidad/2945755120a0c7da57a06e405824ff2a.pdf', 'application/pdf', 172009, NULL, 2, '2025-10-21 16:13:28'),
(13, 4, 4, 1, 'Doc1.pdf', '/uploads/empresas/4/cif/bf849f271e1369e6277a248b7d5e5330.pdf', 'application/pdf', 369226, NULL, 2, '2025-10-21 16:13:28'),
(14, 4, 8, 1, 'Doc2.pdf', '/uploads/empresas/4/consiliacion_social/2a47e0d5387b54e8358248972aafeb46.pdf', 'application/pdf', 188199, NULL, 2, '2025-10-21 16:13:28'),
(15, 3, 3, 1, 'ine mama.pdf', '/uploads/empresas/3/acta_constitutiva/27fa5440efe6e6486da52a9988a96a87.pdf', 'application/pdf', 179525, NULL, 2, '2025-10-21 16:14:31'),
(16, 3, 10, 1, 'tarjetaNSS.pdf', '/uploads/empresas/3/asamblea_extraordinaria/e98ee3f9181a209a6516872e20182ba9.pdf', 'application/pdf', 80259, NULL, 2, '2025-10-21 16:14:31'),
(17, 3, 9, 1, 'udemy certificado desarrolo web.pdf', '/uploads/empresas/3/asamblea_ordinaria/4781f2d2795b46d680cdb3cda5fa8d5c.pdf', 'application/pdf', 238043, NULL, 2, '2025-10-21 16:14:31');

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
  `dias_anticipacion` tinyint UNSIGNED NOT NULL DEFAULT '5',
  `enviar_correo` tinyint(1) NOT NULL DEFAULT '1',
  `notas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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

INSERT INTO `empresa_obligacion` (`id`, `empresa_id`, `obligacion_id`, `periodicidad`, `tipo_dias`, `dia_vencimiento`, `offset_dias`, `dias_anticipacion`, `enviar_correo`, `notas`, `fecha_inicio`, `fecha_fin`, `responsable_id`, `area_id`, `activo`, `creado_en`, `actualizado_en`) VALUES
(37, 1, 22, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(38, 1, 4, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(39, 1, 19, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(40, 1, 21, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(41, 1, 20, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(42, 1, 23, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(43, 1, 26, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(44, 1, 25, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(45, 1, 24, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(46, 1, 3, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(47, 1, 1, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(48, 1, 10, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(49, 1, 9, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(50, 1, 5, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(51, 1, 11, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(52, 1, 17, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(53, 1, 14, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(54, 1, 13, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(55, 1, 2, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(56, 1, 6, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(57, 1, 8, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(58, 1, 12, 'MENSUAL', 'NATURALES', 22, 0, 5, 1, NULL, '2025-10-21', '2030-11-17', 4, NULL, 1, '2025-10-21 14:46:25', '2025-11-17 20:41:51'),
(59, 1, 18, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(60, 1, 15, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(61, 1, 7, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(62, 1, 16, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:25', NULL),
(98, 1, 51, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(99, 1, 31, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(100, 1, 27, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(101, 1, 28, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(102, 1, 35, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(103, 1, 50, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(104, 1, 41, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(105, 1, 40, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(106, 1, 29, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(107, 1, 37, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(108, 1, 36, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(109, 1, 39, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(110, 1, 32, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(111, 1, 38, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(112, 1, 30, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(113, 1, 49, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(114, 1, 47, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(115, 1, 48, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(116, 1, 46, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(117, 1, 43, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(118, 1, 33, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(119, 1, 45, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(120, 1, 44, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(121, 1, 34, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(122, 1, 42, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 14:46:52', NULL),
(123, 4, 56, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:34:52', NULL),
(124, 4, 55, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:34:52', NULL),
(125, 4, 53, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:34:52', NULL),
(126, 4, 54, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:34:52', NULL),
(127, 4, 52, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:34:52', NULL),
(129, 3, 55, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(130, 3, 53, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(131, 3, 54, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(132, 3, 52, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(133, 3, 51, 'EVENTUAL', 'NATURALES', 23, 0, 5, 1, NULL, '2025-10-21', '2029-10-18', 8, NULL, 1, '2025-10-21 15:35:11', '2025-11-18 23:20:36'),
(134, 3, 31, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(135, 3, 27, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(136, 3, 28, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(137, 3, 35, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(138, 3, 50, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(139, 3, 41, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(140, 3, 40, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(141, 3, 29, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(142, 3, 37, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(143, 3, 36, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(144, 3, 39, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(145, 3, 32, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(146, 3, 38, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(147, 3, 30, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(148, 3, 49, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(149, 3, 47, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(150, 3, 48, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(151, 3, 46, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(152, 3, 43, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(153, 3, 33, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(154, 3, 45, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(155, 3, 44, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(156, 3, 34, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(157, 3, 42, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(158, 3, 62, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(159, 3, 65, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(160, 3, 63, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(161, 3, 66, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(162, 3, 64, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(163, 3, 22, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(164, 3, 4, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(165, 3, 19, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(166, 3, 21, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(167, 3, 20, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(168, 3, 23, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(169, 3, 26, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(170, 3, 25, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(171, 3, 24, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(172, 3, 3, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(173, 3, 1, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(174, 3, 10, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(175, 3, 9, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(176, 3, 5, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(177, 3, 11, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(178, 3, 17, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(179, 3, 14, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(180, 3, 13, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(181, 3, 2, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(182, 3, 6, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(183, 3, 8, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(184, 3, 12, 'MENSUAL', 'NATURALES', 22, 0, 5, 1, NULL, '2025-10-21', '2030-11-17', 2, NULL, 1, '2025-10-21 15:35:11', '2025-11-17 19:22:33'),
(185, 3, 18, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(186, 3, 15, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(187, 3, 7, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(188, 3, 16, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 15:35:11', NULL),
(194, 3, 58, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:21', NULL),
(195, 3, 59, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:21', NULL),
(196, 3, 61, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:21', NULL),
(197, 3, 57, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:21', NULL),
(198, 3, 60, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:21', NULL),
(199, 1, 62, 'MENSUAL', 'NATURALES', 10, 0, 5, 1, NULL, '2025-10-10', '2050-11-30', 2, NULL, 1, '2025-10-21 16:12:27', '2025-11-17 17:26:45'),
(200, 1, 65, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:27', NULL),
(201, 1, 63, 'EVENTUAL', 'NATURALES', 20, 0, 5, 1, NULL, '2025-10-21', NULL, 2, NULL, 1, '2025-10-21 16:12:27', '2025-11-17 17:12:47'),
(202, 1, 66, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:27', NULL),
(203, 1, 64, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:27', NULL),
(204, 4, 58, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:36', NULL),
(205, 4, 59, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:36', NULL),
(206, 4, 61, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:36', NULL),
(207, 4, 57, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:36', NULL),
(208, 4, 60, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-10-21', NULL, NULL, NULL, 1, '2025-10-21 16:12:36', NULL),
(209, 3, 56, 'EVENTUAL', 'NATURALES', NULL, 0, 5, 1, NULL, '2025-11-15', NULL, NULL, NULL, 1, '2025-11-15 17:46:24', NULL);

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
(2, NULL, 'Obligaciones', 'obligaciones', 'item', 'fas fa-file-contract', 'obligaciones', NULL, '_self', 4, 'sidebar', 1, NULL, NULL, 'menu.obligaciones', NULL, NULL, NULL, NULL, NULL),
(3, 2, 'Rutinas', 'rutinas', 'item', 'fas fa-calendar-check', 'rutinas', NULL, '_self', 0, 'sidebar', 1, NULL, NULL, 'menu.rutinas', NULL, NULL, NULL, NULL, NULL),
(4, NULL, 'Seguridad', 'seguridad', 'item', 'fas fa-shield-alt', NULL, NULL, '_self', 3, 'sidebar', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 4, 'Usuarios', 'usuarios', 'item', 'fas fa-users', 'admin/usuarios', NULL, '_self', 0, 'sidebar', 1, NULL, NULL, 'admin.usuarios.ver', NULL, NULL, NULL, NULL, NULL),
(6, 4, 'Roles', 'roles', 'item', 'fas fa-user-tag', 'admin/roles', NULL, '_self', 1, 'sidebar', 1, NULL, NULL, 'admin.roles.ver', NULL, NULL, NULL, NULL, NULL),
(8, 4, 'Menú', 'men', 'item', 'fas fa-sitemap', 'admin/menu', NULL, '_self', 2, 'sidebar', 1, NULL, NULL, 'admin.menu.ver', NULL, NULL, NULL, NULL, NULL),
(20, NULL, 'Administrar Empresas', 'admon_empresas', 'item', 'fas fa-building', NULL, NULL, '_self', 0, 'sidebar', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 20, 'Empresas', 'empresas', 'item', 'fas fa-building', 'empresas/', NULL, '_self', 0, 'sidebar', 1, NULL, NULL, 'empresa.ver', NULL, NULL, NULL, NULL, NULL),
(23, 20, 'Expediente', 'expediente', 'item', 'fas fa-building', 'empresas/expediente', NULL, '_self', 1, 'sidebar', 1, NULL, NULL, 'empresa.expediente', NULL, NULL, NULL, NULL, NULL),
(27, NULL, 'Notificaciones', 'notificaciones', 'item', 'fas fa-building', NULL, NULL, '_self', 1, 'sidebar', 1, NULL, NULL, 'revisiones.ver', NULL, NULL, NULL, NULL, NULL),
(29, 27, 'Nueva revision', 'index', 'item', 'fas fa-building', 'revisiones/', NULL, '_self', 0, 'sidebar', 1, NULL, NULL, 'revisiones.ver', NULL, NULL, NULL, NULL, NULL),
(30, 27, 'Historial', 'historial', 'item', 'fas fa-building', 'revisiones/historial', NULL, '_self', 1, 'sidebar', 1, NULL, NULL, 'revisiones.ver_historial', NULL, NULL, NULL, NULL, NULL),
(31, 20, 'Rutinas', 'rutinas', 'item', NULL, 'empresas/rutinas', NULL, '_self', 2, 'sidebar', 1, NULL, NULL, 'empresa.rutinas.ver', NULL, NULL, NULL, NULL, NULL),
(32, NULL, 'Tareas', 'tareas', 'item', NULL, '', NULL, '_self', 2, 'sidebar', 1, NULL, NULL, 'tareas.ver', NULL, NULL, NULL, NULL, NULL),
(33, 32, 'Mis tareas', 'mis_tareas', 'item', NULL, 'tareas/mis_tareas', NULL, '_self', 0, 'sidebar', 1, NULL, NULL, 'tareas.ver', NULL, NULL, NULL, NULL, NULL),
(34, 4, 'Menu rol', 'menu_rol', 'item', NULL, 'admin/menu_rol', NULL, '_self', 3, 'sidebar', 1, NULL, NULL, 'admin.menu.ver', NULL, NULL, NULL, NULL, NULL);

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
(27, 2),
(29, 2),
(30, 2),
(31, 2),
(32, 2),
(33, 2),
(34, 2),
(5, 3),
(21, 3),
(23, 3),
(27, 3),
(29, 3),
(30, 3),
(32, 3),
(33, 3);

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
(1, 'SAT-ISR-PROV', 'ISR Provisional', 'SAT', 1),
(2, 'SAT-GLOBAL-BANCOS', 'Prueba Global de Bancos', 'SAT', 1),
(3, 'SAT-ISR-ANUAL', 'ISR Anual', 'SAT', 1),
(4, 'SAT-RET-ANUAL', 'Constancia de retenciones anuales', 'SAT', 1),
(5, 'SAT-ISR-SEM', 'ISR Semestral', 'SAT', 1),
(6, 'SAT-RET-ISR', 'Retención de ISR', 'SAT', 1),
(7, 'SAT-SUELDOS', 'Sueldos y salarios', 'SAT', 1),
(8, 'SAT-ISR-ASIMIL', 'Retención ISR asimilables', 'SAT', 1),
(9, 'SAT-ISR-PROFES', 'ISR ret por Servicios Profesionales / RESICO', 'SAT', 1),
(10, 'SAT-ISR-ARRENDA', 'ISR ret por arrendamiento', 'SAT', 1),
(11, 'SAT-IVA-DEF', 'IVA Definitivo', 'SAT', 1),
(12, 'SAT-RET-IVA', 'Retenciones IVA', 'SAT', 1),
(13, 'SAT-IEPS-PROV', 'Pago provisional IEPS', 'SAT', 1),
(14, 'SAT-OPINION', 'Opinión de cumplimiento', 'SAT', 1),
(15, 'SAT-LISTA69B', 'Revisión lista 69B', 'SAT', 1),
(16, 'SAT-VISOR-NOM', 'Visor de nóminas', 'SAT', 1),
(17, 'SAT-MATERIALIDAD', 'Materialidad mensual (proveedores, contratos)', 'SAT', 1),
(18, 'SAT-BUZON', 'Revisión buzón SAT (cada 3 días)', 'SAT', 1),
(19, 'SAT-CONTROL-VOL', 'Control volumétrico / Dictamen', 'SAT', 1),
(20, 'SAT-DICTAMEN-PERIODO', 'Dictamen revisar periodo', 'SAT', 1),
(21, 'SAT-DICTAMEN-FISCAL', 'Dictamen fiscal Contabilidad electrónica', 'SAT', 1),
(22, 'SAT-BENEFICIARIO', 'Beneficiario controlador', 'SAT', 1),
(23, 'SAT-DIOT', 'DIOT', 'SAT', 1),
(24, 'SAT-IEPS-TRI', 'IEPS Trimestral', 'SAT', 1),
(25, 'SAT-IEPS-SEM', 'IEPS Semestral', 'SAT', 1),
(26, 'SAT-IEPS-ANUAL', 'IEPS Anual', 'SAT', 1),
(27, 'IMSS-CUOTAS', 'Cuotas IMSS', 'IMSS', 1),
(28, 'IMSS-RCV', 'Cuotas RCV', 'IMSS', 1),
(29, 'IMSS-INFONAVIT', 'INFONAVIT', 'IMSS', 1),
(30, 'IMSS-PRT', 'Presentación de PRT', 'IMSS', 1),
(31, 'IMSS-BUZON', 'Buzón IMSS', 'IMSS', 1),
(32, 'IMSS-MOD-SAL', 'Modificaciones de salario', 'IMSS', 1),
(33, 'IMSS-SEMESTRAL', 'Semestral', 'IMSS', 1),
(34, 'IMSS-SIROC', 'SIROC', 'IMSS', 1),
(35, 'IMSS-ICSOE-SISUB', 'ICSOE y SISUB', 'IMSS', 1),
(36, 'IMSS-INEGI-MENSUAL', 'Informativa INEGI Mensual', 'IMSS', 1),
(37, 'IMSS-INEGI-ANUAL', 'Informativa INEGI Anual', 'IMSS', 1),
(38, 'IMSS-TRANSFERENCIA', 'Precios de Transferencia', 'IMSS', 1),
(39, 'IMSS-TRANSPARENCIA', 'Informativa Transparencia', 'IMSS', 1),
(40, 'IMSS-CEDULAR-MENSUAL', 'Impuesto Cedular Mensual', 'IMSS', 1),
(41, 'IMSS-CEDULAR-ANUAL', 'Impuesto Cedular Anual', 'IMSS', 1),
(42, 'IMSS-SITI', 'SITI', 'IMSS', 1),
(43, 'IMSS-PRESTAMOS', 'Revisión de Préstamos a personas de riesgo', 'IMSS', 1),
(44, 'IMSS-SIPRES', 'SIPRES', 'IMSS', 1),
(45, 'IMSS-SIC', 'SIC', 'IMSS', 1),
(46, 'IMSS-REUNE', 'REUNE', 'IMSS', 1),
(47, 'IMSS-RECO', 'RECO', 'IMSS', 1),
(48, 'IMSS-REDECO', 'REDECO', 'IMSS', 1),
(49, 'IMSS-RECA', 'RECA', 'IMSS', 1),
(50, 'IMSS-IFIT', 'IFIT', 'IMSS', 1),
(51, 'IMSS-PADRON', 'Actualización de padrón de proveedores', 'IMSS', 1),
(52, 'FIN-ISN', 'ISN', 'FINANZAS', 1),
(53, 'FIN-FONACOT', 'Fonacot', 'FINANZAS', 1),
(54, 'FIN-ISAAN', 'ISAAN', 'FINANZAS', 1),
(55, 'FIN-BUZON', 'Buzón Finanzas', 'FINANZAS', 1),
(56, 'FIN-ANTILAVADO', 'Avisos Ley Antilavado', 'FINANZAS', 1),
(57, 'STPS-NOM035', 'NOM 035', 'SECRETARÍA DEL TRABAJO', 1),
(58, 'STPS-PTU', 'Comisión PTU', 'SECRETARÍA DEL TRABAJO', 1),
(59, 'STPS-RIT', 'Comisión RIT', 'SECRETARÍA DEL TRABAJO', 1),
(60, 'STPS-REPSE', 'REPSE', 'SECRETARÍA DEL TRABAJO', 1),
(61, 'STPS-CONTRATOS', 'Contratos laborales', 'SECRETARÍA DEL TRABAJO', 1),
(62, 'REL-69B', '69B', 'RELACIÓN DE NEGOCIOS', 1),
(63, 'REL-DOMICILIO', 'Domicilio localizado', 'RELACIÓN DE NEGOCIOS', 1),
(64, 'REL-TRABAJADORES', 'Trabajadores', 'RELACIÓN DE NEGOCIOS', 1),
(65, 'REL-ACTIVOS', 'Activos', 'RELACIÓN DE NEGOCIOS', 1),
(66, 'REL-MATERIALIDAD', 'Materialidad', 'RELACIÓN DE NEGOCIOS', 1);

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
(95, 'empresa.obligacion.borrar', 'Desasignar obligaciones de una empresa'),
(96, 'revisiones.ver', 'Permite listar y consultar revisiones visibles según el alcance del usuario'),
(97, 'revisiones.crear', 'Permite registrar una nueva revisión con evidencia inicial obligatoria'),
(98, 'revisiones.editar', 'Permite modificar los datos generales de la revisión (no documentos)'),
(99, 'revisiones.borrar', 'Permite eliminar una revisión completa'),
(100, 'revisiones.cambiar_estatus', 'Permite cambiar el estatus de una revisión entre EN PROCESO y COMPLETA'),
(101, 'revisiones.subir_archivo', 'Permite subir documentos anexos a una revisión'),
(102, 'revisiones.descargar_archivo', 'Permite descargar documentos de una revisión'),
(103, 'revisiones.eliminar_archivo', 'Permite eliminar documentos anexos de una revisión'),
(104, 'revisiones.reemplazar_inicial', 'Permite reemplazar la evidencia inicial de una revisión'),
(105, 'revisiones.ver_historial', 'Permite acceder a la vista de historial de revisiones (solo lectura)'),
(106, 'revisiones.alertas_config', 'Permite configurar alertas automáticas por vencimiento'),
(107, 'revisiones.historial.ver', 'Acceder al historial de revisiones (solo lectura)'),
(108, 'revisiones.historial.exportar', 'Exportar historial filtrado a CSV'),
(109, 'empresa.rutinas.ver', 'Ver configuración de rutinas por empresa'),
(110, 'empresa.rutinas.editar', 'Editar configuración de rutinas por empresa'),
(111, 'empresa.rutinas.menu', 'Acceso al módulo de Rutinas en el menú'),
(114, 'tareas.cambiar_estado', 'Cambiar estado de las tareas (pendiente / en revisión)'),
(115, 'tareas.evidencias.ver', 'Ver evidencias de tareas'),
(116, 'tareas.evidencias.subir', 'Subir evidencias de tareas'),
(117, 'tareas.evidencias.borrar', 'Eliminar evidencias de tareas'),
(118, 'tareas.evidencias.descargar', 'Descargar evidencias de tareas'),
(119, 'admin.menu.asignar', 'Asignar elementos del menú a los roles');

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
-- Estructura de tabla para la tabla `revision`
--

CREATE TABLE `revision` (
  `id` int NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `numero_orden` varchar(80) DEFAULT NULL,
  `numero_oficio` varchar(80) DEFAULT NULL,
  `ejercicio` varchar(16) DEFAULT NULL,
  `fecha_notificacion` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `tipo_revision_id` int NOT NULL,
  `tipo_impuesto` varchar(120) DEFAULT NULL,
  `dependencia` varchar(160) DEFAULT NULL,
  `antecedente` varchar(160) DEFAULT NULL,
  `estatus` enum('en_proceso','completa') NOT NULL DEFAULT 'en_proceso',
  `riesgo` enum('bajo','medio','alto') NOT NULL DEFAULT 'medio',
  `observaciones` text,
  `area_id` int NOT NULL,
  `responsable_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Volcado de datos para la tabla `revision`
--

INSERT INTO `revision` (`id`, `nombre`, `numero_orden`, `numero_oficio`, `ejercicio`, `fecha_notificacion`, `fecha_vencimiento`, `tipo_revision_id`, `tipo_impuesto`, `dependencia`, `antecedente`, `estatus`, `riesgo`, `observaciones`, `area_id`, `responsable_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'dfasdf', 'sdfasd', 'sdfasd', '3', '2025-10-22', '2025-10-31', 2, 'asdfasd', 'asdfas', 'asdfasd', 'completa', 'medio', NULL, 4, 4, 2, '2025-10-22 15:25:09', '2025-10-22 16:38:16'),
(2, 'dasdas', 'asdasd', 'asdasd', '4', '2025-10-19', '2025-11-08', 2, 'asdas', 'asdas', 'asdas', 'en_proceso', 'bajo', '', 2, 4, 2, '2025-10-22 16:05:55', '2025-10-22 16:05:55'),
(3, 'Actualizacion', '125', '2530', '3', '2025-10-23', '2025-11-08', 2, 'asdas', 'dasd', 'asdasd', 'completa', 'alto', NULL, 4, 4, 2, '2025-10-22 16:46:51', '2025-10-22 17:32:32'),
(4, 'fsdfasdf', 'asdfasdf', 'asdfasd', '3', '2025-10-19', '2025-10-31', 2, 'fasdf', 'asdfasd', 'sadfasd', 'completa', 'bajo', NULL, 4, 4, 2, '2025-10-22 17:35:29', '2025-10-22 18:30:04'),
(5, 'Actualizacion', '2123123132', '21231231', '4', '2025-10-24', '2025-11-08', 2, 'dfsd', 'fsdfsd', 'fsdffsdf', 'completa', 'medio', NULL, 4, 4, 2, '2025-10-24 18:55:37', '2025-10-27 18:19:10'),
(6, 'Test', '5234', '3245234', '4', '2025-10-27', '2025-11-03', 2, 'fasdf', 'asdf', 'asdfad', 'en_proceso', 'medio', NULL, 4, 4, 2, '2025-10-27 18:43:12', '2025-10-29 21:43:23'),
(7, 'Empresa 1', '32423', '23423', '2', '2025-10-30', '2025-11-06', 2, 'rwr', 'wrwe', 'rwrwe', 'en_proceso', 'bajo', '', 4, 4, 2, '2025-10-30 22:55:24', '2025-10-30 22:55:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revision_bitacora`
--

CREATE TABLE `revision_bitacora` (
  `id` bigint NOT NULL,
  `revision_id` int NOT NULL,
  `evento` varchar(60) NOT NULL,
  `detalle` json DEFAULT NULL,
  `actor_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `revision_bitacora`
--

INSERT INTO `revision_bitacora` (`id`, `revision_id`, `evento`, `detalle`, `actor_id`, `created_at`) VALUES
(1, 1, 'creacion', '{\"area_id\": 4, \"responsable_id\": 4}', 2, '2025-10-22 15:25:09'),
(2, 1, 'subida_doc', '{\"docId\": 1, \"nombre\": \"1761146709_tmp_fa47a3ed7c235b38.docx\", \"is_inicial\": 1}', 2, '2025-10-22 15:25:09'),
(3, 2, 'creacion', '{\"area_id\": 2, \"responsable_id\": 4}', 2, '2025-10-22 16:05:55'),
(4, 2, 'subida_doc', '{\"docId\": 2, \"nombre\": \"1761149155_tmp_ed9e35c2c356afc5.pdf\", \"is_inicial\": 1}', 2, '2025-10-22 16:05:55'),
(5, 1, 'cambio_estatus', '{\"a\": \"completa\", \"de\": \"en_proceso\"}', 2, '2025-10-22 16:11:42'),
(6, 1, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-22 16:36:37'),
(7, 1, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-22 16:37:48'),
(8, 1, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-22 16:38:16'),
(9, 3, 'creacion', '{\"area_id\": 4, \"responsable_id\": 4}', 2, '2025-10-22 16:46:51'),
(10, 3, 'subida_doc', '{\"docId\": 3, \"nombre\": \"1761151611_tmp_f77ffd1f65e115b2.pdf\", \"is_inicial\": 1}', 2, '2025-10-22 16:46:51'),
(11, 3, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-22 16:59:14'),
(12, 3, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-22 16:59:34'),
(13, 3, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-22 16:59:46'),
(14, 3, 'subida_doc', '{\"docId\": 4, \"nombre\": \"14221633.docx\", \"is_inicial\": 0}', 2, '2025-10-22 17:29:04'),
(15, 3, 'subida_doc', '{\"docId\": 5, \"nombre\": \"14221633.pdf\", \"is_inicial\": 0}', 2, '2025-10-22 17:29:04'),
(16, 3, 'subida_doc', '{\"docId\": 6, \"nombre\": \"14221633_.pdf\", \"is_inicial\": 0}', 2, '2025-10-22 17:29:06'),
(17, 3, 'subida_doc', '{\"docId\": 7, \"nombre\": \"14221633_.docx\", \"is_inicial\": 0}', 2, '2025-10-22 17:29:07'),
(18, 3, 'cambio_estatus', '{\"a\": \"completa\", \"de\": \"en_proceso\"}', 2, '2025-10-22 17:32:32'),
(19, 4, 'creacion', '{\"area_id\": 4, \"responsable_id\": 4}', 2, '2025-10-22 17:35:29'),
(20, 4, 'subida_doc', '{\"docId\": 8, \"nombre\": \"1761154529_tmp_c0e4bcfea97c3db2.pdf\", \"is_inicial\": 1}', 2, '2025-10-22 17:35:29'),
(21, 4, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-22 17:35:53'),
(22, 4, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-22 18:28:24'),
(23, 4, 'subida_doc', '{\"docId\": 9, \"nombre\": \"udemy certificado desarrolo web.pdf\", \"is_inicial\": 0}', 2, '2025-10-22 18:29:18'),
(24, 4, 'subida_doc', '{\"docId\": 10, \"nombre\": \"udemy certificado desarrolo web.pdf\", \"is_inicial\": 0}', 2, '2025-10-22 18:29:37'),
(25, 4, 'subida_doc', '{\"docId\": 11, \"nombre\": \"tarjetaNSS.pdf\", \"is_inicial\": 0}', 2, '2025-10-22 18:29:37'),
(26, 4, 'cambio_estatus', '{\"a\": \"completa\", \"de\": \"en_proceso\"}', 2, '2025-10-22 18:30:04'),
(27, 5, 'creacion', '{\"area_id\": 4, \"responsable_id\": 4}', 2, '2025-10-24 18:55:37'),
(28, 5, 'subida_doc', '{\"docId\": 12, \"nombre\": \"1761332137_tmp_1867abe430e0491d.pdf\", \"is_inicial\": 1}', 2, '2025-10-24 18:55:37'),
(29, 5, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-24 18:56:03'),
(30, 5, 'cambio_estatus', '{\"a\": \"completa\", \"de\": \"en_proceso\"}', 2, '2025-10-27 18:19:10'),
(31, 6, 'creacion', '{\"area_id\": 4, \"responsable_id\": 4}', 2, '2025-10-27 18:43:12'),
(32, 6, 'subida_doc', '{\"docId\": 13, \"nombre\": \"1761590592_tmp_06f579b19a34bf85.pdf\", \"is_inicial\": 1}', 2, '2025-10-27 18:43:12'),
(33, 6, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-28 19:44:59'),
(34, 6, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-28 19:47:55'),
(35, 6, 'actualizacion', '{\"campos\": [\"nombre\", \"numero_orden\", \"numero_oficio\", \"ejercicio\", \"fecha_notificacion\", \"fecha_vencimiento\", \"tipo_revision_id\", \"tipo_impuesto\", \"dependencia\", \"antecedente\", \"riesgo\", \"observaciones\", \"area_id\", \"responsable_id\"]}', 2, '2025-10-29 21:43:23'),
(36, 7, 'creacion', '{\"area_id\": 4, \"responsable_id\": 4}', 2, '2025-10-30 22:55:24'),
(37, 7, 'subida_doc', '{\"docId\": 14, \"nombre\": \"1761864924_tmp_057b7086444523fb.docx\", \"is_inicial\": 1}', 2, '2025-10-30 22:55:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revision_documento`
--

CREATE TABLE `revision_documento` (
  `id` int NOT NULL,
  `revision_id` int NOT NULL,
  `version` int NOT NULL DEFAULT '1',
  `is_inicial` tinyint(1) DEFAULT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `archivo_path` varchar(255) NOT NULL,
  `mime` varchar(120) DEFAULT NULL,
  `size_bytes` int DEFAULT NULL,
  `uploaded_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Volcado de datos para la tabla `revision_documento`
--

INSERT INTO `revision_documento` (`id`, `revision_id`, `version`, `is_inicial`, `nombre_original`, `archivo_path`, `mime`, `size_bytes`, `uploaded_by`, `created_at`) VALUES
(1, 1, 1, 1, '1761146709_tmp_fa47a3ed7c235b38.docx', '/uploads/revisiones/1/1761146709_tmp_fa47a3ed7c235b38.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 475049, 2, '2025-10-22 15:25:09'),
(2, 2, 1, 1, '1761149155_tmp_ed9e35c2c356afc5.pdf', '/uploads/revisiones/2/1761149155_tmp_ed9e35c2c356afc5.pdf', 'application/pdf', 295885, 2, '2025-10-22 16:05:55'),
(3, 3, 1, 1, '1761151611_tmp_f77ffd1f65e115b2.pdf', '/uploads/revisiones/3/1761151611_tmp_f77ffd1f65e115b2.pdf', 'application/pdf', 176363, 2, '2025-10-22 16:46:51'),
(4, 3, 2, NULL, '14221633.docx', '/uploads/revisiones/3/8bdad85a2d3197b9abcacc161c6822dd.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 475049, 2, '2025-10-22 17:29:04'),
(5, 3, 3, NULL, '14221633.pdf', '/uploads/revisiones/3/3edb80a201c75371a663c1dda1894c6f.pdf', 'application/pdf', 391227, 2, '2025-10-22 17:29:04'),
(6, 3, 4, NULL, '14221633_.pdf', '/uploads/revisiones/3/3a31aa083d50a4633bdcedaf4cd7f7a2.pdf', 'application/pdf', 295885, 2, '2025-10-22 17:29:06'),
(7, 3, 5, NULL, '14221633_.docx', '/uploads/revisiones/3/13f3e58f55650e8fffb8a8ba55808a9a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 688788, 2, '2025-10-22 17:29:07'),
(8, 4, 1, 1, '1761154529_tmp_c0e4bcfea97c3db2.pdf', '/uploads/revisiones/4/1761154529_tmp_c0e4bcfea97c3db2.pdf', 'application/pdf', 391227, 2, '2025-10-22 17:35:29'),
(9, 4, 2, NULL, 'udemy certificado desarrolo web.pdf', '/uploads/revisiones/4/d9e53aa288b7d2671a19bda7fcd4a1ab.pdf', 'application/pdf', 238043, 2, '2025-10-22 18:29:18'),
(10, 4, 3, NULL, 'udemy certificado desarrolo web.pdf', '/uploads/revisiones/4/96e2be32e9db308121b8cdf4a781347c.pdf', 'application/pdf', 238043, 2, '2025-10-22 18:29:37'),
(11, 4, 4, NULL, 'tarjetaNSS.pdf', '/uploads/revisiones/4/c43895f74a3bc23bc3726af993219f34.pdf', 'application/pdf', 80259, 2, '2025-10-22 18:29:37'),
(12, 5, 1, 1, '1761332137_tmp_1867abe430e0491d.pdf', '/uploads/revisiones/5/1761332137_tmp_1867abe430e0491d.pdf', 'application/pdf', 391227, 2, '2025-10-24 18:55:37'),
(13, 6, 1, 1, '1761590592_tmp_06f579b19a34bf85.pdf', '/uploads/revisiones/6/1761590592_tmp_06f579b19a34bf85.pdf', 'application/pdf', 160636, 2, '2025-10-27 18:43:12'),
(14, 7, 1, 1, '1761864924_tmp_057b7086444523fb.docx', '/uploads/revisiones/7/1761864924_tmp_057b7086444523fb.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 475049, 2, '2025-10-30 22:55:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revision_notificacion`
--

CREATE TABLE `revision_notificacion` (
  `id` bigint NOT NULL,
  `revision_id` int NOT NULL,
  `tipo` enum('proxima','vencida') NOT NULL,
  `enviado_a` varchar(180) NOT NULL,
  `dias_antes` int DEFAULT NULL,
  `enviado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `enviado_fecha` date GENERATED ALWAYS AS (cast(`enviado_en` as date)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `revision_notificacion`
--

INSERT INTO `revision_notificacion` (`id`, `revision_id`, `tipo`, `enviado_a`, `dias_antes`, `enviado_en`) VALUES
(5, 6, 'proxima', 'crat.james@gmail.com', 5, '2025-10-29 13:00:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revision_tipo`
--

CREATE TABLE `revision_tipo` (
  `id` int NOT NULL,
  `clave` varchar(60) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `revision_tipo`
--

INSERT INTO `revision_tipo` (`id`, `clave`, `nombre`, `activo`) VALUES
(1, 'requerimiento', 'Requerimiento', 1),
(2, 'carta_invitacion', 'Carta-Invitación', 1),
(3, 'pt_dictamen', 'PT-Dictamen', 1),
(4, 'revision_gabinete', 'Revisión-Gabinete', 1),
(5, 'visita_domiciliaria', 'Visita-Domiciliaria', 1),
(6, 'compulsa', 'Compulsa', 1),
(7, 'otro', 'Otro', 1);

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
(2, 20),
(2, 21),
(2, 22),
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
(2, 78),
(2, 79),
(2, 84),
(2, 85),
(2, 90),
(2, 91),
(2, 92),
(2, 93),
(2, 94),
(2, 95),
(2, 96),
(2, 97),
(2, 98),
(2, 99),
(2, 100),
(2, 101),
(2, 102),
(2, 103),
(2, 104),
(2, 105),
(2, 106),
(2, 107),
(2, 108),
(2, 109),
(2, 110),
(2, 111),
(2, 114),
(2, 115),
(2, 116),
(2, 117),
(2, 118),
(2, 119),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 6),
(3, 7),
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
(3, 19),
(3, 20),
(3, 21),
(3, 22),
(3, 23),
(3, 24),
(3, 25),
(3, 26),
(3, 27),
(3, 28),
(3, 29),
(3, 30),
(3, 31),
(3, 32),
(3, 33),
(3, 34),
(3, 35),
(3, 36),
(3, 37),
(3, 43),
(3, 46),
(3, 48),
(3, 49),
(3, 50),
(3, 52),
(3, 53),
(3, 65),
(3, 74),
(3, 75),
(3, 76),
(3, 77),
(3, 78),
(3, 79),
(3, 84),
(3, 85),
(3, 90),
(3, 91),
(3, 92),
(3, 93),
(3, 94),
(3, 95),
(3, 96),
(3, 97),
(3, 98),
(3, 99),
(3, 100),
(3, 101),
(3, 102),
(3, 103),
(3, 104),
(3, 105),
(3, 106),
(3, 107),
(3, 108),
(3, 109),
(3, 110),
(3, 111),
(3, 114),
(3, 115),
(3, 116),
(3, 117),
(3, 118),
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
-- Estructura de tabla para la tabla `tarea`
--

CREATE TABLE `tarea` (
  `id` bigint NOT NULL,
  `empresa_obligacion_id` int DEFAULT NULL,
  `tipo_tarea` enum('OBLIGACION','EXTRAORDINARIA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'OBLIGACION',
  `origen` enum('AUTOMATICO','MANUAL') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'AUTOMATICO',
  `titulo` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `empresa_id` int DEFAULT NULL,
  `periodo_inicio` date NOT NULL,
  `periodo_fin` date NOT NULL,
  `fecha_objetivo` date DEFAULT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('PENDIENTE','EN_REVISION','COMPLETA','CANCELADA','BLOQUEADA') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDIENTE',
  `progreso` decimal(5,2) NOT NULL DEFAULT '0.00',
  `responsable_id` int DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_general_ci,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tarea`
--

INSERT INTO `tarea` (`id`, `empresa_obligacion_id`, `tipo_tarea`, `origen`, `titulo`, `empresa_id`, `periodo_inicio`, `periodo_fin`, `fecha_objetivo`, `fecha_vencimiento`, `estado`, `progreso`, `responsable_id`, `observaciones`, `creado_en`, `actualizado_en`) VALUES
(10, 58, 'OBLIGACION', 'AUTOMATICO', '[SAT] Retenciones IVA – NOV 2025', 1, '2025-11-01', '2025-11-30', '2025-11-22', '2025-11-23', 'PENDIENTE', 0.00, 4, 'Tarea generada automáticamente por configuración de rutina.', '2025-11-17 21:12:13', NULL),
(11, 184, 'OBLIGACION', 'AUTOMATICO', '[SAT] Retenciones IVA – NOV 2025', 3, '2025-11-01', '2025-11-30', '2025-11-22', '2025-11-30', 'EN_REVISION', 0.00, 2, 'Tarea generada automáticamente por configuración de rutina.', '2025-11-17 21:12:13', '2025-11-18 23:15:09'),
(12, 133, 'OBLIGACION', 'AUTOMATICO', '[IMSS] Actualización de padrón de proveedores – NOV 2025', 3, '2025-11-01', '2025-11-30', '2025-11-23', '2025-12-01', 'PENDIENTE', 0.00, 8, 'Tarea generada automáticamente por configuración de rutina.', '2025-11-18 23:22:28', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarea_documento`
--

CREATE TABLE `tarea_documento` (
  `id` bigint NOT NULL,
  `tarea_id` bigint NOT NULL,
  `nombre_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `archivo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `extension` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `size_bytes` bigint DEFAULT NULL,
  `subido_por` int DEFAULT NULL,
  `nota` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tarea_documento`
--

INSERT INTO `tarea_documento` (`id`, `tarea_id`, `nombre_original`, `archivo_path`, `mime_type`, `extension`, `size_bytes`, `subido_por`, `nota`, `creado_en`, `actualizado_en`) VALUES
(1, 11, 'DRAFT CW46.5.pdf', 'uploads/tareas/11/20251118_231155_8436c40d.pdf', 'application/pdf', 'pdf', 112066, 2, NULL, '2025-11-18 23:11:55', NULL),
(2, 11, 'SGA-ISO14001_2025.pdf', 'uploads/tareas/11/20251118_231155_066a95b9.pdf', 'application/pdf', 'pdf', 2491673, 2, NULL, '2025-11-18 23:11:55', NULL),
(3, 11, 'DRAFT CW46 (1).pdf', 'uploads/tareas/11/20251118_231155_f5e74e28.pdf', 'application/pdf', 'pdf', 273398, 2, NULL, '2025-11-18 23:11:55', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarea_notificacion`
--

CREATE TABLE `tarea_notificacion` (
  `id` bigint NOT NULL,
  `tarea_id` bigint NOT NULL,
  `tipo` enum('CREACION','RECORDATORIO','VENCIMIENTO','OTRO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'CREACION',
  `canal` enum('EMAIL') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'EMAIL',
  `destinatario` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `asunto` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `estado` enum('PENDIENTE','ENVIADO','ERROR') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDIENTE',
  `intentos` smallint NOT NULL DEFAULT '0',
  `programada_para` datetime DEFAULT NULL,
  `ultimo_intento_en` datetime DEFAULT NULL,
  `enviado_en` datetime DEFAULT NULL,
  `error_ultimo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tarea_notificacion`
--

INSERT INTO `tarea_notificacion` (`id`, `tarea_id`, `tipo`, `canal`, `destinatario`, `asunto`, `estado`, `intentos`, `programada_para`, `ultimo_intento_en`, `enviado_en`, `error_ultimo`, `creado_en`, `actualizado_en`) VALUES
(8, 10, 'CREACION', 'EMAIL', 'crataldo@gmail.com', 'Nueva tarea: [SAT] Retenciones IVA – NOV 2025 (vence el 23/11)', 'ENVIADO', 1, '2025-11-17 15:12:13', NULL, '2025-11-17 15:12:39', NULL, '2025-11-17 21:12:13', '2025-11-17 21:12:39'),
(9, 11, 'CREACION', 'EMAIL', 'deathdrag26@gmail.com', 'Nueva tarea: [SAT] Retenciones IVA – NOV 2025 (vence el 30/11)', 'ENVIADO', 1, '2025-11-17 15:12:13', NULL, '2025-11-17 15:12:40', NULL, '2025-11-17 21:12:13', '2025-11-17 21:12:40'),
(10, 12, 'CREACION', 'EMAIL', 'auxiliar@gmi.local', 'Nueva tarea: [IMSS] Actualización de padrón de proveedores – NOV 2025 (vence el 01/12)', 'PENDIENTE', 0, '2025-11-18 17:22:28', NULL, NULL, NULL, '2025-11-18 23:22:28', NULL);

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
(2, 'Gerente Fiscal', 'gerencia@gmi.local', '$2y$10$d3q2Thfym/KgvbyppJLsfOyqZHNRbo7IrnA8wNVXx5Rbu9sEH3/z2', 4, NULL, 1, '2025-09-28 19:59:41', '2025-11-17 22:04:37'),
(3, 'Auxiliar Contable', 'auxiliar1@gmi.local', '$2y$10$0dPMU8GZ9tsd/rL1tKz8zuEN9eTgL9yRrRnv5S2nDhdYXK0eXW0ni', 2, NULL, 1, '2025-09-28 19:59:41', '2025-11-18 23:19:01'),
(4, 'Aldo', 'crataldo@gmail.com', '$2y$10$UyStw6oWuB8e8rMo76NhzeVm7uc47fevv0GII5K7Xz.JX3FVZQxCG', 4, 2, 1, '2025-10-03 00:17:44', '2025-11-17 20:47:46'),
(5, 'Usuario Prueba', 'prueba@prueba.com', '$2y$10$WxjlAXtpSMhHf/Kj9gzH2Oqev9kzP6HPXYjAvcIlWCjlTBlQEMztm', 1, NULL, 1, '2025-10-05 18:29:30', '2025-10-20 18:10:24'),
(8, 'Auxiliar', 'auxiliar@gmi.local', '$2y$10$RmQvRCxgvCMIWG4LHkufIOHkJeb6thA9wmTtWgO0rHKc0R4x7Asla', 4, 2, 1, '2025-11-18 23:19:04', NULL);

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
(4, 4),
(5, 4),
(8, 3);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_permiso`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_permiso` (
`id` int
,`clave` varchar(120)
,`modulo` varchar(120)
,`descripcion` varchar(200)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_revisiones_listado`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_revisiones_listado` (
`id` int
,`nombre` varchar(180)
,`numero_orden` varchar(80)
,`numero_oficio` varchar(80)
,`ejercicio` varchar(16)
,`fecha_notificacion` date
,`fecha_vencimiento` date
,`dias_restantes` int
,`estatus` enum('en_proceso','completa')
,`riesgo` enum('bajo','medio','alto')
,`area_id` int
,`responsable_id` int
,`tipo_revision` varchar(120)
,`tipo_impuesto` varchar(120)
,`dependencia` varchar(160)
,`antecedente` varchar(160)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_rol_permisos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_rol_permisos` (
`rol_id` int
,`rol_nombre` varchar(60)
,`rol_slug` varchar(80)
,`permiso_clave` varchar(120)
,`modulo` varchar(120)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_permiso`
--
DROP TABLE IF EXISTS `v_permiso`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_permiso`  AS SELECT `permiso`.`id` AS `id`, `permiso`.`clave` AS `clave`, substring_index(`permiso`.`clave`,'.',1) AS `modulo`, `permiso`.`descripcion` AS `descripcion` FROM `permiso` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_revisiones_listado`
--
DROP TABLE IF EXISTS `v_revisiones_listado`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_revisiones_listado`  AS SELECT `r`.`id` AS `id`, `r`.`nombre` AS `nombre`, `r`.`numero_orden` AS `numero_orden`, `r`.`numero_oficio` AS `numero_oficio`, `r`.`ejercicio` AS `ejercicio`, `r`.`fecha_notificacion` AS `fecha_notificacion`, `r`.`fecha_vencimiento` AS `fecha_vencimiento`, (to_days(`r`.`fecha_vencimiento`) - to_days(curdate())) AS `dias_restantes`, `r`.`estatus` AS `estatus`, `r`.`riesgo` AS `riesgo`, `r`.`area_id` AS `area_id`, `r`.`responsable_id` AS `responsable_id`, `rt`.`nombre` AS `tipo_revision`, `r`.`tipo_impuesto` AS `tipo_impuesto`, `r`.`dependencia` AS `dependencia`, `r`.`antecedente` AS `antecedente`, `r`.`created_at` AS `created_at`, `r`.`updated_at` AS `updated_at` FROM (`revision` `r` join `revision_tipo` `rt` on((`rt`.`id` = `r`.`tipo_revision_id`))) ;

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
-- Indices de la tabla `app_parametro`
--
ALTER TABLE `app_parametro`
  ADD PRIMARY KEY (`k`);

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
  ADD KEY `fk_empresa_constancia` (`constancia_doc_id`),
  ADD KEY `ix_empresa_area` (`area_id`);

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
-- Indices de la tabla `revision`
--
ALTER TABLE `revision`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_rev_estatus` (`estatus`),
  ADD KEY `ix_rev_riesgo` (`riesgo`),
  ADD KEY `ix_rev_venc` (`fecha_vencimiento`),
  ADD KEY `ix_rev_area` (`area_id`),
  ADD KEY `ix_rev_responsable` (`responsable_id`),
  ADD KEY `ix_rev_tipo` (`tipo_revision_id`),
  ADD KEY `ix_rev_dep` (`dependencia`),
  ADD KEY `ix_revision_responsable` (`responsable_id`);

--
-- Indices de la tabla `revision_bitacora`
--
ALTER TABLE `revision_bitacora`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_rbit_rev` (`revision_id`),
  ADD KEY `ix_rbit_evt` (`evento`);

--
-- Indices de la tabla `revision_documento`
--
ALTER TABLE `revision_documento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_revdoc_unico_inicial` (`revision_id`,`is_inicial`),
  ADD KEY `ix_revdoc_rev` (`revision_id`),
  ADD KEY `ix_revdoc_revision` (`revision_id`);

--
-- Indices de la tabla `revision_notificacion`
--
ALTER TABLE `revision_notificacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rnotif_diaria` (`revision_id`,`tipo`,`enviado_a`,`enviado_fecha`),
  ADD KEY `ix_rnotif_rev` (`revision_id`),
  ADD KEY `ix_rnotif_tipo` (`tipo`);

--
-- Indices de la tabla `revision_tipo`
--
ALTER TABLE `revision_tipo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

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
-- Indices de la tabla `tarea`
--
ALTER TABLE `tarea`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tarea_periodo` (`empresa_obligacion_id`,`periodo_inicio`,`periodo_fin`),
  ADD KEY `fk_rutina_responsable` (`responsable_id`),
  ADD KEY `idx_rutina_eo` (`empresa_obligacion_id`),
  ADD KEY `idx_rutina_venc` (`fecha_vencimiento`),
  ADD KEY `idx_rutina_estado` (`estado`),
  ADD KEY `idx_tarea_tipo` (`tipo_tarea`),
  ADD KEY `idx_tarea_empresa` (`empresa_id`);

--
-- Indices de la tabla `tarea_documento`
--
ALTER TABLE `tarea_documento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_td_tarea` (`tarea_id`),
  ADD KEY `idx_td_subido_por` (`subido_por`);

--
-- Indices de la tabla `tarea_notificacion`
--
ALTER TABLE `tarea_notificacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tn_tarea` (`tarea_id`),
  ADD KEY `idx_tn_estado` (`estado`),
  ADD KEY `idx_tn_tipo` (`tipo`),
  ADD KEY `idx_tn_programada` (`programada_para`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario_email` (`email`),
  ADD KEY `idx_usuario_area` (`area_id`),
  ADD KEY `idx_usuario_jefe` (`jefe_id`),
  ADD KEY `ix_usuario_jefe` (`jefe_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `empresa_documento`
--
ALTER TABLE `empresa_documento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `empresa_documento_tipo`
--
ALTER TABLE `empresa_documento_tipo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `empresa_obligacion`
--
ALTER TABLE `empresa_obligacion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT de la tabla `evidencia`
--
ALTER TABLE `evidencia`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `menu_backup_20251003`
--
ALTER TABLE `menu_backup_20251003`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `obligacion`
--
ALTER TABLE `obligacion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT de la tabla `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT de la tabla `rate_limit`
--
ALTER TABLE `rate_limit`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `revision`
--
ALTER TABLE `revision`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `revision_bitacora`
--
ALTER TABLE `revision_bitacora`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `revision_documento`
--
ALTER TABLE `revision_documento`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `revision_notificacion`
--
ALTER TABLE `revision_notificacion`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `revision_tipo`
--
ALTER TABLE `revision_tipo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `tarea`
--
ALTER TABLE `tarea`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `tarea_documento`
--
ALTER TABLE `tarea_documento`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tarea_notificacion`
--
ALTER TABLE `tarea_notificacion`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  ADD CONSTRAINT `fk_evidencia_rutina` FOREIGN KEY (`rutina_id`) REFERENCES `tarea` (`id`) ON DELETE CASCADE,
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
-- Filtros para la tabla `revision`
--
ALTER TABLE `revision`
  ADD CONSTRAINT `fk_revision_tipo` FOREIGN KEY (`tipo_revision_id`) REFERENCES `revision_tipo` (`id`);

--
-- Filtros para la tabla `revision_bitacora`
--
ALTER TABLE `revision_bitacora`
  ADD CONSTRAINT `fk_rbit_revision` FOREIGN KEY (`revision_id`) REFERENCES `revision` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `revision_documento`
--
ALTER TABLE `revision_documento`
  ADD CONSTRAINT `fk_revdoc_revision` FOREIGN KEY (`revision_id`) REFERENCES `revision` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `revision_notificacion`
--
ALTER TABLE `revision_notificacion`
  ADD CONSTRAINT `fk_rnotif_revision` FOREIGN KEY (`revision_id`) REFERENCES `revision` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permiso` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tarea`
--
ALTER TABLE `tarea`
  ADD CONSTRAINT `fk_rutina_eo` FOREIGN KEY (`empresa_obligacion_id`) REFERENCES `empresa_obligacion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rutina_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `fk_tarea_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tarea_documento`
--
ALTER TABLE `tarea_documento`
  ADD CONSTRAINT `fk_td_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tarea` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_td_usuario` FOREIGN KEY (`subido_por`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `tarea_notificacion`
--
ALTER TABLE `tarea_notificacion`
  ADD CONSTRAINT `fk_tn_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tarea` (`id`) ON DELETE CASCADE;

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
