@echo off
title STATON ERP Management System
setlocal
set "XAMPP=C:\Users\a0210\xampp"

echo ============================================================
echo   STATON ERP  -  Launcher
echo ============================================================
echo.

echo [1/2] Starting MariaDB (MySQL) ...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 (
  start "MariaDB" /min "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file=%XAMPP%\mysql\bin\my.ini --console
  echo       started.
) else (
  echo       already running.
)

echo [2/2] Starting Apache (Web Server) ...
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 (
  start "Apache" /min "%XAMPP%\apache\bin\httpd.exe"
  echo       started.
) else (
  echo       already running.
)

echo.
echo Waiting for services to be ready ...
timeout /t 4 /nobreak >nul
start "" "http://localhost/"

echo.
echo ============================================================
echo   Ready!
echo     URL     : http://localhost/
echo     Account : admin
echo ============================================================
echo.
echo You can close this window; the servers keep running.
echo To stop them, run  stop-quote-system.bat
timeout /t 6 /nobreak >nul
endlocal
