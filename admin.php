<?php
$file = 'data.json';
$data = ["players" => [], "matches" => []];
if (file_exists($file)) {
    $data = json_decode(file_get_contents($file), true);
}
$players = $data['players'] ?? [];
$matches = $data['matches'] ?? [];

// Spieler in offenen Forderungen
$activePlayers = [];
foreach ($matches as $m) {
    if ($m['score'] === "") {
        $activePlayers[] = $m['challenger'];
        $activePlayers[] = $m['opponent'];
    }
}

// Nur Spieler, die frei sind (nicht aktiv in Forderungen + nicht freigestellt)
$availablePlayers = [];
foreach ($players as $p) {
    if (!$p['freigestellt'] && !in_array($p['name'], $activePlayers)) {
        $availablePlayers[] = $p['name'];
    }
}
$availablePlayersSorted = $availablePlayers;
sort($availablePlayersSorted, SORT_STRING | SORT_FLAG_CASE);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin – Forderungspyramide</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f8f9fa; }
    .open-match-card { padding: 10px; margin-bottom: 10px; border-radius: 8px; background: #fff; border: 1px solid #dee2e6; }
    .open-match-card .row { align-items: center; }
</style>
</head>
<body>
<div class="container py-4">
<h1 class="text-center mb-4">Admin – Forderungen & Spieler</h1>

<div class="row">
  <!-- Linke Spalte -->
  <div class="col-lg-5">
    <h4>Neue Forderung eintragen</h4>
    <form method="post" action="save_result.php" class="mb-4 p-3 bg-white border rounded">
        <div class="mb-3">
            <label class="form-label">Fordernder Spieler</label>
            <select class="form-select" name="challenger" id="challengerSelect" required onchange="updateOpponentOptions()">
                <?php foreach ($availablePlayersSorted as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Geforderter Spieler</label>
            <select class="form-select" name="opponent" id="opponentSelect" required></select>
        </div>
        <button type="submit" class="btn btn-primary w-100">Forderung eintragen</button>
    </form>

    <h4>Spieler freistellen / aktivieren</h4>
    <form method="post" action="save_result.php" class="mb-4 p-3 bg-white border rounded">
        <div class="mb-2">
            <label class="form-label">Spieler auswählen</label>
            <select class="form-select" name="toggle_player" required>
                <?php foreach ($players as $p): ?>
                    <option value="<?= htmlspecialchars($p['name']) ?>">
                        <?= htmlspecialchars($p['name']) ?> <?= $p['freigestellt'] ? '(freigestellt)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-warning w-100">Status wechseln</button>
    </form>

    <h4>Neuen Spieler hinzufügen</h4>
    <form method="post" action="save_result.php" class="p-3 bg-white border rounded mb-4">
        <div class="mb-2">
            <label class="form-label">Spielername</label>
            <input class="form-control" type="text" name="new_player" placeholder="Neuer Spielername" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Spieler hinzufügen</button>
    </form>

    <h4>Letzten Schritt zurücksetzen</h4>
    <form method="post" action="save_result.php" class="mb-4 p-3 bg-white border rounded">
        <button type="submit" name="revert" value="1" class="btn btn-danger w-100">Letzten Schritt zurücksetzen</button>
    </form>

  </div>

  <!-- Rechte Spalte -->
  <div class="col-lg-7">
    <h4>Offene Forderungen</h4>
    <?php foreach ($matches as $entry): ?>
      <?php if ($entry['score'] === ""): ?>
        <form method="post" action="save_result.php" class="open-match-card">
            <div class="mb-1"><strong><?= htmlspecialchars($entry['challenger']) ?> fordert <?= htmlspecialchars($entry['opponent']) ?></strong></div>
            <div class="row g-2">
                <div class="col-5">
                    <label class="form-label small">Sieger</label>
                    <select class="form-select form-select-sm" name="winner" required>
                        <option value="<?= htmlspecialchars($entry['challenger']) ?>"><?= htmlspecialchars($entry['challenger']) ?></option>
                        <option value="<?= htmlspecialchars($entry['opponent']) ?>"><?= htmlspecialchars($entry['opponent']) ?></option>
                    </select>
                </div>
                <div class="col-5">
                    <label class="form-label small">Ergebnis</label>
                    <input class="form-control form-control-sm" type="text" name="score" placeholder="z.B. 6:3 7:6" required>
                </div>
                <div class="col-2 d-flex align-items-end">
                    <input type="hidden" name="challenger" value="<?= htmlspecialchars($entry['challenger']) ?>">
                    <input type="hidden" name="opponent" value="<?= htmlspecialchars($entry['opponent']) ?>">
                    <button type="submit" class="btn btn-success w-100 btn-sm">OK</button>
                </div>
            </div>
        </form>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>

<div class="text-center mt-4">
    <a class="btn btn-secondary" href="index.php">Zurück zur Pyramide</a>
</div>

<script>
const players = <?= json_encode(array_map(fn($p) => $p['name'], $players)) ?>;
const availablePlayers = <?= json_encode($availablePlayers) ?>;

function updateOpponentOptions() {
    const challenger = document.getElementById('challengerSelect').value;
    const opponentSelect = document.getElementById('opponentSelect');
    opponentSelect.innerHTML = '';

    const challengerIndex = players.indexOf(challenger);

    let options = [];
    availablePlayers.forEach(p => {
        const idx = players.indexOf(p);
        if (idx !== -1 && idx < challengerIndex) {
            options.push(p);
        }
    });

    options.sort((a, b) => a.localeCompare(b));

    if (options.length > 0) {
        options.forEach(p => {
            const option = document.createElement('option');
            option.value = p;
            option.text = p;
            opponentSelect.appendChild(option);
        });
    } else {
        const option = document.createElement('option');
        option.text = "Keine verfügbaren Gegner";
        option.disabled = true;
        option.selected = true;
        opponentSelect.appendChild(option);
    }
}
updateOpponentOptions();
</script>
</div>
</body>
</html>
