<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

$pageTitle = "Add Internship Opportunity";
require_once '../includes/layout_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Add New Internship Opportunity</h1>
        <p>Publish a verified internship position with role details, specifications, and direct employer contact info.</p>
    </div>
</div>

<div class="card">
    <form action="../api/add_internship.php" method="POST" enctype="multipart/form-data" id="addInternshipForm">
        
        <!-- SECTION 1: Company Information -->
        <div class="form-section-title"><i class="fa-solid fa-building"></i> Section 1: Company Information</div>

        <div class="form-row">
            <div class="form-group">
                <label for="company_name">Company Name <span style="color: #ef4444;">*</span></label>
                <input type="text" id="company_name" name="company_name" required placeholder="e.g. Acme Technologies Inc.">
            </div>
            <div class="form-group">
                <label for="company_location">Company Location / Headquarters</label>
                <input type="text" id="company_location" name="company_location" placeholder="e.g. Kochi, Kerala, India">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="company_logo">Company Logo (File Upload)</label>
                <input type="file" id="company_logo" name="company_logo" accept="image/jpeg,image/png,image/webp,image/gif">
                <span class="form-help">Max 5MB. PNG, JPG, WEBP. If empty, a professional default icon will be used.</span>
            </div>
            <div class="form-group">
                <label for="company_website">Company Website URL</label>
                <input type="url" id="company_website" name="company_website" placeholder="https://example.com">
            </div>
        </div>

        <!-- SECTION 2: Internship Information -->
        <div class="form-section-title"><i class="fa-solid fa-briefcase"></i> Section 2: Internship Information</div>

        <div class="form-row">
            <div class="form-group">
                <label for="internship_title">Internship Title <span style="color: #ef4444;">*</span></label>
                <input type="text" id="internship_title" name="internship_title" required placeholder="e.g. Junior Full-Stack Developer Intern">
            </div>

            <div class="form-group">
                <label for="category">Category / Domain <span style="color: #ef4444;">*</span></label>
                <select id="category" name="category" required onchange="toggleCategoryCustom(this.value)">
                    <option value="" disabled selected>Select Category</option>
                    <option value="Web Development">Web Development</option>
                    <option value="Mobile App Development">Mobile App Development</option>
                    <option value="Artificial Intelligence / ML">Artificial Intelligence / ML</option>
                    <option value="Data Science & Analytics">Data Science & Analytics</option>
                    <option value="Cloud & DevOps">Cloud & DevOps</option>
                    <option value="UI/UX Design">UI/UX Design</option>
                    <option value="Cyber Security">Cyber Security</option>
                    <option value="Embedded Systems / IoT">Embedded Systems / IoT</option>
                    <option value="Other">Other</option>
                </select>
                <div id="categoryCustomWrap" style="margin-top: 0.5rem; display: none;">
                    <input type="text" id="category_custom" name="category_custom" placeholder="Enter custom category name">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="short_description">Short Description <span style="color: #ef4444;">*</span></label>
            <input type="text" id="short_description" name="short_description" required maxlength="300" placeholder="Concise overview shown on internship listing cards (max 300 characters)...">
            <span class="form-help">This summary appears directly on the opportunity card.</span>
        </div>

        <div class="form-group">
            <label for="description">Full Description <span style="color: #ef4444;">*</span></label>
            <textarea id="description" name="description" required rows="5" placeholder="Detailed description of the internship position, mission, team structure, and technologies involved..."></textarea>
        </div>

        <div class="form-group">
            <label for="responsibilities">Key Responsibilities</label>
            <textarea id="responsibilities" name="responsibilities" rows="4" placeholder="List core tasks, day-to-day work, features to build, etc. (one per line recommended)..."></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="requirements">Required Skills & Technologies</label>
                <textarea id="requirements" name="requirements" rows="4" placeholder="e.g. React, Node.js, SQL, Git, REST APIs..."></textarea>
            </div>
            <div class="form-group">
                <label for="eligibility">Eligibility Criteria</label>
                <textarea id="eligibility" name="eligibility" rows="4" placeholder="e.g. MCA / B.Tech / M.Tech students, 2025/2026 batches, minimum 7.0 CGPA..."></textarea>
            </div>
        </div>

        <!-- SECTION 3: Specifications -->
        <div class="form-section-title"><i class="fa-solid fa-sliders"></i> Section 3: Specifications</div>

        <div class="form-row">
            <div class="form-group">
                <label for="duration">Duration</label>
                <input type="text" id="duration" name="duration" placeholder="e.g. 3 Months, 6 Months">
            </div>

            <div class="form-group">
                <label for="work_mode">Work Mode</label>
                <select id="work_mode" name="work_mode">
                    <option value="Remote">Remote</option>
                    <option value="On-site">On-site</option>
                    <option value="Hybrid">Hybrid</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="location">Location (City / Office)</label>
                <input type="text" id="location" name="location" placeholder="e.g. Kakkanad, Kochi (or 'Remote - India')">
            </div>

            <div class="form-group">
                <label for="openings">Number of Openings</label>
                <input type="number" id="openings" name="openings" min="1" placeholder="e.g. 2">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="stipend_type">Stipend Type</label>
                <select id="stipend_type" name="stipend_type">
                    <option value="Paid">Paid</option>
                    <option value="Unpaid">Unpaid</option>
                    <option value="Performance-based">Performance-based</option>
                </select>
            </div>

            <div class="form-group">
                <label for="stipend_amount">Stipend Amount / Terms</label>
                <input type="text" id="stipend_amount" name="stipend_amount" placeholder="e.g. ₹15,000 / month, ₹10,000 - ₹20,000">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Expected Start Date</label>
                <input type="date" id="start_date" name="start_date">
            </div>

            <div class="form-group">
                <label for="application_deadline">Application Deadline <span style="color: #ef4444;">*</span></label>
                <input type="date" id="application_deadline" name="application_deadline" required min="<?php echo date('Y-m-d'); ?>">
                <span class="form-help">After this date, the opportunity will automatically expire and disappear from public view.</span>
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
                <input type="email" id="application_email" name="application_email" placeholder="e.g. careers@company.com">
                <span class="form-help">Visitors will receive a one-click mailto link and copy button.</span>
            </div>

            <div class="form-group">
                <label for="application_url">External Company Application URL</label>
                <input type="url" id="application_url" name="application_url" placeholder="https://company.com/careers/apply">
                <span class="form-help">Direct link to apply on the company's portal.</span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="contact_person">Contact Person / Recruiter Name</label>
                <input type="text" id="contact_person" name="contact_person" placeholder="e.g. John Doe, HR Lead">
            </div>

            <div class="form-group">
                <label for="contact_phone">Contact Phone Number</label>
                <input type="text" id="contact_phone" name="contact_phone" placeholder="e.g. +91 98765 43210">
            </div>
        </div>

        <div class="form-group">
            <label for="application_instructions">Application Instructions</label>
            <textarea id="application_instructions" name="application_instructions" rows="3" placeholder="e.g. Send your resume and GitHub profile with subject line 'Application for Web Intern - [Your Name]'..."></textarea>
        </div>

        <!-- SECTION 5: Poster -->
        <div class="form-section-title"><i class="fa-solid fa-image"></i> Section 5: Official Announcement Poster</div>

        <div class="form-group">
            <label for="poster">Announcement Poster Image</label>
            <input type="file" id="poster" name="poster" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewPoster(event)">
            <span class="form-help">Max 10MB. Formats: JPG, PNG, WEBP. Visitors can click to expand full-size in a lightbox modal.</span>
            
            <div id="posterPreviewContainer" style="margin-top: 1rem; display: none;">
                <p style="font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.4rem;">Selected Poster Preview:</p>
                <img id="posterPreviewImg" src="" alt="Poster Preview" style="max-height: 250px; border-radius: 8px; border: 1px solid #cbd5e1;">
            </div>
        </div>

        <!-- SECTION 6: Status & Publishing Settings -->
        <div class="form-section-title"><i class="fa-solid fa-gear"></i> Section 6: Status & Publishing Settings</div>

        <div class="form-row">
            <div class="form-group">
                <label for="status">Opportunity Status <span style="color: #ef4444;">*</span></label>
                <select id="status" name="status" required>
                    <option value="Draft">Draft (Hidden from public website)</option>
                    <option value="Published" selected>Published (Immediately visible on website)</option>
                    <option value="Closed">Closed (Archived)</option>
                </select>
                <span class="form-help">Only 'Published' opportunities with deadlines in the future appear on the public site and floating counter.</span>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fa-solid fa-floppy-disk"></i> Save &amp; Publish Opportunity
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
