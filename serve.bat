@echo off
REM Supplify local server (port 8080 — avoids OpenJarvis on 8000)
set PHP83=C:\Users\Muhammad Areeb\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe
cd /d C:\xampp\htdocs\supplement
echo Starting Supplify at http://127.0.0.1:8080
"%PHP83%" artisan serve --host=127.0.0.1 --port=8080
