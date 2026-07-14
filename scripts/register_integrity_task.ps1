# Run as Administrator to register a scheduled task that runs the integrity check daily at 03:00
$php = 'C:\xampp\php\php.exe'
$script = 'C:\xampp\htdocs\smoketech_inventory\scripts\check_integrity_all.php'
$taskName = 'SmokeTech_Integrity_Sweep'
$time = '03:00'
$cmd = "schtasks /Create /SC DAILY /TN \"$taskName\" /TR \"\"$php\" \"$script\"\" /ST $time /F"
Write-Output "Registering scheduled task (requires admin):"
Write-Output $cmd
# Uncomment the next line to run automatically when the script is executed as admin
# Invoke-Expression $cmd
Write-Output "To register the task, run this script as Administrator and remove the comment on Invoke-Expression or run the printed schtasks command manually in an elevated PowerShell."