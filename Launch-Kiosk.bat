@echo off
title MIT Kiosk Thermal Printer Bridge
echo ========================================================
echo        MIT KIOSK - DIRECT THERMAL PRINTER BRIDGE
echo ========================================================
echo.
echo Starting local thermal print service on http://127.0.0.1:18181...
start /B php -S 127.0.0.1:18181 scripts\local-printer-service.php > nul 2>&1

echo [OK] Local Print Bridge is running on port 18181!
echo Any ticket requested from the hosted kiosk will print directly to POS-58
echo with ZERO popup dialogs and authentic thermal ESC/POS layout.
echo.

set TARGET_URL=https://laravel-web-app-kr4g.onrender.com

REM Locate Chrome
set CHROME_BIN=""
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" set CHROME_BIN="C:\Program Files\Google\Chrome\Application\chrome.exe"
if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" set CHROME_BIN="C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" set CHROME_BIN="%LocalAppData%\Google\Chrome\Application\chrome.exe"

if %CHROME_BIN%=="" (
    start "" "%TARGET_URL%"
) else (
    echo Launching Chrome with --kiosk-printing...
    start "" %CHROME_BIN% --kiosk-printing "%TARGET_URL%"
)

echo.
echo ========================================================
echo Kiosk is running! Keep this window open while using the Kiosk.
echo ========================================================
pause
