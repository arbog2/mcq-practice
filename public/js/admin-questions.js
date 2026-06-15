function batchAction(action) {
    var checkboxes = document.querySelectorAll('input[name="selected_ids[]"]:checked');
    var ids = Array.from(checkboxes).map(function(cb) { return cb.value; });
    if (ids.length === 0) { alert('请先勾选题目。'); return; }
    var csrf = document.querySelector('meta[name="csrf-token"]').content;

    if (action === 'delete') {
        if (!confirm('确认删除选中的 ' + ids.length + ' 道题目？')) return;
        fetch('/admin/questions/batch-destroy', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids })
        }).then(function(r) { return r.json(); }).then(function(d) { if (d.reload) location.reload(); else alert(d.message); });
    } else if (action === 'move') {
        var catId = prompt('请输入目标分类 ID：');
        if (!catId) return;
        fetch('/admin/questions/batch-move', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids, category_id: catId })
        }).then(function(r) { return r.json(); }).then(function(d) { if (d.reload) location.reload(); else alert(d.message); });
    } else if (action === 'score') {
        var score = prompt('请输入分值（1-999）：');
        if (!score) return;
        fetch('/admin/questions/batch-score', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids, score: parseInt(score) })
        }).then(function(r) { return r.json(); }).then(function(d) { if (d.reload) location.reload(); else alert(d.message); });
    }
}
