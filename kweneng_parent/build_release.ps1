$pubspec = "pubspec.yaml"

$content = Get-Content $pubspec -Raw

if ($content -match "version:\s*(\d+)\.(\d+)\.(\d+)\+(\d+)") {
    $major = [int]$matches[1]
    $minor = [int]$matches[2]
    $patch = [int]$matches[3]
    $build = [int]$matches[4] + 1

    $newVersion = "version: $major.$minor.$patch+$build"
    $content = $content -replace "version:\s*\d+\.\d+\.\d+\+\d+", $newVersion

    Set-Content $pubspec $content

    Write-Host "Updated app version to $major.$minor.$patch+$build"
} else {
    Write-Host "Could not find version in pubspec.yaml"
    exit 1
}

flutter clean
flutter pub get
flutter build apk --release