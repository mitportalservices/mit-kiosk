' Starts the MIT Kiosk thermal print bridge (http://127.0.0.1:18181) hidden in the background.
' Safe to run multiple times: if the bridge is already running, the new copy exits immediately.
Set sh = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
root = fso.GetParentFolderName(fso.GetParentFolderName(WScript.ScriptFullName))
sh.CurrentDirectory = root
sh.Run "php -S 127.0.0.1:18181 scripts\local-printer-service.php", 0, False

