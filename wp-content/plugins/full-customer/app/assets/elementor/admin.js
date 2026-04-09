(function ($) {
  "use strict";

  let canBeLoaded = true;

  const IN_ELEMENTOR = typeof window.elementor !== "undefined";

  const SWAL_SETTINGS = {
    elementor: (item) => {
      return {
        titleText: item.title,
        showConfirmButton: true,
        showDenyButton: true,
        confirmButtonText: "Inserir na página",
        denyButtonText: "Cancelar",
        showLoaderOnConfirm: true,
        showLoaderOnDeny: true,
        backdrop: true,
        allowOutsideClick: () => !Swal.isLoading(),
        html: "<p>Adicione este template na sua página agora mesmo!</p>",
        customClass: {
          container: "full-template-popup",
        },
        preConfirm: () => installTemplateItem("builder", item),
      };
    },
    admin: (item) => ({
      titleText: item.title,
      showConfirmButton: true,
      showDenyButton: true,
      confirmButtonText: "Inserir como página",
      denyButtonText: "Inserir como modelo",
      showLoaderOnConfirm: true,
      showLoaderOnDeny: true,
      backdrop: true,
      allowOutsideClick: () => !Swal.isLoading(),
      html:
        "<p>Crie uma nova página a partir deste modelo para disponibilizá-la como uma página de rascunho em sua lista de páginas.</p>" +
        "<p>Importe este modelo para sua biblioteca para disponibilizá-lo em sua lista de modelos salvos do Elementor para uso futuro.</p>",
      customClass: {
        container: "full-template-popup",
      },
      preDeny: () => installTemplateItem("template", item),
      preConfirm: () => installTemplateItem("page", item),
    }),
    pack: (item) => ({
      titleText: item.title,
      showConfirmButton: true,
      showDenyButton: true,
      confirmButtonText: "Inserir como páginas",
      denyButtonText: "Inserir como modelos",
      showLoaderOnConfirm: true,
      showLoaderOnDeny: true,
      backdrop: true,
      allowOutsideClick: () => !Swal.isLoading(),
      html:
        "<p>Ao importar um pack, todos os templates serão importados em seu site WordPress como páginas ou modelos.</p>" +
        "<p>A importação do pack pode demorar um pouco dependendo da quantidade de templates que ele possuir. Não feche a página durante o processo.</p>",
      customClass: {
        container: "full-template-popup",
      },
      preDeny: () => installTemplateItem("template", item),
      preConfirm: () => installTemplateItem("page", item),
    }),
  };

  const filterTemplate = () => $($("#filter-template").html());

  const getCurrentPage = () => {
    const page = parseInt($("#response-container").data("page"));
    return isNaN(page) ? 1 : page;
  };

  const resetAndFetchTemplates = (page = 1) => {
    $("#response-container").data("page", page).html("");
    fetchTemplates();
  };

  const fetchTemplates = () => {
    canBeLoaded = false;

    const page = getCurrentPage();

    const data = {
      origin: $("#response-container").data("type"),
      price: getCurrentPriceFilter(),
      site: FULL.site_url,
      categories: getCurrentCategoriesFilter(),
      search: getCurrentSearch(),
      types: getCurrentTypesFilter(),
      segment: getCurrentSegmentFiler(),
      search: $("#template-searcher input").val()
        ? $("#template-searcher input").val().trim()
        : "",
    };

    const endpoint =
      "cloud" === data.origin ? "templates/cloud/" : "templates/";
    const apiUrl = FULL.dashboard_url + endpoint + page;

    toggleLoader();

    $.getJSON(apiUrl, data, function (response) {
      updatePagination(response);

      toggleLoader();

      $("#response-container").data("page", page + 1);

      if (1 === page && !response.items.length) {
        $("#no-items").show();
        return;
      }

      $("#no-items").hide();

      for (const item of response.items) {
        const html = parseTemplateHtml(item);
        $("#response-container").append(html);
      }

      canBeLoaded = response.totalPages > response.currentPage;

      $("#endpoint-viewport")[0].scrollIntoView({
        behavior: "smooth",
      });
    });
  };

  const updatePagination = (response) => {
    const $pagination = $(document).find("#full-templates-pagination");

    if (1 >= response.totalPages) {
      $pagination.hide();
      return;
    }

    $pagination.find('[data-js="current-page"]').text(response.currentPage);
    $pagination.find('[data-js="total-pages"]').text(response.totalPages);

    if (1 == response.currentPage) {
      $pagination.find('[data-js="previous-page"]').attr("disabled", true);
    } else {
      $pagination.find('[data-js="previous-page"]').attr("disabled", false);
    }

    if (response.totalPages == response.currentPage) {
      $pagination.find('[data-js="next-page"]').attr("disabled", true);
    } else {
      $pagination.find('[data-js="next-page"]').attr("disabled", false);
    }

    $pagination.show();
  };

  const toggleLoader = () => $("#full-templates-loader").toggle();

  const getCurrentSearch = () =>
    $(document).find('[data-js="search"] input').val();

  const parseTemplateHtml = (item) => {
    const selector =
      "cloud" === item.origin
        ? "#tpl-templately-cloud-item"
        : "#tpl-templately-item";
    let html = $(selector).html();

    Object.entries(item).forEach((data) => {
      const [key, value] = data;
      html = html.replace(new RegExp("{" + key + "}", "g"), value);
    });

    let buttonHtml = item?.canBeInstalled
      ? $("#tpl-button-insert-item").html()
      : $("#tpl-button-purchase-item").html();

    buttonHtml = buttonHtml?.replace("{purchaseUrl}", item.purchaseUrl);

    html = html.replace("{button}", buttonHtml);
    html = html.replace(/{json}/g, JSON.stringify(item));

    return html;
  };

  const getCurrentPriceFilter = () => {
    return $(".templately-plan-switcher button.active").data("plan");
  };

  const getCurrentCategoriesFilter = () => {
    const categories = [];

    $("#full-template-category-filter input:checked").each(function () {
      categories.push($(this).val());
    });

    return categories;
  };

  const getCurrentTypesFilter = () => {
    const types = [];

    $("#full-template-type-filter input:checked").each(function () {
      types.push($(this).val());
    });

    return types;
  };

  const getCurrentSegmentFiler = () => {
    const types = [];

    $("#full-template-segment-filter input:checked").each(function () {
      types.push($(this).val());
    });

    return types;
  };

  const deleteCloudItem = (item) => {
    const endpoint = "full-customer/elementor/delete-from-cloud/" + item.id;

    fetch(FULL.rest_url + endpoint, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": FULL.auth,
      },
    }).then((response) => {
      return response.json();
    });
  };

  const installTemplateItem = (mode, item) => {
    const token = Math.random().toString(36).slice(2);
    const endpoint = "full-customer/elementor/install/?token=" + token;

    const $el = $(document).find("#swal2-html-container");
    $el.html("Realizando importação");

    let fetching = false;
    let eventsInterval = setInterval(() => {
      if (fetching) {
        return;
      }

      fetching = true;

      $.get(
        FULL.rest_url + "full-customer/elementor/install-events",
        { token },
        function (response) {
          fetching = false;
          $el.html(response.data);
        }
      );
    }, 1000);

    return fetch(FULL.rest_url + endpoint, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": FULL.auth,
      },
      body: JSON.stringify({ mode, item }),
    }).then((response) => {
      clearInterval(eventsInterval);
      return response.json();
    });
  };

  const installTemplateDependencies = (item) => {
    const endpoint = "full-customer/elementor/install-dependencies/";

    return fetch(FULL.rest_url + endpoint, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": FULL.auth,
      },
      body: JSON.stringify({ item }),
    }).then((response) => {
      return response.json();
    });
  };

  const getSwalSettings = (item) => {
    if (item.hasZipFile) {
      return SWAL_SETTINGS.pack(item);
    }

    return IN_ELEMENTOR
      ? SWAL_SETTINGS.elementor(item)
      : SWAL_SETTINGS.admin(item);
  };

  const getTemplatePositionToInsert = () => {
    let at = -1;

    const children = elementor
      .getPreviewContainer()
      .view.getChildViewContainer()
      .children();

    for (const child of children) {
      at++;

      if ("choose-action" === child.dataset.view) {
        break;
      }
    }

    return Math.max(at, 0);
  };

  const addTemplateToElementorBuilder = (template) => {
    let at = getTemplatePositionToInsert();
    const withPageSettings = null;

    for (const element of template.content) {
      window.$e.run("document/elements/create", {
        container: window.elementor.getPreviewContainer(),
        model: element,
        options: { at, withPageSettings },
      });

      at++;
    }

    $(document).trigger("full-templates/imported");
  };

  const getTemplateItemFromDOMElement = ($el) => {
    return $el.data("item")
      ? $el.data("item")
      : $el.parents("[data-item]").first().data("item");
  };

  const initItemGallery = () => {
    if (!$(".full-template-carousel").length) {
      return;
    }

    $(".full-template-carousel").flickity({
      draggable: ">1",
      freeScroll: true,
      fullscreen: true,
      cellAlign: "left",
      prevNextButtons: false,
      imagesLoaded: IN_ELEMENTOR,
    });

    $(".full-template-carousel a").magnificPopup({ type: "image" });
  };

  const insertTemplateCallback = (response, item) => {
    if (response.isDismissed) {
      return;
    }

    const data = response.value;

    if (data.dependencies) {
      fireDependenciesSwal(data.dependencies, data.mode, item);
      return;
    }

    if (data.error) {
      Swal.fire("Ops", data.error, "error");
      return;
    }

    if (!IN_ELEMENTOR || item.hasZipFile) {
      Swal.fire("Feito", data.message, "success");
      return;
    }

    if (response.isConfirmed) {
      addTemplateToElementorBuilder(data.builder);
    }
  };

  const fireDependenciesSwal = (dependencies, mode, template) => {
    Swal.fire({
      titleText: "Quase lá!",
      html: dependencies,
      showConfirmButton: true,
      showCancelButton: true,
      confirmButtonText: "Ok, continuar",
      cancelButtonText: "Voltar",
      showLoaderOnConfirm: true,
      backdrop: true,
      allowOutsideClick: () => !Swal.isLoading(),
      customClass: {
        container: "full-template-popup full-template-dependencies-popup",
      },
      preConfirm: () => {
        return installTemplateDependencies(template).then((response) => {
          const $popup = $(document).find(".full-template-popup");

          $popup.find("#swal2-title").text("Feito!");
          $popup
            .find("#swal2-html-container")
            .html("<p>" + response.message + "</p>");

          return installTemplateItem(mode, template);
        });
      },
    }).then((response) => insertTemplateCallback(response, template));
  };

  const initTemplateFilters = () => {
    $(".template-filter").each(function () {
      const $ul = $(this);
      const cacheKey = "full-filters-" + $ul.data("filter");

      const itemsInCache = JSON.parse(localStorage.getItem(cacheKey));

      if (
        itemsInCache &&
        Date.now() - itemsInCache.timestamp < 1000 * 60 * 60 * 24
      ) {
        renderTemplateFilters($ul, itemsInCache.items);
      } else {
        $.get(FULL.dashboard_url + $ul.data("filter"), function (response) {
          const list = response.items ?? response;
          renderTemplateFilters($ul, list);
          localStorage.setItem(
            cacheKey,
            JSON.stringify({
              items: list,
              timestamp: Date.now(),
            })
          );
        });
      }
    });
  };

  const renderTemplateFilters = ($ul, list) => {
    $ul.empty();

    const maxVisible = 4;
    let counter = 0;

    for (const item of list) {
      const normalized = {
        id: typeof item === "string" ? "item-" + counter : item.id,
        name: typeof item === "string" ? item : item.name,
        value: typeof item === "string" ? item : item.name,
      };

      const $li = filterTemplate();
      const id = "item-" + normalized.id;

      $li.find(".toggle-switch").attr("for", id);
      $li.find(".toggle-switch-input").val(normalized.value).attr("id", id);
      $li.find(".toggle-switch-content span").text(normalized.name);

      if (counter > maxVisible) {
        $li.addClass("hidden");
      }

      $ul.append($li);

      counter++;
    }

    if (counter > maxVisible) {
      $ul.next().show();
    }
  };

  $(document).on(
    "change",
    "#full-template-category-filter input",
    resetAndFetchTemplates
  );

  $(document).on(
    "change",
    "#full-template-type-filter input",
    resetAndFetchTemplates
  );

  $(document).on(
    "change",
    "#full-template-segment-filter input",
    resetAndFetchTemplates
  );

  $(document).on("click", ".templately-plan-switcher button", function (e) {
    e.preventDefault();

    $(".templately-plan-switcher button").removeClass("active");
    $(this).addClass("active");

    resetAndFetchTemplates();
  });

  $(document).on("click", "[data-js='insert-item']", function (e) {
    e.preventDefault();

    const item = getTemplateItemFromDOMElement($(this));

    Swal.fire(getSwalSettings(item)).then((response) =>
      insertTemplateCallback(response, item)
    );
  });

  $(document).on("click", "[data-js='buy-item']", function (e) {
    e.preventDefault();

    location.href = $(this).data("href")
      ? $(this).data("href")
      : FULL.store_url;
  });

  $(document).on("click", '[data-js="send-to-cloud"]', function (e) {
    e.preventDefault();

    const $el = $(this);

    const endpoint =
      "full-customer/elementor/send-to-cloud/" + $el.data("post");

    $el.attr("disabled", true).text("Enviando...");

    fetch(FULL.rest_url + endpoint, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": FULL.auth,
      },
    })
      .then((response) => response.json())
      .then((data) => {
        $el.replaceWith(data.button);
      });
  });

  $(document).on("click", '[data-js="delete-from-cloud"]', function (e) {
    e.preventDefault();

    const $el = $(this);
    const item = getTemplateItemFromDOMElement($el);

    Swal.fire({
      titleText: "Excluir template " + item.title,
      showConfirmButton: true,
      showDenyButton: true,
      confirmButtonText: "Voltar",
      denyButtonText: "Excluir",
      showLoaderOnDeny: true,
      backdrop: true,
      allowOutsideClick: () => !Swal.isLoading(),
      html:
        "<p>Tem certeza que quer excluir este template?</p>" +
        "<p>Após excluí-lo, o template só ficará disponível dos sites em que ele foi instalado anteriormente.</p>",
      customClass: {
        container: "full-template-popup",
      },
      preDeny: () => deleteCloudItem(item),
    }).then((response) => {
      if (!response.isDenied) {
        return;
      }

      const data = response.value;

      if (data.error) {
        Swal.fire("Ops", data.error, "error");
        return;
      }

      Swal.fire("Feito", "Template excluído com sucesso!", "success");

      $el.parents(".single-cloud-item").remove();

      if (!$(".single-cloud-item").length) {
        $("#no-items").show();
      }
    });
  });

  $(document).on("click", '[data-js="toggle-template-dropdown"]', function (e) {
    e.preventDefault();

    $(this).next().toggleClass("active");
  });

  $(document).on("click", function (e) {
    const $el = $(e.target);

    if (
      !$el.parents(".cloud-segment").length &&
      !$el.is('[data-js="toggle-template-dropdown"]') &&
      !$el.parents('[data-js="toggle-template-dropdown"]').length
    ) {
      $(".cloud-segment").removeClass("active");
    }
  });

  $(document).on("keypress", '[data-js="search"] input', function (e) {
    if (e.keyCode !== 13) {
      return;
    }

    resetAndFetchTemplates();
  });

  $(document).on("change", '[data-js="search"] input', resetAndFetchTemplates);

  $(document).on("click", '[data-js="search"] button', resetAndFetchTemplates);

  $(document).on(
    "click",
    '[data-js="sync-cloud-template"]:not(.syncing-full-cloud)',
    function (e) {
      e.preventDefault();

      $(this).addClass("syncing-full-cloud");

      const endpoint = "full-customer/elementor/sync";

      fetch(FULL.rest_url + endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": FULL.auth,
        },
      }).then((response) => {
        $(this).removeClass("syncing-full-cloud");

        Swal.fire({
          titleText: "Cache excluído",
          confirmButtonText: "Obrigado",
          html: '<p>Pronto! Todos os caches relacionados a biblioteca foram limpos e seus modelos serão verificados na próxima vez que você acessar a página de "Modelos" do Elementor</p>',
          customClass: {
            container: "full-template-popup",
          },
        });
      });
    }
  );

  $(document).on("full-templates/ready", function () {
    initTemplateFilters();
    resetAndFetchTemplates();
  });

  $(document).on("click", ".view-more-filters", function () {
    const $trigger = $(this);
    const $ul = $trigger.prev();

    if ($trigger.is(".opened")) {
      $trigger.text("Ver mais").removeClass("opened");
      $ul.find("li:gt(4)").addClass("hidden");
    } else {
      $trigger.text("Fechar").addClass("opened");
      $ul.find("li.hidden").removeClass("hidden");
    }
  });

  $(document).on("full-templates/ready", initItemGallery);
  $(document).on("full-templates/ready", initTemplateFilters);

  $(document).on("keydown", "#template-searcher input", function (e) {
    if (e.key === "Enter") {
      resetAndFetchTemplates();
    }
  });

  $(document).on("click", '[data-js="export-template"]', function (e) {
    e.preventDefault();

    const id = getTemplateItemFromDOMElement($(this)).id;

    window.location.replace(
      FULL.rest_url + "full-customer/elementor/download/?id=" + id
    );
  });

  $(document).on("click", '[data-js="previous-page"]', function (e) {
    e.preventDefault();

    const page = getCurrentPage() - 2;

    if (page >= 1) {
      resetAndFetchTemplates(page);
    }
  });

  $(document).on("click", '[data-js="next-page"]', function (e) {
    e.preventDefault();

    const page = getCurrentPage();
    resetAndFetchTemplates(page);
  });

  initTemplateFilters();
  initItemGallery();
  if ($("#response-container").length) {
    resetAndFetchTemplates();
  }
})(jQuery);
