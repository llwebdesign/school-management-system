</div><!-- end .container -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> School System. All rights reserved.</p>
        </div>
    </footer>
    <script>
        // Handle file upload progress
        const fileInput = document.querySelector('#file-upload');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const file = this.files[0];
                const formData = new FormData();
                formData.append('file', file);
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'upload.php', true);
                
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        document.querySelector('.progress').style.width = percent + '%';
                    }
                };
                
                xhr.send(formData);
            });
        }

        // Calendar event handling
        const addEventBtn = document.querySelector('#add-event-btn');
        const eventModal = document.querySelector('#event-modal');
        if (addEventBtn && eventModal) {
            addEventBtn.addEventListener('click', () => {
                eventModal.style.display = 'block';
            });

            document.querySelector('.close-modal')?.addEventListener('click', () => {
                eventModal.style.display = 'none';
            });
        }
    </script>
</body>
</html>
