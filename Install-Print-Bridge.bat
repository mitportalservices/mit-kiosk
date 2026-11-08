@echo off
title MIT Kiosk - Install Print Bridge (auto-start)
cd /d "%~dp0"

echo Adding the print bridge to Windows Startup (runs hidden at every login)...
cscript //nologo "%~dp0scripts\install-startup-shortcut.vbs"

echo Starting the print bridge now...
wscript.exe "%~dp0scripts\start-print-bridge.vbs"
timeout /t 3 /nobreak >nul

curl -s -m 3 http://127.0.0.1:18181
if errorlevel 1 (
    echo.
    echo [WARNING] The print bridge did not respond on port 18181.
) else (
    echo.
    echo [OK] Print bridge is running. Tickets will print directly to POS-58 with no print window.
)
echo.
pause
