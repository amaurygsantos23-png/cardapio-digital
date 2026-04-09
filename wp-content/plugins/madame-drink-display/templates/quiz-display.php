<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<meta name="theme-color" content="<?php echo esc_attr(get_option('mdd_secondary_color', '#1A1A2E')); ?>">
<title>Quiz de Drinks</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
<?php
$primary   = esc_attr(get_option('mdd_quiz_primary_color', '') ?: get_option('mdd_primary_color', '#C8962E'));
$secondary = esc_attr(get_option('mdd_quiz_bg_color', '') ?: get_option('mdd_secondary_color', '#1A1A2E'));
$accent    = esc_attr(get_option('mdd_quiz_accent_color', '') ?: get_option('mdd_accent_color', '#E8593C'));
$logo_url  = MDD_Settings::get_active_logo();
$logo_h    = intval(get_option('mdd_logo_max_height_quiz', 80));
$quiz_bg_img = esc_url(get_option('mdd_quiz_bg_image', ''));
if ($quiz_bg_img) $quiz_bg_img .= (strpos($quiz_bg_img, '?') !== false ? '&' : '?') . 'v=' . time();
$q_title_s = intval(get_option('mdd_quiz_title_size', 22));
$q_opt_s   = intval(get_option('mdd_quiz_option_size', 15));
$q_btn_s   = intval(get_option('mdd_quiz_btn_size', 16));
?>
:root{--primary:<?php echo $primary;?>;--bg:<?php echo $secondary;?>;--accent:<?php echo $accent;?>;--logo-h:<?php echo $logo_h;?>px;--q-title:<?php echo $q_title_s;?>px;--q-opt:<?php echo $q_opt_s;?>px;--q-btn:<?php echo $q_btn_s;?>px}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html{height:100%}
body{min-height:100%;font-family:'Outfit',sans-serif;background:var(--bg);color:#fff;display:flex;flex-direction:column<?php if ($quiz_bg_img): ?>;background-image:url('<?php echo $quiz_bg_img; ?>');background-size:cover;background-position:center;background-attachment:fixed<?php endif; ?>}
<?php if ($quiz_bg_img): ?>
.quiz-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:0}
.screen{position:relative;z-index:1}
<?php endif; ?>

/* ─── Shared ─── */
.screen{display:none;flex-direction:column;min-height:100vh;padding:24px;animation:fadeUp .5s ease}
.screen.active{display:flex}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}

.quiz-logo{max-height:var(--logo-h,80px);max-width:50vw;object-fit:contain;margin:0 auto 16px;display:block;opacity:.85}
.quiz-progress{display:flex;gap:6px;justify-content:center;margin-bottom:32px}
.quiz-progress-dot{width:32px;height:4px;border-radius:2px;background:rgba(255,255,255,.1);transition:all .4s}
.quiz-progress-dot.done{background:var(--primary)}
.quiz-progress-dot.current{background:var(--primary);width:48px}

/* ─── Welcome Screen ─── */
.welcome{align-items:center;justify-content:center;text-align:center;gap:16px}
.welcome__icon{font-size:64px;margin-bottom:8px}
.welcome__title{font-family:'Playfair Display',serif;font-size:calc(var(--q-title) + 6px);font-weight:700;line-height:1.2}
.welcome__title span{color:var(--primary)}
.welcome__subtitle{font-size:15px;color:rgba(255,255,255,.5);max-width:300px;line-height:1.6}
.welcome__btn{margin-top:24px;padding:16px 48px;background:var(--primary);color:#fff;border:none;border-radius:28px;font-family:'Outfit',sans-serif;font-size:var(--q-btn);font-weight:600;cursor:pointer;letter-spacing:1px;transition:transform .2s}
.welcome__btn:active{transform:scale(.95)}

/* ─── Question Screen ─── */
.question{flex:1;justify-content:center}
.question__icon{font-size:40px;text-align:center;margin-bottom:12px}
.question__text{font-family:'Playfair Display',serif;font-size:calc(var(--q-title) + 2px);font-weight:600;text-align:center;margin-bottom:32px;line-height:1.3}
.question__options{display:flex;flex-direction:column;gap:12px;max-width:400px;margin:0 auto;width:100%}
.question__option{display:flex;align-items:center;gap:14px;padding:16px 20px;border:2px solid rgba(255,255,255,.25);border-radius:14px;background:rgba(255,255,255,.06);cursor:pointer;transition:all .3s;font-size:var(--q-opt)}
.question__option:active,.question__option.selected{background:rgba(200,150,46,.15);border-color:var(--primary);transform:scale(.98)}
.question__option-icon{font-size:24px;flex-shrink:0}
.question__option-label{flex:1}

/* ─── Loading Screen ─── */
.loading{align-items:center;justify-content:center;text-align:center;gap:20px}
.loading__spinner{width:48px;height:48px;border:3px solid rgba(255,255,255,.1);border-top-color:var(--primary);border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.loading__text{font-size:16px;color:rgba(255,255,255,.5)}

/* ─── Result Screen ─── */
.result{align-items:center;padding-top:32px;gap:8px}
.result__badge{padding:6px 18px;background:rgba(200,150,46,.15);border:1px solid var(--primary);border-radius:20px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--primary);margin-bottom:8px}
.result__title{font-family:'Playfair Display',serif;font-size:22px;font-weight:600;text-align:center;margin-bottom:24px}
.result__cards{display:flex;flex-direction:column;gap:16px;width:100%;max-width:420px}
.result-card{display:flex;gap:14px;padding:14px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:14px;position:relative;overflow:hidden}
.result-card__rank{position:absolute;top:8px;left:8px;width:24px;height:24px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700}
.result-card__img{width:90px;height:90px;border-radius:10px;object-fit:cover;flex-shrink:0}
.result-card__info{flex:1;display:flex;flex-direction:column;justify-content:center}
.result-card__title{font-family:'Playfair Display',serif;font-size:17px;font-weight:600;margin-bottom:4px}
.result-card__desc{font-size:12px;color:rgba(255,255,255,.5);line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.result-card__price{font-size:15px;font-weight:600;color:var(--primary);margin-top:6px}
.result-card__match{font-size:10px;color:rgba(255,255,255,.3);letter-spacing:1px;text-transform:uppercase;margin-top:2px}

.result__actions{display:flex;gap:10px;margin-top:28px;width:100%;max-width:420px}
.result__btn{flex:1;padding:14px;border-radius:14px;font-family:'Outfit',sans-serif;font-size:14px;font-weight:500;cursor:pointer;text-align:center;transition:transform .2s;border:none}
.result__btn:active{transform:scale(.95)}
.result__btn--share{background:var(--accent);color:#fff}
.result__btn--retry{background:rgba(255,255,255,.06);color:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.08)}

/* ─── Name Screen ─── */
.name-screen{align-items:center;justify-content:center;text-align:center;gap:16px}
.name-screen__icon{font-size:48px}
.name-screen__title{font-family:'Playfair Display',serif;font-size:22px;font-weight:600}
.name-screen__input{width:100%;max-width:320px;padding:14px 20px;border:2px solid rgba(255,255,255,.2);border-radius:14px;background:rgba(255,255,255,.08);color:#fff;font-family:'Outfit',sans-serif;font-size:16px;text-align:center;outline:none;transition:border-color .3s}
.name-screen__input:focus{border-color:var(--primary)}
.name-screen__input::placeholder{color:rgba(255,255,255,.45)}
.name-screen__skip{font-size:13px;color:rgba(255,255,255,.3);cursor:pointer;margin-top:8px;text-decoration:underline}

/* ─── Confirm + Harmonize Screen ─── */
.confirm{align-items:center;padding-top:24px;gap:12px}
.confirm__drink-img{width:240px;height:240px;border-radius:18px;object-fit:contain;border:3px solid var(--primary);box-shadow:0 8px 30px rgba(0,0,0,.4);background:rgba(255,255,255,.03);padding:8px}
.confirm__check{width:40px;height:40px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;margin-top:-24px;margin-bottom:4px;position:relative;z-index:2;box-shadow:0 4px 12px rgba(200,150,46,.3)}
.confirm__msg{font-family:'Playfair Display',serif;font-size:20px;font-weight:600;text-align:center;line-height:1.3;max-width:340px}
.confirm__submsg{font-size:13px;color:rgba(255,255,255,.5);text-align:center;max-width:300px;margin-bottom:8px}
.confirm__context{font-size:14px;color:var(--primary);text-align:center;font-style:italic;margin-bottom:8px}
.confirm__pairing-title{font-size:13px;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:2px;margin-top:8px;margin-bottom:12px}
.confirm__pairing-cards{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;max-width:360px}
.pairing-card{width:100px;text-align:center}
.pairing-card__img{width:80px;height:80px;border-radius:10px;object-fit:cover;margin:0 auto 6px;display:block;border:1px solid rgba(255,255,255,.08)}
.pairing-card__name{font-size:11px;color:rgba(255,255,255,.6);line-height:1.3}

/* ─── Back Button ─── */
.quiz-back{width:auto;padding:12px 32px;border-radius:28px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);color:rgba(255,255,255,.8);font-size:15px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .2s;margin:20px auto 0;font-family:'Outfit',sans-serif;letter-spacing:.5px}
.quiz-back:active{transform:scale(.95);background:rgba(255,255,255,.18)}

/* ─── Final Share Screen ─── */
.final{align-items:center;justify-content:center;text-align:center;gap:16px;padding:24px}
.final__icon{font-size:48px}
.final__title{font-family:'Playfair Display',serif;font-size:22px;font-weight:600}
.final__msg{font-size:14px;color:rgba(255,255,255,.5);max-width:320px;line-height:1.6}
.final__post-msg{font-size:13px;color:var(--primary);max-width:300px;line-height:1.5;margin-top:8px;padding:12px 16px;background:rgba(200,150,46,.08);border:1px solid rgba(200,150,46,.15);border-radius:12px}

/* ─── Drink Rating Screen (via ?rate= link) ─── */
.drink-rate{align-items:center;justify-content:center;text-align:center;gap:16px;padding:24px}
.drink-rate__img{width:180px;height:180px;border-radius:16px;object-fit:cover;border:3px solid var(--primary);box-shadow:0 8px 30px rgba(0,0,0,.4)}
.drink-rate__name{font-family:'Playfair Display',serif;font-size:26px;font-weight:600}
.drink-rate__subtitle{font-size:16px;color:rgba(255,255,255,.5)}

/* ─── Rating Screen ─── */
.rating{align-items:center;justify-content:center;text-align:center;gap:16px}
.rating__title{font-size:16px;color:rgba(255,255,255,.6)}
.rating__stars{display:flex;gap:8px;justify-content:center;margin:8px 0}
.rating__star{font-size:32px;cursor:pointer;opacity:.25;transition:all .2s;filter:grayscale(1)}
.rating__star.active{opacity:1;filter:none;transform:scale(1.15)}
.rating__finalize{margin-top:20px;padding:14px 48px;background:var(--primary);color:#fff;border:none;border-radius:28px;font-family:'Outfit',sans-serif;font-size:16px;font-weight:600;cursor:pointer;letter-spacing:1px;transition:transform .2s}
.rating__finalize:active{transform:scale(.95)}

/* ─── CTA on result cards ─── */
.result-card__cta{margin-top:6px;padding:6px 14px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-family:'Outfit',sans-serif;font-size:12px;font-weight:500;cursor:pointer;transition:transform .2s}
.result-card__cta:active{transform:scale(.95)}
</style>
</head>
<body>
<?php if ($quiz_bg_img): ?><div class="quiz-overlay"></div><?php endif; ?>

<!-- Welcome Screen -->
<div class="screen welcome active" id="screenWelcome">
    <?php if ($logo_url): ?>
        <img class="quiz-logo" src="<?php echo esc_url($logo_url); ?>" alt="">
    <?php endif; ?>
    <div class="welcome__icon">🍸</div>
    <h1 class="welcome__title">Qual drink combina <span>com você?</span></h1>
    <p class="welcome__subtitle">Responda algumas perguntas rápidas e descubra o drink perfeito para o seu momento.</p>
    <button class="welcome__btn" id="startBtn">Começar ✨</button>
</div>

<!-- Name Screen -->
<div class="screen name-screen" id="screenName">
    <?php if ($logo_url): ?>
        <img class="quiz-logo" src="<?php echo esc_url($logo_url); ?>" alt="">
    <?php endif; ?>
    <div class="name-screen__icon">👋</div>
    <h2 class="name-screen__title">Como posso te chamar?</h2>
    <input type="text" class="name-screen__input" id="nameInput" placeholder="Seu nome (opcional)" maxlength="50" autocomplete="off">
    <input type="tel" class="name-screen__input" id="phoneInput" placeholder="📱 Seu telefone (opcional)" maxlength="15" autocomplete="off" style="margin-top:10px;font-size:15px">
    <p style="font-size:12px;color:rgba(255,255,255,.5);max-width:320px;text-align:center;margin-top:6px;line-height:1.4" id="phoneIncentive"><?php echo esc_html(get_option('mdd_quiz_phone_text', 'Cadastre seu telefone e receba promoções exclusivas de drinks!')); ?></p>
    <button class="welcome__btn" id="nameNextBtn" style="padding:14px 40px;font-size:15px">Continuar →</button>
    <div class="name-screen__skip" id="nameSkipBtn">Pular</div>
</div>

<!-- Question Screen -->
<div class="screen question" id="screenQuestion" style="position:relative">
    <?php if ($logo_url): ?>
        <img class="quiz-logo" src="<?php echo esc_url($logo_url); ?>" alt="">
    <?php endif; ?>
    <div class="quiz-progress" id="progressDots"></div>
    <div class="question__icon" id="qIcon"></div>
    <h2 class="question__text" id="qText"></h2>
    <div class="question__options" id="qOptions"></div>
    <button class="quiz-back" id="quizBackBtn" onclick="quizGoBack()" style="display:none">← Voltar</button>
</div>

<!-- Loading Screen -->
<div class="screen loading" id="screenLoading">
    <div class="loading__spinner"></div>
    <div class="loading__text">Analisando seu perfil...</div>
</div>

<!-- Result Screen -->
<div class="screen result" id="screenResult">
    <?php if ($logo_url): ?>
        <img class="quiz-logo" src="<?php echo esc_url($logo_url); ?>" alt="">
    <?php endif; ?>
    <div class="result__badge">Seu resultado</div>
    <h2 class="result__title" id="resultTitle">Drinks perfeitos para você</h2>
    <div class="result__cards" id="resultCards"></div>
    <div class="result__actions">
        <button class="result__btn result__btn--retry" id="resultRetryBtn">Refazer Quiz</button>
    </div>
</div>

<!-- Confirm + Harmonize Screen -->
<div class="screen confirm" id="screenConfirm">
    <?php if ($logo_url): ?>
        <img class="quiz-logo" src="<?php echo esc_url($logo_url); ?>" alt="">
    <?php endif; ?>
    <img class="confirm__drink-img" id="confirmImg" src="" alt="">
    <div class="confirm__check">✓</div>
    <div class="confirm__msg" id="confirmMsg"></div>
    <div class="confirm__submsg" id="confirmSubMsg"></div>
    <div class="confirm__context" id="confirmContext" style="display:none"></div>
    <div class="confirm__pairing-title" id="pairingTitle" style="display:none"></div>
    <div class="confirm__pairing-cards" id="pairingCards"></div>
    <button class="welcome__btn" id="confirmNextBtn" style="margin-top:20px;padding:14px 36px;font-size:15px">Continuar →</button>
</div>

<!-- Rating Screen -->
<div class="screen rating" id="screenRating">
    <?php if ($logo_url): ?>
        <img class="quiz-logo" src="<?php echo esc_url($logo_url); ?>" alt="">
    <?php endif; ?>
    <div class="rating__title" id="ratingTitle">Como foi o Quiz?</div>
    <div class="rating__stars" id="ratingStars">
        <span class="rating__star" data-v="1">⭐</span>
        <span class="rating__star" data-v="2">⭐</span>
        <span class="rating__star" data-v="3">⭐</span>
        <span class="rating__star" data-v="4">⭐</span>
        <span class="rating__star" data-v="5">⭐</span>
    </div>
    <button class="rating__finalize" id="finalizeBtn">FINALIZAR QUIZ</button>
    <div class="name-screen__skip" id="ratingSkipBtn" style="margin-top:12px">Pular avaliação</div>
</div>

<!-- Final / Share Screen -->
<div class="screen final" id="screenFinal">
    <?php if ($logo_url): ?>
        <img class="quiz-logo" src="<?php echo esc_url($logo_url); ?>" alt="">
    <?php endif; ?>
    <div class="final__icon">🎉</div>
    <div class="final__title" id="finalTitle">Obrigado!</div>
    <div class="final__msg" id="finalMsg"></div>
    <!-- WhatsApp rating link notice -->
    <div id="whatsappNotice" style="display:none;margin:16px 0;text-align:center;padding:14px 20px;background:rgba(37,211,102,.08);border:1px solid rgba(37,211,102,.2);border-radius:14px;max-width:340px">
        <p style="font-size:13px;color:rgba(255,255,255,.7);margin:0;line-height:1.5">📱 Após experimentar o drink, você receberá um link no WhatsApp para avaliar!</p>
    </div>
    <div class="final__post-msg" id="finalPostMsg"></div>
    <div style="display:flex;gap:10px;margin-top:20px;width:100%;max-width:340px">
        <button class="result__btn result__btn--share" id="shareBtn" style="flex:1">Compartilhar 📱</button>
        <button class="result__btn result__btn--retry" id="retryBtn" style="flex:1">Novo Quiz</button>
    </div>
</div>

<!-- Drink Rating Screen (accessed via ?rate=TOKEN link) -->
<div class="screen drink-rate" id="screenDrinkRate">
    <?php if ($logo_url): ?>
        <img class="quiz-logo" src="<?php echo esc_url($logo_url); ?>" alt="">
    <?php endif; ?>
    <img class="drink-rate__img" id="drinkRateImg" src="" alt="">
    <div class="drink-rate__name" id="drinkRateName"></div>
    <div class="drink-rate__subtitle" id="drinkRateSubtitle">Como foi o drink?</div>
    <div class="rating__stars" id="drinkRateStars">
        <span class="rating__star" data-v="1">⭐</span>
        <span class="rating__star" data-v="2">⭐</span>
        <span class="rating__star" data-v="3">⭐</span>
        <span class="rating__star" data-v="4">⭐</span>
        <span class="rating__star" data-v="5">⭐</span>
    </div>
    <button class="rating__finalize" id="drinkRateSubmit">Enviar Avaliação</button>
    <div id="drinkRateMsg" style="margin-top:12px;font-size:14px"></div>
</div>

<script>
(function() {
    const API_BASE = '<?php echo esc_js(rest_url('mdd/v1')); ?>';
    const RATING_TOKEN = '<?php echo esc_js($GLOBALS['mdd_rating_token'] ?? ''); ?>' || new URLSearchParams(window.location.search).get('rate') || '';
    const SITE_NAME = '<?php echo esc_js(get_bloginfo('name')); ?>';
    const CFG = {
        askName:    <?php echo get_option('mdd_quiz_ask_name', 1) ? 'true' : 'false'; ?>,
        skipBase:   <?php echo get_option('mdd_quiz_skip_base_if_no_alcohol', 1) ? 'true' : 'false'; ?>,
        ctaText:    '<?php echo esc_js(get_option('mdd_quiz_cta_text', 'Experimentar')); ?>',
        confirmTxt: '<?php echo esc_js(get_option('mdd_quiz_confirm_text', 'Informe ao garçom para confirmar seu pedido.')); ?>',
        pairingTxt: '<?php echo esc_js(get_option('mdd_quiz_pairing_text', 'Sabia que esse drink combina com')); ?>',
        showRating: <?php echo get_option('mdd_quiz_show_rating', 1) ? 'true' : 'false'; ?>,
        ratingTxt:  '<?php echo esc_js(get_option('mdd_quiz_rating_text', 'Como foi o Quiz?')); ?>',
        shareTxt:   '<?php echo esc_js(get_option('mdd_quiz_share_text', '')); ?>',
        postMsg:    '<?php echo esc_js(get_option('mdd_quiz_post_rating_msg', 'Se possível, após sua experiência com o drink, volte e avalie-o. É importante para nós!')); ?>',
        finalMsg:   '<?php echo esc_js(get_option('mdd_quiz_final_msg', 'Aproveite a experiência!')); ?>',
    };

    let questions = [];
    let activeQuestions = [];
    let currentQ = 0;
    let responses = [];
    let customerName = '';
    let customerPhone = '';
    let sessionId = '';
    let ratingToken = '';
    let ratingUrl = '';
    let lastRecommended = [];
    let chosenDrink = null;
    let selectedRating = 0;
    let choseNoAlcohol = false;

    function showScreen(id) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        window.scrollTo(0, 0);
    }

    // ─── Load Questions ───
    async function loadQuestions() {
        try {
            const res = await fetch(API_BASE + '/quiz/questions');
            const data = await res.json();
            if (data.success) questions = data.questions;
        } catch(e) { console.error(e); }
    }

    // ─── Build active questions (apply conditional logic) ───
    function buildActiveQuestions() {
        activeQuestions = questions.filter(function(q) {
            if (CFG.skipBase && choseNoAlcohol && q.id === 'base') return false;
            return true;
        });
    }

    // ─── Render Question ───
    function renderQuestion() {
        if (currentQ >= activeQuestions.length) { submitQuiz(); return; }

        // Show/hide back button
        var backBtn = document.getElementById('quizBackBtn');
        if (backBtn) backBtn.style.display = currentQ > 0 ? 'flex' : 'none';

        var q = activeQuestions[currentQ];
        document.getElementById('qIcon').textContent = q.icon || '';
        document.getElementById('qText').textContent = q.question;

        var dots = document.getElementById('progressDots');
        dots.innerHTML = activeQuestions.map(function(_, i) {
            var cls = 'quiz-progress-dot';
            if (i < currentQ) cls += ' done';
            if (i === currentQ) cls += ' current';
            return '<div class="' + cls + '"></div>';
        }).join('');

        var opts = document.getElementById('qOptions');
        opts.innerHTML = q.options.map(function(opt, i) {
            return '<div class="question__option" data-index="' + i + '">' +
                '<span class="question__option-icon">' + (opt.icon || '') + '</span>' +
                '<span class="question__option-label">' + opt.label + '</span></div>';
        }).join('');

        opts.querySelectorAll('.question__option').forEach(function(el) {
            el.addEventListener('click', function() {
                this.classList.add('selected');
                var idx = parseInt(this.dataset.index);

                responses.push({ question_id: q.id, option_index: idx });

                // Check if "sem álcool" was selected
                if (q.id === 'strength' && q.options[idx] && q.options[idx].tags) {
                    if (q.options[idx].tags.indexOf('sem-alcool') !== -1 || q.options[idx].tags.indexOf('zero') !== -1) {
                        choseNoAlcohol = true;
                        buildActiveQuestions();
                    }
                }

                setTimeout(function() {
                    currentQ++;
                    renderQuestion();
                }, 400);
            });
        });

        showScreen('screenQuestion');
    }

    // ─── Back Button ───
    window.quizGoBack = function() {
        if (currentQ > 0) {
            currentQ--;
            responses.pop(); // Remove last response
            renderQuestion();
        }
    };

    // ─── Submit Quiz ───
    async function submitQuiz() {
        showScreen('screenLoading');
        try {
            var controller = new AbortController();
            var timeout = setTimeout(function() { controller.abort(); }, 15000);

            var res = await fetch(API_BASE + '/quiz/result', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ responses: responses, customer_name: customerName, customer_phone: customerPhone }),
                signal: controller.signal,
            });
            clearTimeout(timeout);
            var data = await res.json();
            if (data.success && data.recommended && data.recommended.length) {
                sessionId = data.session_id || '';
                ratingToken = data.rating_token || '';
                ratingUrl = data.rating_url || '';
                renderResult(data.recommended);
            } else {
                // No results or error — show friendly message
                document.querySelector('.loading__text').textContent = 'Não encontramos drinks com esse perfil. Tente novamente!';
                setTimeout(function() { resetQuiz(); }, 3000);
            }
        } catch(e) {
            console.error('Quiz submit error:', e);
            document.querySelector('.loading__text').textContent = 'Erro de conexão. Tentando novamente...';
            setTimeout(function() { resetQuiz(); }, 3000);
        }
    }

    // ─── Render Result ───
    function renderResult(drinks) {
        lastRecommended = drinks;
        var title = customerName
            ? customerName + ', estes são seus drinks ideais!'
            : 'Drinks perfeitos para você!';
        document.getElementById('resultTitle').textContent = title;

        var cards = document.getElementById('resultCards');
        cards.innerHTML = drinks.map(function(d, i) {
            return '<div class="result-card" data-drink-id="' + d.id + '">' +
                '<span class="result-card__rank">' + (i+1) + '</span>' +
                '<img class="result-card__img" src="' + (d.image || '') + '" alt="' + d.title + '" onerror="this.style.background=\'rgba(255,255,255,.04)\'">' +
                '<div class="result-card__info">' +
                    '<div class="result-card__title">' + d.title + '</div>' +
                    '<div class="result-card__desc">' + (d.short_desc || '') + '</div>' +
                    (function(){ var p = d.price_formatted || ''; if (d.price_2_formatted) p += ' · ' + d.price_2_formatted; if (d.price_3_formatted) p += ' · ' + d.price_3_formatted; return p ? '<div class="result-card__price">' + (d.price_2_formatted ? 'a partir de ' : '') + d.price_formatted + (d.price_2_formatted || d.price_3_formatted ? '<div style="font-size:11px;color:rgba(255,255,255,.4);margin-top:2px">' + p + '</div>' : '') + '</div>' : ''; })() +
                    '<button class="result-card__cta" data-idx="' + i + '">' + CFG.ctaText + '</button>' +
                '</div></div>';
        }).join('');

        // CTA buttons
        cards.querySelectorAll('.result-card__cta').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var idx = parseInt(this.dataset.idx);
                chooseDrink(drinks[idx]);
            });
        });

        showScreen('screenResult');
    }

    // ─── Choose Drink → Confirm Screen ───
    async function chooseDrink(drink) {
        chosenDrink = drink;

        // Update server
        if (sessionId) {
            fetch(API_BASE + '/quiz/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: sessionId, chosen_drink_id: drink.id }),
            }).catch(function(){});
        }

        // Set confirm screen content
        document.getElementById('confirmImg').src = drink.image || '';
        var msg = customerName
            ? customerName + ', você escolheu o ' + drink.title + '!'
            : 'Ótima escolha! Você escolheu o ' + drink.title + '!';
        document.getElementById('confirmMsg').textContent = msg;
        document.getElementById('confirmSubMsg').textContent = CFG.confirmTxt;

        // Fetch full drink data (with food pairing)
        var drinkDetail = null;
        try {
            var dRes = await fetch(API_BASE + '/drinks/' + drink.id);
            if (dRes.ok) {
                var dJson = await dRes.json();
                if (dJson.success && dJson.drink) drinkDetail = dJson.drink;
            }
        } catch(e) { console.warn('Drink detail fetch error:', e); }

        // Context message
        var ctxEl = document.getElementById('confirmContext');
        var ctxMsg = (drinkDetail && drinkDetail.context_message) || drink.context_message || '';
        if (ctxMsg) {
            ctxEl.textContent = '💡 ' + ctxMsg;
            ctxEl.style.display = 'block';
        } else {
            ctxEl.style.display = 'none';
        }

        // Food pairing (from drink detail)
        var pTitle = document.getElementById('pairingTitle');
        var pCards = document.getElementById('pairingCards');
        var pairing = (drinkDetail && drinkDetail.food_pairing) || drink.food_pairing || [];
        if (pairing && pairing.length > 0) {
            pTitle.textContent = CFG.pairingTxt;
            pTitle.style.display = 'block';
            pCards.innerHTML = pairing.map(function(p) {
                return '<div class="pairing-card">' +
                    '<img class="pairing-card__img" src="' + (p.image || '') + '" alt="' + p.title + '" onerror="this.style.background=\'rgba(255,255,255,.04)\'">' +
                    '<div class="pairing-card__name">' + p.title + '</div></div>';
            }).join('');
        } else {
            pTitle.style.display = 'none';
            pCards.innerHTML = '';
        }

        showScreen('screenConfirm');
    }

    // ─── Confirm → Rating or Share ───
    document.getElementById('confirmNextBtn').addEventListener('click', function() {
        if (CFG.showRating) {
            document.getElementById('ratingTitle').textContent = CFG.ratingTxt;
            showScreen('screenRating');
        } else {
            finishQuiz();
        }
    });

    // ─── Rating Stars ───
    document.querySelectorAll('.rating__star').forEach(function(star) {
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.dataset.v);
            document.querySelectorAll('.rating__star').forEach(function(s) {
                s.classList.toggle('active', parseInt(s.dataset.v) <= selectedRating);
            });
        });
    });

    // ─── Finalize Quiz ───
    document.getElementById('finalizeBtn').addEventListener('click', finishQuiz);
    document.getElementById('ratingSkipBtn').addEventListener('click', finishQuiz);

    function finishQuiz() {
        // Send rating + completion to server
        if (sessionId) {
            var payload = { session_id: sessionId, completed: 1 };
            if (selectedRating > 0) payload.quiz_rating = selectedRating;
            fetch(API_BASE + '/quiz/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            }).catch(function(){});
        }

        // Show Final screen
        var finalTitle = customerName ? customerName + ', obrigado!' : 'Obrigado!';
        document.getElementById('finalTitle').textContent = finalTitle;
        document.getElementById('finalMsg').textContent = CFG.finalMsg || 'Aproveite a experiência!';
        if (CFG.postMsg) {
            document.getElementById('finalPostMsg').textContent = CFG.postMsg;
            document.getElementById('finalPostMsg').style.display = 'block';
        }
        showScreen('screenFinal');

        // Show WhatsApp notice if phone was provided
        if (customerPhone) {
            document.getElementById('whatsappNotice').style.display = 'block';
        }
    }

    // ─── Share ───
    document.getElementById('shareBtn').addEventListener('click', async function() {
        var btn = this;
        btn.textContent = 'Gerando...';
        btn.style.opacity = '0.6';

        try {
            var img = await generateShareCard(lastRecommended);
            var drinkNames = lastRecommended.map(function(d){return d.title}).join(', ');
            var shareText = CFG.shareTxt
                ? CFG.shareTxt.replace('{estabelecimento}', SITE_NAME)
                : 'Fiz o quiz de drinks no ' + SITE_NAME + ' e as sugestões ideais para mim são:';
            shareText += ' ' + drinkNames + '! 🍸✨';
            if (ratingUrl) shareText += '\n\nAvalie o drink: ' + ratingUrl;

            if (navigator.share && navigator.canShare && navigator.canShare({files: [img.file]})) {
                await navigator.share({ title: 'Quiz de Drinks — ' + SITE_NAME, text: shareText, files: [img.file] });
            } else if (navigator.share) {
                await navigator.share({ title: 'Quiz de Drinks — ' + SITE_NAME, text: shareText, url: window.location.href });
            } else {
                var a = document.createElement('a');
                a.href = img.dataUrl;
                a.download = 'meu-drink-ideal.png';
                a.click();
            }
        } catch(e) {
            var text = 'Fiz o quiz de drinks no ' + SITE_NAME + ' e minhas sugestões são: ' + lastRecommended.map(function(d){return d.title}).join(', ') + '! 🍸✨\n' + window.location.href;
            if (navigator.clipboard) { navigator.clipboard.writeText(text); alert('Texto copiado! Cole no WhatsApp ou Instagram.'); }
        }
        // Track share action
        if (sessionId) {
            fetch(API_BASE + '/quiz/update', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ session_id: sessionId, shared: 1 })
            }).catch(function(){});
        }
        btn.textContent = 'Compartilhar 📱';
        btn.style.opacity = '1';
    });

    // ─── Retry (Final screen) ───
    document.getElementById('retryBtn').addEventListener('click', resetQuiz);

    // ─── Retry (Result screen) ───
    document.getElementById('resultRetryBtn').addEventListener('click', resetQuiz);

    function resetQuiz() {
        currentQ = 0; responses = []; customerName = ''; customerPhone = ''; choseNoAlcohol = false;
        selectedRating = 0; chosenDrink = null; sessionId = ''; ratingToken = '';
        document.querySelectorAll('.rating__star').forEach(function(s){ s.classList.remove('active'); });
        showScreen('screenWelcome');
    }

    // ─── Start ───
    document.getElementById('startBtn').addEventListener('click', async function() {
        if (!questions.length) await loadQuestions();
        if (!questions.length) return;
        buildActiveQuestions();
        if (CFG.askName) {
            showScreen('screenName');
        } else {
            renderQuestion();
        }
    });

    // ─── Name flow ───
    document.getElementById('nameNextBtn').addEventListener('click', function() {
        customerName = document.getElementById('nameInput').value.trim();
        customerPhone = document.getElementById('phoneInput').value.trim();
        renderQuestion();
    });
    document.getElementById('nameSkipBtn').addEventListener('click', function() {
        customerName = '';
        customerPhone = '';
        renderQuestion();
    });
    document.getElementById('nameInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('nameNextBtn').click(); }
    });

    // ─── Drink Rating Page (?rate=TOKEN) ───
    var drinkRateSelected = 0;

    document.querySelectorAll('#drinkRateStars .rating__star').forEach(function(star) {
        star.addEventListener('click', function() {
            drinkRateSelected = parseInt(this.dataset.v);
            document.querySelectorAll('#drinkRateStars .rating__star').forEach(function(s) {
                s.classList.toggle('active', parseInt(s.dataset.v) <= drinkRateSelected);
            });
        });
    });

    document.getElementById('drinkRateSubmit').addEventListener('click', function() {
        if (drinkRateSelected < 1) { alert('Selecione uma nota de 1 a 5.'); return; }
        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Enviando...';

        fetch(API_BASE + '/quiz/rate-drink', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: RATING_TOKEN, rating: drinkRateSelected }),
        }).then(function(r){ return r.json(); }).then(function(data) {
            if (data.success) {
                document.getElementById('drinkRateMsg').innerHTML = '<span style="color:#22c55e;font-size:18px">✅ Obrigado pela avaliação!</span>';
                btn.style.display = 'none';
                document.querySelectorAll('#drinkRateStars .rating__star').forEach(function(s) {
                    s.style.pointerEvents = 'none';
                });
                // Redirect to quiz after 3 seconds
                setTimeout(function() {
                    window.location.href = window.location.pathname;
                }, 3000);
            } else {
                document.getElementById('drinkRateMsg').innerHTML = '<span style="color:#e03c3c">' + (data.message || 'Erro ao enviar.') + '</span>';
                btn.disabled = false;
                btn.textContent = 'Enviar Avaliação';
            }
        }).catch(function() {
            document.getElementById('drinkRateMsg').innerHTML = '<span style="color:#e03c3c">Erro de conexão.</span>';
            btn.disabled = false;
            btn.textContent = 'Enviar Avaliação';
        });
    });

    // ─── Init: check if ?rate= or normal quiz ───
    if (RATING_TOKEN) {
        // Load drink rating page
        fetch(API_BASE + '/quiz/rating-info/' + RATING_TOKEN)
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (data.already_rated) {
                        document.getElementById('drinkRateMsg').innerHTML = '<span style="color:#22c55e;font-size:18px">✅ Você já avaliou este drink! Nota: ' + data.drink_rating + '/5</span>';
                        document.getElementById('drinkRateSubmit').style.display = 'none';
                        document.getElementById('drinkRateSubtitle').textContent = 'Obrigado pela sua avaliação!';
                        // Disable stars
                        document.querySelectorAll('#drinkRateStars .rating__star').forEach(function(s) {
                            s.style.pointerEvents = 'none';
                            s.style.opacity = '0.3';
                        });
                    }
                    if (data.drink) {
                        document.getElementById('drinkRateImg').src = data.drink.image || '';
                        document.getElementById('drinkRateName').textContent = data.drink.title || '';
                        if (data.customer_name) {
                            document.getElementById('drinkRateSubtitle').textContent = data.customer_name + ', como foi sua experiência com o drink ' + (data.drink.title || '') + '?';
                        }
                    }
                    showScreen('screenDrinkRate');
                } else {
                    // Invalid token — show normal quiz
                    loadQuestions();
                }
            }).catch(function() { loadQuestions(); });
    } else {
        loadQuestions();
    }

    async function generateShareCard(drinks) {
        const W = 1080, H = 1920;
        const canvas = document.createElement('canvas');
        canvas.width = W;
        canvas.height = H;
        const ctx = canvas.getContext('2d');

        // Background
        const bg = getComputedStyle(document.body).getPropertyValue('--bg').trim() || '#1A1A2E';
        const prim = getComputedStyle(document.body).getPropertyValue('--primary').trim() || '#C8962E';
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, W, H);

        // Subtle gradient overlay
        const grad = ctx.createLinearGradient(0, 0, W, H);
        grad.addColorStop(0, 'rgba(200,150,46,0.06)');
        grad.addColorStop(1, 'rgba(232,89,60,0.04)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, W, H);

        // Logo
        const logoEl = document.querySelector('.quiz-logo');
        if (logoEl && logoEl.src) {
            try {
                const logoImg = await loadImage(logoEl.src);
                const lh = 70, lw = (logoImg.width / logoImg.height) * lh;
                ctx.globalAlpha = 0.7;
                ctx.drawImage(logoImg, (W - lw) / 2, 80, lw, lh);
                ctx.globalAlpha = 1;
            } catch(e) {}
        }

        // Badge
        ctx.fillStyle = prim;
        ctx.globalAlpha = 0.15;
        roundRect(ctx, W/2 - 120, 190, 240, 36, 18);
        ctx.fill();
        ctx.globalAlpha = 1;
        ctx.fillStyle = prim;
        ctx.font = '500 14px "Outfit", sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('QUIZ DE DRINKS', W/2, 214);

        // Title
        ctx.fillStyle = '#ffffff';
        ctx.font = '700 48px "Playfair Display", serif';
        ctx.fillText('Meus drinks', W/2, 300);
        ctx.fillStyle = prim;
        ctx.fillText('ideais!', W/2, 360);

        // Drink cards
        let y = 430;
        for (let i = 0; i < Math.min(drinks.length, 3); i++) {
            const d = drinks[i];

            // Card background
            ctx.fillStyle = 'rgba(255,255,255,0.04)';
            roundRect(ctx, 60, y, W - 120, 340, 24);
            ctx.fill();
            ctx.strokeStyle = 'rgba(255,255,255,0.06)';
            ctx.lineWidth = 1;
            roundRect(ctx, 60, y, W - 120, 340, 24);
            ctx.stroke();

            // Rank badge
            ctx.fillStyle = prim;
            ctx.beginPath();
            ctx.arc(110, y + 40, 22, 0, Math.PI * 2);
            ctx.fill();
            ctx.fillStyle = '#fff';
            ctx.font = '700 18px "Outfit", sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(String(i + 1), 110, y + 46);

            // Image
            if (d.image) {
                try {
                    const drinkImg = await loadImage(d.image);
                    ctx.save();
                    roundRect(ctx, 90, y + 70, 240, 240, 16);
                    ctx.clip();
                    ctx.drawImage(drinkImg, 90, y + 70, 240, 240);
                    ctx.restore();
                } catch(e) {
                    ctx.fillStyle = 'rgba(255,255,255,0.05)';
                    roundRect(ctx, 90, y + 70, 240, 240, 16);
                    ctx.fill();
                }
            }

            // Text
            ctx.textAlign = 'left';
            ctx.fillStyle = '#fff';
            ctx.font = '600 34px "Playfair Display", serif';
            ctx.fillText(d.title || '', 360, y + 120);

            ctx.fillStyle = 'rgba(255,255,255,0.5)';
            ctx.font = '400 20px "Outfit", sans-serif';
            const desc = (d.short_desc || '').substring(0, 60) + ((d.short_desc || '').length > 60 ? '...' : '');
            wrapText(ctx, desc, 360, y + 160, 580, 28);

            if (d.price_formatted) {
                ctx.fillStyle = prim;
                ctx.font = '600 32px "Playfair Display", serif';
                ctx.fillText(d.price_formatted, 360, y + 280);
            }

            y += 370;
        }

        // Footer
        ctx.textAlign = 'center';
        ctx.fillStyle = 'rgba(255,255,255,0.2)';
        ctx.font = '400 18px "Outfit", sans-serif';
        ctx.fillText('Escaneie o QR Code e faça você também!', W/2, H - 100);
        ctx.fillStyle = 'rgba(255,255,255,0.1)';
        ctx.font = '400 14px "Outfit", sans-serif';
        ctx.fillText(window.location.host, W/2, H - 60);

        // Convert to blob
        const dataUrl = canvas.toDataURL('image/png');
        const blob = await (await fetch(dataUrl)).blob();
        const file = new File([blob], 'meu-drink-ideal.png', {type: 'image/png'});

        return { dataUrl, file };
    }

    function loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    }

    function roundRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    function wrapText(ctx, text, x, y, maxW, lineH) {
        const words = text.split(' ');
        let line = '';
        for (const w of words) {
            const test = line + w + ' ';
            if (ctx.measureText(test).width > maxW && line) {
                ctx.fillText(line.trim(), x, y);
                line = w + ' ';
                y += lineH;
            } else {
                line = test;
            }
        }
        ctx.fillText(line.trim(), x, y);
    }

})();
</script>
</body>
</html>
