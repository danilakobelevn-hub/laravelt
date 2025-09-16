<div class="modal fade" id="uploadVersionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Загрузка новой версии</h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            <form action="{{ route('admin.contents.upload-version', $content) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Платформа *</label>
                        <select class="form-control" name="platform" required>
                            <option value="android">Android</option>
                            <option value="ios">iOS</option>
                            <option value="windows">Windows</option>
                            <option value="web">Web</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Тип обновления *</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="major" id="major">
                            <label class="form-check-label" for="major">Major (крупное обновление)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="minor" id="minor">
                            <label class="form-check-label" for="minor">Minor (новые функции)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="micro" id="micro">
                            <label class="form-check-label" for="micro">Micro (исправления багов)</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Release Note</label>
                        <textarea class="form-control" name="release_note" placeholder="Что нового в этой версии?"></textarea>
                    </div>

                    <div class="form-group">
                        <label>ZIP-файл *</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="file" name="file" accept=".zip" required>
                            <label class="custom-file-label" for="file">Выберите ZIP-файл</label>
                        </div>
                        <small class="form-text text-muted">Максимальный размер: 100MB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Загрузить</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // Показываем имя файла в input
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    </script>
@endpush
