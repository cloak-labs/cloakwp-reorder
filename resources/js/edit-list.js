(function () {
  const config = window.reorderList;
  if (!config || typeof jQuery === 'undefined') {
    return;
  }

  const $ = jQuery;

  $(function () {
    const $list = $('#the-list');
    if (!$list.length) {
      return;
    }

    const table = $list.closest('table').get(0);
    let saving = false;
    let snapshot = [];

    function rows() {
      return $list.children('tr.iedit').get();
    }

    function idsFrom(rowEls) {
      return rowEls
        .map((row) => {
          const match = String(row.id || '').match(/post-(\d+)/);
          return match ? parseInt(match[1], 10) : 0;
        })
        .filter((id) => id > 0);
    }

    function restore(order) {
      order.forEach((row) => {
        $list.append(row);
      });
    }

    function speak(message, politeness) {
      if (window.wp && wp.a11y && typeof wp.a11y.speak === 'function') {
        wp.a11y.speak(message, politeness || 'polite');
      }
    }

    function messageFrom(error) {
      if (error && typeof error.message === 'string' && error.message) {
        return error.message;
      }

      const responseMessage = error && error.responseJSON && error.responseJSON.message;
      if (typeof responseMessage === 'string' && responseMessage) {
        return responseMessage;
      }

      return config.i18n.error;
    }

    function showError(message) {
      $('.reorder-notice').remove();
      const $notice = $('<div class="notice notice-error reorder-notice"><p></p></div>');
      $notice.find('p').text(message);

      const $anchor = $('.wp-header-end');
      if ($anchor.length) {
        $anchor.after($notice);
      } else {
        $('.wrap h1').first().after($notice);
      }

      if ($notice[0] && typeof $notice[0].scrollIntoView === 'function') {
        $notice[0].scrollIntoView({ block: 'nearest' });
      }

      speak(message, 'assertive');
    }

    function setSaving(isSaving) {
      saving = isSaving;
      if (table) {
        table.classList.toggle('reorder-saving', isSaving);
        table.setAttribute('aria-busy', isSaving ? 'true' : 'false');
      }
      $list.sortable(isSaving ? 'disable' : 'enable');
    }

    function request(body) {
      if (window.wp && wp.apiFetch) {
        return wp.apiFetch({
          url: config.restUrl,
          method: 'POST',
          data: body,
        });
      }

      return $.ajax({
        url: config.restUrl,
        method: 'POST',
        contentType: 'application/json',
        beforeSend(xhr) {
          xhr.setRequestHeader('X-WP-Nonce', config.nonce);
        },
        data: JSON.stringify(body),
      });
    }

    function save() {
      if (saving) {
        return;
      }

      const current = rows();
      const ids = idsFrom(current);
      if (!ids.length) {
        return;
      }

      setSaving(true);

      Promise.resolve(
        request({
          post_type: config.postType,
          ids: ids,
          paged: config.paged,
        })
      )
        .then(() => {
          snapshot = current;
          speak(config.i18n.saved);
        })
        .catch((error) => {
          restore(snapshot);
          showError(messageFrom(error));
        })
        .finally(() => {
          setSaving(false);
        });
    }

    snapshot = rows();

    $list.sortable({
      items: '> tr.iedit',
      handle: '.reorder-handle',
      // jQuery UI defaults cancel to "input,textarea,button,select,option".
      // A <button> handle therefore never starts a drag.
      cancel: 'input, textarea, select, option',
      axis: 'y',
      // Pointer tolerance can place the row after the last item. The default
      // intersect test stops short, the drop is discarded, and nothing is saved.
      tolerance: 'pointer',
      distance: 3,
      forcePlaceholderSize: true,
      placeholder: 'reorder-placeholder',
      helper(event, item) {
        const $helper = item.clone();
        $helper.children().each(function (index) {
          $(this).width(item.children().eq(index).outerWidth());
        });
        return $helper;
      },
      start(event, ui) {
        ui.placeholder.height(ui.item.outerHeight());
      },
      sort(event, ui) {
        const last = $list.children('tr.iedit').not(ui.item).last()[0];
        if (!last || !ui.placeholder.length) {
          return;
        }

        const rect = last.getBoundingClientRect();
        if (event.clientY > rect.bottom && ui.placeholder[0].previousElementSibling !== last) {
          last.after(ui.placeholder[0]);
        }
      },
      update() {
        save();
      },
    });

    $list.on('keydown', '.reorder-handle', function (event) {
      if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
        return;
      }

      event.preventDefault();
      if (saving) {
        return;
      }

      const row = this.closest('tr');
      if (!row) {
        return;
      }

      const sibling =
        event.key === 'ArrowUp' ? row.previousElementSibling : row.nextElementSibling;
      if (!sibling || !sibling.classList.contains('iedit')) {
        return;
      }

      if (event.key === 'ArrowUp') {
        $list[0].insertBefore(row, sibling);
      } else {
        $list[0].insertBefore(sibling, row);
      }

      this.focus();
      save();
    });
  });
})();
