@echo off
REM Temporary local runner without Docker (AGENTS.md section 4.4).
REM Starts MySQL 8.4 (port 3307), the queue worker and the web server.
cd /d D:\projects\beauty-cms

netstat -ano | findstr ":3307 " | findstr LISTENING >nul
if errorlevel 1 (
    echo Menyalakan MySQL 8.4 di port 3307...
    start "MySQL 8.4" /min D:\tools\mysql84\bin\mysqld.exe --defaults-file=D:\tools\mysql84\my.ini
    timeout /t 5 /nobreak >nul
)

start "Queue worker" /min D:\tools\php84\php.exe artisan queue:work --sleep=2 --tries=3

echo.
echo Website : http://127.0.0.1:8000
echo Admin   : http://127.0.0.1:8000/admin
echo Tutup jendela ini untuk mematikan web server.
echo.
start "" http://127.0.0.1:8000
D:\tools\php84\php.exe artisan serve --host=127.0.0.1 --port=8000
