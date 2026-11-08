Set sh = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
root = fso.GetParentFolderName(fso.GetParentFolderName(WScript.ScriptFullName))
startupDir = sh.SpecialFolders("Startup")
Set lnk = sh.CreateShortcut(startupDir & "\MIT Kiosk Print Bridge.lnk")
lnk.TargetPath = "wscript.exe"
lnk.Arguments = Chr(34) & root & "\scripts\start-print-bridge.vbs" & Chr(34)
lnk.WorkingDirectory = root
lnk.Description = "MIT Kiosk Thermal Print Bridge (port 18181)"
lnk.Save
