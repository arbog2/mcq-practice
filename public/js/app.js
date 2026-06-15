var uploadUrl = document.querySelector('meta[name="upload-url"]')?.content || '';
var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function mcqEditorUploadHandler(blobInfo, progress) {
    return new Promise(function (resolve, reject) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', uploadUrl);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onload = function () {
            if (xhr.status < 200 || xhr.status >= 300) { reject('HTTP ' + xhr.status); return; }
            var json;
            try { json = JSON.parse(xhr.responseText); } catch (e) { reject('Invalid JSON'); return; }
            if (!json || typeof json.location !== 'string') { reject('Invalid upload response'); return; }
            resolve(json.location);
        };
        xhr.onerror = function () { reject('Upload failed'); };
        var formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        xhr.send(formData);
    });
}

function initTinymce() {
    if (typeof tinymce === 'undefined') return;
    tinymce.remove('textarea.rich-text');
    tinymce.init({
        selector: 'textarea.rich-text',
        promotion: false,
        branding: false,
        height: 320,
        menubar: false,
        plugins: 'image link lists autoresize code table',
        toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | image link table | removeformat | code',
        automatic_uploads: true,
        images_upload_handler: mcqEditorUploadHandler,
        relative_urls: false,
        convert_urls: true,
        content_style: 'body { font-family: system-ui, -apple-system, Segoe UI, Microsoft YaHei, sans-serif; font-size:14px; } img { max-width:100%; height:auto; }'
    });
}

function openAjaxModal(url, title) {
    document.getElementById('ajax-modal-title').textContent = title;
    document.getElementById('ajax-modal-body').innerHTML = '<p class="muted">加载中...</p>';
    document.getElementById('ajax-modal').setAttribute('data-open', '1');
    document.body.style.overflow = 'hidden';
    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(res) { return res.text(); }).then(function(html) {
        document.getElementById('ajax-modal-body').innerHTML = html;
        setTimeout(initTinymce, 100);
    }).catch(function() {
        document.getElementById('ajax-modal-body').innerHTML = '<p class="error">加载失败</p>';
    });
}

function closeAjaxModal() {
    if (typeof tinymce !== 'undefined') { tinymce.remove('textarea.rich-text'); }
    document.getElementById('ajax-modal').setAttribute('data-open', '');
    document.body.style.overflow = '';
}

function deleteCategory(id) { deleteItem('/admin/categories/', id); }
function deleteQuestion(id) { deleteItem('/admin/questions/', id); }
function deleteUser(id) { deleteItem('/admin/students/', id); }

function deleteItem(url, id) {
    if (!confirm('确认删除？')) return;
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    fetch(url + id, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_method=DELETE&_token=' + csrf
    }).then(function() { location.reload(); });
}

function approveUser(id) {
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    fetch('/admin/students/' + id + '/approve', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf }
    }).then(function() { location.reload(); });
}

function rejectUser(id) {
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    fetch('/admin/students/' + id + '/reject', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf }
    }).then(function() { location.reload(); });
}

document.addEventListener('submit', function(e) {
    var form = e.target;
    var modalContent = document.querySelector('#ajax-modal .modal-content');
    if (!modalContent || !form.closest('.modal-content')) return;
    e.preventDefault();

    if (typeof tinymce !== 'undefined') { tinymce.triggerSave(); }

    var btn = form.querySelector('button[type="submit"]');
    if (btn) { btn.dataset.origText = btn.textContent; btn.disabled = true; btn.textContent = '提交中...'; }

    var formData = new FormData(form);
    var methodInput = form.querySelector('input[name="_method"]');
    if (methodInput) {
        formData.set('_method', methodInput.value);
    }

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    }).then(function(res) {
        if (!res.ok) {
            return res.json().then(function(err) {
                throw new Error(err.message || '服务器错误');
            });
        }
        return res.json();
    }).then(function(data) {
        if (data.reload) {
            location.reload();
        } else if (data.message) {
            alert(data.message);
            if (btn) { btn.disabled = false; btn.textContent = btn.dataset.origText || '提交'; }
        }
    }).catch(function(err) {
        alert(err.message || '提交失败，请重试');
        if (btn) { btn.disabled = false; btn.textContent = btn.dataset.origText || '提交'; }
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAjaxModal();
    }
});
