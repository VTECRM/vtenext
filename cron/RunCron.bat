@echo off
set ROOTDIR=%~dp0
set VTECRM_ROOTDIR=%~dp0..

REM search for command php.exe
where "php.exe" >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
	if exist "C:\vtenext18ce\php\php.exe" (
		set PHP_EXE="C:\vtenext18ce\php\php.exe"
	) else (
		echo "Command php.exe not found"
		goto :eof
	)
) else (
	set PHP_EXE="php.exe"
)

cd /D %VTECRM_ROOTDIR%
%PHP_EXE% -f %ROOTDIR%RunCron.php
cd /D %ROOTDIR%