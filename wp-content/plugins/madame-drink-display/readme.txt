=== Drink Display ===
Contributors: amaurysantos
Tags: drinks, menu, digital signage, smart tv, tablet, quiz, restaurant, elementor
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Cardápio digital de drinks para Smart TVs, Tablets e Quiz interativo via QR Code.

== Description ==

O Drink Display transforma seu cardápio de drinks em uma ferramenta ativa de vendas.
Conecta-se ao CPT existente do JetEngine/Crocoblock e oferece três modos de exibição:

= Modo TV =
Slideshow automático para Smart TVs com foto, nome, descrição e preço.
3 layouts: Fullscreen, Split e Grid. Suporta vídeos curtos entre slides.

= Modo Tablet =
Cardápio interativo com zoom, galeria e vídeos para tablets no salão.
Modo quiosque com PWA, screensaver e proteção contra navegação.

= Quiz de Drinks =
Experiência gamificada via QR Code. O cliente responde 3-5 perguntas
e recebe sugestões personalizadas com opção de compartilhar no WhatsApp/Instagram.

= Recursos Adicionais =
* Sistema de Logos com 3 níveis de prioridade (estabelecimento, evento, dispositivo)
* Auto-Tagger que analisa descrições e atribui tags automaticamente
* QR Code Generator com card para impressão em mesas
* Widgets nativos do Elementor (Quiz QR Code e Vitrine de Drinks)
* Shortcodes: [mdd_quiz_qr], [mdd_quiz_button], [mdd_drink_showcase]
* PWA com manifest e service worker para modo quiosque em tablets
* REST API completa acessível via /wp-json/mdd/v1/
* Tokens individuais por dispositivo com controle de acesso

== Installation ==

1. Faça upload do ZIP em Plugins → Adicionar Novo → Upload
2. Ative o plugin
3. Vá em Drink Display → Geral → selecione o CPT de drinks
4. Aba Logos → suba o logo do estabelecimento
5. Aba Auto-Tagger → clique "Auto-tagear todos os drinks"
6. Aba Tokens → gere tokens para cada TV/tablet
7. Configurações → Links Permanentes → clique "Salvar"
8. Acesse as URLs geradas nos dispositivos

== Changelog ==

= 1.0.0 =
* Versão inicial completa com todos os módulos
