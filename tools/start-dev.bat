@echo off
REM Avvia tutto l'ambiente di sviluppo con un doppio click:
REM backend Laravel, sito React, Mailpit (email di prova) e worker delle code
REM (senza il worker le email restano in coda e non arrivano su Mailpit).
REM Alla fine apre nel browser il sito e la casella di Mailpit.
REM Per fermare tutto basta chiudere le finestre che si aprono.

set ROOT=%~dp0..
set PHP_BIN=D:\xampp\php\php.exe

echo Avvio backend Laravel (http://127.0.0.1:8000)...
REM Con OPcache attivo e Xdebug spento (circa 4 volte piu' veloce); vedi tools\dev-server.php.
start "Backend Laravel" /D "%ROOT%" "%PHP_BIN%" -d xdebug.mode=off -d opcache.enable=1 -d opcache.enable_cli=1 -d opcache.memory_consumption=128 -d opcache.revalidate_freq=0 -S 127.0.0.1:8000 -t backend\public tools\dev-server.php

echo Avvio Mailpit (http://127.0.0.1:8025)...
start "Mailpit" /D "%~dp0mailpit" mailpit.exe --listen 127.0.0.1:8025 --smtp 127.0.0.1:1025

echo Avvio il worker delle code (invio email)...
start "Coda email" /D "%ROOT%\backend" "%PHP_BIN%" artisan queue:work --sleep=3

echo Avvio il sito React (http://localhost:5173)...
start "Sito React" /D "%ROOT%\frontend" cmd /k npm run dev -- --port 5173

REM Qualche secondo per dare tempo ai server di partire prima di aprire il browser.
timeout /t 5 /nobreak > nul

start "" http://localhost:5173
start "" http://127.0.0.1:8025

echo.
echo Tutto avviato:
echo   Sito:    http://localhost:5173
echo   Admin:   http://127.0.0.1:8000/admin
echo   Mailpit: http://127.0.0.1:8025
