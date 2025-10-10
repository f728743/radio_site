<?php
require_once '../config/app.php';
require_once '../classes/RadioStationManager.php';

$manager = new RadioStationManager();
$page = max(1, intval($_GET['page'] ?? 1));
$duplicateGroups = $manager->getDuplicateGroups($page);
$totalGroups = $manager->getTotalDuplicateGroups();
$totalPages = ceil($totalGroups / $manager->getItemsPerPage());

include 'templates/header.php';
include 'templates/duplicate_groups.php';
include 'templates/footer.php';
?>