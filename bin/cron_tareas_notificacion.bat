@echo off
setlocal enabledelayedexpansion

rem === CONFIG BÁSICA ===
set "PROJ_DIR=C:\apache\htdocs\erp-gmi"
set "SCRIPT=%PROJ_DIR%\bin\cron_tareas_notificacion.php"
set "LOG_DIR=%PROJ_DIR%\storage\logs"
set "LOG_FILE=%LOG_DIR%\cron_tareas_notificacion.log"

rem === BUSCAR PHP ===
set "PHP_PATH="
for /f "delims=" %%I in ('where php 2^>NUL') do (
  set "PHP_PATH=%%I"
  goto :foundphp
)

rem Si no está en PATH, DESCOMENTA la línea correcta:
rem set "PHP_PATH=C:\xampp\php\php.exe"
rem set "PHP_PATH=C:\wamp64\bin\php\php8.2.12\php.exe"
rem set "PHP_PATH=C:\apache\php\php.exe"

:foundphp

rem === TIMESTAMP ===
for /f "tokens=1-3 delims=/- " %%a in ("%date%") do set DATESTAMP=%%c-%%b-%%a
for /f "tokens=1-2 delims=:." %%h in ("%time%") do set HOUR=%%h& set MIN=%%i
set HOUR=%HOUR: =0%
set TIMESTAMP=%HOUR%-%MIN%

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%" >NUL 2>&1

>>"%LOG_FILE%" echo [%DATESTAMP% %TIMESTAMP%] Iniciando job de cron_tareas_notificacion...

rem === VALIDACIONES DE RUTA ===
if "%PHP_PATH%"=="" (
  >>"%LOG_FILE%" echo [ERROR] No se encontró php.exe en PATH y no se definio PHP_PATH en el .bat
  >>"%LOG_FILE%" echo [SOLUCION] Edita este .bat y establece la ruta exacta en la seccion "DESCOMENTA la linea correcta".
  goto :fin
)
if not exist "%PHP_PATH%" (
  >>"%LOG_FILE%" echo [ERROR] PHP_PATH no existe: "%PHP_PATH%"
  goto :fin
)
if not exist "%SCRIPT%" (
  >>"%LOG_FILE%" echo [ERROR] SCRIPT no existe: "%SCRIPT%"
  goto :fin
)

rem === EJECUCION ===
"%PHP_PATH%" "%SCRIPT%" >> "%LOG_FILE%" 2>&1

>>"%LOG_FILE%" echo [%DATESTAMP% %TIMESTAMP%] Job cron_tareas_notificacion finalizado.

:fin
endlocal
