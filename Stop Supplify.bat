@echo off
title Supplify — Stop 8080
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\start-supplify.ps1" -Stop
echo.
pause
