<?php 
use App\Csrf; 

// --- Mini Markdown Parser für IT-Doku ---
function parseWiki($text) {
    $text = htmlspecialchars($text);
    // Code Blöcke ```...```
    $text = preg_replace('/```(.*?)```/s', '<pre style="background:#2d2d2d; color:#f8f8f2; padding:10px; border-radius:4px; overflow-x:auto;"><code>$1</code></pre>', $text);
    // Inline Code `...`
    $text = preg_replace('/`(.*?)`/', '<code style="background:#eee; padding:2px 5px; border-radius:3px; font-family:monospace; color:#d63384;">$1</code>', $text);
    // Fett **...**
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    // Überschriften ## ...
    $text = preg_replace('/## (.*)/', '<h3 style="margin-top:15px; border-bottom:1px solid #eee; padding-bottom:5px;">$1</h3>', $text);
    // Listen - ...
    $text = preg_replace('/^\- (.*)/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*<\/li>)/s', '<ul style="padding-left:20px;">$1</ul>', $text);
    // Links [Text](Url)
    $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" style="color:#0056b3; text-decoration:underline;">$1</a>', $text);
    // Zeilenumbrüche
    $text = nl2br($text);
    return $text;
}

// Kategorien sammeln für Filter
$categories = [];
foreach($entries as $e) { $categories[$e['category']] = true; }
$categories = array_keys($categories);
sort($categories);
?>

<style>
.wiki-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
.wiki-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
.wiki-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: #ccc; }
.wiki-header { padding: 15px; border-bottom: 1px solid #f0f0f0; background: #fcfcfc; display: flex; justify-content: space-between; align-items: start; }
.wiki-cat { font-size: 0.7em; text-transform: uppercase; font-weight: 700; color: #555; background: #e9ecef; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px; }
.wiki-title { margin: 5px 0 0 0; font-size: 1.1rem; color: #0056b3; font-weight: 600; }
.wiki-body { padding: 20px; font-size: 0.95rem; color: #444; line-height: 1.6; flex: 1; }
.wiki-footer { padding: 10px 15px; background: #f8f9fa; border-top: 1px solid #eee; font-size: 0.8rem; color: #888; display: flex; justify-content: space-between; align-items: center; }
.btn-wiki-action { color: #666; text-decoration: none; padding: 4px 8px; border-radius: 4px; transition: background 0.2s; }
.btn-wiki-action:hover { background: #e2e6ea; color: #333; }
</style>

<section class="dash" style="max-width:1400px; margin:0 auto; padding:20px;">
    
    <header class="dash-header">
        <div class="dash-title">
            <h2>Wiki: <?= htmlspecialchars($c['name']) ?></h2>
            <div class="muted">Dokumentation & Wissensdatenbank</div>
        </div>
        <div class="actions">
            <a href="?route=customer_view&id=<?= $c['id'] ?>" class="btn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> Zurück
            </a>
            <?php if(!isset($entry)): ?>
            <button onclick="document.getElementById('wiki-editor').style.display='block'; window.scrollTo(0,0);" class="btn-primary" style="padding:8px 15px; background:#0056b3; color:white; border:none; border-radius:4px; cursor:pointer;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="vertical-align:middle; margin-right:5px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Neuer Eintrag
            </button>
            <?php endif; ?>
        </div>
    </header>

    <div id="wiki-editor" class="content-card" style="display:<?= isset($entry)?'block':'none' ?>; margin-bottom:40px; border-left:5px solid #0056b3;">
        <div class="card-head"><h3><?= isset($entry) ? 'Eintrag bearbeiten' : 'Neuen Eintrag erstellen' ?></h3></div>
        <form method="post" action="?route=wiki_save" style="padding:20px;">
            <?= Csrf::field() ?>
            <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
            <?php if(isset($entry)): ?><input type="hidden" name="id" value="<?= $entry['id'] ?>"><?php endif; ?>
            
            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; margin-bottom:20px;">
                <div>
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Titel</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($entry['title']??'') ?>" placeholder="z.B. Router Konfiguration" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Kategorie</label>
                    <input type="text" name="category" list="cat-list" value="<?= htmlspecialchars($entry['category']??'Allgemein') ?>" placeholder="z.B. Netzwerk" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    <datalist id="cat-list">
                        <?php foreach($categories as $cat): ?><option value="<?= htmlspecialchars($cat) ?>"><?php endforeach; ?>
                        <option value="Netzwerk"><option value="Server"><option value="Drucker"><option value="Software"><option value="Zugangsdaten">
                    </datalist>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                    <label style="font-weight:bold;">Inhalt</label>
                    <small style="color:#666; font-size:0.85em;">Markdown unterstützt: **fett**, `code`, ## Titel</small>
                </div>
                <textarea name="content" rows="12" placeholder="Beschreibe hier die Konfiguration oder Anleitung..." required style="width:100%; padding:15px; border:1px solid #ccc; border-radius:4px; font-family:monospace; line-height:1.5;"><?= htmlspecialchars($entry['content']??'') ?></textarea>
            </div>

            <div style="text-align:right;">
                <a href="?route=customer_wiki&id=<?= $c['id'] ?>" style="margin-right:15px; text-decoration:none; color:#555;">Abbrechen</a>
                <button type="submit" class="btn-primary" style="padding:10px 25px; border:none; border-radius:4px; cursor:pointer; background:#0056b3; color:white;">Speichern</button>
            </div>
        </form>
    </div>

    <?php if(!isset($entry) && !empty($entries)): ?>
    <div style="margin-bottom:25px; display:flex; gap:15px;">
        <input type="text" id="wikiSearch" placeholder="🔍 Wiki durchsuchen..." onkeyup="filterWiki()" style="padding:10px 15px; border:1px solid #ddd; border-radius:40px; flex:1; box-shadow:0 2px 5px rgba(0,0,0,0.03);">
        <select id="wikiCatFilter" onchange="filterWiki()" style="padding:10px 15px; border:1px solid #ddd; border-radius:40px; background:#fff; cursor:pointer;">
            <option value="">Alle Kategorien</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if(empty($entries) && !isset($entry)): ?>
        <div class="content-card" style="text-align:center; padding:50px; color:#888;">
            <div style="font-size:3em; margin-bottom:10px;">📚</div>
            <h3>Noch keine Einträge</h3>
            <p>Erstelle die erste Dokumentation für diesen Kunden.</p>
        </div>
    <?php elseif(!isset($entry)): ?>
        <div class="wiki-grid">
            <?php foreach($entries as $w): ?>
            <div class="wiki-card" data-title="<?= strtolower($w['title'].' '.$w['content']) ?>" data-cat="<?= $w['category'] ?>">
                <div class="wiki-header">
                    <div>
                        <span class="wiki-cat"><?= htmlspecialchars($w['category']) ?></span>
                        <h3 class="wiki-title"><?= htmlspecialchars($w['title']) ?></h3>
                    </div>
                </div>
                <div class="wiki-body">
                    <?= parseWiki($w['content']) ?>
                </div>
                <div class="wiki-footer">
                    <div>
                        <span title="Erstellt von <?= htmlspecialchars($w['user_name']??'Unknown') ?>">
                            <?= date('d.m.Y', strtotime($w['updated_at'])) ?>
                        </span>
                    </div>
                    <div>
                        <a href="?route=customer_wiki&id=<?= $c['id'] ?>&edit=<?= $w['id'] ?>" class="btn-wiki-action" title="Bearbeiten">✏️</a>
                        <a href="?route=wiki_delete&id=<?= $w['id'] ?>" onclick="return confirm('Eintrag unwiderruflich löschen?')" class="btn-wiki-action" title="Löschen" style="color:#d9534f;">🗑️</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>

<script>
function filterWiki() {
    const term = document.getElementById('wikiSearch').value.toLowerCase();
    const cat  = document.getElementById('wikiCatFilter').value;
    const cards = document.querySelectorAll('.wiki-card');
    
    cards.forEach(card => {
        const textMatch = card.dataset.title.includes(term);
        const catMatch  = cat === '' || card.dataset.cat === cat;
        card.style.display = (textMatch && catMatch) ? 'flex' : 'none';
    });
}
</script>