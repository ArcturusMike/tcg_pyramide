<?php
date_default_timezone_set('Europe/Berlin');

$file = 'data.json';
$data = ["players" => [], "matches" => []];
if (file_exists($file)) {
    $data = json_decode(file_get_contents($file), true);
}

$players = $data['players'] ?? [];
$matches = $data['matches'] ?? [];

// Offene Forderungen und Farben
$openMatches = [];
$colors = ['#fff3cd', '#d4edda', '#f8d7da', '#ffe5b4', '#d6c3ff'];
$playerColors = [];

$colorIndex = 0;
foreach ($matches as $m) {
    if (($m['score'] ?? "") === "") {
        $color = $colors[$colorIndex % count($colors)];
        $openMatches[] = $m;
        $playerColors[$m['challenger']] = $color;
        $playerColors[$m['opponent']] = $color;
        $colorIndex++;
    }
}

// Dynamische Berechnung der benötigten Reihen
$player_count = count($players);
$rows = [];
$total_slots = 0;
$row_number = 1;
while ($total_slots < $player_count) {
    $rows[] = $row_number;
    $total_slots += $row_number;
    $row_number++;
}

// Letztes Änderungsdatum
$lastUpdate = null;
foreach ($matches as $m) {
    if (!empty($m['timestamp'])) {
        $lastUpdate = max($lastUpdate ?? $m['timestamp'], $m['timestamp']);
    }
}

// Ergebnisse nach Timestamp sortieren (neueste zuerst)
usort($matches, function($a, $b) {
    return strtotime($b['timestamp'] ?? 0) <=> strtotime($a['timestamp'] ?? 0);
});
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Forderungspyramide</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .pyramid-container {
        overflow-x: auto;
        padding-bottom: 10px;
        width: 100%;
    }
    .pyramid {
        display: table;       /* sorgt dafür, dass margin auto wirkt */
        margin: 0 auto;       /* zentriert, solange sie in den Bildschirm passt */
        white-space: nowrap;  /* keine Umbrüche */
    }
    .pyramid .row { 
        justify-content: center; 
        margin: 5px 0; 
    }
    .slot {
      position: relative;
      background: #e7f1ff;
      border: 1px solid #8aa9d6;
      padding: 15px 25px;
      margin: 0 8px;
      border-radius: 6px;
      min-width: 200px;
      min-height: 56px; /* Fixhöhe */
      text-align: center;
      font-weight: bold;
      color: #0d1b2a;
      box-shadow:
        0 4px 6px rgba(0, 0, 0, 0.25),
        4px 6px 10px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.6);
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .slot-number {
      position: absolute;
      top: 4px;
      left: 6px;
      font-size: 0.8rem;
      font-weight: bold;
      color: #adb5bd;
      opacity: 0.5;
    }
    .status-muted {
      position: absolute;
      bottom: 4px;
      left: 0;
      width: 100%;
      text-align: center;
      font-size: 0.75rem;
      color: #6c757d;
      opacity: 0.8;
      font-weight: normal;
    }
</style>
</head>
<body class="bg-light">
<div class="container py-4">
<h1 class="text-center mb-2">Forderungspyramide</h1>

<?php if ($lastUpdate): ?>
    <p class="text-center text-muted">Stand: <?= date("d.m.Y H:i", strtotime($lastUpdate)) ?></p>
<?php endif; ?>
</div>

<!-- Vollbreiter Container nur für die Pyramide -->
<div class="pyramid-container">
    <div class="pyramid mb-5">
    <?php
    $player_index = 0;
    $slot_number = 1;
    foreach ($rows as $row_slots) {
        echo "<div class='d-flex justify-content-center mb-2'>";
        for ($i = 0; $i < $row_slots; $i++) {
            $slot = $players[$player_index] ?? null;
            $slot_player = $slot['name'] ?? "–";
            $isFree = $slot['freigestellt'] ?? false;
            
            // Hintergrundfarbe
            if ($isFree) {
                $bgColor = "#e0e0e0"; // blassgrau für freigestellte Spieler
            } else {
                $bgColor = $playerColors[$slot_player] ?? "#e7f1ff"; // Standard
            }

            echo "<div class='slot' style='background-color: $bgColor'>
                    <span class='slot-number'>$slot_number</span>"
                    . htmlspecialchars($slot_player);
            if ($isFree) {
                echo "<div class='status-muted'>freigestellt</div>";
            }
            echo "</div>";

            $player_index++;
            $slot_number++;
        }
        echo "</div>";
    }
    ?>
    </div>
</div>

<div class="container">
<div class="mb-4">
<h3>Offene Forderungen</h3>
<ul class="list-group">
<?php
foreach ($openMatches as $entry) {
    echo "<li class='list-group-item'>" . htmlspecialchars($entry['challenger']) . " fordert " . htmlspecialchars($entry['opponent']) . "</li>";
}
?>
</ul>
</div>

<div>
<h3>Letzte Ergebnisse</h3>
<ul class="list-group">
<?php
foreach ($matches as $entry) {
    if (!empty($entry['score'])) {
        echo "<li class='list-group-item'>" 
            . htmlspecialchars($entry['challenger']) 
            . " vs. " 
            . htmlspecialchars($entry['opponent']) 
            . " → " 
            . htmlspecialchars($entry['score']) 
            . "</li>";
    }
}
?>
</ul>
</div>

<div class="text-center mt-4">
<a class="btn btn-primary" href="admin.php">Zum Admin-Formular</a>
</div>
</div>
</body>
</html>
