<div class="modal fade" id="uploadVersionModal" tabindex="-1" role="dialog" aria-labelledby="uploadVersionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadVersionModalLabel">Upload New Version</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="uploadVersionForm" action="{{ route('admin.contents.upload-version', $content->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="content_id" value="{{ $content->id }}">

                <div class="modal-body">
                    <div class="form-group">
                        <label for="platform">Platform *</label>
                        <select name="platform" id="platform" class="form-control" required>
                            <option value="">Select Platform</option>
                            <option value="windows">Windows</option>
                            <option value="macos">macOS</option>
                            <option value="linux">Linux</option>
                            <option value="android">Android</option>
                            <option value="ios">iOS</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="major">Major Version *</label>
                                <input type="number" name="major" id="major" class="form-control" required min="0" value="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="minor">Minor Version *</label>
                                <input type="number" name="minor" id="minor" class="form-control" required min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="micro">Micro Version *</label>
                                <input type="number" name="micro" id="micro" class="form-control" required min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="file">ZIP File *</label>
                        <div class="custom-file">
                            <input type="file" name="file" id="file" class="custom-file-input" accept=".zip" required>
                            <label class="custom-file-label" for="file">Choose ZIP file</label>
                        </div>
                        <small class="form-text text-muted">Maximum file size: 100MB. Only .zip files allowed.</small>
                    </div>

                    <div class="form-group">
                        <label for="release_note">Release Notes</label>
                        <textarea name="release_note" id="release_note" class="form-control" rows="3" placeholder="Optional release notes..."></textarea>
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" name="tested" id="tested" class="form-check-input" value="1">
                        <label for="tested" class="form-check-label">Mark as tested</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Version</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
    @parent
    <script>
        // Show selected file name
        document.getElementById('file').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });

        // Form submission handling
        document.getElementById('uploadVersionForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        $('#uploadVersionModal').modal('hide');
                        location.reload(); // Reload page to show new version
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during upload.');
                });
        });
    </script>
@endsection
