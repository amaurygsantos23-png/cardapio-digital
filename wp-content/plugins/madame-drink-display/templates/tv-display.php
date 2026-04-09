<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<title>Drink Display — TV</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
<?php
$device = $GLOBALS['mdd_device'] ?? null;
$token  = $GLOBALS['mdd_token'] ?? '';
$primary   = esc_attr(get_option('mdd_primary_color', '#C8962E'));
$secondary = esc_attr(get_option('mdd_secondary_color', '#1A1A2E'));
$accent    = esc_attr(get_option('mdd_accent_color', '#E8593C'));
?>
:root {
  --primary: <?php echo $primary; ?>;
  --bg: <?php echo $secondary; ?>;
  --accent: <?php echo $accent; ?>;
  --tv-title: #fff;
  --tv-desc: rgba(255,255,255,.85);
  --tv-price: <?php echo $primary; ?>;
  --tv-cat: <?php echo $primary; ?>;
  --tv-title-size: 32px;
  --tv-desc-size: 14px;
  --tv-price-size: 24px;
  --tv-cat-size: 12px;
}
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:100%;height:100%;overflow:hidden;font-family:'Outfit',sans-serif;background:var(--bg);color:#fff}

/* ─── Slide Container ─── */
.slides-wrapper{position:relative;width:100%;height:100%;z-index:1}
.slide{position:absolute;inset:0;display:flex;opacity:0;pointer-events:none;will-change:opacity,transform;z-index:1}
.slide.active{opacity:1;pointer-events:auto;z-index:3}
.slide.prev{z-index:2}

/* Transitions */
.transition-fade .slide{transition:opacity 1s ease}
.transition-fade .slide.prev{opacity:0}
.transition-slide .slide{transition:opacity .5s ease,transform .7s ease;transform:translateX(40px)}
.transition-slide .slide.active{transform:translateX(0)}
.transition-slide .slide.prev{transform:translateX(-40px);opacity:0}
.transition-zoom .slide{transition:opacity .8s ease,transform 1s ease;transform:scale(.95)}
.transition-zoom .slide.active{transform:scale(1)}
.transition-zoom .slide.prev{transform:scale(1.05);opacity:0}

/* ─── Fullscreen Layout ─── */
.slide--fullscreen{align-items:stretch}
.slide--fullscreen .slide__img-wrap{flex:0 0 55%;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center}
.slide--fullscreen .slide__img{width:100%;height:100%;object-fit:cover;border-radius:0 16px 16px 0}
.slide--fullscreen .slide__img-overlay{position:absolute;inset:0;background:linear-gradient(90deg,transparent 30%,var(--bg) 100%),linear-gradient(180deg,var(--bg) 0%,transparent 8%,transparent 92%,var(--bg) 100%)}
.slide--fullscreen .slide__info{flex:1;display:flex;flex-direction:column;justify-content:center;padding:5vh 6vw;position:relative;z-index:2}

/* ─── Split Layout ─── */
.slide--split{align-items:center}
.slide--split .slide__img-wrap{flex:0 0 50%;height:100%;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;padding:4vh}
.slide--split .slide__img{max-width:85%;max-height:85%;object-fit:contain;border-radius:16px;filter:drop-shadow(0 20px 60px rgba(0,0,0,.5))}
.slide--split .slide__info{flex:1;padding:5vh 5vw}

/* ─── Text Shadows (legibility on any background) ─── */
.slide__category{font-size:clamp(var(--tv-cat-size),1.2vw,18px);text-transform:uppercase;letter-spacing:4px;color:var(--tv-cat);font-weight:500;margin-bottom:1.5vh;text-shadow:0 1px 6px rgba(0,0,0,.5)}
.slide__title{font-family:'Playfair Display',serif;font-size:clamp(var(--tv-title-size),5vw,80px);font-weight:700;line-height:1.1;margin-bottom:2vh;color:var(--tv-title);text-shadow:0 2px 8px rgba(0,0,0,.4)}
.slide__desc{font-size:clamp(var(--tv-desc-size),1.6vw,22px);line-height:1.6;color:var(--tv-desc);font-weight:300;max-width:500px;margin-bottom:3vh;text-shadow:0 1px 6px rgba(0,0,0,.5)}
.slide__price{font-family:'Playfair Display',serif;font-size:clamp(var(--tv-price-size),3.5vw,56px);font-weight:600;color:var(--tv-price);text-shadow:0 2px 10px rgba(0,0,0,.5)}
.slide__price-label{font-size:clamp(10px,1vw,14px);text-transform:uppercase;letter-spacing:3px;color:rgba(255,255,255,.5);font-weight:400;margin-bottom:.5vh;text-shadow:0 1px 4px rgba(0,0,0,.4)}

/* ─── Video Overlay ─── */
.slide__video{position:absolute;inset:0;z-index:5;background:#000}
.slide__video video{width:100%;height:100%;object-fit:cover}

/* ─── Logo ─── */
.tv-logo{position:fixed;top:3vh;left:3vw;z-index:100;max-width:15vw;opacity:.9;filter:drop-shadow(0 2px 12px rgba(0,0,0,.7)) drop-shadow(0 0 20px rgba(0,0,0,.4))}

/* ─── Event Badge ─── */
.tv-event-badge{position:fixed;top:3vh;right:3vw;z-index:100;padding:8px 20px;background:rgba(200,150,46,.15);border:1px solid var(--primary);border-radius:4px;font-size:clamp(10px,1vw,14px);letter-spacing:2px;text-transform:uppercase;color:var(--primary);backdrop-filter:blur(10px)}

/* ─── QR Code ─── */
.tv-qr{position:fixed;z-index:100;text-align:center}
.tv-qr.pos-bottom-right{bottom:3vh;right:3vw}
.tv-qr.pos-bottom-left{bottom:3vh;left:3vw}
.tv-qr.pos-top-right{top:3vh;right:3vw}
.tv-qr img{border-radius:8px;border:2px solid var(--primary)}
.tv-qr-label{font-size:clamp(9px,.8vw,12px);color:rgba(255,255,255,.5);margin-top:6px;letter-spacing:1px;text-transform:uppercase}

/* ─── Progress ─── */
.tv-progress{position:fixed;bottom:0;left:0;width:100%;height:3px;z-index:100;background:rgba(255,255,255,.08)}
.tv-progress-bar{height:100%;background:var(--primary);transition:width linear}

/* ─── Dots ─── */
.tv-dots{position:fixed;bottom:3vh;left:50%;transform:translateX(-50%);z-index:100;display:flex;gap:8px}
.tv-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.2);transition:all .4s}
.tv-dot.active{background:var(--primary);transform:scale(1.3)}

/* ─── Ambient Glow ─── */
.ambient-glow{position:fixed;width:40vw;height:40vw;border-radius:50%;filter:blur(120px);opacity:.08;pointer-events:none;z-index:0}
.ambient-glow--1{top:-10vw;right:-10vw;background:var(--primary)}
.ambient-glow--2{bottom:-15vw;left:-10vw;background:var(--accent)}

/* ─── Grid Layout (3-4 cards) ─── */
.slide--grid{display:grid;grid-template-columns:repeat(2,1fr);grid-template-rows:repeat(2,1fr);gap:2vh 2vw;padding:8vh 4vw 6vh}
.grid-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:16px;display:flex;align-items:center;gap:2vw;padding:2vh 2vw;overflow:hidden;position:relative;box-shadow:0 4px 20px rgba(0,0,0,.3),inset 0 1px 0 rgba(255,255,255,.05)}
.grid-card__img{width:clamp(80px,12vw,200px);height:clamp(80px,12vw,200px);object-fit:contain;border-radius:12px;flex-shrink:0;background:rgba(0,0,0,.2);padding:4px}
.grid-card__info{flex:1}
.grid-card__title{font-family:'Playfair Display',serif;font-size:clamp(18px,2.2vw,32px);font-weight:600;margin-bottom:.8vh;color:var(--tv-title);text-shadow:0 1px 3px rgba(0,0,0,.3)}
.grid-card__desc{font-size:clamp(11px,1.1vw,16px);color:var(--tv-desc);line-height:1.4;margin-bottom:1vh}
.grid-card__price{font-size:clamp(16px,1.8vw,28px);font-weight:600;color:var(--tv-price);text-shadow:0 1px 3px rgba(0,0,0,.3)}

/* ─── Custom Slides ─── */
.slide--custom{align-items:center;justify-content:center;text-align:center;flex-direction:column;padding:8vh 10vw}
.slide--custom .custom-text{font-family:'Playfair Display',serif;font-size:clamp(32px,5vw,72px);font-weight:700;line-height:1.2;margin-bottom:2vh;text-shadow:0 2px 20px rgba(0,0,0,.3)}
.slide--custom .custom-sub{font-size:clamp(14px,2vw,28px);opacity:.8;font-weight:300}
</style>
</head>
<body>

<div class="ambient-glow ambient-glow--1"></div>
<div class="ambient-glow ambient-glow--2"></div>

<!-- Logo -->
<img class="tv-logo" id="tvLogo" src="" alt="" style="display:none">

<!-- Event Badge -->
<div class="tv-event-badge" id="tvEventBadge" style="display:none"></div>

<!-- QR Code -->
<div class="tv-qr" id="tvQr" style="display:none">
    <img id="qrImg" src="" alt="QR" style="border-radius:8px;border:3px solid var(--primary);width:140px;height:140px">
    <div class="tv-qr-label">Faça o Quiz</div>
</div>

<!-- Progress Bar -->
<div class="tv-progress"><div class="tv-progress-bar" id="progressBar"></div></div>

<!-- Dots -->
<div class="tv-dots" id="tvDots"></div>

<!-- Slides Container -->
<div class="slides-wrapper" id="slidesWrapper"></div>

<script>
(function() {
    const API_BASE = '<?php echo esc_js(rest_url('mdd/v1')); ?>';
    const TOKEN = '<?php echo esc_js($token); ?>';
    const POLL_INTERVAL = 60000; // 1 min

    let settings = {};
    let drinks = [];
    let currentIndex = 0;
    let slideTimer = null;
    let progressTimer = null;

    // ─── Fetch Settings ───
    async function fetchSettings() {
        try {
            const res = await fetch(API_BASE + '/settings/display?token=' + TOKEN);
            const data = await res.json();
            if (data.success) settings = data.settings;
            applySettings();
        } catch(e) { console.error('Settings error:', e); }
    }

    // ─── Fetch Drinks ───
    async function fetchDrinks() {
        try {
            const res = await fetch(API_BASE + '/drinks?token=' + TOKEN);
            const data = await res.json();
            if (data.success && data.drinks.length) {
                drinks = data.drinks;
                buildSlides();
                startSlideshow();
            }
        } catch(e) { console.error('Drinks error:', e); }
    }

    // ─── Apply Settings ───
    function applySettings() {
        // Logo
        if (settings.logo) {
            const logo = document.getElementById('tvLogo');
            logo.src = settings.logo;
            logo.style.display = 'block';
            if (settings.logo_heights && settings.logo_heights.tv) {
                logo.style.maxHeight = settings.logo_heights.tv + 'px';
            }
        }

        // Event badge
        if (settings.event_mode && settings.event_name) {
            const badge = document.getElementById('tvEventBadge');
            badge.textContent = settings.event_name;
            badge.style.display = 'block';
        }

        // Transition class on wrapper
        var trans = (settings.tv && settings.tv.transition) ? settings.tv.transition : 'fade';
        document.getElementById('slidesWrapper').className = 'slides-wrapper transition-' + trans;

        // QR Code (server-side image — works on all Smart TVs)
        if (settings.tv && settings.tv.show_qr && settings.quiz_url) {
            var qrWrap = document.getElementById('tvQr');
            var posClass = 'pos-' + (settings.tv.qr_position || 'bottom-right');
            qrWrap.className = 'tv-qr ' + posClass;
            qrWrap.style.display = 'block';
            var qrLabel = qrWrap.querySelector('.tv-qr-label');
            if (qrLabel && settings.tv.qr_text) qrLabel.textContent = settings.tv.qr_text;
            var qrImg = document.getElementById('qrImg');
            qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?data=' + encodeURIComponent(settings.quiz_url) + '&size=200x200&format=png&margin=10&color=ffffff&bgcolor=1A1A2E';
        }

        // TV background image
        if (settings.tv && settings.tv.bg_image) {
            document.body.style.backgroundImage = 'url(' + settings.tv.bg_image + ')';
            document.body.style.backgroundSize = 'cover';
            document.body.style.backgroundPosition = 'center';
            // Add overlay for legibility (only once)
            if (!document.getElementById('tvBgOverlay')) {
                var overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:0;pointer-events:none';
                overlay.id = 'tvBgOverlay';
                document.body.insertBefore(overlay, document.body.firstChild);
            }
        } else {
            document.body.style.backgroundImage = '';
            var existingOverlay = document.getElementById('tvBgOverlay');
            if (existingOverlay) existingOverlay.remove();
        }

        // TV text colors + font sizes (custom CSS variables)
        if (settings.tv) {
            var root = document.documentElement;
            if (settings.tv.title_color) root.style.setProperty('--tv-title', settings.tv.title_color);
            if (settings.tv.desc_color) root.style.setProperty('--tv-desc', settings.tv.desc_color);
            if (settings.tv.price_color) root.style.setProperty('--tv-price', settings.tv.price_color);
            if (settings.tv.cat_color) root.style.setProperty('--tv-cat', settings.tv.cat_color);
            if (settings.tv.title_size) root.style.setProperty('--tv-title-size', settings.tv.title_size + 'px');
            if (settings.tv.desc_size) root.style.setProperty('--tv-desc-size', settings.tv.desc_size + 'px');
            if (settings.tv.price_size) root.style.setProperty('--tv-price-size', settings.tv.price_size + 'px');
            if (settings.tv.cat_size) root.style.setProperty('--tv-cat-size', settings.tv.cat_size + 'px');
        }
    }

    // ─── Check if custom slide is active now ───
    function isSlideActiveNow(slide) {
        if (!slide.active) return false;
        if (!slide.days || !slide.days.length) return true; // No schedule = always active
        if (!slide.time_start && !slide.time_end) return true;

        var now = new Date();
        var dayIndex = (now.getDay() + 6) % 7; // Convert: Sun=0 → Mon=0
        if (slide.days.indexOf(dayIndex) === -1) return false;

        if (slide.time_start && slide.time_end) {
            var nowMin = now.getHours() * 60 + now.getMinutes();
            var parts_s = slide.time_start.split(':');
            var parts_e = slide.time_end.split(':');
            var startMin = parseInt(parts_s[0]) * 60 + parseInt(parts_s[1]);
            var endMin = parseInt(parts_e[0]) * 60 + parseInt(parts_e[1]);

            if (nowMin < startMin || nowMin >= endMin) {
                // Check end behavior
                if (slide.end_behavior === 'message' && slide.end_message && nowMin >= endMin && nowMin < endMin + 5) {
                    return 'ending'; // Show end message for 5 min after
                }
                return false;
            }
        }
        return true;
    }

    // ─── Build Slides ───
    function buildSlides() {
        const wrapper = document.getElementById('slidesWrapper');
        const layout = settings.tv ? settings.tv.layout : 'fullscreen';

        wrapper.innerHTML = '';

        // Collect active custom slides
        var customSlides = [];
        if (settings.tv && settings.tv.custom_slides) {
            settings.tv.custom_slides.forEach(function(cs) {
                var status = isSlideActiveNow(cs);
                if (status === true) {
                    customSlides.push({type: 'custom', data: cs});
                } else if (status === 'ending') {
                    customSlides.push({type: 'custom_ending', data: cs});
                }
            });
        }

        if (layout === 'grid') {
            const chunks = [];
            for (let i = 0; i < drinks.length; i += 4) {
                chunks.push(drinks.slice(i, i + 4));
            }
            chunks.forEach((chunk, ci) => {
                const slide = document.createElement('div');
                slide.className = 'slide slide--grid' + (ci === 0 ? ' active' : '');
                slide.innerHTML = chunk.map(d => `
                    <div class="grid-card">
                        <img class="grid-card__img" src="${d.image || ''}" alt="${d.title}" loading="lazy" onerror="this.style.background='rgba(255,255,255,.06)';this.alt='🍸'">
                        <div class="grid-card__info">
                            <div class="grid-card__title">${d.title}</div>
                            <div class="grid-card__desc">${d.short_desc || ''}</div>
                            ${d.price_formatted ? '<div class="grid-card__price">' + d.price_formatted + (d.price_2_formatted ? ' · ' + d.price_2_formatted : '') + (d.price_3_formatted ? ' · ' + d.price_3_formatted : '') + '</div>' : ''}
                        </div>
                    </div>
                `).join('');
                wrapper.appendChild(slide);
            });

            // Insert custom slides after grid chunks
            customSlides.forEach(function(cs) {
                wrapper.appendChild(buildCustomSlide(cs));
            });
        } else {
            const cls = layout === 'split' ? 'slide--split' : 'slide--fullscreen';
            var slideIndex = 0;
            drinks.forEach((d, i) => {
                const slide = document.createElement('div');
                slide.className = 'slide ' + cls + (slideIndex === 0 ? ' active' : '');
                const cat = d.categories && d.categories[0] ? d.categories[0].name : '';
                const showPrice = settings.tv ? settings.tv.show_price : true;

                // Build price HTML with variants
                function buildPriceHtml(d) {
                    if (!showPrice || !d.price_formatted) return '';
                    var prices = [d.price_formatted];
                    if (d.price_2_formatted) prices.push(d.price_2_formatted);
                    if (d.price_3_formatted) prices.push(d.price_3_formatted);
                    if (prices.length > 1) {
                        return '<div class="slide__price-label">a partir de</div><div class="slide__price">' + prices[0] + '</div>'
                            + '<div style="font-size:clamp(11px,1.2vw,16px);color:rgba(255,255,255,.5);margin-top:.5vh">' + prices.join(' · ') + '</div>';
                    }
                    return '<div class="slide__price">' + prices[0] + '</div>';
                }

                if (layout === 'split') {
                    slide.innerHTML = `
                        <div class="slide__img-wrap">
                            <img class="slide__img" src="${d.image_full || d.image || ''}" alt="${d.title}" style="object-fit:contain" onerror="this.src='';this.style.background='rgba(255,255,255,.04)'">
                        </div>
                        <div class="slide__info">
                            ${cat ? '<div class="slide__category">' + cat + '</div>' : ''}
                            <h1 class="slide__title">${d.title}</h1>
                            <p class="slide__desc">${d.short_desc || ''}</p>
                            ${buildPriceHtml(d)}
                        </div>
                    `;
                } else {
                    slide.innerHTML = `
                        <div class="slide__img-wrap">
                            <img class="slide__img" src="${d.image_full || d.image || ''}" alt="${d.title}" style="object-fit:cover" onerror="this.src='';this.style.background='rgba(255,255,255,.04)'">
                            <div class="slide__img-overlay"></div>
                        </div>
                        <div class="slide__info">
                            ${cat ? '<div class="slide__category">' + cat + '</div>' : ''}
                            <h1 class="slide__title">${d.title}</h1>
                            <p class="slide__desc">${d.short_desc || ''}</p>
                            ${buildPriceHtml(d)}
                        </div>
                    `;
                }

                if (d.video) {
                    const videoEl = document.createElement('div');
                    videoEl.className = 'slide__video';
                    videoEl.style.display = 'none';
                    videoEl.innerHTML = `<video src="${d.video}" muted playsinline></video>`;
                    slide.appendChild(videoEl);
                    slide.dataset.hasVideo = '1';
                }

                wrapper.appendChild(slide);
                slideIndex++;

                // Insert custom slide every 4 drinks
                if ((i + 1) % 4 === 0 && customSlides.length > 0) {
                    var csIdx = Math.floor(i / 4) % customSlides.length;
                    wrapper.appendChild(buildCustomSlide(customSlides[csIdx]));
                    slideIndex++;
                }
            });

            // If no custom slides were inserted (fewer than 4 drinks), add them at the end
            if (drinks.length < 4 && customSlides.length > 0) {
                customSlides.forEach(function(cs) {
                    wrapper.appendChild(buildCustomSlide(cs));
                });
            }
        }

        buildDots();
    }

    // ─── Build Custom Slide Element ───
    function buildCustomSlide(cs) {
        var slide = document.createElement('div');
        slide.className = 'slide slide--custom';

        var bgColor = cs.data.bg_color || '#E8593C';
        var txtColor = cs.data.text_color || '#FFFFFF';
        var bgImage = cs.data.bg_image || '';
        var content = cs.type === 'custom_ending'
            ? (cs.data.end_message || 'Promoção encerrada!')
            : (cs.data.content || cs.data.title);

        slide.style.color = txtColor;

        if (bgImage) {
            // Background image mode
            slide.style.background = 'url(' + bgImage + ') center/cover no-repeat';
            // If has text content, show it over the image with dark overlay
            if (content && content.trim()) {
                slide.innerHTML = '<div style="position:absolute;inset:0;background:rgba(0,0,0,.35)"></div><div class="custom-text" style="position:relative;z-index:2;text-shadow:0 3px 20px rgba(0,0,0,.6)">' + content + '</div>';
            }
            // If no text, just show the image fullscreen (arte pronta)
        } else {
            // Solid color mode (existing behavior)
            slide.style.background = bgColor;
            slide.innerHTML = '<div class="custom-text">' + content + '</div>';
        }

        return slide;
    }

    // ─── Build Dots ───
    function buildDots() {
        const container = document.getElementById('tvDots');
        const count = document.querySelectorAll('.slide').length;
        container.innerHTML = '';
        for (let i = 0; i < count; i++) {
            const dot = document.createElement('div');
            dot.className = 'tv-dot' + (i === 0 ? ' active' : '');
            container.appendChild(dot);
        }
    }

    // ─── Slideshow ───
    function startSlideshow() {
        // ALWAYS clear any existing timer first
        if (slideTimer) clearInterval(slideTimer);
        if (progressTimer) clearInterval(progressTimer);

        const duration = (settings.tv ? settings.tv.slide_duration : 8) * 1000;
        resetProgress(duration);
        slideTimer = setInterval(function() { nextSlide(duration); }, duration);
    }

    function nextSlide(duration) {
        var slides = document.querySelectorAll('.slide');
        var dots = document.querySelectorAll('.tv-dot');
        if (slides.length <= 1) return;

        // Remove ALL active/prev classes first (safety)
        for (var i = 0; i < slides.length; i++) {
            if (i === currentIndex) continue;
            slides[i].classList.remove('active', 'prev');
        }

        var prevIndex = currentIndex;
        currentIndex = (currentIndex + 1) % slides.length;

        // Animate out
        slides[prevIndex].classList.remove('active');
        slides[prevIndex].classList.add('prev');

        // Animate in
        slides[currentIndex].classList.add('active');

        // Update dots
        for (var d = 0; d < dots.length; d++) {
            dots[d].classList.toggle('active', d === currentIndex);
        }

        // Clean prev after transition completes
        var pIdx = prevIndex;
        setTimeout(function() {
            if (slides[pIdx]) slides[pIdx].classList.remove('prev');
        }, 1500);

        // Handle video on new slide
        var slide = slides[currentIndex];
        if (slide && slide.dataset.hasVideo === '1') {
            var videoWrap = slide.querySelector('.slide__video');
            var video = videoWrap ? videoWrap.querySelector('video') : null;
            if (videoWrap && video) {
                videoWrap.style.display = 'block';
                video.currentTime = 0;
                video.play().catch(function(){});
                video.onended = function() { videoWrap.style.display = 'none'; };
            }
        }

        resetProgress(duration || 8000);
    }

    function resetProgress(duration) {
        const bar = document.getElementById('progressBar');
        bar.style.transition = 'none';
        bar.style.width = '0%';
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                bar.style.transition = 'width ' + (duration / 1000) + 's linear';
                bar.style.width = '100%';
            });
        });
    }

    // ─── Poll for updates (settings + drinks) ───
    async function pollUpdate() {
        await fetchSettings();
        await fetchDrinks();
    }
    setInterval(pollUpdate, POLL_INTERVAL);

    // ─── Init ───
    fetchSettings().then(fetchDrinks);

    // ─── Auto Fullscreen on first interaction ───
    var fullscreenTriggered = false;
    function tryFullscreen() {
        if (fullscreenTriggered) return;
        fullscreenTriggered = true;
        var el = document.documentElement;
        var rfs = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
        if (rfs) {
            try { rfs.call(el); } catch(e) {}
        }
        document.removeEventListener('click', tryFullscreen);
        document.removeEventListener('touchstart', tryFullscreen);
    }
    document.addEventListener('click', tryFullscreen, { once: true });
    document.addEventListener('touchstart', tryFullscreen, { once: true });

    // Also show a brief overlay hint
    var hint = document.createElement('div');
    hint.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.7);color:#fff;padding:10px 24px;border-radius:20px;font-size:13px;z-index:9999;pointer-events:none;transition:opacity 1s';
    hint.textContent = 'Toque na tela para ativar tela cheia';
    document.body.appendChild(hint);
    setTimeout(function() { hint.style.opacity = '0'; }, 4000);
    setTimeout(function() { hint.remove(); }, 5000);

})();
</script>
</body>
</html>
