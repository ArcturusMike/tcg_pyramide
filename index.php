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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="style.css">
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
            $status = $slot['status'] ?? "aktiv";

            if ($status === "freigestellt") {
                $bgColor = "#e0e0e0";
                $textColor = "grey";
            } else {
                $bgColor = $playerColors[$slot_player] ?? "#e7f1ff";
                $textColor = "#0d1b2a";
            }

            echo "<div 
                    class='slot' 
                    data-slot-number='$slot_number'
                    data-status='" . htmlspecialchars($status) . "'
                    style='background-color: $bgColor; color: $textColor; cursor: pointer;'
                  >
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

<script>
document.querySelectorAll('.slot').forEach(function(slot) {

    slot.addEventListener('click', function() {

        // Prüfen, ob dieser Slot bereits ausgewählt ist
        const alreadySelected = this.classList.contains('highlight-herausforderer');

        // Alle bisherigen Hervorhebungen entfernen
        document.querySelectorAll('.slot.highlight-challenge').forEach(function(s) {
            s.classList.remove('highlight-challenge');
        });

        document.querySelectorAll('.slot.highlight-herausforderer').forEach(function(s) {
            s.classList.remove('highlight-herausforderer');
        });

        // Wenn der bereits ausgewählte Slot erneut geklickt wurde:
        // Hervorhebungen sind bereits entfernt -> nichts weiter tun
        if (alreadySelected) {
            return;
        }

        // Freigestellte Spieler können nicht ausgewählt werden
        if (this.dataset.status === 'freigestellt') {
            return;
        }

        // Ausgewählten Slot hervorheben
        this.classList.add('highlight-herausforderer');

        const clickedNumber = parseInt(this.dataset.slotNumber);

        const slots = Array.from(document.querySelectorAll('.slot'));

        // Nur aktive Slots berücksichtigen
        const activeSlots = slots.filter(function(s) {
            return s.dataset.status !== 'freigestellt';
        });

        // Position des angeklickten Spielers innerhalb der aktiven Spieler
        const activeIndex = activeSlots.findIndex(function(s) {
            return parseInt(s.dataset.slotNumber) === clickedNumber;
        });

        if (activeIndex === -1) {
            return;
        }

        // Die 3 vorherigen aktiven Spieler markieren
        for (let i = 1; i <= 3; i++) {
            const previousSlot = activeSlots[activeIndex - i];

            if (previousSlot) {
                previousSlot.classList.add('highlight-challenge');
            }
        }

        // ------------------------------------------------
        // Slot rechts oben markieren – hardcodierte Zuordnung
        // ------------------------------------------------

        const rechtsObenZuordnung = {
            11: 7,
            12: 8,
            13: 9,
            14: 10,
            // 15 hat keinen rechts oben
            16: 11,
            17: 12,
            18: 13,
            19: 14,
            20: 15,
            // 21 hat keinen rechts oben
            22: 16,
            23: 17,
            24: 18,
            25: 19,
            26: 20,
            27: 21
        };

        const aboveNumber = rechtsObenZuordnung[clickedNumber];

        if (aboveNumber) {

            const aboveSlot = document.querySelector(
                '.slot[data-slot-number="' + aboveNumber + '"]'
            );

            // Freigestellte Spieler werden nicht markiert
            if (
                aboveSlot &&
                aboveSlot.dataset.status !== 'freigestellt'
            ) {
                aboveSlot.classList.add('highlight-challenge');
            }
        }
    });
});
</script>




<div class="container">
<div class="mb-4">
<h3>Offene Forderungen</h3>
<ul class="list-group">
<?php
foreach ($openMatches as $entry) {
    echo "<li class='list-group-item'>"
        . "<span class='badge badge-eigen bg-warning text-dark me-2'>" . htmlspecialchars($entry['challenger']) . "</span>"
        . " fordert "
        . "<span class='badge badge-eigen bg-warning text-dark ms-2 me-2'>" . htmlspecialchars($entry['opponent']) . "</span>"
        . " |&nbsp; zu spielen bis "
        . date("d.m.Y", strtotime($entry['timestamp'] . " +7 days"))
        . "</li>";
}
?>
</ul>
</div>

<div>
<h3>Ergebnisse</h3>
<p style="font-size: 9pt;">Ein Klick auf den Namen verrät, wie lange eine Forderpause eingelegt werden muss!</p>
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
$resultIndex = 0;

foreach ($matches as $entry) {
    if (!empty($entry['score'])) {
        $winner = $entry['winner'] ?? null;

        $challengerIsWinner = ($winner === $entry['challenger']);

        $challengerClass = $challengerIsWinner ? "bg-success" : "bg-danger";
        $opponentClass   = $challengerIsWinner ? "bg-danger" : "bg-success";

        $challengerDays = $challengerIsWinner ? 2 : 5;
        $opponentDays   = $challengerIsWinner ? 5 : 2;

        $challengerSperreId = "spiel" . $resultIndex . "a";
        $opponentSperreId   = "spiel" . $resultIndex . "b";

        $challengerDate = date(
            "d.m.Y",
            strtotime($entry['timestamp'] . " +" . $challengerDays . " days")
        );

        $opponentDate = date(
            "d.m.Y",
            strtotime($entry['timestamp'] . " +" . $opponentDays . " days")
        );

        echo "<tr>"
            . "<td>"
            . "<span class='badge badge-eigen $challengerClass' data-bs-toggle='collapse' data-bs-target='#$challengerSperreId' style='cursor: pointer;'>"
            . htmlspecialchars($entry['challenger'])
            . "</span> "
            . "<span id='$challengerSperreId' class='collapse'>"
            . "darf ab $challengerDate wieder selber fordern."
            . "</span>"
            . "</td>"

            . "<td>"
            . "<span class='badge badge-eigen $opponentClass' data-bs-toggle='collapse' data-bs-target='#$opponentSperreId' style='cursor: pointer;'>"
            . htmlspecialchars($entry['opponent'])
            . "</span> "
            . "<span id='$opponentSperreId' class='collapse'>"
            . "darf ab $opponentDate wieder selber fordern."
            . "</span>"
            . "</td>"

            . "<td>" . htmlspecialchars($entry['score']) . "</td>"
            . "</tr>";

        $resultIndex++;
    }
}
?>
    </tbody>
</table>
</div>
</div>

<br>
<div>
<h3>Wichtigste Regeln</h3>
<ul class="list-group">
    <li class="list-group-item">Gefordert werden darf jeder, der in der Pyramide <b>3 Felder vor einem</b> oder <b>eine Reihe darüber rechts oben vor einem</b> steht.<br><b>Freigestellte Spieler können dabei in der Zählung ausgelassen werden</b></li>
    <li class="list-group-item">Die Zeit zwischen Forderung und Spiel darf <b>nicht mehr als 7 Tage</b> betragen.<br>Die Spiele <b>müssen auf Platz 4</b> gespielt und reserviert werden, damit die Zuschauer informiert sind. <b>Spiele auf anderen Plätzen werden nicht gewertet!</b></li>
    <li class="list-group-item">Gespielt wird auf <b>2 Gewinnsätze</b>. Dritter Satz ausschließlich <b>Champions-Tie-Break</b>.</li>
    <li class="list-group-item">Der <b>Gewinner darf erst am übernächsten Tag erneut selber fordern</b>, aber sofort von einem anderen Spieler gefordert werden.</li>
    <li class="list-group-item">Der <b>Verlierer darf erst am 5. Tag nach der Niederlage erneut selber fordern</b>, aber sofort von einem anderen Spieler gefordert werden.</li>
    <li class="list-group-item">Gewinnt der Herausforderer, so rückt er <b>auf den Platz des Verlierers</b>. Der Geforderte <b>fällt um einen Platz zurück</b>, alle dazwischen liegenden Spieler ebenfalls.</li>
</ul>
</div>

</div>
</body>
</html>
