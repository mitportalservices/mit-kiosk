' Starts the MIT Kiosk thermal print bridge (http://127.0.0.1:18181) hidden in the background.
' Safe to run multiple times: if the bridge is already running, the new copy exits immediately.
Set sh = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
root = fso.GetParentFolderName(fso.GetParentFolderName(WScript.ScriptFullName))
sh.CurrentDirectory = root

phpCmd = "php"
If fso.FileExists("C:\xampp\php\php.exe") Then
    phpCmd = Chr(34) & "C:\xampp\php\php.exe" & Chr(34)
End If

sh.Run phpCmd & " -S 127.0.0.1:18181 " & Chr(34) & root & "\scripts\local-printer-service.php" & Chr(34), 0, False


