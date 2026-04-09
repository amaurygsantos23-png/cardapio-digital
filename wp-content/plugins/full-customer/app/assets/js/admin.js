(function ($) {
  "use strict";

  $("#full-connect").on("submit", function (e) {
    e.preventDefault();
    const $form = $(this);

    $form.find("button").addClass("loading");

    $.post(ajaxurl, $form.serialize(), function (response) {
      $form.find("button").removeClass("loading");
      console.log(response);

      fireAlert(
        response?.success ? "success" : "error",
        response?.data || "Algo deu errado, tente novamente por favor"
      ).then(() => {
        if (response?.success) location.reload();
      });
    });
  });

  const fireAlert = (type, message) => {
    const titles = {
      success: "🎉 Tudo certo",
      error: "📢 Algo deu errado",
      warning: "🧐 Ei",
    };

    return Swal.fire({
      titleText: titles[type],
      text: message,
    });
  };

  // WIDGETS
  // ========================
  const $container = $("#full-widgets");
  if ($container.length) {
    const changed = [];

    const categoryContainerTemplate = $("#widget-container-template").html();
    const widgetCardTemplate = $("#widget-template").html();
    const widgetToggleTemplate = $("#widget-toggle-template").html();

    $.get(
      FULL.dashboard_url + "widgets",
      { site: FULL.site_url },
      function (response) {
        for (const widget of response) {
          const categoryKey = "fw-" + slugfy(widget.category);
          let $category = $("#" + categoryKey);

          if (!$category.length) {
            $category = $(categoryContainerTemplate).clone();
            $category.find("h4").text(widget.category);
            $category.attr("id", categoryKey);
            $container.append($category);
          }

          const $widget = $(widgetCardTemplate).clone();

          $widget.find("img").attr("src", widget.icon).attr("alt", widget.name);
          $widget.find(".widget-name").text(widget.name);
          $widget.find(".widget-description").text(widget.description);
          $widget.find("a").attr("href", widget.url);

          const $toggle =
            widget.purchased || "native" === widget.tier
              ? $(widgetToggleTemplate).clone()
              : $("<div></div>");

          $toggle.find("label").attr("for", "input-" + widget.key);
          $toggle
            .find("input")
            .attr("id", "input-" + widget.key)
            .attr("value", widget.key)
            .attr("checked", FULL.enabled_services.includes(widget.key));

          if ("addon" === widget.tier && !widget.purchased) {
            $toggle.html("");
          } else if (widget.required) {
            $toggle.text("Obrigatório");
            $widget.addClass("widget-required");
          }

          $widget.find(".status").append($toggle);

          $category.find(".widgets-grid").append($widget);
        }
      }
    );

    $container.on("change", "input", function () {
      const key = $(this).val();
      const index = changed.indexOf(key);

      index > -1 ? changed.splice(index, 1) : changed.push(key);
    });

    $("#update-widgets").on("click", function () {
      const count = changed.length;
      if (!count) {
        Swal.fire(
          "Ops",
          "O status de nenhuma extensão foi modificado para atualizarmos.",
          "info"
        );
        return;
      }

      const legend = count > 1 ? " extensões" : " extensão";

      Swal.fire({
        titleText: "Quase lá!",
        html: "Tem certeza que deseja alterar o status de " + count + legend,
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: "Sim, continuar",
        cancelButtonText: "Voltar",
        showLoaderOnConfirm: true,
        backdrop: true,
        allowOutsideClick: () => !Swal.isLoading(),
        customClass: {
          container: "full-template-popup full-template-dependencies-popup",
        },
        preConfirm: () => {
          toggleWidgetsStatus(changed);

          return new Promise((resolve, reject) => {
            let index = 0;
            let messages = [
              "Preparando para decolar...",
              "Checando compatibilidades e dependências...",
              "Configurando as extensões...",
              "Conferindo últimos ajustes...",
              "Aperte os cintos, vamos decolar...",
            ];

            let interval = setInterval(() => {
              const message = messages[index];

              if (!message) {
                clearInterval(interval);
                resolve();
              }

              $("#swal2-html-container").text(message);

              index++;
            }, 1000);
          });
        },
      }).then((response) => {
        if (!response.isConfirmed) {
          return;
        }

        location.reload();
      });
    });

    function toggleWidgetsStatus(widgets) {
      const endpoint = "full-customer/toggle-widgets?widgets=" + widgets.join();
      return fetch(FULL.rest_url + endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": FULL.auth,
        },
      });
    }
  }

  // WIDGETS SETTINGS FORMS
  // ========================
  $("form.full-widget-form").on("submit", function (e) {
    e.preventDefault();

    const $form = jQuery(this);
    const $btn = $form.find("button");
    $btn.addClass("loading");

    $(window).trigger("full/form-submitted/" + $form.attr("id"));

    jQuery.post(ajaxurl, $form.serialize(), function (response) {
      $btn.removeClass("loading");

      $(window).trigger("full/form-received/" + $form.attr("id"), response);

      fireAlert(
        response?.success ? "success" : "warning",
        response?.success
          ? "Configurações atualizadas com sucesso"
          : "Falha ao atualizar as configurações, tente novamente por favor"
      );

      if (response?.data?.reload) {
        location.reload();
      }
    });
  });

  // TABS
  // ========================
  const $tabLinks = $(".full-tab-nav a");
  $tabLinks.on("click", function (e) {
    e.preventDefault();

    const $target = $($(this).attr("href"));

    $tabLinks.not(this).removeClass("active");
    $(this).addClass("active");

    $(".full-tab-panel").hide();
    $target.show();
  });

  $tabLinks.first().trigger("click");

  // ACCESS TOKEN
  // ========================
  $("[data-js='full-generate-temporary-token']").on("click", function (e) {
    e.preventDefault();

    const $btn = $(this);
    $btn.addClass("disabled").text("Gerando...").attr("disabled", true);

    const data = {
      action: "full/generate-temporary-token",
      userId: $(this).data("user"),
    };

    $.post(ajaxurl, data, function ({ data }) {
      $btn.removeClass("disabled").text("Gerar link").attr("disabled", false);

      prompt(
        "URL temporária criada com sucesso! Este token é de uso único e recriado em cada nova solicitação",
        data
      );

      navigator.clipboard.writeText(data);
    });
  });

  function slugfy(str) {
    str = str.replace(/^\s+|\s+$/g, "");
    str = str.toLowerCase();

    const from = "àáäâèéëêìíïîòóöôùúüûñçěščřžýúůďťň·/_,:;";
    const to = "aaaaeeeeiiiioooouuuuncescrzyuudtn------";

    for (let i = 0, l = from.length; i < l; i++) {
      str = str.replace(new RegExp(from.charAt(i), "g"), to.charAt(i));
    }

    return str
      .replace(".", "-") // replace a dot by a dash
      .replace(/[^a-z0-9 -]/g, "") // remove invalid chars
      .replace(/\s+/g, "-") // collapse whitespace and replace by a dash
      .replace(/-+/g, "-") // collapse dashes
      .replace(/\//g, ""); // collapse all forward-slashes
  }

  $("[data-modal]").on("click", function (e) {
    e.preventDefault();

    const $modal = $(this).data("modal");
    $(`${$modal}`).addClass("open");

    $(document).trigger("modal-opened/" + $(`${$modal}`).attr("id"));
  });

  $(".full-modal-overlay, .full-modal-close").on("click", function (e) {
    e.preventDefault();

    const $modal = $(".full-modal-container.open");

    $(".full-modal-container").removeClass("open");
    $(document).trigger("modal-closed/" + $modal.attr("id"));
  });
})(jQuery);
