# RLabz Production Deployment Packager
$ProgressPreference = 'SilentlyContinue'
$sourceDir = $PSScriptRoot
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$zipFileName = "rlabz_production_$timestamp.zip"
$destinationZip = Join-Path $sourceDir $zipFileName

Write-Host "Creating clean deployment package: $zipFileName ..." -ForegroundColor Cyan

$excludeList = @(
    "*.git*",
    "*.gemini*",
    "*.zip",
    "create_deployment_zip.ps1",
    "check_status.php",
    "update_status.php",
    "WhatWeDo"
)

$items = Get-ChildItem -Path $sourceDir | Where-Object {
    $name = $_.Name
    $exclude = $false
    foreach ($pattern in $excludeList) {
        if ($name -like $pattern) {
            $exclude = $true
            break
        }
    }
    return -not $exclude
}

Compress-Archive -Path $items.FullName -DestinationPath $destinationZip -Force

Write-Host "SUCCESS: Deployment package created at:" -ForegroundColor Green
Write-Host $destinationZip -ForegroundColor Yellow
