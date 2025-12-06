<?php

use App\Controllers\AuthController;
use App\Http\Middlewares\AuthMiddleware;
use App\Http\Middlewares\CsrfMiddleware;
use App\Http\Middlewares\RbacMiddleware;
use App\Http\Middlewares\ScopeMiddleware;

use App\Controllers\EmpresaController;
use App\Controllers\EmpresaObligacionController;
use App\Controllers\EmpresaObligacionRutinaController;

use App\Controllers\UserController;
use App\Controllers\RoleController;
use App\Controllers\PermisoController;
use App\Controllers\MenuController;
use App\Controllers\MenuRolController;

use App\Controllers\CatalogosController;

use App\Controllers\RevisionController;
use App\Controllers\RevisionDocumentoController;

use App\Controllers\TareaExtraController;
use App\Controllers\TareaTrabajoController;
use App\Controllers\TareaDocumentoController;
use App\Controllers\TareaEvaluacionController;
use App\Controllers\UploadTempController;

// Instancias de controladores
$auth     = new AuthController();
$emp      = new EmpresaController();
$usr      = new UserController();
$rol      = new RoleController();
$per      = new PermisoController();
$men      = new MenuController();
$cat      = new CatalogosController();
$empObl   = new EmpresaObligacionController();
$rev      = new RevisionController();
$revDoc   = new RevisionDocumentoController();
$uplTmp   = new UploadTempController();
$empRut   = new EmpresaObligacionRutinaController();
$tareaTrab = new TareaTrabajoController();
$tDoc     = new TareaDocumentoController();
$menuRol  = new MenuRolController();
$tareaExtra = new TareaExtraController();
$tev      = new TareaEvaluacionController();

/**
 * ===================== RUTAS SUELTAS (SIN /v1) =====================
 */

// Login (NO Auth, NO CSRF)
$router->post('/api/login', [$auth, 'login']);

// Logout (puede quedarse sin Auth/Csrf como lo tienes)
$router->post('/api/logout', [$auth, 'logout']);

// Obtener token CSRF (libre)
$router->get('/api/csrf', [$auth, 'csrf']);


/**
 * ===================== AGRUPAMOS TODO /api/v1 =====================
 */

$router->group(['prefix' => '/api/v1'], function ($r) use (
   $auth,
   $usr,
   $rol,
   $per,
   $men,
   $cat,
   $emp,
   $empObl,
   $rev,
   $revDoc,
   $uplTmp,
   $empRut,
   $tareaTrab,
   $tDoc,
   $menuRol,
   $tareaExtra,
   $tev
) {

   /**
    * ----------- /api/v1/auth -----------
    */
   $r->group(['prefix' => '/auth', 'middleware' => [new AuthMiddleware()]], function ($r) use ($auth) {
      $r->get('/whoami', [
         // Auth ya viene del group
         [$auth, 'whoami'],
      ]);
   });

   /**
    * ----------- /api/v1/catalogos -----------
    */
   $r->group(['prefix' => '/catalogos', 'middleware' => [new AuthMiddleware()]], function ($r) use ($cat) {
      // /api/v1/catalogos/areas
      $r->get('/areas', [
         [$cat, 'areas'],
      ]);

      // /api/v1/catalogos/jefes  (Auth + Scope)
      $r->get('/jefes', [
         new ScopeMiddleware(),
         [$cat, 'jefes'],
      ]);

      // /api/v1/catalogos/roles
      $r->get('/roles', [
         [$cat, 'roles'],
      ]);

      // /api/v1/catalogos/empresas (Auth + Scope)
      $r->get('/empresas', [
         new ScopeMiddleware(),
         [$cat, 'empresas'],
      ]);

      // /api/v1/catalogos/empresa_documento_tipos
      $r->get('/empresa_documento_tipos', [
         [$cat, 'empresaDocumentoTipos'],
      ]);

      // /api/v1/catalogos/revision_tipos
      $r->get('/revision_tipos', [
         [$cat, 'revisionTipos'],
      ]);

      // /api/v1/catalogos/obligaciones
      $r->get('/obligaciones', [
         [$cat, 'obligaciones'],
      ]);
   });

   /**
    * ----------- /api/v1/admin -----------
    * AuthMiddleware común a TODO lo administrativo.
    */
   $r->group(['prefix' => '/admin', 'middleware' => [new AuthMiddleware()]], function ($r) use (
      $usr,
      $rol,
      $per,
      $men,
      $menuRol
   ) {
      /** USUARIOS */
      $r->get('/usuarios', [
         new RbacMiddleware(['admin.usuarios.ver']),
         [$usr, 'index'],
      ]);
      $r->post('/usuarios', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.usuarios.crear']),
         [$usr, 'store'],
      ]);
      $r->put('/usuarios', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.usuarios.editar']),
         [$usr, 'update'],
      ]);
      $r->patch('/usuarios/password', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.usuarios.editar']),
         [$usr, 'resetPassword'],
      ]);
      $r->delete('/usuarios', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.usuarios.borrar']),
         [$usr, 'destroy'],
      ]);

      /** ROLES */
      // GET /api/v1/admin/roles/permisos?id=123
      $r->get('/roles/permisos', [
         new RbacMiddleware(['admin.roles.permisos']),
         [$rol, 'permisos'],
      ]);

      // PATCH /api/v1/admin/roles/permisos
      $r->patch('/roles/permisos', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.roles.permisos']),
         [$rol, 'savePermisos'],
      ]);

      $r->get('/roles', [
         new RbacMiddleware(['admin.roles.ver']),
         [$rol, 'index'],
      ]);
      $r->post('/roles', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.roles.crear']),
         [$rol, 'store'],
      ]);
      $r->put('/roles', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.roles.editar']),
         [$rol, 'update'],
      ]);
      $r->delete('/roles', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.roles.borrar']),
         [$rol, 'destroy'],
      ]);

      // Versión RESTful con {id} para permisos de rol
      $r->get('/roles/{id:\d+}/permisos', [
         new RbacMiddleware(['admin.roles.permisos']),
         [$rol, 'permisos'],
      ]);
      $r->patch('/roles/{id:\d+}/permisos', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.roles.permisos']),
         [$rol, 'savePermisos'],
      ]);

      /** PERMISOS */
      $r->get('/permisos', [
         new RbacMiddleware(['admin.roles.permisos']),
         [$per, 'index'],
      ]);
      $r->post('/permisos', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.permisos.crear']),
         [$per, 'store'],
      ]);
      $r->put('/permisos/{id:\d+}', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.permisos.editar']),
         [$per, 'update'],
      ]);
      $r->delete('/permisos/{id:\d+}', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.permisos.borrar']),
         [$per, 'destroy'],
      ]);

      /** MENÚ (editor AdminLTE) */
      $r->get('/menu', [
         new RbacMiddleware(['admin.menu.ver']),
         [$men, 'index'],
      ]);
      $r->post('/menu', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.menu.crear']),
         [$men, 'store'],
      ]);
      $r->put('/menu', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.menu.editar']),
         [$men, 'update'],
      ]);
      $r->delete('/menu', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.menu.borrar']),
         [$men, 'destroy'],
      ]);
      $r->post('/menu/roles', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.menu.roles']),
         [$men, 'setRoles'],
      ]);
      $r->patch('/menu/reorder', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.menu.reordenar']),
         [$men, 'reorder'],
      ]);

      /** MENU-ROL (árbol por rol) */
      $r->get('/menu-rol/tree', [
         new RbacMiddleware(['admin.menu.asignar']),
         [$menuRol, 'tree'],
      ]);
      $r->post('/menu-rol/save', [
         new CsrfMiddleware(),
         new RbacMiddleware(['admin.menu.asignar']),
         [$menuRol, 'save'],
      ]);
   });

   /**
    * ----------- Árbol de menú del usuario vigente (sidebar) -----------
    * /api/v1/menu/tree (NO es /admin)
    */
   $r->get('/menu/tree', [
      new AuthMiddleware(),
      [$men, 'myTree'],
   ]);

   /**
    * ----------- /api/v1/empresas -----------
    */
   $r->group(['prefix' => '/empresas', 'middleware' => [new AuthMiddleware(), new ScopeMiddleware()]], function ($r) use (
      $emp,
      $empObl,
      $empRut
   ) {
      /** CRUD Empresa */
      $r->get('', [
         new RbacMiddleware(['empresa.ver']),
         [$emp, 'index'],
      ]);
      $r->get('/show', [
         new RbacMiddleware(['empresa.ver']),
         [$emp, 'show'],
      ]);
      $r->post('', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.crear']),
         [$emp, 'store'],
      ]);
      $r->put('', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.editar']),
         [$emp, 'update'],
      ]);
      $r->delete('', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.borrar']),
         [$emp, 'destroy'],
      ]);

      /** EXPEDIENTE */
      $r->get('/expediente', [
         new RbacMiddleware(['empresa.expediente']),
         [$emp, 'expedienteList'],
      ]);
      $r->post('/expediente', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.expediente']),
         [$emp, 'expedienteUpload'],
      ]);
      $r->get('/expediente/versions', [
         new RbacMiddleware(['empresa.expediente']),
         [$emp, 'expedienteVersions'],
      ]);
      $r->get('/expediente/download', [
         new RbacMiddleware(['empresa.expediente']),
         [$emp, 'expedienteDownload'],
      ]);

      // Panel de expediente
      $r->get('/expediente-panel', [
         new RbacMiddleware(['empresa.expediente']),
         [$emp, 'expedientePanelList'],
      ]);

      /** CIF */
      $r->post('/cif/upload', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.cif']),
         [$emp, 'cifUpload'],
      ]);
      $r->post('/cif/parse', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.cif']),
         [$emp, 'cifParse'],
      ]);
      $r->post('/cif/apply', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.cif']),
         [$emp, 'cifApply'],
      ]);

      /** OBLIGACIONES POR EMPRESA */
      $r->get('/obligaciones', [
         new RbacMiddleware(['empresa.obligacion.ver']),
         [$empObl, 'index'],
      ]);
      $r->post('/obligaciones', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.obligacion.asignar']),
         [$empObl, 'store'],
      ]);
      $r->put('/obligaciones', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.obligacion.editar']),
         [$empObl, 'update'],
      ]);
      $r->delete('/obligaciones', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.obligacion.borrar']),
         [$empObl, 'destroy'],
      ]);

      /** RUTINAS / CONFIGURACIÓN POR EMPRESA */
      $r->get('/obligaciones/rutinas', [
         new RbacMiddleware(['empresa.obligacion.ver']),
         [$empRut, 'index'],
      ]);
      $r->get('/obligaciones/rutina', [
         new RbacMiddleware(['empresa.obligacion.ver']),
         [$empRut, 'show'],
      ]);
      $r->put('/obligaciones/rutina', [
         new CsrfMiddleware(),
         new RbacMiddleware(['empresa.obligacion.editar']),
         [$empRut, 'update'],
      ]);
   });

   /**
    * ----------- /api/v1/revisiones -----------
    */
   $r->group(['prefix' => '/revisiones', 'middleware' => [new AuthMiddleware(), new ScopeMiddleware()]], function ($r) use (
      $rev,
      $revDoc
   ) {
      // Revisiones
      $r->get('', [
         new RbacMiddleware(['revisiones.ver']),
         [$rev, 'index'],
      ]);
      $r->get('/show', [
         new RbacMiddleware(['revisiones.ver']),
         [$rev, 'show'],
      ]);
      $r->post('', [
         new CsrfMiddleware(),
         new RbacMiddleware(['revisiones.crear']),
         [$rev, 'store'],
      ]);
      $r->put('', [
         new CsrfMiddleware(),
         new RbacMiddleware(['revisiones.editar']),
         [$rev, 'update'],
      ]);
      $r->patch('/estatus', [
         new CsrfMiddleware(),
         new RbacMiddleware(['revisiones.cambiar_estatus']),
         [$rev, 'estatus'],
      ]);
      $r->delete('', [
         new CsrfMiddleware(),
         new RbacMiddleware(['revisiones.borrar']),
         [$rev, 'destroy'],
      ]);

      // Documentos de revisión
      $r->get('/documentos', [
         new RbacMiddleware(['revisiones.ver']),
         [$revDoc, 'list'],
      ]);
      $r->post('/documentos', [
         new CsrfMiddleware(),
         new RbacMiddleware(['revisiones.subir_archivo']),
         [$revDoc, 'upload'],
      ]);
      $r->get('/documentos/download', [
         new RbacMiddleware(['revisiones.descargar_archivo']),
         [$revDoc, 'download'],
      ]);
      $r->delete('/documentos', [
         new CsrfMiddleware(),
         new RbacMiddleware(['revisiones.editar']),
         [$revDoc, 'delete'],
      ]);
      $r->patch('/documentos/inicial', [
         new CsrfMiddleware(),
         new RbacMiddleware(['revisiones.editar']),
         [$revDoc, 'replaceInitial'],
      ]);
   });

   /**
    * ----------- /api/v1/uploads -----------
    */
   $r->group(['prefix' => '/uploads', 'middleware' => [new AuthMiddleware()]], function ($r) use ($uplTmp) {
      // Subida temporal (FilePond)
      $r->post('/temp', [
         new CsrfMiddleware(),
         [$uplTmp, 'store'],
      ]);
   });

   /**
    * ----------- /api/v1/tareas -----------
    */
   $r->group(['prefix' => '/tareas', 'middleware' => [new AuthMiddleware(), new ScopeMiddleware()]], function ($r) use (
      $tareaTrab,
      $tDoc,
      $tareaExtra,
      $tev
   ) {
      // Mis tareas
      $r->get('', [
         new RbacMiddleware(['tareas.ver']),
         [$tareaTrab, 'index'],
      ]);
      $r->get('/show', [
         new RbacMiddleware(['tareas.ver']),
         [$tareaTrab, 'show'],
      ]);
      $r->patch('/estado', [
         new CsrfMiddleware(),
         new RbacMiddleware(['tareas.cambiar_estado']),
         [$tareaTrab, 'estado'],
      ]);

      /** Evidencias de tareas */
      $r->get('/documentos', [
         new RbacMiddleware(['tareas.evidencias.ver']),
         [$tDoc, 'list'],
      ]);
      $r->post('/documentos', [
         new CsrfMiddleware(),
         new RbacMiddleware(['tareas.evidencias.subir']),
         [$tDoc, 'upload'],
      ]);
      $r->get('/documentos/download', [
         new RbacMiddleware(['tareas.evidencias.descargar']),
         [$tDoc, 'download'],
      ]);
      $r->delete('/documentos', [
         new CsrfMiddleware(),
         new RbacMiddleware(['tareas.evidencias.borrar']),
         [$tDoc, 'delete'],
      ]);

      /** Tareas extraordinarias */
      $r->get('/extra', [
         new RbacMiddleware(['tareas.extra.ver']),
         [$tareaExtra, 'index'],
      ]);
      $r->post('/extra', [
         new CsrfMiddleware(),
         new RbacMiddleware(['tareas.extra.crear']),
         [$tareaExtra, 'store'],
      ]);
      $r->put('/extra', [
         new CsrfMiddleware(),
         new RbacMiddleware(['tareas.extra.editar']),
         [$tareaExtra, 'update'],
      ]);
      $r->delete('/extra', [
         new CsrfMiddleware(),
         new RbacMiddleware(['tareas.extra.borrar']),
         [$tareaExtra, 'destroy'],
      ]);

      /** Evaluación de tareas */
      $r->get('/evaluacion', [
         new RbacMiddleware(['tareas.evaluacion.ver']),
         [$tev, 'index'],
      ]);
      $r->get('/evaluacion/show', [
         new RbacMiddleware(['tareas.evaluacion.ver']),
         [$tev, 'show'],
      ]);
      $r->post('/evaluacion', [
         new CsrfMiddleware(),
         new RbacMiddleware(['tareas.evaluacion.evaluar']),
         [$tev, 'store'],
      ]);
      $r->get('/evaluacion/historial', [
         new RbacMiddleware(['tareas.evaluacion.ver']),
         [$tev, 'history'],
      ]);
   });
});
