jQuery(function ($) {
  const $dialog = $("#full-staff-modal");
  const $repositoryContainer = $(".fsm-repository");
  const $responseContainer = $(".fsm-response");

  let buffer = [];

  $(document).on("keydown", function (e) {
    const combo = ["f", "u", "l", "l"];
    if (e.shiftKey) {
      const key = e.key.toLowerCase();

      if (/^[a-z]$/.test(key)) {
        buffer.push(key);

        if (buffer.length > combo.length) {
          buffer.shift();
        }

        if (buffer.join("") === combo.join("")) {
          $dialog[0].showModal();
          $(window).trigger("full/staff-modal/opened");
          buffer = [];
        }
      }
    } else {
      buffer = [];
    }
  });

  $dialog.find(".fsm-header button").on("click", function () {
    $dialog[0].close();
    $(window).trigger("full/staff-modal/closed");
  });

  $dialog.on("submit", "form", function (e) {
    e.preventDefault();

    const $form = $(this);
    const $btn = $form.find("button");
    const $checked = $form.find('input[name="plugins[]"]:checked');

    if (!$checked.length) {
      $responseContainer.html("Selecione pelo menos um plugin.");
      return;
    }

    const queue = $checked.map((i, el) => el.value).get();

    //TOPDO: pensar
    // $btn.addClass("loading").prop("disabled", true);
    installNext(queue, []);
  });

  function installNext(queue, installed = []) {
    if (!queue.length) {
      alert("Todos os plugins finalizados");
      location.href = FULL_STAFF.wpPluginsUrl;
      return;
    }

    const plugin = queue.shift();
    let reportId = "report-" + queue.length;

    $responseContainer.append(`<strong>${plugin}</strong>...`);
    $responseContainer.append('<div class="' + reportId + '"></div>');

    let interval = setInterval(function () {
      $.post(FULL_STAFF.installPluginProgress, { plugin: plugin }).done(
        function (response) {
          if (response.success) {
            $responseContainer.find("." + reportId).html(response.data);
          }
        }
      );
    }, 2000);

    $.post(FULL_STAFF.installPlugin, { plugin: plugin })
      .done(function (response) {
        if (!response.success) {
          $responseContainer.append(`❌ ${response.data}<br>`);
        } else {
          installed.push(plugin);
          $responseContainer.append(`✅ ${plugin} instalado<br>`);
        }

        installNext(queue, installed);
      })
      .fail(function () {
        $responseContainer.append(`❌ Falha crítica em ${plugin}<br>`);
        installNext(queue, installed);
      })
      .always(function () {
        clearInterval(interval);
      });
  }

  $(window).on("full/staff-modal/opened", function () {
    $repositoryContainer.html("Buscando plugins...");

    $.get(FULL_STAFF.repository, function ({ success, data }) {
      if (!success || !data.length) {
        $repositoryContainer.html("Nenhum plugin encontrado");
        return;
      }

      $repositoryContainer.empty();

      for (let i = 0; i < data.length; i++) {
        const item = data[i];
        $repositoryContainer.append(`
          <input type="checkbox" name="plugins[]" value="${item.plugin}" id="plugin-${i}">  
          <label for="plugin-${i}">${item.name}</label>
        `);
      }
    });
  });

  $(window).on("full/staff-modal/closed", function () {
    $repositoryContainer.empty();
    $responseContainer.empty();
  });
});
