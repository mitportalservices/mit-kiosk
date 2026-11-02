@echo off
title MIT Staff and Admin Portal - Mobile App Builder
echo ========================================================
echo   BUILDING MIT STAFF ^& ADMIN PORTAL MOBILE APP
echo   (Android APK + iOS/Web Portal)
echo ========================================================
echo.

set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%

cd /d "%~dp0render-flutter_staff_app"
if exist "build\app\outputs\flutter-apk\app-release.apk" del /F /Q "build\app\outputs\flutter-apk\app-release.apk"
if exist "build\app\outputs\flutter-apk\app-debug.apk" del /F /Q "build\app\outputs\flutter-apk\app-debug.apk"

echo [1/2] Building Android Release APK...
call C:\flutter\bin\flutter.bat build apk --release --android-skip-build-dependency-validation --dart-define=API_BASE_URL=https://laravel-web-app-kr4g.onrender.com

if exist "build\app\outputs\flutter-apk\app-release.apk" (
    copy /Y "build\app\outputs\flutter-apk\app-release.apk" "%~dp0MIT-Staff-Admin-Portal.apk" > nul
    copy /Y "build\app\outputs\flutter-apk\app-release.apk" "%~dp0public\MIT-Staff-Admin-Portal.apk" > nul
    echo.
    echo [SUCCESS] APK built and copied to:
    echo   %~dp0MIT-Staff-Admin-Portal.apk
    echo   %~dp0public\MIT-Staff-Admin-Portal.apk
) else (
    echo.
    echo [ERROR] APK build did not produce app-release.apk.
    exit /b 1
)

echo.
echo [2/2] Building iOS / Web Mobile Portal (PWA)...
call C:\flutter\bin\flutter.bat build web --release --base-href /mobile/ --dart-define=API_BASE_URL=https://laravel-web-app-kr4g.onrender.com

if exist "build\web\index.html" (
    if not exist "%~dp0public\mobile" mkdir "%~dp0public\mobile"
    xcopy /E /I /Y "build\web\*" "%~dp0public\mobile\" > nul
    echo.
    echo [SUCCESS] iOS/Web Portal built and copied to:
    echo   %~dp0public\mobile\
) else (
    echo.
    echo [WARNING] Web build did not produce build\web\index.html.
)

echo.
echo ========================================================
echo   BUILD COMPLETE!
echo   - Android APK: %~dp0MIT-Staff-Admin-Portal.apk
echo   - iOS/Web App: %~dp0public\mobile\
echo ========================================================
