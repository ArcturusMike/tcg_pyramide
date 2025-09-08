<?php
date_default_timezone_set('Europe/Berlin');

$file = 'data.json';
$data = ["players" => [], "matches" => []];
if (file_exists($file)) {
    $data = json_decode(file_get_contents($file), true);
}

$players = $data['players'] ?? [];
$matches = $data['matches'] ?? [];

// Neuen Spieler hinzufügen
if (!empty($_POST['new_player'])) {
    $newPlayer = trim($_POST['new_player']);
    if ($newPlayer !== '') {
        $exists = false;
        foreach ($players as $p) {
            if ($p['name'] === $newPlayer) { $exists = true; break; }
        }
        if (!$exists) {
            $players[] = ["name" => $newPlayer, "freigestellt" => false];
        }
    }
}
// Spieler freistellen / aktivieren
elseif (!empty($_POST['toggle_player'])) {
    foreach ($players as &$p) {
        if ($p['name'] === $_POST['toggle_player']) {
            $p['freigestellt'] = !$p['freigestellt'];
            break;
        }
    }
    unset($p);
}
// Neue Forderung ohne Ergebnis
elseif (!empty($_POST['challenger']) && !empty($_POST['opponent']) && empty($_POST['score'])) {
    $challenger = $_POST['challenger'];
    $opponent   = $_POST['opponent'];

    // Schutz: freigestellte Spieler dürfen nicht fordern/gefodert werden
    $byName = fn($name) => array_values(array_filter($players, fn($pl) => $pl['name'] === $name))[0] ?? null;
    $chP = $byName($challenger);
    $opP = $byName($opponent);
    if ($chP && $opP && !$chP['freigestellt'] && !$opP['freigestellt']) {
        $matches[] = [
            "challenger" => $challenger,
            "opponent"   => $opponent,
            "score"      => "",
            "timestamp"  => date("Y-m-d H:i:s")
        ];
    }
}
// Ergebnis für offene Forderung
elseif (!empty($_POST['winner']) && !empty($_POST['score']) && !empty($_POST['challenger']) && !empty($_POST['opponent'])) {
    $challenger = $_POST['challenger'];
    $opponent   = $_POST['opponent'];
    $winner     = $_POST['winner'];
    $scoreInput = trim($_POST['score']);

    // Score korrekt umdrehen für mehrere Sätze
    $sets = preg_split('/\s+/', $scoreInput);
    $sets_correct = [];
    foreach ($sets as $s) {
        if (strpos($s, ":") !== false) {
            list($a, $b) = explode(":", $s, 2);
            if ($winner === $challenger) {
                $sets_correct[] = trim($a) . ":" . trim($b);
            } else {
                $sets_correct[] = trim($b) . ":" . trim($a);
            }
        } else {
            $sets_correct[] = $s;
        }
    }
    $scoreCorrect = implode(" ", $sets_correct);

    // Score in offener Forderung aktualisieren
    foreach ($matches as &$match) {
        if ($match['challenger'] === $challenger && $match['opponent'] === $opponent && ($match['score'] ?? "") === "") {
            $match['score'] = $scoreCorrect;
            $match['timestamp'] = date("Y-m-d H:i:s");
            break;
        }
    }
    unset($match);

    // Pyramide aktualisieren: Gewinner rückt auf Platz des Verlierers, alle dazwischen rücken einen nach hinten
    $posWinner = array_search($winner, array_column($players, 'name'));
    $loser = ($winner === $challenger) ? $opponent : $challenger;
    $posLoser = array_search($loser, array_column($players, 'name'));

    if ($posWinner !== false && $posLoser !== false && $posWinner > $posLoser) {
        $winnerData = $players[$posWinner];
        array_splice($players, $posWinner, 1);                  // Gewinner entfernen
        array_splice($players, $posLoser, 0, [$winnerData]);    // Gewinner auf Platz des Verlierers einsetzen
    }
}

// Speichern
$data['players'] = $players;
$data['matches'] = $matches;
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header("Location: admin.php");
exit;
?>