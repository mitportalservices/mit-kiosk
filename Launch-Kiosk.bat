@echo off
title MIT Kiosk Thermal Printer Bridge
echo ========================================================
echo        MIT KIOSK - DIRECT THERMAL PRINTER BRIDGE
echo ========================================================
echo.

cd /d "%~dp0"

set TARGET_URL=https://laravel-web-app-kr4g.onrender.com
set "BRIDGE_DIR=%LocalAppData%\MITKiosk"
set "KIOSK_PROFILE=%BRIDGE_DIR%\ChromeProfile"
if not exist "%KIOSK_PROFILE%" mkdir "%KIOSK_PROFILE%"

REM ---- 1) Install into %LocalAppData%\MITKiosk & Windows Startup ----
if exist "%~dp0scripts\local-printer-bridge.ps1" (
    copy /Y "%~dp0scripts\local-printer-bridge.ps1" "%BRIDGE_DIR%\local-printer-bridge.ps1" >nul 2>&1
)
if exist "%~dp0scripts\install-startup-shortcut.vbs" (
    cscript //nologo "%~dp0scripts\install-startup-shortcut.vbs" >nul 2>&1
)

reg add "HKCU\Software\Policies\Google\Chrome" /v InsecurePrivateNetworkRequestsAllowed /t REG_DWORD /d 1 /f >nul 2>&1
reg add "HKCU\Software\Policies\Google\Chrome\LocalNetworkAccessAllowedForUrls" /v 1 /t REG_SZ /d "https://laravel-web-app-kr4g.onrender.com" /f >nul 2>&1

echo Starting local thermal print service on http://127.0.0.1:18181...
if exist "%~dp0scripts\start-print-bridge.vbs" (
    start "" wscript.exe "%~dp0scripts\start-print-bridge.vbs"
) else if exist "%BRIDGE_DIR%\start-print-bridge.vbs" (
    start "" wscript.exe "%BRIDGE_DIR%\start-print-bridge.vbs"
)

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
start "" "%CHROME_BIN%" --user-data-dir="%KIOSK_PROFILE%" --no-first-run --no-default-browser-check --kiosk-printing --disable-features=LocalNetworkAccessChecks,BlockInsecurePrivateNetworkRequests "%TARGET_URL%"

:done
echo.
echo ========================================================
echo Kiosk is running! You can close this window if you like.
echo ========================================================
pause
