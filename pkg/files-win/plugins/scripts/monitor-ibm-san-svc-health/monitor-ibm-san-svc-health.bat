@ECHO OFF
set PHPDIR=..\..\..\apache\php\
"%PHPDIR%\php.exe" monitor-ibm-san-svc-health.php %1
