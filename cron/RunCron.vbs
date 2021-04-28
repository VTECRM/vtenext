Set WshShell = CreateObject("WScript.Shell")
WshShell.Run chr(34) & CreateObject("Scripting.FileSystemObject").GetAbsolutePathName(".") & "\" & left(WScript.ScriptName,(Len(WScript.ScriptName))-(len(".vbs"))) & ".bat" & Chr(34), 0
Set WshShell = Nothing
