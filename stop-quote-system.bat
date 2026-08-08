@echo off
setlocal
set "XAMPP=C:\Users\a0210\xampp"

echo Stopping Apache ...
taskkill /IM httpd.exe /F >nul 2>&1

echo Stopping MariaDB (graceful) ...
"%XAMPP%\mysql\bin\mysqladmin.exe" -u root shutdown >nul 2>&1
timeout /t 3 /nobreak >nul
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul && taskkill /IM mysqld.exe /F >nul 2>&1

echo All services stopped.
timeout /t 3 /nobreak >nul
endlocal
