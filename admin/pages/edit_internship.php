<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once '../api/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: manage_internships.php?error=" . urlencode("Invalid internship ID."));
    exit;
}

// Fetch record
$stmt = $pdo->prepare("SELECT * FROM internship_opportunities WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: manage_internships.php?error=" . urlencode("Internship not found or has been deleted."));
    exit;
}

$pageTitle = "Edit Internship: " . $item['internship_title'];
require_once '../includes/layout_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Internship Opportunity</h1>
        <p>Updating specifications for <?php echo htmlspecialchars($item['internship_title']); ?> at <?php echo htmlspecialchars($item['company_name']); ?></p>
    </div>
    <div class="page-header-actions">
        <a href="manage_internships.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Back to Internships</a>
    </div>
</div>

<div class="card">
    <form action="../api/edit_internship_action.php" method="POST" enctype="multipart/form-data" id="editInternshipForm">
        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">

        <!-- SECTION 1: Company Information -->
        <div class="form-section-title"><i class="fa-solid fa-building"></i> Section 1: Company Information</div>

        <div class="form-row">
            <div class="form-group">
                <label for="company_name">Company Name <span style="color: #ef4444;">*</span></label>
                <input type="text" id="company_name" name="company_name" required value="<?php echo htmlspecialchars($item['company_name']); ?>">
            </div>
            <div class="form-group">
                <label for="company_location">Company Location / Headquarters</label>
                <input type="text" id="company_location" name="company_location" value="<?php echo htmlspecialchars($item['company_location'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="company_logo">Company Logo (Replace)</label>
                <input type="file" id="company_logo" name="company_logo" accept="image/jpeg,image/png,image/webp,image/gif">
                <span class="form-help">Upload a new image to replace current logo.</span>

                <?php if (!empty($item['company_logo'])): ?>
                    <div style="margin-top: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                        <img src="<?php echo htmlspecialchars(preg_match('#^https?://#i', $item['company_logo']) ? $item['company_logo'] : '../../' . ltrim($item['company_logo'], '/')); ?>" alt="Current Logo" style="width: 44px; height: 44px; object-fit: contain; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc;">
                        <label style="font-size: 0.8rem; font-weight: normal; color: #dc2626; display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                            <input type="checkbox" name="remove_logo" value="1"> Remove current logo
                        </label>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="company_website">Company Website URL</label>
                <input type="url" id="company_website" name="company_website" value="<?php echo htmlspecialchars($item['company_website'] ?? ''); ?>">
            </div>
        </div>

        <!-- SECTION 2: Internship Information -->
        <div class="form-section-title"><i class="fa-solid fa-briefcase"></i> Section 2: Internship Information</div>

        <div class="form-row">
            <div class="form-group">
                <label for="internship_title">Internship Title <span style="color: #ef4444;">*</span></label>
                <input type="text" id="internship_title" name="internship_title" required value="<?php echo htmlspecialchars($item['internship_title']); ?>">
            </div>

            <div class="form-group">
                <?php
                $standardCategories = [
                    'Web Development', 'Mobile App Development', 'Artificial Intelligence / ML',
                    'Data Science & Analytics', 'Cloud & DevOps', 'UI/UX Design', 'Cyber Security',
                    'Embedded Systems / IoT'
                ];
                $isCustomCategory = !in_array($item['category'], $standardCategories);
                ?>
                <label for="category">Category / Domain <span style="color: #ef4444;">*</span></label>
                <select id="category" name="category" required onchange="toggleCategoryCustom(this.value)">
                    <?php foreach($standardCategories as $scat): ?>
                        <option value="<?php echo htmlspecialchars($scat); ?>" <?php echo ($item['category'] === $scat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($scat); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="Other" <?php echo $isCustomCategory ? 'selected' : ''; ?>>Other</option>
                </select>
                <div id="categoryCustomWrap" style="margin-top: 0.5rem; <?php echo $isCustomCategory ? 'display: block;' : 'display: none;'; ?>">
                    <input type="text" id="category_custom" name="category_custom" value="<?php echo $isCustomCategory ? htmlspecialchars($item['category']) : ''; ?>" placeholder="Enter custom category name">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="short_description">Short Description <span style="color: #ef4444;">*</span></label>
            <input type="text" id="short_description" name="short_description" required maxlength="300" value="<?php echo htmlspecialchars($item['short_description']); ?>">
            <span class="form-help">Shown on internship cards. Keep it concise.</span>
        </div>

        <div class="form-group">
            <label for="description">Full Description <span style="color: #ef4444;">*</span></label>
            <textarea id="description" name="description" required rows="5"><?php echo htmlspecialchars($item['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="responsibilities">Key Responsibilities</label>
            <textarea id="responsibilities" name="responsibilities" rows="4"><?php echo htmlspecialchars($item['responsibilities'] ?? ''); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="requirements">Required Skills & Technologies</label>
                <textarea id="requirements" name="requirements" rows="4"><?php echo htmlspecialchars($item['requirements'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="eligibility">Eligibility Criteria</label>
                <textarea id="eligibility" name="eligibility" rows="4"><?php echo htmlspecialchars($item['eligibility'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- SECTION 3: Specifications -->
        <div class="form-section-title"><i class="fa-solid fa-sliders"></i> Section 3: Specifications</div>

        <div class="form-row">
            <div class="form-group">
                <label for="duration">Duration</label>
                <input type="text" id="duration" name="duration" value="<?php echo htmlspecialchars($item['duration'] ?? ''); ?>" placeholder="e.g. 3 Months, 6 Months">
            </div>

            <div class="form-group">
                <label for="work_mode">Work Mode</label>
                <select id="work_mode" name="work_mode">
                    <option value="Remote" <?php echo ($item['work_mode'] === 'Remote') ? 'selected' : ''; ?>>Remote</option>
                    <option value="On-site" <?php echo ($item['work_mode'] === 'On-site') ? 'selected' : ''; ?>>On-site</option>
                    <option value="Hybrid" <?php echo ($item['work_mode'] === 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="location">Location (City / Office)</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($item['location'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="openings">Number of Openings</label>
                <input type="number" id="openings" name="openings" min="1" value="<?php echo htmlspecialchars($item['openings'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="stipend_type">Stipend Type</label>
                <select id="stipend_type" name="stipend_type">
                    <option value="Paid" <?php echo ($item['stipend_type'] === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                    <option value="Unpaid" <?php echo ($item['stipend_type'] === 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="Performance-based" <?php echo ($item['stipend_type'] === 'Performance-based') ? 'selected' : ''; ?>>Performance-based</option>
                </select>
            </div>

            <div class="form-group">
                <label for="stipend_amount">Stipend Amount / Terms</label>
                <input type="text" id="stipend_amount" name="stipend_amount" value="<?php echo htmlspecialchars($item['stipend_amount'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Expected Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($item['start_date'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="application_deadline">Application Deadline <span style="color: #ef4444;">*</span></label>
                <input type="date" id="application_deadline" name="application_deadline" required value="<?php echo htmlspecialchars($item['application_deadline']); ?>">
                <span class="form-help">Active listings require this date to be today or in the future.</span>
            </div>
        </div>

        <!-- SECTION 4: Contact & Application Details -->
        <div class="form-section-title"><i class="fa-solid fa-paper-plane"></i> Section 4: Contact & Application Information</div>

        <div class="alert" style="background: rgba(249,68,13,0.08); border: 1px solid rgba(249,68,13,0.25); color: #c2410c; margin-bottom: 1.25rem; font-size: 0.85rem;">
            <i class="fa-solid fa-circle-info"></i> <strong>Notice:</strong> At least one valid contact method (Email or Application URL) recommended.
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="application_email">Application Email</label>
                <input type="email" id="application_email" name="application_email" value="<?php echo htmlspecialchars($item['application_email'] ?? ''); ?>" placeholder="e.g. careers@company.com">
            </div>

            <div class="form-group">
                <label for="application_url">External Company Application URL</label>
                <input type="url" id="application_url" name="application_url" value="<?php echo htmlspecialchars($item['application_url'] ?? ''); ?>" placeholder="https://company.com/careers/apply">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="contact_person">Contact Person / Recruiter Name</label>
                <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($item['contact_person'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="contact_phone">Contact Phone Number</label>
                <input type="text" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($item['contact_phone'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="application_instructions">Application Instructions</label>
            <textarea id="application_instructions" name="application_instructions" rows="3"><?php echo htmlspecialchars($item['application_instructions'] ?? ''); ?></textarea>
        </div>

        <!-- SECTION 5: Poster -->
        <div class="form-section-title"><i class="fa-solid fa-image"></i> Section 5: Official Announcement Poster</div>

        <div class="form-group">
            <label for="poster">Announcement Poster Image (Replace)</label>
            <input type="file" id="poster" name="poster" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewPoster(event)">
            <span class="form-help">Max 10MB. Upload a new image to replace current poster.</span>

            <?php if (!empty($item['poster_path'])): ?>
                <div style="margin-top: 0.75rem;">
                    <p style="font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Current Poster:</p>
                    <img src="<?php echo htmlspecialchars(preg_match('#^https?://#i', $item['poster_path']) ? $item['poster_path'] : '../../' . ltrim($item['poster_path'], '/')); ?>" alt="Current Poster" style="max-height: 200px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <div style="margin-top: 0.4rem;">
                        <label style="font-size: 0.8rem; font-weight: normal; color: #dc2626; display: inline-flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                            <input type="checkbox" name="remove_poster" value="1"> Remove current poster
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <div id="posterPreviewContainer" style="margin-top: 1rem; display: none;">
                <p style="font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.4rem;">New Poster Preview:</p>
                <img id="posterPreviewImg" src="" alt="Poster Preview" style="max-height: 250px; border-radius: 8px; border: 1px solid #cbd5e1;">
            </div>
        </div>

        <!-- SECTION 6: Status & Publishing Settings -->
        <div class="form-section-title"><i class="fa-solid fa-gear"></i> Section 6: Status & Publishing Settings</div>

        <div class="form-row">
            <div class="form-group">
                <label for="status">Opportunity Status <span style="color: #ef4444;">*</span></label>
                <select id="status" name="status" required>
                    <option value="Draft" <?php echo ($item['status'] === 'Draft') ? 'selected' : ''; ?>>Draft (Hidden from public website)</option>
                    <option value="Published" <?php echo ($item['status'] === 'Published') ? 'selected' : ''; ?>>Published (Visible on website)</option>
                    <option value="Closed" <?php echo ($item['status'] === 'Closed') ? 'selected' : ''; ?>>Closed (Archived)</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fa-solid fa-floppy-disk"></i> Update Internship Opportunity
            </button>
            <a href="manage_internships.php" class="btn-cancel">
                <i class="fa-solid fa-xmark"></i> Cancel
            </a>
        </div>

    </form>
</div>

<script>
function toggleCategoryCustom(val) {
    const wrap = document.getElementById('categoryCustomWrap');
    const input = document.getElementById('category_custom');
    if (val === 'Other') {
        wrap.style.display = 'block';
        input.required = true;
        input.focus();
    } else {
        wrap.style.display = 'none';
        input.required = false;
    }
}

function previewPoster(event) {
    const file = event.target.files[0];
    const previewContainer = document.getElementById('posterPreviewContainer');
    const previewImg = document.getElementById('posterPreviewImg');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
    }
}
</script>

<?php require_once '../includes/layout_footer.php'; ?>
