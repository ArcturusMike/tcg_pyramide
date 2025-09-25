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

// Letztes Änderungsdatum aus stand.json
$lastUpdate = null;
if (file_exists('stand.json')) {
    $standData = json_decode(file_get_contents('stand.json'), true);
    $lastUpdate = $standData['lastUpdate'] ?? null;
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
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Forderungspyramide</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .pyramid-container {
        overflow-x: auto;
        padding-bottom: 10px;
        width: 100%;
    }
    .pyramid {
        display: table;
        margin: 0 auto;
        white-space: nowrap;
    }
    /* Auf kleineren Bildschirmen Pyramide verkleinern */
    @media (max-width: 760px) {
        .pyramid {
            transform: scale(0.8);
            margin-left: -130px;
            margin-top: -40px;
        }
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
      min-height: 56px;
      text-align: center;
      font-weight: bold;
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
    .list-group-item {
  white-space: nowrap;       /* kein Zeilenumbruch */
  overflow-x: auto;          /* horizontal scrollen erlauben */
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
            $status = $slot['status'] ?? "aktiv"; // neu: status-Feld

            if ($status === "freigestellt") {
                $bgColor = "#e0e0e0";
                $textColor = "inherit";
            } elseif ($status === "überspringen") {
                $bgColor = "#e0e0e0";
                $textColor = "grey";
            } else {
                $bgColor = $playerColors[$slot_player] ?? "#e7f1ff";
                $textColor = "#0d1b2a";
            }


            echo "<div class='slot' style='background-color: $bgColor; color:  $textColor'>
                    <span class='slot-number'>$slot_number</span>"
                    . htmlspecialchars($slot_player);

            if ($status === "freigestellt") {
                echo "<div class='status-muted'>freigestellt</div>";
            } elseif ($status === "überspringen") {
                echo "<div class='status-muted'>überspringen</div>";
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
    echo "<li class='list-group-item'>"
        . "<span class='badge bg-warning text-dark me-2'>" . htmlspecialchars($entry['challenger']) . "</span>"
        . " fordert "
        . "<span class='badge bg-warning text-dark ms-2'>" . htmlspecialchars($entry['opponent']) . "</span>"
        . "</li>";
}
?>
</ul>
</div>

<div>
<h3>Ergebnisse</h3>
<div class="table-responsive">
<table class="table table-striped table-bordered align-middle">
    <thead class="table-light">
        <tr>
            <th>Forderer</th>
            <th>Geforderter</th>
            <th>Ergebnis</th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach ($matches as $entry) {
        if (!empty($entry['score'])) {
            $winner = $entry['winner'] ?? null;
            $challengerClass = ($winner === $entry['challenger']) ? "bg-success" : "bg-danger";
            $opponentClass   = ($winner === $entry['opponent'])   ? "bg-success" : "bg-danger";

            echo "<tr>"
                . "<td><span class='badge $challengerClass'>" . htmlspecialchars($entry['challenger']) . "</span></td>"
                . "<td><span class='badge $opponentClass'>" . htmlspecialchars($entry['opponent']) . "</span></td>"
                . "<td>" . htmlspecialchars($entry['score']) . "</td>"
                . "</tr>";
        }
    }
    ?>
    </tbody>
</table>
</div>
</div>

<div>
<h3>Regelauszug</h3>
<ul class="list-group">
    <li class="list-group-item">Gefordert werden darf jeder, der in der Pyramide <b>3 Plätze vor einem</b> oder <b>eine Reihe darüber rechts oben vor einem</b> steht.<br>Freigestellte Spieler werden dabei mitgezählt, "überspringbare" Spieler nicht. Ein Spieler ist nur "überspringbar", wenn er für eine längere Zeit ausfällt.</li>
    <li class="list-group-item">Die Zeit zwischen Forderung und Spiel darf <b>nicht mehr als 7 Tage</b> betragen.<br>Die Spiele <b>müssen auf Platz 4</b> gespielt und reserviert werden, damit die Zuschauer informiert sind. <b>Spiele auf anderen Plätzen werden nicht gewertet!</b></li>
    <li class="list-group-item">Gespielt wird auf <b>2 Gewinnsätze</b>. Dritter Satz ausschließlich <b>Champions-Tie-Break</b>.</li>
    <li class="list-group-item">Der <b>Gewinner</b> darf sofort selber weiterfordern, aber erst nach 3 Tagen wieder gefordert werden.</li>
    <li class="list-group-item">Der <b>Verlierer</b> darf erst nach Ablauf von 7 Tagen wieder fordern, aber darf sofort gefordert werden.</li>
    <li class="list-group-item">Gewinnt der Herausforderer, so rückt er <b>auf den Platz des Verlierers</b>. Der Geforderte <b>fällt um einen Platz zurück</b>, alle dazwischen liegenden Spieler <b>ebenfalls</b>.</li>
</ul>
</div>

</div>
</body>
</html>
