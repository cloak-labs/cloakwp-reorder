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
        .catch(() => {
          restore(snapshot);
          speak(config.i18n.error, 'assertive');
        })
        .finally(() => {
          setSaving(false);
        });
    }

    snapshot = rows();

    $list.sortable({
      items: '> tr.iedit',
      handle: '.reorder-handle',
      axis: 'y',
      placeholder: {
        element() {
          return $('<tr class="ui-sortable-placeholder"><td>&nbsp;</td></tr>')[0];
        },
        update() {},
      },
      start(event, ui) {
        const cols = $list.closest('table').find('thead tr:first').children('th, td').length;
        if (cols) {
          ui.placeholder.children('td').attr('colspan', cols);
        }
      },
      helper(event, ui) {
        ui.children().each(function () {
          $(this).width($(this).width());
        });
        return ui;
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
