' Starts the MIT Kiosk thermal print bridge (http://127.0.0.1:18181) hidden in the background.
' Works on ANY Windows 10/11 PC or Mini PC:
' - Uses PHP if installed (PATH or C:\xampp\php\php.exe)
' - Otherwise uses the built-in Windows PowerShell bridge (scripts\local-printer-bridge.ps1) with ZERO external dependencies!
Set sh = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
root = fso.GetParentFolderName(fso.GetParentFolderName(WScript.ScriptFullName))
sh.CurrentDirectory = root

If fso.FileExists("C:\xampp\php\php.exe") Then
    sh.Run Chr(34) & "C:\xampp\php\php.exe" & Chr(34) & " -S 127.0.0.1:18181 " & Chr(34) & root & "\scripts\local-printer-service.php" & Chr(34), 0, False
Else
    sh.Run "powershell.exe -NoLogo -NoProfile -NonInteractive -ExecutionPolicy Bypass -WindowStyle Hidden -File " & Chr(34) & root & "\scripts\local-printer-bridge.ps1" & Chr(34), 0, False
End If
