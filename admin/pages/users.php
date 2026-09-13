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
    <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="margin: 0; font-size: 1.8rem; color: #1e293b;">Manage Users</h1>
            <p style="margin: 0; color: #64748b; margin-top: 0.5rem;">Add new admin users and manage their permissions.</p>
        </div>
        <button id="btnAddNewUser" class="btn btn-primary" style="background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;">
            <i class="fa-solid fa-user-plus"></i> Add New User
        </button>
    </div>

    <div class="table-container" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #f1f5f9;">
                    <th style="padding: 1rem; color: #64748b; font-weight: 600;">ID</th>
                    <th style="padding: 1rem; color: #64748b; font-weight: 600;">Username</th>
                    <th style="padding: 1rem; color: #64748b; font-weight: 600;">Role</th>
                    <th style="padding: 1rem; color: #64748b; font-weight: 600;">Created At</th>
                    <th style="padding: 1rem; color: #64748b; font-weight: 600; text-align: right;">Actions</th>
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
<div id="userModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 400px; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="margin: 0; font-size: 1.25rem;">Add New User</h3>
            <button id="closeModal" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #64748b;"><i class="fa-solid fa-times"></i></button>
        </div>
        <div style="padding: 1.5rem;">
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #334155;">Username <span style="color: red;">*</span></label>
                    <input type="text" id="username" name="username" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #334155;">Password <span id="passwordReq" style="color: red;">*</span></label>
                    <input type="password" id="password" name="password" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    <small id="passwordHint" style="color: #64748b; display: none;">Leave blank to keep current password</small>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #334155;">Role <span style="color: red;">*</span></label>
                    <select id="role" name="role" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                        <option value="admin">Admin (Full Access)</option>
                        <option value="editor">Editor (Projects Only)</option>
                        <option value="viewer">Viewer (Read Only)</option>
                    </select>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" id="btnCancel" style="padding: 0.75rem 1.5rem; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Save User</button>
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
    let isEditing = false;
    const currentAdminId = <?php echo $_SESSION['admin_id']; ?>;

    // Fetch and render users
    function loadUsers() {
        fetch('../api/users.php?action=list')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    tableBody.innerHTML = data.users.map(u => `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 1rem;">${u.id}</td>
                            <td style="padding: 1rem; font-weight: 500; color: #0f172a;">${u.username} ${u.id == currentAdminId ? '<span style="background: #dbeafe; color: #1e40af; font-size: 0.75rem; padding: 0.15rem 0.5rem; border-radius: 12px; margin-left: 0.5rem;">You</span>' : ''}</td>
                            <td style="padding: 1rem;">
                                <span style="background: ${u.role === 'admin' ? '#fef3c7' : '#e0e7ff'}; color: ${u.role === 'admin' ? '#92400e' : '#3730a3'}; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem; font-weight: 500; text-transform: capitalize;">${u.role}</span>
                            </td>
                            <td style="padding: 1rem; color: #64748b;">${new Date(u.created_at).toLocaleDateString()}</td>
                            <td style="padding: 1rem; text-align: right;">
                                <button onclick="editUser(${u.id}, '${u.username}', '${u.role}')" style="background: none; border: none; color: #3b82f6; cursor: pointer; margin-right: 0.5rem;"><i class="fa-solid fa-edit"></i> Edit</button>
                                ${u.id != currentAdminId ? `<button onclick="deleteUser(${u.id}, '${u.username}')" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i class="fa-solid fa-trash"></i> Delete</button>` : ''}
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 2rem; color: red;">${data.message}</td></tr>`;
                }
            })
            .catch(err => {
                tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 2rem; color: red;">Error loading users</td></tr>`;
            });
    }

    loadUsers();

    // Open Add User Modal
    document.getElementById('btnAddNewUser').addEventListener('click', () => {
        isEditing = false;
        userForm.reset();
        document.getElementById('userId').value = '';
        usernameInput.readOnly = false;
        usernameInput.style.backgroundColor = 'white';
        modalTitle.textContent = 'Add New User';
        passwordReq.style.display = 'inline';
        passwordInput.required = true;
        passwordHint.style.display = 'none';
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
        usernameInput.readOnly = true; // Prevent changing username for simplicity
        usernameInput.style.backgroundColor = '#f1f5f9';
        document.getElementById('role').value = role;
        
        modalTitle.textContent = 'Edit User';
        passwordReq.style.display = 'none';
        passwordInput.required = false;
        passwordHint.style.display = 'block';
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
        const formData = new FormData(userForm);
        formData.append('action', isEditing ? 'update' : 'add');
        
        fetch('../api/users.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeModalFunc();
                loadUsers();
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            alert('An error occurred. Please try again.');
        });
    });
});
</script>

<?php require_once '../includes/layout_footer.php'; ?>
