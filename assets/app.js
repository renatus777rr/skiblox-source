(function() {
  function onReady(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  function formatDate(iso) {
    if (!iso) {
      return '';
    }

    const normalized = iso.replace(' ', 'T') + 'Z';
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) {
      return iso;
    }

    return date.toLocaleString();
  }

  function fetchMessages() {
    const box = document.querySelector('.chat-box');
    if (!box) {
      return;
    }

    window.fetch('/get_chat_messages.php', { credentials: 'same-origin' })
      .then((res) => res.ok ? res.json() : Promise.reject(res))
      .then((data) => {
        box.innerHTML = '';
        for (const msg of data) {
          const card = document.createElement('div');
          card.className = 'msg';

          const name = escapeHtml(msg.username || 'user');
          const text = msg.message ? `'${escapeHtml(msg.message)}'` : '';
          const meta = ` <span class="meta">(${formatDate(msg.created_at)})</span>`;

          const row = document.createElement('div');
          row.innerHTML = `(<strong>${name}</strong>)${text ? ': ' + text : ''}${meta}`;
          card.appendChild(row);

          if (msg.file_path) {
            if (Number(msg.is_image) === 1) {
              const image = document.createElement('img');
              image.className = 'chat-image';
              image.src = msg.file_path;
              image.alt = msg.file_name ? escapeHtml(msg.file_name) : 'image';
              card.appendChild(image);
            } else {
              const link = document.createElement('a');
              link.className = 'chat-download';
              link.href = msg.file_path;
              link.target = '_blank';
              link.rel = 'noopener';
              link.textContent = `(${escapeHtml(msg.file_name || 'file')}) Download`;
              card.appendChild(link);
            }
          }

          box.appendChild(card);
        }
      })
      .catch(() => {
        // Silent fail; live chat will retry.
      });
  }

  function connectChat() {
    const chatBox = document.querySelector('.chat-box');
    if (!chatBox) {
      return;
    }

    fetchMessages();
    setInterval(fetchMessages, 3000);
  }

  function connectProfileEditor() {
    const profileMeta = document.getElementById('profileMeta');
    if (!profileMeta || profileMeta.dataset.isOwner !== '1') {
      return;
    }

    const editTrigger = document.getElementById('editTrigger');
    const descView = document.getElementById('descView');
    const descForm = document.getElementById('descForm');
    const descInput = document.getElementById('descInput');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const descMsg = document.getElementById('descMsg');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const username = profileMeta.dataset.username || '';

    if (!editTrigger || !descView || !descForm || !descInput || !saveBtn || !cancelBtn || !descMsg) {
      return;
    }

    function showForm() {
      descView.style.display = 'none';
      descForm.style.display = 'block';
      descMsg.style.display = 'none';
      descInput.focus();
    }

    function hideForm() {
      descForm.style.display = 'none';
      descView.style.display = 'block';
      descMsg.style.display = 'none';
    }

    function showMessage(message, isError = false) {
      descMsg.style.display = 'block';
      descMsg.textContent = message;
      descMsg.style.color = isError ? '#ff8b8b' : '#9be6a6';
    }

    editTrigger.addEventListener('click', showForm);
    cancelBtn.addEventListener('click', hideForm);

    saveBtn.addEventListener('click', function (event) {
      event.preventDefault();
      const value = descInput.value;
      saveBtn.disabled = true;
      descMsg.style.display = 'none';

      const fd = new FormData();
      fd.append('action', 'update_description');
      fd.append('description', value);
      fd.append('csrf_token', csrfToken);

      fetch(location.pathname + '?u=' + encodeURIComponent(username), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd,
      })
        .then(async (res) => {
          saveBtn.disabled = false;
          const text = await res.text();
          let json = null;
          try {
            json = JSON.parse(text);
          } catch (error) {
            showMessage('Server error: ' + res.status, true);
            return;
          }

          if (!res.ok || !json || !json.ok) {
            showMessage((json && json.error) ? json.error : 'Update failed.', true);
            return;
          }

          descView.textContent = json.description || '';
          hideForm();
          showMessage('Updated.');
          setTimeout(() => { descMsg.style.display = 'none'; }, 2500);
        })
        .catch(() => {
          saveBtn.disabled = false;
          showMessage('Network error. Please try again.', true);
        });
    });
  }

  onReady(function () {
    connectChat();
    connectProfileEditor();
  });
})();
