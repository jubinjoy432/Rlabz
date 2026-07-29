        </main>
        <!-- Page Content Ends -->
    </div> <!-- admin-main ends -->

    <script>
        // Sidebar Toggle Logic for Mobile
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');

        if(toggleBtn && sidebar && overlay) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.add('open');
                overlay.classList.add('show');
            });
            
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                let msg = urlParams.get('success');
                if (msg === '1' || msg === '') {
                    msg = 'Updated successfully.';
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: msg,
                    timer: 3000,
                    showConfirmButton: false,
                    background: '#1e293b',
                    color: '#f8fafc'
                });
                
                // Clean URL but keep id if present
                const id = urlParams.get('id');
                const newUrl = window.location.pathname + (id ? '?id=' + id : '');
                window.history.replaceState({}, document.title, newUrl);
            }
            if (urlParams.has('error')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: urlParams.get('error'),
                    background: '#1e293b',
                    color: '#f8fafc'
                });
                
                const id = urlParams.get('id');
                const newUrl = window.location.pathname + (id ? '?id=' + id : '');
                window.history.replaceState({}, document.title, newUrl);
            }
            
            // Delete confirmation dialog
            const deleteBtns = document.querySelectorAll('.btn-delete');
            deleteBtns.forEach(btn => {
                // If it already has an inline onclick, we override it
                btn.onclick = function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This action cannot be undone!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#3b82f6',
                        confirmButtonText: 'Yes, delete it!',
                        background: '#1e293b',
                        color: '#f8fafc'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = href;
                        }
                    });
                };
            });
        });
    </script>
</body>
</html>
