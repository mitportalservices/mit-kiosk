' Starts the MIT Kiosk thermal print bridge (http://127.0.0.1:18181) hidden in the background.
' Works on ANY Windows 10/11 PC or Mini PC with zero external dependencies!
Set sh = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)
root = fso.GetParentFolderName(scriptDir)

phpBin = "C:\xampp\php\php.exe"
phpSvc = root & "\scripts\local-printer-service.php"
psLocal = scriptDir & "\local-printer-bridge.ps1"
psRoot  = root & "\scripts\local-printer-bridge.ps1"

If fso.FileExists(phpBin) And fso.FileExists(phpSvc) Then
    sh.CurrentDirectory = root
    sh.Run Chr(34) & phpBin & Chr(34) & " -S 127.0.0.1:18181 " & Chr(34) & phpSvc & Chr(34), 0, False
ElseIf fso.FileExists(psLocal) Then
    sh.CurrentDirectory = scriptDir
    sh.Run "powershell.exe -NoLogo -NoProfile -NonInteractive -ExecutionPolicy Bypass -WindowStyle Hidden -File " & Chr(34) & psLocal & Chr(34), 0, False
ElseIf fso.FileExists(psRoot) Then
    sh.CurrentDirectory = root
    sh.Run "powershell.exe -NoLogo -NoProfile -NonInteractive -ExecutionPolicy Bypass -WindowStyle Hidden -File " & Chr(34) & psRoot & Chr(34), 0, False
End If
