@echo off
title MIT Kiosk Thermal Printer Bridge
echo ========================================================
echo        MIT KIOSK - DIRECT THERMAL PRINTER BRIDGE
echo ========================================================
echo.

REM Always run from this script's folder (so scripts\start-print-bridge.vbs is found
REM even when started from a desktop shortcut or another working directory).
cd /d "%~dp0"

set TARGET_URL=https://laravel-web-app-kr4g.onrender.com

REM Dedicated Chrome profile for the kiosk. This keeps the silent-print settings working
REM no matter which Gmail / Chrome account is used in the normal Chrome window, and makes
REM sure --kiosk-printing is applied even if regular Chrome is already open.
set KIOSK_PROFILE=%LocalAppData%\MITKiosk\ChromeProfile
if not exist "%KIOSK_PROFILE%" mkdir "%KIOSK_PROFILE%"

REM ---- 1) Install into Windows Startup & Start the local print bridge ----
cscript //nologo "%~dp0scripts\install-startup-shortcut.vbs" >nul 2>&1
echo Starting local thermal print service on http://127.0.0.1:18181...
start "" wscript.exe "%~dp0scripts\start-print-bridge.vbs"
timeout /t 2 /nobreak >nul
curl -s -m 3 http://127.0.0.1:18181 >nul 2>&1
if errorlevel 1 (
    timeout /t 2 /nobreak >nul
    curl -s -m 3 http://127.0.0.1:18181 >nul 2>&1
)
if errorlevel 1 (
    echo [WARNING] Print bridge did not respond on port 18181.
) else (
    echo [OK] Local Print Bridge is running on port 18181!
)
echo.

REM ---- 2) Locate Chrome ----
set CHROME_BIN=
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" set "CHROME_BIN=C:\Program Files\Google\Chrome\Application\chrome.exe"
if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" set "CHROME_BIN=C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" set "CHROME_BIN=%LocalAppData%\Google\Chrome\Application\chrome.exe"

if not defined CHROME_BIN (
    echo [WARNING] Google Chrome not found. Opening default browser...
    start "" "%TARGET_URL%"
    goto done
)

REM ---- 3) Launch the kiosk in its own Chrome profile ----
echo Launching kiosk Chrome with silent printing...
start "" "%CHROME_BIN%" --user-data-dir="%KIOSK_PROFILE%" --no-first-run --no-default-browser-check --kiosk-printing --disable-features=LocalNetworkAccessChecks "%TARGET_URL%"

:done
echo.
echo ========================================================
echo Kiosk is running! You can close this window if you like.
echo ========================================================
pause
