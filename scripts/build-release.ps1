param(
	[string]$ComponentId = "",
	[string]$Version = "",
	[switch]$SkipPhpLint
)

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
$ManifestPath = Join-Path $RepoRoot "ossn_com.xml"

if (!(Test-Path -LiteralPath $ManifestPath)) {
	throw "Missing ossn_com.xml at $ManifestPath"
}

[xml]$Manifest = Get-Content -Raw -LiteralPath $ManifestPath
if ([string]::IsNullOrWhiteSpace($ComponentId)) {
	$ComponentId = [string]$Manifest.component.id
}
if ([string]::IsNullOrWhiteSpace($Version)) {
	$Version = [string]$Manifest.component.version
}

if ([string]::IsNullOrWhiteSpace($ComponentId)) {
	throw "Could not read component id from ossn_com.xml"
}
if ([string]::IsNullOrWhiteSpace($Version)) {
	throw "Could not read component version from ossn_com.xml"
}
if ($ComponentId -match '[\\/:*?"<>|]') {
	throw "Component id '$ComponentId' is not safe for a folder name"
}

$RequiredRootFiles = @("ossn_com.php", "ossn_com.xml")
foreach ($Name in $RequiredRootFiles) {
	$Path = Join-Path $RepoRoot $Name
	if (!(Test-Path -LiteralPath $Path)) {
		throw "Missing required OSSN component file: $Name"
	}
}

if (!$SkipPhpLint) {
	$Php = Get-Command php -ErrorAction SilentlyContinue
	if ($Php) {
		$PhpFiles = Get-ChildItem -LiteralPath $RepoRoot -Recurse -File -Filter "*.php" |
			Where-Object { $_.FullName -notmatch '\\\.git\\|\\dist\\' }
		foreach ($File in $PhpFiles) {
			& $Php.Source -l $File.FullName
			if ($LASTEXITCODE -ne 0) {
				throw "PHP syntax check failed: $($File.FullName)"
			}
		}
	} else {
		Write-Warning "php was not found in PATH; skipping local PHP syntax checks."
	}
}

$DistDir = Join-Path $RepoRoot "dist"
$BuildRoot = Join-Path $DistDir ".release-build"
$ComponentStage = Join-Path $BuildRoot $ComponentId
$ZipPath = Join-Path $DistDir "$ComponentId-$Version.zip"

$ResolvedRepoRoot = [System.IO.Path]::GetFullPath($RepoRoot)
$ResolvedDistDir = [System.IO.Path]::GetFullPath($DistDir)
$ResolvedBuildRoot = [System.IO.Path]::GetFullPath($BuildRoot)
if (!$ResolvedBuildRoot.StartsWith($ResolvedDistDir, [System.StringComparison]::OrdinalIgnoreCase)) {
	throw "Refusing to clean a build path outside dist: $ResolvedBuildRoot"
}

New-Item -ItemType Directory -Force -Path $DistDir | Out-Null
if (Test-Path -LiteralPath $BuildRoot) {
	Remove-Item -LiteralPath $BuildRoot -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $ComponentStage | Out-Null

$ExcludedNames = @(".git", "dist", "scripts", "RELEASE_CHECKLIST.md")
$ExcludedPatterns = @("*.zip", "*.tar", "*.tgz", "*.log", "*.bak.*")

Get-ChildItem -LiteralPath $RepoRoot -Force | Where-Object {
	$Item = $_
	if ($ExcludedNames -contains $Item.Name) {
		return $false
	}
	foreach ($Pattern in $ExcludedPatterns) {
		if ($Item.Name -like $Pattern) {
			return $false
		}
	}
	return $true
} | ForEach-Object {
	Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $ComponentStage $_.Name) -Recurse -Force
}

if (Test-Path -LiteralPath $ZipPath) {
	Remove-Item -LiteralPath $ZipPath -Force
}

Push-Location -LiteralPath $BuildRoot
try {
	Compress-Archive -LiteralPath ".\$ComponentId" -DestinationPath $ZipPath -Force
} finally {
	Pop-Location
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
$Zip = [System.IO.Compression.ZipFile]::OpenRead($ZipPath)
try {
	$EntryNames = $Zip.Entries | ForEach-Object { $_.FullName -replace '\\', '/' }
	$RequiredEntries = @("$ComponentId/ossn_com.php", "$ComponentId/ossn_com.xml")
	foreach ($Entry in $RequiredEntries) {
		if ($EntryNames -notcontains $Entry) {
			throw "Release ZIP is missing required entry: $Entry"
		}
	}
	if ($EntryNames | Where-Object { $_ -match '(^|/)\.git(/|$)' }) {
		throw "Release ZIP unexpectedly contains .git data"
	}
} finally {
	$Zip.Dispose()
}

Remove-Item -LiteralPath $BuildRoot -Recurse -Force

Write-Host "Built release ZIP:"
Write-Host $ZipPath
Write-Host ""
Write-Host "Expected OSSN ZIP root:"
Write-Host "$ComponentId/ossn_com.php"
