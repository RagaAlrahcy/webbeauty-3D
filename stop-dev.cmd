@echo off
REM Stops everything start-dev.cmd started: queue worker, web server, MySQL 8.4 (port 3307).
REM Order matters: the queue worker is asked to stop while MySQL is still up,
REM and MySQL is shut down cleanly (not killed) so no data is lost.
cd /d D:\projects\beauty-cms

netstat -ano | findstr ":3307 " | findstr LISTENING >nul
if errorlevel 1 goto stop_php

echo [1/3] Meminta queue worker berhenti setelah job yang sedang berjalan...
D:\tools\php84\php.exe artisan queue:restart >nul 2>&1
call :wait 4

:stop_php
echo [2/3] Mematikan web server dan queue worker...
powershell -NoProfile -Command "Get-CimInstance Win32_Process -Filter \"Name='php.exe'\" | Where-Object { $_.ExecutablePath -eq 'D:\tools\php84\php.exe' -and ($_.CommandLine -like '*artisan*' -or $_.CommandLine -like '*server.php*') } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }"

netstat -ano | findstr ":3307 " | findstr LISTENING >nul
if errorlevel 1 (
    echo [3/3] MySQL sudah tidak berjalan.
    goto done
)

echo [3/3] Mematikan MySQL 8.4 dengan aman...
D:\tools\mysql84\bin\mysqladmin.exe --defaults-file=D:\tools\mysql84\my.ini -u root shutdown
call :wait 5

netstat -ano | findstr ":3307 " | findstr LISTENING >nul
if not errorlevel 1 (
    echo MySQL belum berhenti, menunggu sebentar lagi...
    call :wait 10
)

netstat -ano | findstr ":3307 " | findstr LISTENING >nul
if not errorlevel 1 (
    echo PERINGATAN: MySQL masih berjalan. Jalankan stop-dev.cmd sekali lagi.
    pause
    exit /b 1
)

:done
echo.
echo Semua layanan sudah dimatikan.
call :wait 3
exit /b 0

:wait
REM Waits N seconds; unlike "timeout" it also works in non-interactive terminals.
set /a _pings=%1+1
ping -n %_pings% 127.0.0.1 >nul
exit /b 0
