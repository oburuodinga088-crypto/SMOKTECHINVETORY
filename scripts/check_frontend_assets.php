<?php
// Simple checker that validates files referenced in includes/header.php and includes/footer.php exist on disk
$root = __DIR__ . '/..';
$files = [];
foreach (['includes/header.php','includes/footer.php'] as $inc) {
    $path = $root . '/' . $inc;
    if (!file_exists($path)) continue;
    $content = file_get_contents($path);
    // find href and src attributes
    preg_match_all('/(?:href|src)=["\']([^"\'>]+)["\']/', $content, $m);
    foreach ($m[1] as $url) {
        // Only check local paths (start with / or without protocol)
        if (preg_match('#^https?://#i', $url)) continue;
        // strip leading / and the app base if present
        $trim = preg_replace('#^/+#','',$url);
        // If the path includes the application folder name, strip it (web root mapping)
        $trim = preg_replace('#^smoketech_inventory/#','',$trim);
        // Map to filesystem path under project root
        $fs = $root . '/' . $trim;
        $files[$url] = $fs;
    }
}

$ok = [];
$missing = [];
foreach ($files as $url => $fs) {
    if (file_exists($fs)) $ok[$url] = $fs; else $missing[$url] = $fs;
}

echo "Frontend asset check:\n";
echo "Found " . count($ok) . " referenced local assets present.\n";
if ($ok) {
    foreach ($ok as $url => $fs) echo "  OK: $url -> $fs\n";
}

echo "Missing: " . count($missing) . "\n";
if ($missing) {
    foreach ($missing as $url => $fs) echo "  MISSING: $url -> $fs\n";
}

exit(empty($missing) ? 0 : 2);
