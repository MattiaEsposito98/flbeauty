@echo off
REM Avvia l'ambiente email di sviluppo: Mailpit (cattura le email in locale)
REM e il worker delle code Laravel (necessario per inviare davvero le email,
REM comprese quelle di "Comunicazioni" e i cambi di stato ordine).
REM Lancia questo file con un doppio click.

set MAILPIT_DIR=%~dp0mailpit
set BACKEND_DIR=%~dp0..\backend
set PHP_BIN=D:\xampp\php\php.exe

echo Avvio Mailpit (interfaccia su http://127.0.0.1:8025)...
start "Mailpit" /D "%MAILPIT_DIR%" mailpit.exe --listen 127.0.0.1:8025 --smtp 127.0.0.1:1025

echo Avvio il worker delle code Laravel...
start "Laravel Queue Worker" /D "%BACKEND_DIR%" "%PHP_BIN%" artisan queue:work

echo.
echo Fatto. Mailpit: http://127.0.0.1:8025
echo Chiudi le due finestre aperte per fermare i servizi.
pause
