@echo off
set "PHP83=C:\Users\Muhammad Areeb\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
if not exist "%PHP83%" (
  echo PHP 8.3 not found at:
  echo   %PHP83%
  exit /b 1
)
"%PHP83%" -v
"%PHP83%" artisan serve %*
