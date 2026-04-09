(function ($) {
  "use strict";

  const templatesEnabled = FULL.enabled_services.includes("full-templates");
  const cloudEnabled = FULL.enabled_services.includes("full-cloud");

  const VIEWS = {
    templates: $('.full-templates[data-endpoint="templates"]').html(),
    cloud: $('.full-templates[data-endpoint="cloud"]').html(),
    single: $('.full-templates[data-endpoint="single"]').html(),
  };

  const insertAddSectionButton = () => {
    if (!templatesEnabled && !cloudEnabled) {
      return;
    }

    const $addSectionContainer = $("#tmpl-elementor-add-section");
    const pointer = '<div class="elementor-add-section-drag-title';
    const icon =
      '<div class="elementor-add-section-area-button elementor-add-full-button" title="FULL."></div>';

    const html = $addSectionContainer.html().replace(pointer, icon + pointer);

    $addSectionContainer.html(html);
  };

  const initFullModal = () => {
    window.FullModal = elementorCommon.dialogsManager.createWidget("lightbox", {
      id: "full-elementor",
      headerMessage: false,
      message: "",
      hide: {
        auto: false,
        onClick: false,
        onOutsideClick: false,
        onOutsideContextMenu: false,
        onBackgroundClick: true,
        onEscKeyPress: false,
      },
      className: "elementor-templates-modal",
      closeButton: false,
      draggable: false,
      onShow: function () {
        const container = window.FullModal.getElements("content");
        container.get(0).innerHTML = templatesEnabled
          ? VIEWS.templates
          : VIEWS.cloud;

        $(document).trigger("full-templates/ready");
      },
      onHide: function () {
        const container = window.FullModal.getElements("content");
        container.get(0).innerHTML = "";
      },
    });

    window.FullModal.getElements("message").append(
      window.FullModal.addElement("content")
    );
  };

  const filterContextMenuGroups = (e, element) => {
    const item = {
      name: "full_loripsum",
      actions: [
        {
          name: "full_loripsum",
          icon: "eicon-cloud-upload",
          title: "Salvar seção na FULL.",
          callback: () => contextMenuCallback(element),
        },
      ],
    };

    return e.splice(1, 0, item), e.join(), e;
  };

  const sendToCloud = (templateName, templateContent, templateType) => {
    const endpoint = "full-customer/elementor/send-to-cloud";

    return fetch(FULL.rest_url + endpoint, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": FULL.auth,
      },
      body: JSON.stringify({
        templateName,
        templateContent,
        templateType,
      }),
    }).then((response) => {
      return response.json();
    });
  };

  const contextMenuCallback = (element) => {
    Swal.fire({
      titleText: "Salvar bloco na FULL.",
      showConfirmButton: true,
      showDenyButton: true,
      confirmButtonText: "Salvar",
      denyButtonText: "Cancelar",
      showLoaderOnConfirm: true,
      showLoaderOnDeny: true,
      backdrop: true,
      allowOutsideClick: () => !Swal.isLoading(),
      html:
        "<p>Salve este bloco como um modelo reutilizável em seu cloud na FULL.</p>" +
        "<p>Defina o nome do template no campo abaixo.</p>",
      input: "text",
      inputAttributes: {
        autocapitalize: "off",
        placeholder: "Nome do template",
      },
      customClass: {
        container: "full-template-popup",
      },
      preConfirm: (templateName) => {
        if (!templateName) {
          Swal.showValidationMessage("Por favor, informe o nome do bloco");
        }

        const templateContent = element.model.toJSON({
          remove: ["default", "editSettings", "isLocked"],
        });

        templateContent.type = "section";

        return sendToCloud(templateName, templateContent, "section");
      },
    }).then(swalSendToCloudCallback);
  };

  const swalSendToCloudCallback = (response) => {
    if (!response.isConfirmed) {
      return;
    }

    const data = response.value;

    if (data.error) {
      Swal.fire("Ops", data.error, "error");
      return;
    }

    Swal.fire("Feito", "Template salvo com sucesso no cloud!", "success");
  };

  elementor.on("preview:loaded", function () {
    const el = elementor.$previewContents[0].body;
    $(el).on("click", ".elementor-add-full-button", function (e) {
      window.FullModal.show();
    });
  });

  elementor.on("panel:init", function () {
    $(".elementor-panel-footer-sub-menu").append(
      '<div id="elementor-panel-footer-full-push-item" class="elementor-panel-footer-sub-menu-item"><i class="elementor-icon eicon-cloud-upload" aria-hidden="true"></i><span class="elementor-title">' +
        "Salvar página na FULL." +
        "</span></div>"
    );
  });

  elementor.hooks.addFilter(
    "elements/section/contextMenuGroups",
    filterContextMenuGroups
  );

  elementor.hooks.addFilter(
    "elements/container/contextMenuGroups",
    filterContextMenuGroups
  );

  $(document).on("full-templates/imported", function () {
    window.FullModal.destroy();
  });

  $(document).on(
    "click",
    ".templately-nav-item a, .endpoint-nav",
    function (e) {
      e.preventDefault();

      const endpoint = $(this).data("endpoint");

      const container = window.FullModal.getElements("content");
      container.get(0).innerHTML = VIEWS[endpoint];

      $(document).trigger("full-templates/ready");
    }
  );

  $(document).on(
    "click",
    "#elementor-panel-footer-full-push-item",
    function () {
      Swal.fire({
        titleText: "Salvar página na FULL.",
        showConfirmButton: true,
        showDenyButton: true,
        confirmButtonText: "Salvar",
        denyButtonText: "Cancelar",
        showLoaderOnConfirm: true,
        showLoaderOnDeny: true,
        backdrop: true,
        allowOutsideClick: () => !Swal.isLoading(),
        html:
          "<p>Salve esta página como um modelo reutilizável em seu cloud na FULL.</p>" +
          "<p>Defina o nome do template no campo abaixo.</p>",
        input: "text",
        inputAttributes: {
          autocapitalize: "off",
          placeholder: "Nome do template",
        },
        customClass: {
          container: "full-template-popup",
        },
        preConfirm: (templateName) => {
          if (!templateName) {
            Swal.showValidationMessage("Por favor, informe o nome da página");
          }

          const templateContent = elementor.elements.toJSON({
            remove: ["default"],
          });

          return sendToCloud(templateName, templateContent, "page");
        },
      }).then(swalSendToCloudCallback);
    }
  );

  $(document).on("click", ".templately-page-item a", function (e) {
    e.preventDefault();
    const item = $(this).parents(".templately-page-item").data("item");
    let html = VIEWS.single;

    Object.entries(item).forEach((data) => {
      const [key, value] = data;
      html = html.replace(new RegExp("{" + key + "}", "g"), value);
    });

    let buttonHtml = item?.canBeInstalled
      ? $("#tpl-single-button-insert-item").html()
      : $("#tpl-single-button-purchase-item").html();

    buttonHtml = buttonHtml?.replace("{purchaseUrl}", item.purchaseUrl);
    html = html.replace("{button}", buttonHtml);

    let categoriesList = item?.categories
      ? item.categories.map((item) => item.name)
      : [];
    categoriesList = categoriesList.length
      ? categoriesList.join(", ")
      : "Sem categoria";

    html = html.replace(/{categoriesList}/g, categoriesList);
    html = html.replace(
      /{priceTagTitle}/g,
      parseFloat(item.price) > 0 ? "Premium" : "Grátis"
    );

    if (item.gallery.length) {
      let galleryItemsHtml = "";
      const template = $("#tpl-single-gallery-item").html();

      for (const src of item.gallery) {
        galleryItemsHtml += template.replace(/{src}/g, src);
      }

      html = html.replace(
        /{galleryContainer}/g,
        $("#tpl-gallery-container").html()
      );
      html = html.replace(/{galleryItems}/g, galleryItemsHtml);
    } else {
      html = html.replace("{galleryContainer}", "");
    }

    html = html.replace(/{json}/g, JSON.stringify(item));

    const container = window.FullModal.getElements("content");
    container.get(0).innerHTML = html;

    $(document).trigger("full-templates/ready");
  });

  insertAddSectionButton();
  initFullModal();
})(jQuery);
