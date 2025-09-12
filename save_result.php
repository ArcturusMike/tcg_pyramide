<?php
date_default_timezone_set('Europe/Berlin');

$file = 'data.json';
$data = ["players" => [], "matches" => []];
if (file_exists($file)) {
    $data = json_decode(file_get_contents($file), true);
}

$players = $data['players'] ?? [];
$matches = $data['matches'] ?? [];

// === 0. Revert-Funktion ===
if (!empty($_POST['revert'])) {
    if (file_exists('data_backup.json')) {
        copy('data_backup.json', 'data.json');
    }
    header("Location: admin.php");
    exit;
}

// === Backup erstellen vor Änderungen ===
if (file_exists($file)) {
    copy($file, 'data_backup.json');
}

// === 1. Forderung löschen ===
if (isset($_POST['delete_match'])) {
    $index = (int)$_POST['delete_match'];
    if (isset($matches[$index])) {
        unset($matches[$index]);
        $matches = array_values($matches); // Indizes neu sortieren
    }
}

// === 2. Neuen Spieler hinzufügen ===
elseif (!empty($_POST['new_player'])) {
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

// === 3. Spieler freistellen / aktivieren ===
elseif (!empty($_POST['toggle_player'])) {
    $toggleName = $_POST['toggle_player'];
    foreach ($players as &$p) {
        if ($p['name'] === $toggleName) {
            $p['freigestellt'] = !$p['freigestellt'];
            break;
        }
    }
    unset($p);
}

// === 4. Neue Forderung ohne Ergebnis eintragen ===
elseif (!empty($_POST['challenger']) && !empty($_POST['opponent']) && empty($_POST['score'])) {
    $challenger = $_POST['challenger'];
    $opponent   = $_POST['opponent'];

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

// === 5. Ergebnis für offene Forderung eintragen ===
elseif (!empty($_POST['winner']) && !empty($_POST['score']) && !empty($_POST['challenger']) && !empty($_POST['opponent'])) {
    $challenger = $_POST['challenger'];
    $opponent   = $_POST['opponent'];
    $winner     = $_POST['winner'];
    $scoreInput = trim($_POST['score']);

    // Score korrekt umdrehen
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

    // Offene Forderung aktualisieren
    foreach ($matches as &$match) {
        if ($match['challenger'] === $challenger && $match['opponent'] === $opponent && ($match['score'] ?? "") === "") {
            $match['score'] = $scoreCorrect;
            $match['winner'] = $winner; // Gewinner speichern
            $match['timestamp'] = date("Y-m-d H:i:s");
            break;
        }
    }
    unset($match);

    // Pyramide aktualisieren
    $posWinner = array_search($winner, array_column($players, 'name'));
    $loser = ($winner === $challenger) ? $opponent : $challenger;
    $posLoser = array_search($loser, array_column($players, 'name'));

    if ($posWinner !== false && $posLoser !== false && $posWinner > $posLoser) {
        $winnerData = $players[$posWinner];
        array_splice($players, $posWinner, 1);
        array_splice($players, $posLoser, 0, [$winnerData]);
    }
}

// === Speichern ===
$data['players'] = $players;
$data['matches'] = $matches;
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header("Location: admin.php");
exit;
?>
