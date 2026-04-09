<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="<?php echo esc_attr(get_option('mdd_secondary_color', '#1A1A2E')); ?>">
<title>Cardápio de Drinks</title>
<?php MDD_PWA::render_manifest_link($GLOBALS['mdd_device'] ?? null); ?>
<link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode(get_option('mdd_tablet_font_title','Playfair Display')); ?>:wght@400;600;700&family=<?php echo urlencode(get_option('mdd_tablet_font_body','Outfit')); ?>:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
<?php
$device = $GLOBALS['mdd_device'] ?? null;
$token  = $GLOBALS['mdd_token'] ?? '';
$primary   = esc_attr(get_option('mdd_primary_color', '#C8962E'));
$secondary = esc_attr(get_option('mdd_secondary_color', '#1A1A2E'));
$accent    = esc_attr(get_option('mdd_accent_color', '#E8593C'));
$columns   = intval(get_option('mdd_tablet_columns', 2));
$columns_p = intval(get_option('mdd_tablet_columns_portrait', 1));
$font_t    = esc_attr(get_option('mdd_tablet_font_title', 'Playfair Display'));
$font_b    = esc_attr(get_option('mdd_tablet_font_body', 'Outfit'));
?>
:root{--primary:<?php echo $primary;?>;--bg:<?php echo $secondary;?>;--accent:<?php echo $accent;?>;--cols:<?php echo $columns;?>;--cols-p:<?php echo $columns_p;?>}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html,body{width:100%;height:100%;font-family:'<?php echo $font_b; ?>',sans-serif;background:var(--bg);color:#fff;overflow-x:hidden}
body{display:flex;flex-direction:column}

/* ─── Header ─── */
.header{position:sticky;top:0;z-index:50;background:var(--bg);border-bottom:1px solid rgba(255,255,255,.06);padding:16px 20px;display:flex;align-items:center;gap:16px;backdrop-filter:blur(20px)}
.header__logo{height:36px;max-width:120px;object-fit:contain}
.header__title{font-family:'<?php echo $font_t; ?>',serif;font-size:20px;font-weight:600;color:var(--primary);flex:1}
.header__quiz-btn{padding:8px 20px;background:var(--accent);color:#fff;border:none;border-radius:24px;font-family:'<?php echo $font_b; ?>',sans-serif;font-size:13px;font-weight:500;cursor:pointer;letter-spacing:.5px;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(232,89,60,.4)}50%{box-shadow:0 0 0 8px rgba(232,89,60,0)}}

/* ─── Filter Tabs ─── */
.filters{display:flex;gap:8px;padding:12px 20px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none}
.filters::-webkit-scrollbar{display:none}
.filter-tab{padding:8px 18px;border-radius:20px;border:1px solid rgba(255,255,255,.1);background:transparent;color:rgba(255,255,255,.5);font-family:'<?php echo $font_b; ?>',sans-serif;font-size:13px;font-weight:400;cursor:pointer;white-space:nowrap;transition:all .3s}
.filter-tab.active,.filter-tab:active{background:var(--primary);color:#fff;border-color:var(--primary);font-weight:500}

/* ─── Grid ─── */
.grid{display:grid;grid-template-columns:repeat(var(--cols),1fr);gap:16px;padding:16px 20px;flex:1}
@media(orientation:portrait){.grid{grid-template-columns:repeat(var(--cols-p),1fr);gap:12px;padding:12px 16px}}
@media(max-width:480px){.grid{grid-template-columns:1fr;gap:12px;padding:12px 16px}}

/* ─── Card ─── */
.card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:16px;overflow:hidden;cursor:pointer;transition:transform .3s,border-color .3s;position:relative}
.card:active{transform:scale(.97);border-color:var(--primary)}
.card__img-wrap{position:relative;padding-top:100%;overflow:hidden;background:rgba(0,0,0,.2)}
.card__img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .5s}
.card:active .card__img{transform:scale(1.05)}
.card__badge{position:absolute;top:10px;right:10px;padding:4px 10px;background:rgba(0,0,0,.6);border-radius:12px;font-size:10px;letter-spacing:1px;text-transform:uppercase;color:var(--primary);backdrop-filter:blur(8px)}
.card__body{padding:14px 16px}
.card__title{font-family:'<?php echo $font_t; ?>',serif;font-size:17px;font-weight:600;margin-bottom:4px}
.card__desc{font-size:12px;color:rgba(255,255,255,.5);line-height:1.4;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.card__price{font-size:18px;font-weight:600;color:var(--primary)}
.card__video-icon{position:absolute;bottom:10px;left:10px;width:28px;height:28px;background:rgba(0,0,0,.6);border-radius:50%;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px)}
.card__video-icon::after{content:'';border-left:8px solid #fff;border-top:5px solid transparent;border-bottom:5px solid transparent;margin-left:2px}

/* ─── Modal ─── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:200;opacity:0;pointer-events:none;transition:opacity .3s;backdrop-filter:blur(10px)}
.modal-overlay.active{opacity:1;pointer-events:auto}
.modal{position:fixed;bottom:0;left:0;right:0;max-height:92vh;z-index:201;background:var(--bg);border-radius:20px 20px 0 0;border:1px solid rgba(255,255,255,.08);border-bottom:none;transform:translateY(100%);transition:transform .4s cubic-bezier(.22,1,.36,1);overflow-y:auto;-webkit-overflow-scrolling:touch}
.modal.active{transform:translateY(0)}
.modal__handle{width:40px;height:4px;background:rgba(255,255,255,.2);border-radius:2px;margin:12px auto}
.modal__close{position:absolute;top:16px;right:16px;width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.08);border:none;color:#fff;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:10}
.modal__img-wrap{width:100%;aspect-ratio:4/3;overflow:hidden;background:#111;position:relative}
.modal__img{width:100%;height:100%;object-fit:cover}
.modal__video-wrap{position:absolute;inset:0;display:none;background:#000}
.modal__video-wrap video{width:100%;height:100%;object-fit:cover}
.modal__video-btn{position:absolute;bottom:16px;left:16px;padding:8px 16px;background:rgba(0,0,0,.7);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:20px;font-size:12px;cursor:pointer;backdrop-filter:blur(8px)}
.modal__content{padding:24px 20px 40px}
.modal__category{font-size:12px;text-transform:uppercase;letter-spacing:3px;color:var(--primary);margin-bottom:8px}
.modal__title{font-family:'<?php echo $font_t; ?>',serif;font-size:28px;font-weight:700;margin-bottom:8px}
.modal__desc{font-size:14px;color:rgba(255,255,255,.6);line-height:1.7;margin-bottom:20px}
.modal__price{font-family:'<?php echo $font_t; ?>',serif;font-size:32px;font-weight:600;color:var(--primary);margin-bottom:20px}
.modal__variants{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
.modal__variant{padding:6px 14px;border:1px solid rgba(255,255,255,.1);border-radius:8px;font-size:12px;color:rgba(255,255,255,.6)}
.modal__variant strong{color:var(--primary)}
.modal__ingredients-title{font-size:13px;text-transform:uppercase;letter-spacing:2px;color:rgba(255,255,255,.4);margin-bottom:8px}
.modal__ingredients{display:flex;gap:6px;flex-wrap:wrap}
.modal__ingredient{padding:4px 12px;background:rgba(255,255,255,.05);border-radius:16px;font-size:12px;color:rgba(255,255,255,.5)}

/* ─── Gallery Dots ─── */
.modal__gallery-dots{display:flex;gap:6px;justify-content:center;padding:10px}
.modal__gallery-dot{width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.2)}
.modal__gallery-dot.active{background:var(--primary)}

/* ─── Screensaver ─── */
.screensaver{position:fixed;inset:0;z-index:300;background:var(--bg);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:24px;opacity:0;pointer-events:none;transition:opacity .8s}
.screensaver.active{opacity:1;pointer-events:auto}
.screensaver__logo{max-width:40vw;max-height:20vh;opacity:.6}
.screensaver__text{font-size:16px;color:rgba(255,255,255,.3);letter-spacing:3px;text-transform:uppercase}
.screensaver__touch{font-size:13px;color:rgba(255,255,255,.15);margin-top:40px;animation:breathe 3s ease-in-out infinite}
@keyframes breathe{0%,100%{opacity:.15}50%{opacity:.4}}
</style>
</head>
<body>

<div class="header">
    <img class="header__logo" id="headerLogo" src="" alt="" style="display:none">
    <div class="header__title" id="headerTitle">Drinks</div>
    <button class="header__quiz-btn" id="quizBtn">Faça o Quiz ✨</button>
</div>

<div class="filters" id="filtersBar"></div>
<div class="grid" id="drinksGrid"></div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay"></div>
<div class="modal" id="modal">
    <div class="modal__handle"></div>
    <button class="modal__close" id="modalClose">&times;</button>
    <div class="modal__img-wrap">
        <img class="modal__img" id="modalImg" src="" alt="">
        <div class="modal__video-wrap" id="modalVideoWrap">
            <video id="modalVideo" playsinline></video>
        </div>
        <button class="modal__video-btn" id="modalVideoBtn" style="display:none">▶ Ver vídeo</button>
    </div>
    <div class="modal__gallery-dots" id="modalGalleryDots"></div>
    <div class="modal__content">
        <div class="modal__category" id="modalCategory"></div>
        <h2 class="modal__title" id="modalTitle"></h2>
        <p class="modal__desc" id="modalDesc"></p>
        <div class="modal__variants" id="modalVariants"></div>
        <div class="modal__price" id="modalPrice"></div>
        <div class="modal__ingredients-title" id="modalIngLabel" style="display:none">Ingredientes</div>
        <div class="modal__ingredients" id="modalIngredients"></div>
    </div>
</div>

<!-- Screensaver -->
<div class="screensaver" id="screensaver">
    <img class="screensaver__logo" id="ssLogo" src="" alt="">
    <div class="screensaver__text" id="ssText">Cardápio de Drinks</div>
    <div class="screensaver__touch">Toque para explorar</div>
</div>

<script>
(function() {
    const API_BASE = '<?php echo esc_js(rest_url('mdd/v1')); ?>';
    const TOKEN = '<?php echo esc_js($token); ?>';
    const TIMEOUT = <?php echo intval(get_option('mdd_tablet_timeout', 60)); ?> * 1000;

    let settings = {};
    let allDrinks = [];
    let filteredDrinks = [];
    let currentFilter = 'all';
    let inactivityTimer = null;

    // ─── Fetch ───
    async function init() {
        const [sRes, dRes, cRes] = await Promise.all([
            fetch(API_BASE + '/settings/display?token=' + TOKEN).then(r => r.json()),
            fetch(API_BASE + '/drinks?token=' + TOKEN).then(r => r.json()),
            fetch(API_BASE + '/categories?token=' + TOKEN).then(r => r.json()),
        ]);

        if (sRes.success) { settings = sRes.settings; applySettings(); }
        if (dRes.success) { allDrinks = dRes.drinks; filteredDrinks = allDrinks; renderGrid(); }
        if (cRes.success) { renderFilters(cRes.categories); }

        startInactivityTimer();
    }

    function applySettings() {
        if (settings.logo) {
            const logo = document.getElementById('headerLogo');
            logo.src = settings.logo;
            logo.style.display = 'block';
            if (settings.logo_heights && settings.logo_heights.tablet) {
                logo.style.height = settings.logo_heights.tablet + 'px';
            }
            document.getElementById('ssLogo').src = settings.logo;
        }
        if (settings.event_mode && settings.event_name) {
            document.getElementById('headerTitle').textContent = settings.event_name;
            document.getElementById('ssText').textContent = settings.event_name;
        } else if (settings.tablet && settings.tablet.header_title) {
            document.getElementById('headerTitle').textContent = settings.tablet.header_title;
        }
        if (settings.tablet && settings.tablet.screensaver_text) {
            document.getElementById('ssText').textContent = settings.tablet.screensaver_text;
        }
        if (settings.tablet && settings.tablet.quiz_text) {
            document.getElementById('quizBtn').textContent = settings.tablet.quiz_text;
        }
        if (settings.quiz_url) {
            document.getElementById('quizBtn').onclick = () => window.location.href = settings.quiz_url;
        }
    }

    // ─── Filters ───
    function renderFilters(categories) {
        const bar = document.getElementById('filtersBar');
        let html = '<button class="filter-tab active" data-filter="all">Todos</button>';
        categories.forEach(c => {
            html += `<button class="filter-tab" data-filter="${c.slug}">${c.name}</button>`;
        });
        bar.innerHTML = html;

        bar.addEventListener('click', e => {
            const tab = e.target.closest('.filter-tab');
            if (!tab) return;
            bar.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentFilter = tab.dataset.filter;
            filterDrinks();
            resetInactivity();
        });
    }

    function filterDrinks() {
        if (currentFilter === 'all') {
            filteredDrinks = allDrinks;
        } else {
            filteredDrinks = allDrinks.filter(d =>
                d.categories.some(c => c.slug === currentFilter)
            );
        }
        renderGrid();
    }

    // ─── Grid ───
    function renderGrid() {
        const grid = document.getElementById('drinksGrid');
        const t = settings.tablet || {};
        const showPrice = t.show_price !== false;
        const showBadge = t.show_badge !== false;
        const showDesc  = t.show_desc !== false;

        grid.innerHTML = filteredDrinks.map(d => {
            const cat = d.categories && d.categories[0] ? d.categories[0].name : '';
            return `
                <div class="card" data-id="${d.id}">
                    <div class="card__img-wrap">
                        <img class="card__img" src="${d.image || ''}" alt="${d.title}" loading="lazy" onerror="this.style.background='rgba(255,255,255,.04)';this.style.objectFit='none'">
                        ${showBadge && cat ? '<span class="card__badge">' + cat + '</span>' : ''}
                        ${d.video ? '<div class="card__video-icon"></div>' : ''}
                    </div>
                    <div class="card__body">
                        <div class="card__title">${d.title}</div>
                        ${showDesc && d.short_desc ? '<div class="card__desc">' + d.short_desc + '</div>' : ''}
                        ${showPrice && d.price_formatted ? '<div class="card__price">' + d.price_formatted + '</div>' : ''}
                    </div>
                </div>
            `;
        }).join('');

        grid.querySelectorAll('.card').forEach(card => {
            card.addEventListener('click', () => openModal(parseInt(card.dataset.id)));
        });
    }

    // ─── Modal ───
    async function openModal(id) {
        resetInactivity();
        try {
            const res = await fetch(API_BASE + '/drinks/' + id + '?token=' + TOKEN);
            const data = await res.json();
            if (!data.success) return;

            const d = data.drink;
            const cat = d.categories && d.categories[0] ? d.categories[0].name : '';

            document.getElementById('modalImg').src = d.image_full || d.image || '';
            document.getElementById('modalImg').onerror = function(){ this.style.background='rgba(255,255,255,.04)'; };
            document.getElementById('modalCategory').textContent = cat;
            document.getElementById('modalTitle').textContent = d.title;
            document.getElementById('modalDesc').innerHTML = d.content || d.short_desc || '';
            document.getElementById('modalPrice').textContent = d.price_formatted || '';

            // Variants
            const varWrap = document.getElementById('modalVariants');
            if (d.variants && d.variants.length) {
                varWrap.innerHTML = d.variants.map(v =>
                    `<span class="modal__variant"><strong>${v.label || v.name}</strong> ${v.price ? 'R$ ' + parseFloat(v.price).toFixed(2).replace('.', ',') : ''}</span>`
                ).join('');
                varWrap.style.display = 'flex';
            } else {
                varWrap.style.display = 'none';
            }

            // Ingredients
            const ingWrap = document.getElementById('modalIngredients');
            const ingLabel = document.getElementById('modalIngLabel');
            if (d.ingredients && d.ingredients.length) {
                ingWrap.innerHTML = d.ingredients.map(i =>
                    `<span class="modal__ingredient">${i}</span>`
                ).join('');
                ingLabel.style.display = 'block';
            } else {
                ingWrap.innerHTML = '';
                ingLabel.style.display = 'none';
            }

            // Video
            const videoBtn = document.getElementById('modalVideoBtn');
            const videoWrap = document.getElementById('modalVideoWrap');
            const videoEl = document.getElementById('modalVideo');
            if (d.video) {
                videoBtn.style.display = 'block';
                videoEl.src = d.video;
                videoBtn.onclick = () => {
                    videoWrap.style.display = 'block';
                    videoEl.play().catch(function(){});
                    videoEl.onended = () => { videoWrap.style.display = 'none'; };
                };
            } else {
                videoBtn.style.display = 'none';
                videoWrap.style.display = 'none';
            }

            document.getElementById('modalOverlay').classList.add('active');
            document.getElementById('modal').classList.add('active');
        } catch(e) { console.error(e); }
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('active');
        document.getElementById('modal').classList.remove('active');
        const videoEl = document.getElementById('modalVideo');
        videoEl.pause();
        document.getElementById('modalVideoWrap').style.display = 'none';
        resetInactivity();
    }

    document.getElementById('modalOverlay').addEventListener('click', closeModal);
    document.getElementById('modalClose').addEventListener('click', closeModal);

    // ─── Screensaver ───
    function startInactivityTimer() {
        clearTimeout(inactivityTimer);
        inactivityTimer = setTimeout(showScreensaver, TIMEOUT);
    }

    function resetInactivity() {
        document.getElementById('screensaver').classList.remove('active');
        startInactivityTimer();
    }

    function showScreensaver() {
        closeModal();
        document.getElementById('screensaver').classList.add('active');
    }

    document.getElementById('screensaver').addEventListener('click', resetInactivity);
    document.addEventListener('touchstart', resetInactivity);
    document.addEventListener('scroll', resetInactivity);

    // ─── Prevent navigation ───
    window.addEventListener('beforeunload', e => { e.preventDefault(); });
    history.pushState(null, '', location.href);
    window.addEventListener('popstate', () => { history.pushState(null, '', location.href); });

    init();
})();

// Register service worker for offline/kiosk mode
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?php echo esc_js(rest_url('mdd/v1/sw.js')); ?>', {scope: '/'})
        .catch(() => {});
}
</script>
</body>
</html>
