# This script adds an entry to the hosts file for greenadmin.local
# Must be run as Administrator

# Define the hosts file path
$hostsFile = "C:\Windows\System32\drivers\etc\hosts"

# Define the line to add
$newLine = "`n127.0.0.1    greenadmin.local"

# Check if the entry already exists
$hostsContent = Get-Content $hostsFile
if ($hostsContent -match "greenadmin.local") {
    Write-Host "Entry for greenadmin.local already exists in hosts file." -ForegroundColor Green
}
else {
    try {
        # Add the new entry to the hosts file
        Add-Content -Path $hostsFile -Value $newLine -ErrorAction Stop
        Write-Host "Successfully added greenadmin.local to hosts file." -ForegroundColor Green
    }
    catch {
        Write-Host "Error: Could not write to hosts file. Make sure you're running as Administrator." -ForegroundColor Red
        Write-Host "Error details: $_" -ForegroundColor Red
    }
}

# Pause to read the output
Write-Host "`nPress any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

