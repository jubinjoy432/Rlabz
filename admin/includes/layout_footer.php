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

        function showAdminToast(type, title, message) {
            let container = document.getElementById('adminToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'adminToastContainer';
                container.style.cssText = 'position:fixed;top:1.5rem;right:1.5rem;z-index:999999;display:flex;flex-direction:column;gap:0.75rem;pointer-events:none;max-width:380px;width:calc(100% - 3rem);';
                document.body.appendChild(container);
            }

            const isSuccess = (type === 'success');
            const accentColor = isSuccess ? '#10b981' : '#ef4444';
            const iconClass = isSuccess ? 'fa-circle-check' : 'fa-circle-exclamation';

            const toast = document.createElement('div');
            toast.style.cssText = `
                pointer-events: auto;
                background: #ffffff;
                border: 1px solid ${isSuccess ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'};
                border-left: 5px solid ${accentColor};
                border-radius: 12px;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12), 0 8px 10px -6px rgba(0,0,0,0.06);
                padding: 0.95rem 1.15rem;
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                position: relative;
                overflow: hidden;
                transform: translateX(110%);
                transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.35s ease;
                opacity: 0;
            `;

            toast.innerHTML = `
                <div style="flex-shrink:0;margin-top:2px;">
                    <i class="fa-solid ${iconClass}" style="color:${accentColor};font-size:1.35rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:0.92rem;color:#0f172a;margin-bottom:0.15rem;">${title}</div>
                    <div style="font-size:0.84rem;color:#475569;line-height:1.4;word-break:break-word;">${message}</div>
                </div>
                <button type="button" aria-label="Close" style="background:none;border:none;color:#94a3b8;font-size:1.25rem;line-height:1;cursor:pointer;padding:0 0 0 0.5rem;margin-top:-2px;">&times;</button>
                <div class="toast-progress-bar" style="position:absolute;bottom:0;left:0;height:3px;background:${accentColor};width:100%;transition:width 4s linear;"></div>
            `;

            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
                const prog = toast.querySelector('.toast-progress-bar');
                if (prog) {
                    requestAnimationFrame(() => { prog.style.width = '0%'; });
                }
            });

            const closeToast = () => {
                toast.style.transform = 'translateX(110%)';
                toast.style.opacity = '0';
                setTimeout(() => { if (toast.parentNode) toast.remove(); }, 350);
            };

            toast.querySelector('button').addEventListener('click', closeToast);
            setTimeout(closeToast, 4000);
        }

        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                let msg = urlParams.get('success');
                if (msg === '1' || msg === '' || msg === 'true') {
                    msg = 'Operation completed successfully!';
                }
                showAdminToast('success', 'Success!', msg);
                
                // Clean URL but keep id if present
                const id = urlParams.get('id');
                const newUrl = window.location.pathname + (id ? '?id=' + id : '');
                window.history.replaceState({}, document.title, newUrl);
            }
            if (urlParams.has('error')) {
                showAdminToast('error', 'Error Occurred', urlParams.get('error'));
                
                const id = urlParams.get('id');
                const newUrl = window.location.pathname + (id ? '?id=' + id : '');
                window.history.replaceState({}, document.title, newUrl);
            }
            
            // Delete confirmation dialog
            const deleteBtns = document.querySelectorAll('.btn-delete');
            deleteBtns.forEach(btn => {
                btn.onclick = function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "This action cannot be undone!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#3b82f6',
                            confirmButtonText: 'Yes, delete it!',
                            background: '#ffffff',
                            color: '#1e293b'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = href;
                            }
                        });
                    } else if (confirm('Are you sure you want to delete this project?')) {
                        window.location.href = href;
                    }
                };
            });
        });
    </script>
</body>
</html>
