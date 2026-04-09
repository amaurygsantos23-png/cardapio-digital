<?php defined('ABSPATH') || exit;
$site_url  = home_url();
$rest_url  = rest_url('mdd/v1');
$quiz_url  = home_url('/display/quiz/');
$post_type = get_option('mdd_drink_post_type', 'drink');
?>

<h1 class="mdd-title" style="margin-bottom:24px">
    <span class="dashicons dashicons-book"></span>
    Guia de Implementação
</h1>

<!-- TOC -->
<div class="mdd-guia-toc">
    <h3>📌 Índice — Siga na ordem</h3>
    <div class="mdd-guia-toc-grid">
        <a href="#" onclick="mddGuiaOpen('gs0');return false"><span class="mdd-toc-n">0</span> Instalação + Licença</a>
        <a href="#" onclick="mddGuiaOpen('gs1');return false"><span class="mdd-toc-n">1</span> Geral — CPT, Campos, Cores</a>
        <a href="#" onclick="mddGuiaOpen('gs2');return false"><span class="mdd-toc-n">2</span> Logos e Modo Evento</a>
        <a href="#" onclick="mddGuiaOpen('gs3');return false"><span class="mdd-toc-n">3</span> Modo TV (Slideshow)</a>
        <a href="#" onclick="mddGuiaOpen('gs4');return false"><span class="mdd-toc-n">4</span> Modo Tablet (Interativo)</a>
        <a href="#" onclick="mddGuiaOpen('gs5');return false"><span class="mdd-toc-n">5</span> Quiz de Drinks</a>
        <a href="#" onclick="mddGuiaOpen('gs6');return false"><span class="mdd-toc-n">6</span> QR Code + Auto-Tagger</a>
        <a href="#" onclick="mddGuiaOpen('gs7');return false"><span class="mdd-toc-n">7</span> Tokens (Dispositivos)</a>
        <a href="#" onclick="mddGuiaOpen('gs8');return false"><span class="mdd-toc-n">8</span> Estatísticas + ROI</a>
        <a href="#" onclick="mddGuiaOpen('gs9');return false"><span class="mdd-toc-n">9</span> Solução de Problemas</a>
    </div>
</div>

<!-- ═══ STEP 0 — Instalação + Licença ═══ -->
<details class="mdd-guia-step open" id="gs0">
    <summary><span class="mdd-step-n">0</span><div><strong>Instalação + Licença</strong><span>Upload, ativação e registro da licença</span></div><span class="mdd-arrow">▸</span></summary>
    <div class="mdd-step-body">
        <div class="mdd-check-list">
            <label><input type="checkbox"> Vá em <strong>Plugins → Adicionar Novo → Enviar plugin</strong></label>
            <label><input type="checkbox"> Selecione <code>drink-display-v<?php echo MDD_VERSION; ?>.zip</code> e clique <strong>"Instalar agora"</strong></label>
            <label><input type="checkbox"> Clique em <strong>"Ativar plugin"</strong> — o menu "Drink Display" aparece na barra lateral</label>
            <label><input type="checkbox"> <strong>OBRIGATÓRIO:</strong> Vá em <strong>Configurações → Links Permanentes</strong> e clique <strong>"Salvar Alterações"</strong> (sem alterar nada)</label>
            <label><input type="checkbox"> Vá em <strong>Drink Display → aba "Licença"</strong></label>
            <label><input type="checkbox"> Cole a chave de licença recebida (formato: XXXX-XXXX-XXXX-XXXX-XXXX-XXXX)</label>
            <label><input type="checkbox"> Clique <strong>"Ativar Licença"</strong> — status deve ficar verde</label>
        </div>
        <div class="mdd-info-box danger">
            <strong>⚠️ Por que salvar Permalinks?</strong> O plugin registra 3 rotas: <code>/display/tv/</code>, <code>/display/tablet/</code> e <code>/display/quiz/</code>. Sem reprocessar as URLs, todas retornam 404.
        </div>
        <div class="mdd-info-box">
            <strong>🔑 Licença:</strong> Sem licença ativa, o admin funciona normalmente mas os displays (TV, Tablet, Quiz) ficam bloqueados com tela de cadeado. A licença é verificada automaticamente a cada 24h.
            Para testar sem servidor de licenças, adicione no <code>wp-config.php</code>:<br>
            <code style="display:block;margin-top:6px;padding:8px 12px;background:rgba(0,0,0,.3);border-radius:6px">define('MDD_DEV_MODE', true);</code>
        </div>
    </div>
</details>

<!-- ═══ STEP 1 — Geral ═══ -->
<details class="mdd-guia-step" id="gs1">
    <summary><span class="mdd-step-n">1</span><div><strong>Geral — CPT, Mapeador de Campos, Cores</strong><span>Selecionar origem dos drinks, mapear campos e definir cores</span></div><span class="mdd-arrow">▸</span></summary>
    <div class="mdd-step-body">
        <p>Acesse <strong>Drink Display → aba "Geral"</strong>:</p>
        <div class="mdd-check-list">
            <label><input type="checkbox"> <strong>CPT de Drinks:</strong> Selecione o tipo de post onde seus drinks estão cadastrados (ex: <code><?php echo esc_html($post_type); ?></code>). Se usa JetEngine, veja o slug em JetEngine → Post Types.</label>
            <label><input type="checkbox"> <strong>CPT de Pratos (opcional):</strong> Se tem pratos cadastrados em outro CPT, selecione aqui. Permite harmonização no Quiz: "Esse drink combina com Ceviche!"</label>
            <label><input type="checkbox"> <strong>Mapeador de Campos:</strong> O sistema detecta os meta fields do seu CPT. Associe cada campo (preço, descrição, vídeo, ingredientes, galeria, variantes, harmonização, mensagem contextual) ao campo correto. Se deixar em branco, o plugin busca automaticamente pelos nomes mais comuns.</label>
            <label><input type="checkbox"> <strong>Cores:</strong> Configure 3 cores (primária, fundo, destaque) usando os color pickers. O preview ao vivo mostra como ficará um card de drink.</label>
            <label><input type="checkbox"> Clique <strong>"Salvar Configurações"</strong></label>
        </div>
        <div class="mdd-info-box">
            <strong>💡 De onde vêm os dados?</strong> Nome = título do post. Foto = imagem destacada. Preço, descrição e vídeo = campos mapeados ou auto-detectados. O plugin também adiciona campos próprios ("Descrição Curta Display" e "Vídeo Curto") na tela de edição de cada drink.
        </div>
    </div>
</details>

<!-- ═══ STEP 2 — Logos ═══ -->
<details class="mdd-guia-step" id="gs2">
    <summary><span class="mdd-step-n">2</span><div><strong>Logos e Modo Evento</strong><span>Logo do estabelecimento, tamanho por tela e logo temporário para eventos</span></div><span class="mdd-arrow">▸</span></summary>
    <div class="mdd-step-body">
        <p>Acesse <strong>Drink Display → aba "Logos"</strong>:</p>
        <div class="mdd-check-list">
            <label><input type="checkbox"> <strong>Logo:</strong> Suba via biblioteca de mídia. Formato ideal: PNG transparente, horizontal, 400×150px. O preview multi-tela mostra como fica na TV, Tablet e Quiz.</label>
            <label><input type="checkbox"> <strong>Tamanho por tela:</strong> Ajuste os 3 sliders — TV (30-120px), Tablet (25-100px), Quiz (40-160px). O preview atualiza em tempo real.</label>
            <label><input type="checkbox"> <strong>Modo Evento:</strong> Ative APENAS para eventos particulares. Quando ativo, substitui o logo em TODAS as telas. <strong>Desative após o evento!</strong></label>
            <label><input type="checkbox"> Clique <strong>"Salvar Logos"</strong></label>
        </div>
        <div class="mdd-info-box">
            <strong>📊 Hierarquia:</strong> 1º Logo do Token → 2º Logo do Evento → 3º Logo do Estabelecimento
        </div>
    </div>
</details>

<!-- ═══ STEP 3 — TV ═══ -->
<details class="mdd-guia-step" id="gs3">
    <summary><span class="mdd-step-n">3</span><div><strong>Modo TV (Slideshow)</strong><span>Layout, transições, QR Code e slides agendados</span></div><span class="mdd-arrow">▸</span></summary>
    <div class="mdd-step-body">
        <p>Acesse <strong>Drink Display → aba "TV"</strong>:</p>
        <div class="mdd-check-list">
            <label><input type="checkbox"> <strong>Layout:</strong> Fullscreen (55"+), Split (32-50") ou Grid (4 drinks). Cada um com preview visual.</label>
            <label><input type="checkbox"> <strong>Slideshow:</strong> Duração (5-20s) e transição (fade, slide, zoom).</label>
            <label><input type="checkbox"> <strong>Toggles:</strong> Exibir preço e QR Code do Quiz (com posição e texto customizáveis).</label>
            <label><input type="checkbox"> <strong>Slides personalizados:</strong> Promoções, Happy Hour, boas-vindas. Com agendamento por dia/hora.</label>
            <label><input type="checkbox"> Clique <strong>"Salvar Configurações TV"</strong></label>
        </div>
        <div class="mdd-info-box">
            <strong>📺 Para colocar na TV:</strong> Gere um token tipo TV (aba Tokens), copie a URL e abra no navegador da Smart TV. Dados atualizam a cada 5 min automaticamente.
        </div>
    </div>
</details>

<!-- ═══ STEP 4 — Tablet ═══ -->
<details class="mdd-guia-step" id="gs4">
    <summary><span class="mdd-step-n">4</span><div><strong>Modo Tablet (Interativo)</strong><span>Grid, filtros, modal, fontes e screensaver</span></div><span class="mdd-arrow">▸</span></summary>
    <div class="mdd-step-body">
        <p>Acesse <strong>Drink Display → aba "Tablet"</strong>:</p>
        <div class="mdd-check-list">
            <label><input type="checkbox"> <strong>Grid:</strong> Colunas por orientação — landscape (2-3) e portrait (1-2).</label>
            <label><input type="checkbox"> <strong>Cards:</strong> Toggles para preço, badge de categoria e descrição curta.</label>
            <label><input type="checkbox"> <strong>Textos + Fontes:</strong> Header, botão Quiz, screensaver. 5 opções para títulos, 5 para corpo.</label>
            <label><input type="checkbox"> <strong>Screensaver:</strong> 15-300 segundos de inatividade → logo com "Toque para explorar".</label>
            <label><input type="checkbox"> Clique <strong>"Salvar Configurações Tablet"</strong></label>
        </div>
        <div class="mdd-info-box">
            <strong>📱 Dica:</strong> No Chrome do tablet, ative modo kiosk (tela cheia) para ocultar a barra de endereço. O screensaver protege a tela.
        </div>
    </div>
</details>

<!-- ═══ STEP 5 — Quiz ═══ -->
<details class="mdd-guia-step" id="gs5">
    <summary><span class="mdd-step-n">5</span><div><strong>Quiz de Drinks</strong><span>Fluxo de 7 telas, avaliação dupla e harmonização</span></div><span class="mdd-arrow">▸</span></summary>
    <div class="mdd-step-body">
        <p>Acesse <strong>Drink Display → aba "Quiz"</strong>:</p>
        <div class="mdd-check-list">
            <label><input type="checkbox"> <strong>Perguntas:</strong> 5 perguntas pré-configuradas com tags de perfil.</label>
            <label><input type="checkbox"> <strong>Nome:</strong> Toggle para perguntar "Como posso te chamar?"</label>
            <label><input type="checkbox"> <strong>Lógica:</strong> "Sem álcool" → pula pergunta sobre base (vodka/gin/rum).</label>
            <label><input type="checkbox"> <strong>Textos:</strong> CTA, confirmação, harmonização e avaliação.</label>
            <label><input type="checkbox"> <strong>Avaliação:</strong> Toggle para nota de 5 estrelas.</label>
            <label><input type="checkbox"> Clique <strong>"Salvar Configurações Quiz"</strong></label>
        </div>
        <div class="mdd-info-box">
            <strong>⭐ Avaliação dupla:</strong> 1) Quiz (imediata, 5 estrelas) — mede se o Quiz é divertido. 2) Drink (pós-experiência, link curto) — mede satisfação com o drink escolhido. Ambas aparecem separadas nas Estatísticas.<br><br>
            <strong>Fluxo:</strong> Boas-vindas → Nome → Perguntas → Resultado (top 3 + CTA) → Confirmação + Harmonização → Avaliação → Compartilhamento
        </div>
    </div>
</details>

<!-- ═══ STEP 6 — QR + Tagger ═══ -->
<details class="mdd-guia-step" id="gs6">
    <summary><span class="mdd-step-n">6</span><div><strong>QR Code + Auto-Tagger</strong><span>QR Codes com cores e tags automáticas para o Quiz</span></div><span class="mdd-arrow">▸</span></summary>
    <div class="mdd-step-body">
        <p><strong>QR Code</strong> (aba "QR Code"):</p>
        <div class="mdd-check-list">
            <label><input type="checkbox"> Configure cores do QR (pontos + fundo) e salve.</label>
            <label><input type="checkbox"> Baixe em 3 tamanhos: 200px, 400px (ideal mesas), 800px.</label>
            <label><input type="checkbox"> Use o card de impressão (A6) para plastificar e colocar nas mesas.</label>
            <label><input type="checkbox"> QR personalizado: domínio fixo + path editável.</label>
        </div>
        <p style="margin-top:16px"><strong>Auto-Tagger</strong> (aba "Auto-Tagger"):</p>
        <div class="mdd-check-list">
            <label><input type="checkbox"> Clique "Auto-tagear todos" — analisa e atribui tags automaticamente.</label>
            <label><input type="checkbox"> Revise a tabela: verde = OK, vermelho = sem tags.</label>
            <label><input type="checkbox"> Exporte CSV para controle.</label>
        </div>
        <div class="mdd-info-box">
            <strong>🏷️ Importante:</strong> Drinks sem tags não são recomendados pelo Quiz. Rode o Auto-Tagger sempre que cadastrar drinks novos.
        </div>
    </div>
</details>

<!-- ═══ STEP 7 — Tokens ═══ -->
<details class="mdd-guia-step" id="gs7">
    <summary><span class="mdd-step-n">7</span><div><strong>Tokens (Dispositivos)</strong><span>Conectar TVs e Tablets ao sistema</span></div><span class="mdd-arrow">▸</span></summary>
    <div class="mdd-step-body">
        <p>Acesse <strong>Drink Display → aba "Tokens"</strong>:</p>
        <div class="mdd-check-list">
            <label><input type="checkbox"> Nome descritivo + tipo (TV ou Tablet) → "Gerar Token".</label>
            <label><input type="checkbox"> Copie a URL e abra no navegador do dispositivo.</label>
            <label><input type="checkbox"> Overrides opcionais: filtro por categoria, layout diferente, logo exclusivo.</label>
        </div>
        <div class="mdd-info-box">
            <strong>🔒 Segurança:</strong> Tokens podem ser revogados individualmente. Se um dispositivo for perdido, revogue o token — o display para imediatamente.
        </div>
    </div>
</details>

<!-- ═══ STEP 8 — Stats ═══ -->
<details class="mdd-guia-step" id="gs8">
    <summary><span class="mdd-step-n">8</span><div><strong>Estatísticas + ROI</strong><span>Volume, conversão, satisfação e vendas assistidas</span></div><span class="mdd-arrow">▸</span></summary>
    <div class="mdd-step-body">
        <p>Acesse <strong>Drink Display → aba "Estatísticas"</strong>. Dashboard completo com:</p>
        <div class="mdd-check-list">
            <label><input type="checkbox"> <strong>KPIs:</strong> Quizzes total/hoje, média 7d, taxa conclusão, notas quiz/drink.</label>
            <label><input type="checkbox"> <strong>Gráfico:</strong> Volume dos últimos 14 dias (barras CSS).</label>
            <label><input type="checkbox"> <strong>Conversão:</strong> Recomendado vs escolhido (top 5 drinks).</label>
            <label><input type="checkbox"> <strong>Satisfação:</strong> Nota por drink (pós-experiência).</label>
            <label><input type="checkbox"> <strong>ROI:</strong> Drinks escolhidos × preço médio = vendas assistidas estimadas.</label>
            <label><input type="checkbox"> <strong>Saúde:</strong> Drinks com foto, com tags, dispositivos conectados.</label>
        </div>
    </div>
</details>

<!-- ═══ STEP 9 — Troubleshooting ═══ -->
<details class="mdd-guia-step" id="gs9">
    <summary><span class="mdd-step-n">9</span><div><strong>Solução de Problemas</strong><span>Diagnóstico rápido dos problemas mais comuns</span></div><span class="mdd-arrow">▸</span></summary>
    <div class="mdd-step-body">
        <div class="mdd-faq">
            <div class="mdd-faq-item"><strong>❓ URLs /display/ retornam 404</strong><p>Configurações → Links Permanentes → Salvar. Se persistir, desative e reative o plugin.</p></div>
            <div class="mdd-faq-item"><strong>❓ Tela de cadeado "Licença Expirada"</strong><p>Aba Licença → verifique status. Para teste: <code>define('MDD_DEV_MODE', true);</code> no wp-config.php.</p></div>
            <div class="mdd-faq-item"><strong>❓ Token inválido ou revogado</strong><p>Aba Tokens → confira status. "Revogado" → Reativar. Excluído → gere novo.</p></div>
            <div class="mdd-faq-item"><strong>❓ Nenhum drink aparece</strong><p>Verifique: CPT correto na aba Geral + drinks "Publicados" + pelo menos 1 com imagem.</p></div>
            <div class="mdd-faq-item"><strong>❓ Preço não aparece</strong><p>Aba Geral → Mapeador de Campos → mapeie o campo de preço correto do seu CPT.</p></div>
            <div class="mdd-faq-item"><strong>❓ Quiz recomenda errado</strong><p>Aba Auto-Tagger → rode o processo. Revise drinks com poucas tags.</p></div>
            <div class="mdd-faq-item"><strong>❓ TV não atualiza</strong><p>Dados atualizam a cada 5 min. Aguarde ou F5 no navegador da TV.</p></div>
            <div class="mdd-faq-item"><strong>❓ Harmonização não aparece</strong><p>Aba Geral → selecione CPT de Pratos + mapeie o campo food_pairing.</p></div>
            <div class="mdd-faq-item"><strong>❓ Funciona sem JetEngine?</strong><p><strong>Sim.</strong> Qualquer CPT + ACF, Pods ou campos nativos.</p></div>
        </div>
        <div class="mdd-info-box success" style="margin-top:20px">
            <strong>🎉 Tudo pronto!</strong> TVs, tablets e Quiz funcionando. Acompanhe na aba Estatísticas.
        </div>
    </div>
</details>

<div class="mdd-guia-footer">
    <strong>Drink Display</strong> v<?php echo MDD_VERSION; ?> · Guia de Implementação<br>
    Desenvolvido por <a href="https://a3tecnologias.com" target="_blank">Amaury Santos · A3 Tecnologias</a>
</div>

<script>
function mddGuiaOpen(id){
    var el=document.getElementById(id);
    if(el){el.open=true;setTimeout(function(){el.scrollIntoView({behavior:'smooth',block:'start'})},100)}
}
</script>
