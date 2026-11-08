@echo off
title MIT Kiosk Thermal Printer Bridge
echo ========================================================
echo        MIT KIOSK - DIRECT THERMAL PRINTER BRIDGE
echo ========================================================
echo.

REM Always run from this script's folder (so scripts\local-printer-service.php is found
REM even when started from a desktop shortcut or another working directory).
cd /d "%~dp0"

set TARGET_URL=https://laravel-web-app-kr4g.onrender.com

REM Dedicated Chrome profile for the kiosk. This keeps the silent-print settings working
REM no matter which Gmail / Chrome account is used in the normal Chrome window, and makes
REM sure --kiosk-printing is applied even if regular Chrome is already open.
set KIOSK_PROFILE=%LocalAppData%\MITKiosk\ChromeProfile
if not exist "%KIOSK_PROFILE%" mkdir "%KIOSK_PROFILE%"

REM ---- 1) Start the local print bridge (direct ESC/POS to POS-58, no dialog) ----
where php >nul 2>&1
if errorlevel 1 (
    echo [WARNING] PHP was not found in PATH. The direct print bridge cannot start.
    echo           Tickets will fall back to silent browser printing to the DEFAULT printer.
    echo           Make sure POS-58 is set as the default Windows printer.
) else (
    echo Starting local thermal print service on http://127.0.0.1:18181...
    start "MIT Kiosk Print Bridge" /MIN php -S 127.0.0.1:18181 scripts\local-printer-service.php
    timeout /t 2 /nobreak >nul
    curl -s -m 3 http://127.0.0.1:18181 >nul 2>&1
    if errorlevel 1 (
        echo [WARNING] Print bridge did not respond on port 18181.
    ) else (
        echo [OK] Local Print Bridge is running on port 18181!
    )
)
echo.

REM ---- 2) Locate Chrome ----
set CHROME_BIN=
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" set "CHROME_BIN=C:\Program Files\Google\Chrome\Application\chrome.exe"
if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" set "CHROME_BIN=C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" set "CHROME_BIN=%LocalAppData%\Google\Chrome\Application\chrome.exe"

if not defined CHROME_BIN (
    echo [WARNING] Google Chrome not found. Opening default browser - the print dialog WILL appear.
    start "" "%TARGET_URL%"
    goto done
)

REM ---- 3) Launch the kiosk in its own Chrome profile ----
REM  --kiosk-printing                      : print silently to the default printer (no dialog)
REM  --user-data-dir                       : separate profile, independent of your Gmail account
REM  --disable-features=LocalNetworkAccessChecks : let the hosted kiosk page reach the
REM                                          local print bridge (127.0.0.1:18181) without a prompt
echo Launching kiosk Chrome with silent printing...
start "" "%CHROME_BIN%" --user-data-dir="%KIOSK_PROFILE%" --no-first-run --no-default-browser-check --kiosk-printing --disable-features=LocalNetworkAccessChecks "%TARGET_URL%"

:done
echo.
echo ========================================================
echo Kiosk is running! Keep this window open while using the Kiosk.
echo ========================================================
pause
