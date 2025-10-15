<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'GAKUMON') ?></title>
  <link rel="icon" href="<?= $favicon ?? 'IMG/Logos/logo_only_white.png' ?>" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <?php if(isset($pageCSS)): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($pageCSS) ?>">
  <?php endif; ?>
  <?php if(isset($kanriCSS)): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($kanriCSS) ?>">
  <?php endif; ?>
  <?php if(isset($kanriCSS2)): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($kanriCSS2) ?>">
  <?php endif; ?>
  <?php if(isset($kanriNavCSS)): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($kanriNavCSS) ?>">
  <?php endif; ?>

</head>
<body class="d-flex flex-column min-vh-100">
    <div class="container-fluid flex-grow-1">