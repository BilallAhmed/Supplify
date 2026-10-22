@echo off
title Supplify — Start on 8080
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\start-supplify.ps1" %*
if errorlevel 1 (
  echo.
  echo [Supplify] Start failed. See messages above.
  pause
  exit /b 1
)
echo.
pause
