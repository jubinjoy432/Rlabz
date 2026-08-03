<?php
$file = 'admin/edit_project.php';
$content = file_get_contents($file);

// Normalize line endings
$content = str_replace("\r\n", "\n", $content);

// 1. Queries
$search1 = <<<'EOD'
$stmtMembers = $pdo->prepare("SELECT * FROM project_members WHERE project_id = ? ORDER BY id");
$stmtMembers->execute([$id]);
$members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);
EOD;
$replace1 = <<<'EOD'
$stmtMembers = $pdo->prepare("SELECT * FROM project_members WHERE project_id = ? ORDER BY id");
$stmtMembers->execute([$id]);
$members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

$stmtFaculties = $pdo->prepare("SELECT * FROM project_faculty WHERE project_id = ? ORDER BY id");
$stmtFaculties->execute([$id]);
$faculties = $stmtFaculties->fetchAll(PDO::FETCH_ASSOC);

$stmtSsl = $pdo->prepare("SELECT * FROM project_ssl_certs WHERE project_id = ?");
$stmtSsl->execute([$id]);
$ssl = $stmtSsl->fetch(PDO::FETCH_ASSOC);
EOD;
$content = str_replace($search1, $replace1, $content);

// 2. Faculty
$search2 = <<<'EOD'
        <div class="form-section-title"><i class="fa-solid fa-user-tie"></i> Faculty Information</div>
        <div class="form-row">
            <div class="form-group">
                <label>Faculty Name</label>
                <input type="text" name="faculty_name" value="<?= htmlspecialchars($project['faculty_name']) ?>">
            </div>
            <div class="form-group">
                <label>Designation</label>
                <input type="text" name="faculty_designation" value="<?= htmlspecialchars($project['faculty_designation']) ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Faculty Photo (Upload new to replace)</label>
            <input type="file" name="faculty_photo" accept="image/*">
        </div>
EOD;
$replace2 = <<<'EOD'
        <div class="form-section-title"><i class="fa-solid fa-user-tie"></i> Faculty Information</div>
        <div class="form-group">
            <div class="faculty-members-container" id="faculty-members-container">
                <?php if (empty($faculties)): ?>
                    <div class="faculty-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">
                        <input type="text" name="faculty_names[]" placeholder="Faculty Name" style="flex: 1;">
                        <input type="text" name="faculty_designations[]" placeholder="Designation" style="flex: 1;">
                        <input type="file" name="faculty_photos[]" accept="image/*" style="flex: 1;">
                        <input type="hidden" name="existing_faculty_photos[]" value="">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                    </div>
                <?php else: ?>
                    <?php foreach ($faculties as $f): ?>
                        <div class="faculty-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items: center;">
                            <input type="text" name="faculty_names[]" value="<?= htmlspecialchars($f['name'] ?? '') ?>" placeholder="Faculty Name" style="flex: 1;">
                            <input type="text" name="faculty_designations[]" value="<?= htmlspecialchars($f['designation'] ?? '') ?>" placeholder="Designation" style="flex: 1;">
                            
                            <div style="display:flex; flex-direction:column; flex:1;">
                                <input type="file" name="faculty_photos[]" accept="image/*">
                                <?php if(!empty($f['photo_path'])): ?>
                                    <div style="display:flex; align-items:center; margin-top:4px; gap:6px;">
                                        <img src="../<?= htmlspecialchars($f['photo_path']) ?>" alt="Current image" style="width:24px; height:24px; object-fit:cover; border-radius:50%; border:1px solid rgba(0,0,0,0.1);">
                                        <span style="font-size: 0.7rem; color: #64748b;">Current image uploaded</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="existing_faculty_photos[]" value="<?= htmlspecialchars($f['photo_path'] ?? '') ?>">
                            
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-add" onclick="addFacultyRow()" style="margin-top:0.5rem;"><i class="fas fa-plus"></i> Add Faculty</button>
        </div>
EOD;
$content = str_replace($search2, $replace2, $content);

// 3. Team Members
$search3 = <<<'EOD'
        <div class="form-section-title"><i class="fa-solid fa-users"></i> Team Members (Comma separated names)</div>
        <div class="form-group">
            <?php 
                $member_names = array_map(function($m) { return trim($m['name']); }, $members);
                $team_str = implode(", ", $member_names);
            ?>
            <input type="text" name="team_members_str" value="<?= htmlspecialchars($team_str) ?>" placeholder="John Doe, Jane Smith">
            <span class="form-help">For simplicity in editing, please list names separated by commas.</span>
        </div>
EOD;
$replace3 = <<<'EOD'
        <div class="form-section-title"><i class="fa-solid fa-users"></i> Team Members</div>
        <div class="form-group">
            <div class="team-members-container" id="team-members-container">
                <?php if (empty($members)): ?>
                    <div class="team-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">
                        <input type="text" name="member_names[]" placeholder="Student Name" style="flex: 1;">
                        <input type="text" name="member_roles[]" placeholder="Role (optional)" style="flex: 1;">
                        <input type="text" name="member_linkedin[]" placeholder="LinkedIn URL (optional)" style="flex: 1.5;">
                        <input type="file" name="member_photos[]" accept="image/*" style="flex: 1;">
                        <input type="hidden" name="existing_photos[]" value="">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                    </div>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                        <div class="team-member-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items: center;">
                            <input type="text" name="member_names[]" value="<?= htmlspecialchars($m['name'] ?? '') ?>" placeholder="Student Name" style="flex: 1;">
                            <input type="text" name="member_roles[]" value="<?= htmlspecialchars($m['role'] ?? '') ?>" placeholder="Role (optional)" style="flex: 1;">
                            <input type="text" name="member_linkedin[]" value="<?= htmlspecialchars($m['linkedin_link'] ?? '') ?>" placeholder="LinkedIn URL (optional)" style="flex: 1.5;">
                            
                            <div style="display:flex; flex-direction:column; flex:1;">
                                <input type="file" name="member_photos[]" accept="image/*">
                                <?php if(!empty($m['photo_path'])): ?>
                                    <div style="display:flex; align-items:center; margin-top:4px; gap:6px;">
                                        <img src="../<?= htmlspecialchars($m['photo_path']) ?>" alt="Current image" style="width:24px; height:24px; object-fit:cover; border-radius:50%; border:1px solid rgba(0,0,0,0.1);">
                                        <span style="font-size: 0.7rem; color: #64748b;">Current image uploaded</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="existing_photos[]" value="<?= htmlspecialchars($m['photo_path'] ?? '') ?>">
                            
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-add" onclick="addMemberRow()" style="margin-top:0.5rem;"><i class="fas fa-plus"></i> Add Member</button>
        </div>
EOD;
$content = str_replace($search3, $replace3, $content);

// 4. SSL fields
$search4 = <<<'EOD'
        <button type="submit" class="btn-submit full-width"><i class="fa-solid fa-save"></i> Save Changes</button>
    </form>
EOD;
$replace4 = <<<'EOD'
        <!-- SSL Certificate Module -->
        <div class="form-section-title"><i class="fa-solid fa-shield-halved"></i> SSL Certificate Details (Optional)</div>
        <div class="form-row">
            <div class="form-group">
                <label for="ssl_domain">Domain URL</label>
                <input type="url" id="ssl_domain" name="ssl_domain" value="<?= htmlspecialchars($ssl['domain_url'] ?? '') ?>" placeholder="https://example.com">
            </div>
            <div class="form-group">
                <label for="ssl_provider">Provider</label>
                <input type="text" id="ssl_provider" name="ssl_provider" value="<?= htmlspecialchars($ssl['provider'] ?? '') ?>" placeholder="Let's Encrypt">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="ssl_issue_date">Issue Date</label>
                <input type="date" id="ssl_issue_date" name="ssl_issue_date" value="<?= htmlspecialchars($ssl['issue_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="ssl_expiry_date">Expiry Date</label>
                <input type="date" id="ssl_expiry_date" name="ssl_expiry_date" value="<?= htmlspecialchars($ssl['expiry_date'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" class="btn-submit full-width"><i class="fa-solid fa-save"></i> Save Changes</button>
    </form>
EOD;
$content = str_replace($search4, $replace4, $content);

// 5. JS Footer
$search5 = <<<'EOD'
<?php require_once 'includes/layout_footer.php'; ?>
EOD;
$replace5 = <<<'EOD'
<?php require_once 'includes/layout_footer.php'; ?>
<script>
    function addMemberRow() {
        const container = document.getElementById('team-members-container');
        const row = document.createElement('div');
        row.className = 'team-member-row';
        row.style.display = 'flex';
        row.style.gap = '0.5rem';
        row.style.marginBottom = '0.5rem';
        row.style.alignItems = 'center';
        row.innerHTML = `
            <input type="text" name="member_names[]" placeholder="Student Name" style="flex: 1;">
            <input type="text" name="member_roles[]" placeholder="Role (optional)" style="flex: 1;">
            <input type="text" name="member_linkedin[]" placeholder="LinkedIn URL (optional)" style="flex: 1.5;">
            <input type="file" name="member_photos[]" accept="image/*" style="flex: 1;">
            <input type="hidden" name="existing_photos[]" value="">
            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(row);
    }

    function addFacultyRow() {
        const container = document.getElementById('faculty-members-container');
        const row = document.createElement('div');
        row.className = 'faculty-member-row';
        row.style.display = 'flex';
        row.style.gap = '0.5rem';
        row.style.marginBottom = '0.5rem';
        row.style.alignItems = 'center';
        row.innerHTML = `
            <input type="text" name="faculty_names[]" placeholder="Faculty Name" style="flex: 1;">
            <input type="text" name="faculty_designations[]" placeholder="Designation" style="flex: 1;">
            <input type="file" name="faculty_photos[]" accept="image/*" style="flex: 1;">
            <input type="hidden" name="existing_faculty_photos[]" value="">
            <button type="button" class="btn-remove" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(row);
    }
</script>
EOD;
$content = str_replace($search5, $replace5, $content);

file_put_contents($file, $content);
echo "Done patching edit_project.php!\n";
?>
