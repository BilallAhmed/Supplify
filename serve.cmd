@echo off
REM Compat wrapper — prefer "Start Supplify.bat" for one-click start.
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\start-supplify.ps1" %*
