<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chunked File Upload</title>
    <!-- Dropzone CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css" />
</head>
<body>
    <!-- Create a form with the Dropzone class -->
    <form action="<?= site_url('upload/chunk_upload'); ?>" class="dropzone" id="myDropzone">
    <div class="dz-message">Drop files here or click to upload.</div>
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
</form>

    <!-- Dropzone JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>


    
<script>
    Dropzone.options.myDropzone = {
        chunking: true,
        chunkSize: 2000000,
        parallelChunkUploads: true,
        retryChunks: true,
        retryChunksLimit: 3,
        init: function() {
            this.on("uploadprogress", function(file, progress) {
                document.querySelector('.progress-bar').style.width = progress + '%';
            });
        }
    };
</script>
</body>
</html>


