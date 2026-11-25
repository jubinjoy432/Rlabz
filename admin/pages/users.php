<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'User Management';
require_once '../includes/layout_header.php';
?>

<div class="admin-panel-container">
    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin: 0; font-size: 1.8rem;">Manage Users</h1>
            <p style="margin: 0; margin-top: 0.5rem;">Add new admin users and manage their permissions.</p>
        </div>
        <button id="btnAddNewUser" class="btn-primary-action btn-add-user-header">
            <i class="fa-solid fa-user-plus"></i> Add New User
        </button>
    </div>

    <div class="card table-card" style="border-radius: 12px; padding: 1.25rem 1.5rem; overflow-x: auto;">
        <table class="recent-table" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr>
                    <th style="padding: 1rem; font-weight: 600;">ID</th>
                    <th style="padding: 1rem; font-weight: 600;">Username</th>
                    <th style="padding: 1rem; font-weight: 600;">Role</th>
                    <th style="padding: 1rem; font-weight: 600;">Created At</th>
                    <th style="padding: 1rem; font-weight: 600; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <!-- Users will be populated here -->
                <tr><td colspan="5" style="text-align: center; padding: 2rem;">Loading users...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- User Modal -->
<div id="userModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center;">
    <div style="width: 440px; max-width: calc(100vw - 2rem); border-radius: 14px; overflow: hidden; margin: 1rem;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="margin: 0; font-size: 1.25rem;">Add New User</h3>
            <button id="closeModal" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #64748b; padding: 0.25rem;"><i class="fa-solid fa-times"></i></button>
        </div>
        <div style="padding: 1.5rem;">
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; margin-bottom: 0.4rem; font-weight: 600;">Username <span style="color: red;">*</span></label>
                    <input type="text" id="username" name="username" required minlength="3" placeholder="Enter username (min 3 chars)" style="width: 100%; padding: 0.75rem 0.85rem; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box; font-family: inherit;">
                    <small style="color: #64748b; font-size: 0.78rem;">Alphanumeric characters only, min 3 letters</small>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; margin-bottom: 0.4rem; font-weight: 600;">Password <span id="passwordReq" style="color: red;">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" minlength="8" placeholder="Enter password (min 8 chars)" style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 0.85rem; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box; font-family: inherit;">
                        <button type="button" id="toggleUserPassword" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 0;">
                            <i class="fa-solid fa-eye" id="toggleUserPasswordIcon"></i>
                        </button>
                    </div>
                    
                    <!-- Password Strength Meter -->
                    <div id="userPasswordStrengthContainer" style="margin-top: 0.5rem; display: none;">
                        <div style="display: flex; gap: 4px; height: 4px; border-radius: 2px; overflow: hidden; background: rgba(0,0,0,0.1); margin-bottom: 0.35rem;">
                            <div id="strBar1" style="flex: 1; background: #e2e8f0; transition: all 0.25s ease;"></div>
                            <div id="strBar2" style="flex: 1; background: #e2e8f0; transition: all 0.25s ease;"></div>
                            <div id="strBar3" style="flex: 1; background: #e2e8f0; transition: all 0.25s ease;"></div>
                            <div id="strBar4" style="flex: 1; background: #e2e8f0; transition: all 0.25s ease;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span id="userPasswordStrengthLabel" style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Strength: Too Short</span>
                            <span style="font-size: 0.72rem; color: #64748b;">Min 8 chars, mix letters &amp; numbers</span>
                        </div>
                    </div>
                    
                    <small id="passwordHint" style="color: #64748b; display: none; margin-top: 0.25rem;">Leave blank to keep current password</small>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.4rem; font-weight: 600;">Role <span style="color: red;">*</span></label>
                    <select id="role" name="role" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box; font-family: inherit;">
                        <option value="admin">Admin (Full Access)</option>
                        <option value="editor">Editor (Projects Only)</option>
                        <option value="viewer">Viewer (Read Only)</option>
                    </select>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" id="btnCancel" class="btn-cancel"><i class="fa-solid fa-xmark"></i> Cancel</button>
                    <button type="submit" id="btnSaveUser" class="btn-primary-action"><i class="fa-solid fa-check"></i> Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('usersTableBody');
    const modal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const modalTitle = document.getElementById('modalTitle');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const passwordReq = document.getElementById('passwordReq');
    const passwordHint = document.getElementById('passwordHint');
    const togglePassBtn = document.getElementById('toggleUserPassword');
    const togglePassIcon = document.getElementById('toggleUserPasswordIcon');
    const strengthContainer = document.getElementById('userPasswordStrengthContainer');
    const strengthLabel = document.getElementById('userPasswordStrengthLabel');
    const strBars = [
        document.getElementById('strBar1'),
        document.getElementById('strBar2'),
        document.getElementById('strBar3'),
        document.getElementById('strBar4')
    ];

    let isEditing = false;
    const currentAdminId = <?php echo $_SESSION['admin_id']; ?>;

    // Toggle password visibility
    if (togglePassBtn) {
        togglePassBtn.addEventListener('click', () => {
            const isPass = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPass ? 'text' : 'password');
            togglePassIcon.className = isPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    }

    // Password strength evaluator
    function evaluatePasswordStrength(val) {
        if (!val) return 0;
        let score = 0;
        if (val.length >= 8) score++;
        if (val.length >= 12) score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/\d/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        return Math.min(4, Math.max(1, score));
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            if (!val) {
                strengthContainer.style.display = 'none';
                return;
            }
            strengthContainer.style.display = 'block';
            const score = evaluatePasswordStrength(val);

            // Colors: 1 = Red, 2 = Orange, 3 = Blue, 4 = Green
            const colors = ['#ef4444', '#f59e0b', '#3b82f6', '#10b981'];
            const labels = ['Weak (too short or simple)', 'Fair', 'Good', 'Strong'];

            strBars.forEach((bar, idx) => {
                if (idx < score) {
                    bar.style.background = colors[score - 1];
                } else {
                    bar.style.background = '#e2e8f0';
                }
            });

            strengthLabel.textContent = `Strength: ${labels[score - 1]}`;
            strengthLabel.style.color = colors[score - 1];
        });
    }

    // Fetch and render users
    function loadUsers() {
        fetch('../api/users.php?action=list')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    tableBody.innerHTML = data.users.map(u => `
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">${u.id}</td>
                            <td style="padding: 1rem; font-weight: 500;">${u.username} ${u.id == currentAdminId ? '<span style="background: rgba(37,99,235,0.15); color: #38bdf8; font-size: 0.72rem; padding: 0.15rem 0.5rem; border-radius: 12px; margin-left: 0.5rem; font-weight: 700;">You</span>' : ''}</td>
                            <td style="padding: 1rem;">
                                <span style="background: ${u.role === 'admin' ? 'rgba(245,158,11,0.15)' : 'rgba(99,102,241,0.15)'}; color: ${u.role === 'admin' ? '#f59e0b' : '#818cf8'}; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600; text-transform: capitalize;">${u.role}</span>
                            </td>
                            <td style="padding: 1rem; color: #94a3b8;">${new Date(u.created_at).toLocaleDateString()}</td>
                            <td style="padding: 1rem; text-align: right;">
                                <button onclick="editUser(${u.id}, '${u.username}', '${u.role}')" class="btn-action" style="background: none; border: none; color: #38bdf8; cursor: pointer; margin-right: 0.5rem; font-weight: 600;"><i class="fa-solid fa-edit"></i> Edit</button>
                                ${u.id != currentAdminId ? `<button onclick="deleteUser(${u.id}, '${u.username}')" class="btn-action btn-delete" style="background: none; border: none; color: #f87171; cursor: pointer; font-weight: 600;"><i class="fa-solid fa-trash"></i> Delete</button>` : ''}
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #ef4444;">${data.message}</td></tr>`;
                }
            })
            .catch(err => {
                tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #ef4444;">Error loading users</td></tr>`;
            });
    }

    loadUsers();

    // Open Add User Modal
    document.getElementById('btnAddNewUser').addEventListener('click', () => {
        isEditing = false;
        userForm.reset();
        document.getElementById('userId').value = '';
        usernameInput.readOnly = false;
        modalTitle.textContent = 'Add New User';
        passwordReq.style.display = 'inline';
        passwordInput.required = true;
        passwordHint.style.display = 'none';
        strengthContainer.style.display = 'none';
        modal.style.display = 'flex';
    });

    // Close Modal
    function closeModalFunc() {
        modal.style.display = 'none';
    }
    document.getElementById('closeModal').addEventListener('click', closeModalFunc);
    document.getElementById('btnCancel').addEventListener('click', closeModalFunc);

    // Edit User
    window.editUser = function(id, username, role) {
        isEditing = true;
        userForm.reset();
        document.getElementById('userId').value = id;
        usernameInput.value = username;
        usernameInput.readOnly = true;
        document.getElementById('role').value = role;
        
        modalTitle.textContent = 'Edit User';
        passwordReq.style.display = 'none';
        passwordInput.required = false;
        passwordHint.style.display = 'block';
        strengthContainer.style.display = 'none';
        modal.style.display = 'flex';
    };

    // Delete User
    window.deleteUser = function(id, username) {
        if (confirm(`Are you sure you want to delete user "${username}"?`)) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            fetch('../api/users.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadUsers();
                } else {
                    alert(data.message);
                }
            });
        }
    };

    // Form Submit
    userForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const usernameVal = usernameInput.value.trim();
        const passwordVal = passwordInput.value;

        if (usernameVal.length < 3) {
            alert('Username must be at least 3 characters long.');
            usernameInput.focus();
            return;
        }

        if (!isEditing && passwordVal.length < 8) {
            alert('Password must be at least 8 characters long for new users.');
            passwordInput.focus();
            return;
        }

        if (isEditing && passwordVal && passwordVal.length < 8) {
            alert('New password must be at least 8 characters long.');
            passwordInput.focus();
            return;
        }

        const formData = new FormData(userForm);
        formData.append('action', isEditing ? 'update' : 'add');
        
        const saveBtn = document.getElementById('btnSaveUser');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        fetch('../api/users.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-check"></i> Save User';
            if (data.success) {
                closeModalFunc();
                loadUsers();
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-check"></i> Save User';
            alert('An error occurred. Please try again.');
        });
    });
});
</script>

<?php require_once '../includes/layout_footer.php'; ?>
