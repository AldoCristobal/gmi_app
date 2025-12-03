<?php

use App\Controllers\AuthController;
use App\Controllers\MenuController;
use App\Controllers\RoleController;
use App\Controllers\UserController;
use App\Controllers\EmpresaController;
use App\Controllers\MenuRolController;
use App\Controllers\PermisoController;
use App\Controllers\RevisionController;
use App\Controllers\CatalogosController;
use App\Http\Middlewares\AuthMiddleware;
use App\Http\Middlewares\CsrfMiddleware;
use App\Http\Middlewares\RbacMiddleware;
use App\Controllers\TareaExtraController;
use App\Controllers\UploadTempController;
use App\Http\Middlewares\ScopeMiddleware;
use App\Controllers\TareaTrabajoController;
use App\Controllers\TareaDocumentoController;
use App\Controllers\TareaEvaluacionController;
use App\Controllers\EmpresaObligacionController;
use App\Controllers\RevisionDocumentoController;
use App\Controllers\EmpresaObligacionRutinaController;

$auth = new AuthController();
$emp = new EmpresaController();
$usr = new UserController();
$rol = new RoleController();
$per = new PermisoController();
$men = new MenuController();
$cat = new CatalogosController();
$empObl = new EmpresaObligacionController();
$rev   = new RevisionController();
$revDoc = new RevisionDocumentoController();
$uplTmp = new UploadTempController();
$empRut = new EmpresaObligacionRutinaController();
$tareaTrab = new TareaTrabajoController();
$tDoc = new TareaDocumentoController();
$menuRol = new MenuRolController();
$tareaExtra = new TareaExtraController();
$tev = new TareaEvaluacionController();

// Login (NO Auth, NO CSRF)
$router->post('/api/login', [$auth, 'login']);

// Logout (requiere sesión; si quieres puedes dejarlo sin CSRF)
$router->post('/api/logout', [$auth, 'logout']);

// Obtener token CSRF (libre)
$router->get('/api/csrf', [$auth, 'csrf']);


//CATALGOS
$router->get('/api/v1/catalogos/areas', [
   new AuthMiddleware(),
   [$cat, 'areas'],
]);

$router->get('/api/v1/catalogos/jefes', [
   new AuthMiddleware(),
   new ScopeMiddleware(),   // <- para que se llene $req->attr('scope')
   [$cat, 'jefes'],
]);

$router->get('/api/v1/catalogos/roles', [
   new AuthMiddleware(),
   [$cat, 'roles'],
]);

$router->get('/api/v1/catalogos/empresas', [
   new AuthMiddleware(),
   new ScopeMiddleware(),   // <- igual aquí
   [$cat, 'empresas'],
]);

$router->get('/api/v1/auth/whoami', [
   new AuthMiddleware(),
   //new ScopeMiddleware(),
   [new \App\Controllers\AuthController(), 'whoami']
]);

/** USUARIOS */
$router->get('/api/v1/admin/usuarios', [
   new AuthMiddleware(),
   new RbacMiddleware(['admin.usuarios.ver']),
   [$usr, 'index']
]);
$router->post('/api/v1/admin/usuarios', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.usuarios.crear']),
   [$usr, 'store']
]);
$router->put('/api/v1/admin/usuarios', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.usuarios.editar']),
   [$usr, 'update']
]);
$router->patch('/api/v1/admin/usuarios/password', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.usuarios.editar']),
   [$usr, 'resetPassword']
]);
$router->delete('/api/v1/admin/usuarios', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.usuarios.borrar']),
   [$usr, 'destroy']
]);

/** ROLES */

// GET /api/v1/admin/roles/permisos?id=123
$router->get('/api/v1/admin/roles/permisos', [
   new AuthMiddleware(),
   new RbacMiddleware(['admin.roles.permisos']),
   [$rol, 'permisos']
]);

// PATCH /api/v1/admin/roles/permisos   body: { role_id, permisos: string[] }
$router->patch('/api/v1/admin/roles/permisos', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.roles.permisos']),
   [$rol, 'savePermisos']
]);

$router->get('/api/v1/admin/roles', [
   new AuthMiddleware(),
   new RbacMiddleware(['admin.roles.ver']),
   [$rol, 'index']
]);

$router->post('/api/v1/admin/roles', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.roles.crear']),
   [$rol, 'store']
]);

// Después (sin path param)
$router->put('/api/v1/admin/roles', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.roles.editar']),
   [$rol, 'update']
]);

// Recomiendo también unificar DELETE a query (?id=...)
$router->delete('/api/v1/admin/roles', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.roles.borrar']),
   [$rol, 'destroy']
]);

// Permisos de un rol (por CLAVE)
$router->get('/api/v1/admin/roles/{id:\d+}/permisos', [
   new AuthMiddleware(),
   new RbacMiddleware(['admin.roles.permisos']),
   [$rol, 'permisos']           // <-- Nombre del método en RoleController
]);

$router->patch('/api/v1/admin/roles/{id:\d+}/permisos', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.roles.permisos']),
   [$rol, 'savePermisos']       // <-- Nombre del método en RoleController
]);


/** PERMISOS */
$router->get('/api/v1/admin/permisos', [
   new AuthMiddleware(),
   new RbacMiddleware(['admin.roles.permisos']),  // <- ya se lo diste a Gerencia
   [$per, 'index']
]);

$router->post('/api/v1/admin/permisos', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.permisos.crear']),
   [$per, 'store']
]);

$router->put('/api/v1/admin/permisos/{id:\d+}', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.permisos.editar']),
   [$per, 'update']
]);

$router->delete('/api/v1/admin/permisos/{id:\d+}', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.permisos.borrar']),
   [$per, 'destroy']
]);


/** MENÚ */
$router->get('/api/v1/admin/menu', [
   new AuthMiddleware(),
   new RbacMiddleware(['admin.menu.ver']),
   [$men, 'index']
]);
$router->post('/api/v1/admin/menu', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.menu.crear']),
   [$men, 'store']
]);
$router->put('/api/v1/admin/menu', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.menu.editar']),
   [$men, 'update']
]);
$router->delete('/api/v1/admin/menu', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.menu.borrar']),
   [$men, 'destroy']
]);
$router->post('/api/v1/admin/menu/roles', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.menu.roles']),
   [$men, 'setRoles']
]);
// PATCH /api/v1/admin/menu/reorder
$router->patch('/api/v1/admin/menu/reorder', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.menu.reordenar']),
   [$men, 'reorder']
]);

/** Árbol de menú del usuario vigente (para el sidebar) */
$router->get('/api/v1/menu/tree', [
   new AuthMiddleware(),
   [$men, 'myTree']
]);


/** Listar empresas */
$router->get('/api/v1/empresas', [
   new AuthMiddleware(),
   new RbacMiddleware(['empresa.ver']),
   new ScopeMiddleware(),
   [$emp, 'index']
]);

/** Ver una empresa (por id en query: ?id=123) */
$router->get('/api/v1/empresas/show', [
   new AuthMiddleware(),
   new RbacMiddleware(['empresa.ver']),
   new ScopeMiddleware(),
   [$emp, 'show']
]);

/** Crear empresa */
$router->post('/api/v1/empresas', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.crear']),
   new ScopeMiddleware(),
   [$emp, 'store']
]);

/** Actualizar empresa */
$router->put('/api/v1/empresas', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.editar']),
   new ScopeMiddleware(),
   [$emp, 'update']
]);

/** Eliminar (baja lógica) */
$router->delete('/api/v1/empresas', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.borrar']),
   new ScopeMiddleware(),
   [$emp, 'destroy']
]);


/** EXPEDIENTE */
$router->get('/api/v1/empresas/expediente', [
   new AuthMiddleware(),
   new RbacMiddleware(['empresa.expediente']),
   new ScopeMiddleware(),
   [$emp, 'expedienteList']
]);

$router->post('/api/v1/empresas/expediente', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.expediente']),
   new ScopeMiddleware(),
   [$emp, 'expedienteUpload']
]);

// (Opcional, paso 3): versiones por tipo
$router->get('/api/v1/empresas/expediente/versions', [
   new AuthMiddleware(),
   new RbacMiddleware(['empresa.expediente']),
   new ScopeMiddleware(),
   [$emp, 'expedienteVersions']
]);

// (Opcional, paso 3): download (devuelve path relativo)
$router->get('/api/v1/empresas/expediente/download', [
   new AuthMiddleware(),
   new RbacMiddleware(['empresa.expediente']),
   new ScopeMiddleware(),
   [$emp, 'expedienteDownload']
]);


/** CIF */
$router->post('/api/v1/empresas/cif/upload', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.cif']),
   new ScopeMiddleware(),
   [$emp, 'cifUpload']
]);

$router->post('/api/v1/empresas/cif/parse', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.cif']),
   new ScopeMiddleware(),
   [$emp, 'cifParse']
]);

$router->post('/api/v1/empresas/cif/apply', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.cif']),
   new ScopeMiddleware(),
   [$emp, 'cifApply']
]);

$router->get('/api/v1/catalogos/empresa_documento_tipos', [
   new \App\Http\Middlewares\AuthMiddleware(),
   [$cat, 'empresaDocumentoTipos']
]);

// Panel de expediente (solo consulta)
$router->get('/api/v1/empresas/expediente-panel', [
   new \App\Http\Middlewares\AuthMiddleware(),
   new \App\Http\Middlewares\RbacMiddleware(['empresa.expediente']),
   new \App\Http\Middlewares\ScopeMiddleware(),
   [$emp, 'expedientePanelList'],
]);


// ===== CATALOGO OBLIGACIONES =====
// Catálogo de obligaciones
$router->get('/api/v1/catalogos/obligaciones', [
   new AuthMiddleware(),
   [$cat, 'obligaciones']
]);

// Obligaciones por empresa (index)
$router->get('/api/v1/empresas/obligaciones', [
   new AuthMiddleware(),
   new RbacMiddleware(['empresa.obligacion.ver']),
   new ScopeMiddleware(),
   [$empObl, 'index']
]);

// Asignar
$router->post('/api/v1/empresas/obligaciones', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.obligacion.asignar']),
   new ScopeMiddleware(),
   [$empObl, 'store']
]);

// Editar
$router->put('/api/v1/empresas/obligaciones', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.obligacion.editar']),
   new ScopeMiddleware(),
   [$empObl, 'update']
]);

// Quitar (desasignar)
$router->delete('/api/v1/empresas/obligaciones', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.obligacion.borrar']),
   new ScopeMiddleware(),
   [$empObl, 'destroy']
]);


/** ===================== RUTINAS / CONFIGURACIÓN OBLIGACIONES POR EMPRESA ===================== **/

// Lista de obligaciones de una empresa + resumen de rutina
// GET /api/v1/empresas/obligaciones/rutinas?empresa_id=123
$router->get('/api/v1/empresas/obligaciones/rutinas', [
   new AuthMiddleware(),
   new RbacMiddleware(['empresa.obligacion.ver']), // mismo permiso que el index de obligaciones
   new ScopeMiddleware(),
   [$empRut, 'index']
]);

// Detalle de la rutina de una obligación específica de la empresa
// GET /api/v1/empresas/obligaciones/rutina?empresa_id=123&obligacion_id=45
$router->get('/api/v1/empresas/obligaciones/rutina', [
   new AuthMiddleware(),
   new RbacMiddleware(['empresa.obligacion.ver']),
   new ScopeMiddleware(),
   [$empRut, 'show']
]);

// Guardar rutina (crear/actualizar configuración)
// PUT /api/v1/empresas/obligaciones/rutina
// Body JSON: { empresa_id, obligacion_id, dia_vencimiento, dias_anticipacion, offset_dias, responsable_id, enviar_correo, ... }
$router->put('/api/v1/empresas/obligaciones/rutina', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['empresa.obligacion.editar']), // reutilizamos el mismo permiso que ya usas para editar obligaciones por empresa
   new ScopeMiddleware(),
   [$empRut, 'update']
]);


/** ===================== REVISIÓNES ===================== **/

$router->get('/api/v1/revisiones', [
   new AuthMiddleware(),
   new RbacMiddleware(['revisiones.ver']),
   new ScopeMiddleware(),
   [$rev, 'index']
]);

$router->get('/api/v1/revisiones/show', [
   new AuthMiddleware(),
   new RbacMiddleware(['revisiones.ver']),
   new ScopeMiddleware(),
   [$rev, 'show']
]);

$router->post('/api/v1/revisiones', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['revisiones.crear']),
   new ScopeMiddleware(),
   [$rev, 'store']
]);

$router->put('/api/v1/revisiones', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['revisiones.editar']),
   new ScopeMiddleware(),
   [$rev, 'update']
]);

$router->patch('/api/v1/revisiones/estatus', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['revisiones.cambiar_estatus']),
   new ScopeMiddleware(),
   [$rev, 'estatus']
]);

$router->delete('/api/v1/revisiones', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['revisiones.borrar']),
   new ScopeMiddleware(),
   [$rev, 'destroy']
]);

/** =============== DOCUMENTOS DE REVISIÓN =============== **/

$router->get('/api/v1/revisiones/documentos', [
   new AuthMiddleware(),
   new RbacMiddleware(['revisiones.ver']),
   new ScopeMiddleware(),
   [$revDoc, 'list']
]);

$router->post('/api/v1/revisiones/documentos', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['revisiones.subir_archivo']),
   new ScopeMiddleware(),
   [$revDoc, 'upload']
]);

$router->get('/api/v1/revisiones/documentos/download', [
   new AuthMiddleware(),
   new RbacMiddleware(['revisiones.descargar_archivo']),
   new ScopeMiddleware(),
   [$revDoc, 'download']
]);

$router->delete('/api/v1/revisiones/documentos', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['revisiones.editar']),
   new ScopeMiddleware(),
   [$revDoc, 'delete']
]);

$router->patch('/api/v1/revisiones/documentos/inicial', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['revisiones.editar']),
   new ScopeMiddleware(),
   [$revDoc, 'replaceInitial']
]);

/** =============== SUBIDA TEMPORAL (EVIDENCIA INICIAL) =============== **/

$router->post('/api/v1/uploads/temp', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   [$uplTmp, 'store']
]);

/** =============== CATÁLOGO: revision_tipo =============== **/

$router->get('/api/v1/catalogos/revision_tipos', [
   new \App\Http\Middlewares\AuthMiddleware(),
   [new \App\Controllers\CatalogosController(), 'revisionTipos']
]);

/** ===================== TAREAS (MIS TAREAS) ===================== **/

$router->get('/api/v1/tareas', [
   new AuthMiddleware(),
   new RbacMiddleware(['tareas.ver']),
   new ScopeMiddleware(),
   [$tareaTrab, 'index']
]);

$router->get('/api/v1/tareas/show', [
   new AuthMiddleware(),
   new RbacMiddleware(['tareas.ver']),
   new ScopeMiddleware(),
   [$tareaTrab, 'show']
]);

$router->patch('/api/v1/tareas/estado', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['tareas.cambiar_estado']),
   new ScopeMiddleware(),
   [$tareaTrab, 'estado']
]);

/** =============== EVIDENCIAS DE TAREAS =============== **/

$router->get('/api/v1/tareas/documentos', [
   new AuthMiddleware(),
   new RbacMiddleware(['tareas.evidencias.ver']),
   new ScopeMiddleware(),
   [$tDoc, 'list']
]);

$router->post('/api/v1/tareas/documentos', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['tareas.evidencias.subir']),
   new ScopeMiddleware(),
   [$tDoc, 'upload']
]);

$router->get('/api/v1/tareas/documentos/download', [
   new AuthMiddleware(),
   new RbacMiddleware(['tareas.evidencias.descargar']),
   new ScopeMiddleware(),
   [$tDoc, 'download']
]);

$router->delete('/api/v1/tareas/documentos', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['tareas.evidencias.borrar']),
   new ScopeMiddleware(),
   [$tDoc, 'delete']
]);

// GET: Árbol de menú para un rol
$router->get('/api/v1/admin/menu-rol/tree', [
   new AuthMiddleware(),
   // permiso que quieras usar para este módulo:
   new RbacMiddleware(['admin.menu.asignar']),
   // si quieres limitar por scope (opcional):
   // new ScopeMiddleware(),
   [$menuRol, 'tree'],
]);

// POST: Guardar menú del rol
$router->post('/api/v1/admin/menu-rol/save', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['admin.menu.asignar']),
   // new ScopeMiddleware(), // opcional también aquí
   [$menuRol, 'save'],
]);

/** ===================== TAREAS EXTRAORDINARIAS ===================== **/

$router->get('/api/v1/tareas/extra', [
   new AuthMiddleware(),
   new RbacMiddleware(['tareas.extra.ver']),
   new ScopeMiddleware(),                 // <--- IMPORTANTE
   [$tareaExtra, 'index'],
]);

$router->post('/api/v1/tareas/extra', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['tareas.extra.crear']),
   new ScopeMiddleware(),                 // <--- IMPORTANTE
   [$tareaExtra, 'store'],
]);

$router->put('/api/v1/tareas/extra', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['tareas.extra.editar']),
   new ScopeMiddleware(),                 // <--- IMPORTANTE
   [$tareaExtra, 'update'],
]);

$router->delete('/api/v1/tareas/extra', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new RbacMiddleware(['tareas.extra.borrar']),
   new ScopeMiddleware(),                 // <--- IMPORTANTE
   [$tareaExtra, 'destroy'],
]);

// TAREAS - EVALUACIÓN
$router->get('/api/v1/tareas/evaluacion', [
   new AuthMiddleware(),
   new ScopeMiddleware(),
   new RbacMiddleware(['tareas.evaluacion.ver']),
   [$tev, 'index'],
]);

$router->get('/api/v1/tareas/evaluacion/show', [
   new AuthMiddleware(),
   new ScopeMiddleware(),
   new RbacMiddleware(['tareas.evaluacion.ver']),
   [$tev, 'show'],
]);

$router->post('/api/v1/tareas/evaluacion', [
   new AuthMiddleware(),
   new CsrfMiddleware(),
   new ScopeMiddleware(),
   new RbacMiddleware(['tareas.evaluacion.evaluar']),
   [$tev, 'store'],
]);

$router->get('/api/v1/tareas/evaluacion/historial', [
   new AuthMiddleware(),
   new ScopeMiddleware(),
   new RbacMiddleware(['tareas.evaluacion.ver']),
   [$tev, 'history'],
]);
