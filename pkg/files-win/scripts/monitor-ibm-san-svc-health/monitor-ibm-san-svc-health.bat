@ECHO OFF
set PHPDIR=..\..\apache\php\
"%PHPDIR%\php.exe" ..\..\plugins\scripts\monitor-ibm-san-svc-health\monitor-ibm-san-svc-health.php %1
