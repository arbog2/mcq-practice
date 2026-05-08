@push('head')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
@endpush

@push('scripts')
    <script>
        const uploadUrl = @json(route('admin.editor.upload-image'));
        const csrfToken = @json(csrf_token());

        function mcqEditorUploadHandler(blobInfo, progress) {
            return new Promise(function (resolve, reject) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl);
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.onload = function () {
                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject('HTTP ' + xhr.status);
                        return;
                    }

                    let json;
                    try {
                        json = JSON.parse(xhr.responseText);
                    } catch (e) {
                        reject('Invalid JSON');
                        return;
                    }

                    if (!json || typeof json.location !== 'string') {
                        reject('Invalid upload response');
                        return;
                    }

                    resolve(json.location);
                };

                xhr.onerror = function () {
                    reject('Upload failed');
                };

                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                xhr.send(formData);
            });
        }

        tinymce.init({
            selector: '.tinymce',
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
    </script>
@endpush
