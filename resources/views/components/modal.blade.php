<div id="modal-{{ $id }}" class="modal" data-modal-id="{{ $id }}">
    <div class="modal-backdrop" onclick="closeModal('{{ $id }}')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>{{ $title }}</h3>
            <button type="button" class="modal-close" onclick="closeModal('{{ $id }}')">&times;</button>
        </div>
        <div class="modal-body">
            {!! $slot !!}
        </div>
    </div>
</div>

<style>
.modal { display: none; position: fixed; inset: 0; z-index: 9999; }
.modal[data-open="1"] { display: block; }
.modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }
.modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 8px; width: 90%; max-width: 500px; max-height: 90vh; overflow: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; }
.modal-header h3 { margin: 0; font-size: 16px; }
.modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }
.modal-body { padding: 16px; }
.modal-body .loading { text-align: center; padding: 32px; color: #6b7280; }
.modal-body .stack { display: flex; flex-direction: column; gap: 12px; }
.modal-body label { font-size: 13px; color: #6b7280; margin-bottom: 4px; display: block; }
.modal-body input[type="text"],
.modal-body input[type="email"],
.modal-body input[type="password"],
.modal-body input[type="number"],
.modal-body select,
.modal-body textarea { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
.modal-body textarea { min-height: 80px; }
.modal-body fieldset { border: 1px solid #d1d5db; padding: 12px; border-radius: 6px; margin: 0; }
.modal-body fieldset legend { font-weight: 600; font-size: 13px; padding: 0 4px; }
.modal-body .row { display: flex; gap: 8px; align-items: center; }
.modal-body .btn { padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; border: 1px solid #d1d5db; background: #fff; }
.modal-body .btn-primary { background: #2563eb; color: #fff; border: none; }
</style>