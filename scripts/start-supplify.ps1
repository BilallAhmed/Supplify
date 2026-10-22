#Requires -Version 5.1
<#
.SYNOPSIS
  One-click Supplify local stack: MySQL (XAMPP) + Laravel on http://127.0.0.1:8080

.PARAMETER WithVite
  Also start Vite (npm run dev) in a second window for hot CSS/JS.

.PARAMETER NoBrowser
  Do not open the browser after the server is ready.

.PARAMETER Stop
  Stop the Laravel process listening on 8080 (does not stop MySQL).
#>
[CmdletBinding()]
param(
    [switch]$WithVite,
    [switch]$NoBrowser,
    [switch]$Stop
)

$ErrorActionPreference = 'Stop'
$HostPort = 8080
$HostAddr = '127.0.0.1'
$AppUrl = "http://${HostAddr}:${HostPort}"
$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$XamppRoot = 'C:\xampp'
$PhpCandidates = @(
    'C:\Users\Muhammad Areeb\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe',
    (Join-Path $XamppRoot 'php\php.exe')
)

function Write-Step([string]$Message) {
    Write-Host "[Supplify] $Message" -ForegroundColor Cyan
}

function Write-Ok([string]$Message) {
    Write-Host "[Supplify] $Message" -ForegroundColor Green
}

function Write-WarnMsg([string]$Message) {
    Write-Host "[Supplify] $Message" -ForegroundColor Yellow
}

function Test-PortOpen([int]$Port) {
    $conn = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -First 1
    return $null -ne $conn
}

function Get-ListenerPid([int]$Port) {
    $conn = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -First 1
    if ($null -eq $conn) { return $null }
    return $conn.OwningProcess
}

function Resolve-PhpExe {
    foreach ($candidate in $PhpCandidates) {
        if ($candidate -and (Test-Path -LiteralPath $candidate)) {
            return $candidate
        }
    }
    $fromPath = Get-Command php -ErrorAction SilentlyContinue
    if ($fromPath) { return $fromPath.Source }
    throw "PHP not found. Install PHP 8.3 (WinGet) or use XAMPP at $XamppRoot\php\php.exe"
}

function Ensure-Mysql {
    if (Test-PortOpen 3306) {
        Write-Ok 'MySQL already running on 3306.'
        return
    }

    Write-Step 'Starting XAMPP MySQL...'
    $mysqlStart = Join-Path $XamppRoot 'mysql_start.bat'
    if (-not (Test-Path -LiteralPath $mysqlStart)) {
        throw "XAMPP MySQL launcher missing: $mysqlStart"
    }

    Start-Process -FilePath $mysqlStart -WorkingDirectory $XamppRoot -WindowStyle Minimized | Out-Null

    $deadline = (Get-Date).AddSeconds(45)
    while ((Get-Date) -lt $deadline) {
        if (Test-PortOpen 3306) {
            Write-Ok 'MySQL is listening on 3306.'
            return
        }
        Start-Sleep -Milliseconds 500
    }

    throw 'MySQL did not become ready on port 3306 within 45s. Open XAMPP Control and start MySQL.'
}

function Stop-AppServer {
    $pidOnPort = Get-ListenerPid $HostPort
    if ($null -eq $pidOnPort) {
        Write-Ok "Nothing is listening on $HostPort."
        return
    }
    Write-Step "Stopping process $pidOnPort on port $HostPort..."
    Stop-Process -Id $pidOnPort -Force -ErrorAction SilentlyContinue
    Start-Sleep -Milliseconds 400
    if (Test-PortOpen $HostPort) {
        throw "Port $HostPort is still in use after stop attempt."
    }
    Write-Ok "Stopped. Port $HostPort is free."
}

function Start-AppServer([string]$PhpExe) {
    if (Test-PortOpen $HostPort) {
        Write-Ok "App already running at $AppUrl"
        return $false
    }

    if (-not (Test-Path -LiteralPath (Join-Path $ProjectRoot 'artisan'))) {
        throw "artisan not found in $ProjectRoot"
    }

    Write-Step "Starting Laravel: $PhpExe artisan serve --host=$HostAddr --port=$HostPort"
    $argList = @(
        'artisan', 'serve',
        "--host=$HostAddr",
        "--port=$HostPort"
    )
    Start-Process -FilePath $PhpExe -ArgumentList $argList -WorkingDirectory $ProjectRoot -WindowStyle Normal | Out-Null

    $deadline = (Get-Date).AddSeconds(30)
    while ((Get-Date) -lt $deadline) {
        if (Test-PortOpen $HostPort) {
            Write-Ok "Server ready at $AppUrl"
            return $true
        }
        Start-Sleep -Milliseconds 400
    }

    throw "Laravel did not bind $AppUrl within 30s. Check the server window for errors."
}

function Start-ViteDev {
    $npm = Get-Command npm -ErrorAction SilentlyContinue
    if (-not $npm) {
        Write-WarnMsg 'npm not found — skipping Vite. Using public/build assets instead.'
        return
    }
    Write-Step 'Starting Vite (npm run dev)...'
    Start-Process -FilePath $npm.Source -ArgumentList 'run', 'dev' -WorkingDirectory $ProjectRoot -WindowStyle Normal | Out-Null
}

# --- entry ---
Set-Location -LiteralPath $ProjectRoot
Write-Host ''
Write-Host '  Supplify one-click launcher' -ForegroundColor White
Write-Host "  Project: $ProjectRoot" -ForegroundColor DarkGray
Write-Host ''

if ($Stop) {
    Stop-AppServer
    exit 0
}

$php = Resolve-PhpExe
Write-Step "PHP: $php"
& $php -v | Select-Object -First 1 | ForEach-Object { Write-Host "         $_" -ForegroundColor DarkGray }

Ensure-Mysql
$started = Start-AppServer -PhpExe $php

if ($WithVite) {
    Start-ViteDev
}

if (-not $NoBrowser) {
    Write-Step "Opening $AppUrl"
    Start-Process $AppUrl | Out-Null
}

if ($started) {
    Write-Ok 'Done. Leave the PHP server window open while you work.'
} else {
    Write-Ok 'Done. App was already up.'
}

Write-Host ''
Write-Host '  Tip: double-click "Start Supplify.bat" or run Cursor task "Start Supplify (8080)".' -ForegroundColor DarkGray
Write-Host '  Stop:  .\scripts\start-supplify.ps1 -Stop' -ForegroundColor DarkGray
Write-Host ''
