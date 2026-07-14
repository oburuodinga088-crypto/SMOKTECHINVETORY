<?php
require '../../includes/auth.php';
require '../../includes/functions.php';
requireRole(['Admin']);

$defaults = [
    'company_name' => 'SmokeTech Technology & Innovation Hub',
    'company_logo' => '/smoketech_inventory/assets/images/moketech-logo.png',
    'receipt_header' => 'Thank you for choosing SmokeTech.',
    'receipt_footer' => 'Goods sold are subject to our terms and warranty policy.',
    'currency' => 'KSh',
    'decimal_places' => '2',
    'timezone' => 'Africa/Nairobi',
    'date_format' => 'Y-m-d',
    'language' => 'en',
    'theme' => 'light',
    'sidebar_style' => 'dark',
];
$values = [];
foreach ($defaults as $key => $default) {
    $values[$key] = appSetting($key, $default);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $_SESSION['flash'] = 'Your session token expired. Please try again.';
    } else {
        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $logo = trim((string) ($_POST['company_logo'] ?? ''));
        $currency = trim((string) ($_POST['currency'] ?? ''));
        $decimalPlaces = filter_var($_POST['decimal_places'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 4]]);
        $timezone = trim((string) ($_POST['timezone'] ?? ''));
        $dateFormat = trim((string) ($_POST['date_format'] ?? ''));
        $language = trim((string) ($_POST['language'] ?? ''));
        $theme = trim((string) ($_POST['theme'] ?? ''));
        $sidebarStyle = trim((string) ($_POST['sidebar_style'] ?? ''));

        try {
            if ($companyName === '' || mb_strlen($companyName) > 150) throw new InvalidArgumentException('Company name is required and must be 150 characters or fewer.');
            if ($logo !== '' && (!str_starts_with($logo, '/') || str_contains($logo, '..'))) throw new InvalidArgumentException('Logo path must be a safe site-relative path.');
            if ($currency === '' || mb_strlen($currency) > 10) throw new InvalidArgumentException('Enter a valid currency label.');
            if ($decimalPlaces === false) throw new InvalidArgumentException('Decimal places must be between 0 and 4.');
            if (!in_array($timezone, timezone_identifiers_list(), true)) throw new InvalidArgumentException('Select a valid timezone.');
            if (!in_array($dateFormat, ['Y-m-d', 'd/m/Y', 'm/d/Y'], true)) throw new InvalidArgumentException('Select a valid date format.');
            if (!in_array($language, ['en'], true)) throw new InvalidArgumentException('Unsupported language.');
            if (!in_array($theme, ['light', 'dark'], true) || !in_array($sidebarStyle, ['dark', 'light'], true)) throw new InvalidArgumentException('Invalid interface preference.');

            saveAppSettings([
                'company_name' => $companyName,
                'company_logo' => $logo ?: $defaults['company_logo'],
                'receipt_header' => trim((string) ($_POST['receipt_header'] ?? '')),
                'receipt_footer' => trim((string) ($_POST['receipt_footer'] ?? '')),
                'currency' => $currency,
                'decimal_places' => (string) $decimalPlaces,
                'timezone' => $timezone,
                'date_format' => $dateFormat,
                'language' => $language,
                'theme' => $theme,
                'sidebar_style' => $sidebarStyle,
            ], (int) $_SESSION['user_id']);
            $_SESSION['flash'] = 'Application settings saved successfully.';
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
            foreach ($defaults as $key => $default) $values[$key] = trim((string) ($_POST[$key] ?? $values[$key]));
        }
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
include '../../includes/sidebar.php';
?>
<div class="content-wrapper"><section class="content-header"><div class="container-fluid"><h1><i class="fas fa-cog"></i> Application Settings</h1></div></section><section class="content"><div class="container-fluid"><div class="card card-primary"><div class="card-header"><h3 class="card-title">Company, receipt and interface preferences</h3></div><form method="post"><div class="card-body">
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?= csrfField() ?>
<div class="row"><div class="col-md-6"><div class="form-group"><label>Company name</label><input name="company_name" class="form-control" maxlength="150" required value="<?= e($values['company_name']) ?>"></div></div><div class="col-md-6"><div class="form-group"><label>Logo path</label><input name="company_logo" class="form-control" value="<?= e($values['company_logo']) ?>"><small class="form-text text-muted">Use a site-relative path, for example /smoketech_inventory/assets/images/moketech-logo.png.</small></div></div></div>
<div class="row"><div class="col-md-6"><div class="form-group"><label>Currency</label><input name="currency" class="form-control" maxlength="10" required value="<?= e($values['currency']) ?>"></div></div><div class="col-md-6"><div class="form-group"><label>Decimal places</label><select name="decimal_places" class="form-control"><?php for ($i=0; $i<=4; $i++): ?><option value="<?= $i ?>" <?= (string)$i === $values['decimal_places'] ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div></div></div>
<div class="row"><div class="col-md-3"><div class="form-group"><label>Timezone</label><select name="timezone" class="form-control"><?php foreach (['Africa/Nairobi', 'UTC', 'Africa/Kampala', 'Africa/Dar_es_Salaam'] as $zone): ?><option value="<?= e($zone) ?>" <?= $zone === $values['timezone'] ? 'selected' : '' ?>><?= e($zone) ?></option><?php endforeach; ?></select></div></div><div class="col-md-3"><div class="form-group"><label>Date format</label><select name="date_format" class="form-control"><?php foreach (['Y-m-d', 'd/m/Y', 'm/d/Y'] as $format): ?><option value="<?= $format ?>" <?= $format === $values['date_format'] ? 'selected' : '' ?>><?= date($format) ?></option><?php endforeach; ?></select></div></div><div class="col-md-3"><div class="form-group"><label>Language</label><select name="language" class="form-control"><option value="en" <?= $values['language'] === 'en' ? 'selected' : '' ?>>English</option></select></div></div><div class="col-md-3"><div class="form-group"><label>Theme</label><select name="theme" class="form-control"><option value="light" <?= $values['theme'] === 'light' ? 'selected' : '' ?>>Light</option><option value="dark" <?= $values['theme'] === 'dark' ? 'selected' : '' ?>>Dark</option></select></div></div></div>
<div class="row"><div class="col-md-4"><div class="form-group"><label>Sidebar style</label><select name="sidebar_style" class="form-control"><option value="dark" <?= $values['sidebar_style'] === 'dark' ? 'selected' : '' ?>>Dark</option><option value="light" <?= $values['sidebar_style'] === 'light' ? 'selected' : '' ?>>Light</option></select></div></div></div>
<div class="form-group"><label>Receipt header</label><textarea name="receipt_header" class="form-control" rows="2" maxlength="500"><?= e($values['receipt_header']) ?></textarea></div><div class="form-group"><label>Receipt footer</label><textarea name="receipt_footer" class="form-control" rows="2" maxlength="500"><?= e($values['receipt_footer']) ?></textarea></div>
</div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-save"></i> Save settings</button></div></form></div></div></section></div>
<?php include '../../includes/footer.php'; ?>
