/**
 * Public Internship Detail Page Script
 * Fetches single opportunity details from admin/api/public_internships.php?action=detail&id=...
 * Handles missing fields gracefully (never displays empty fields)
 * Supports Lightbox Modal for poster and mailto / copy email actions.
 */

document.addEventListener('DOMContentLoaded', () => {
    const loader = document.getElementById('internshipsLoader');
    const container = document.getElementById('detailContentContainer');
    const errorState = document.getElementById('detailErrorState');

    // Parse ID from URL query
    const urlParams = new URLSearchParams(window.location.search);
    const internshipId = parseInt(urlParams.get('id'), 10);

    if (!internshipId || isNaN(internshipId)) {
        showError();
        return;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function getDeadlineInfo(deadlineStr, apiDaysLeft) {
        if (!deadlineStr) {
            return { label: 'Open', badgeClass: 'badge-open', daysLeft: 999, tag: 'open', icon: 'fa-regular fa-calendar-check' };
        }

        let days = (typeof apiDaysLeft === 'number') ? apiDaysLeft : null;
        if (days === null) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const dl = new Date(deadlineStr);
            dl.setHours(23, 59, 59, 999);
            days = Math.floor((dl.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
        }

        if (days < 0) {
            return {
                label: 'Expired',
                badgeClass: 'badge-expired',
                daysLeft: days,
                tag: 'expired',
                icon: 'fa-solid fa-circle-exclamation'
            };
        } else if (days === 0) {
            return {
                label: 'Expires Today',
                badgeClass: 'badge-urgent',
                daysLeft: 0,
                tag: 'today',
                icon: 'fa-solid fa-hourglass-end'
            };
        } else if (days === 1) {
            return {
                label: '1 Day Left',
                badgeClass: 'badge-urgent',
                daysLeft: 1,
                tag: 'urgent',
                icon: 'fa-solid fa-hourglass-half'
            };
        } else if (days <= 3) {
            return {
                label: `Ending Soon (${days}d left)`,
                badgeClass: 'badge-urgent',
                daysLeft: days,
                tag: 'urgent',
                icon: 'fa-solid fa-hourglass-half'
            };
        } else if (days <= 7) {
            return {
                label: `Closing Soon (${days}d left)`,
                badgeClass: 'badge-warning',
                daysLeft: days,
                tag: 'closing_soon',
                icon: 'fa-solid fa-clock'
            };
        } else if (days <= 14) {
            return {
                label: `${days} days left`,
                badgeClass: 'badge-notice',
                daysLeft: days,
                tag: 'notice',
                icon: 'fa-regular fa-calendar'
            };
        } else {
            return {
                label: `Actively Open (${days}d left)`,
                badgeClass: 'badge-open',
                daysLeft: days,
                tag: 'open',
                icon: 'fa-regular fa-calendar-check'
            };
        }
    }

    function showError() {
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => { loader.style.display = 'none'; }, 200);
        }
        if (container) container.style.display = 'none';
        if (errorState) errorState.style.display = 'block';
    }

    async function loadDetail() {
        try {
            const res = await fetch(`../admin/api/public_internships.php?action=detail&id=${encodeURIComponent(internshipId)}`);
            const data = await res.json();

            const item = data.data || data.internship;
            if (!data.success || !item) {
                showError();
                return;
            }

            renderDetail(item);
        } catch (err) {
            console.error('Error fetching internship details:', err);
            showError();
        } finally {
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => { loader.style.display = 'none'; }, 200);
            }
        }
    }

    function renderDetail(item) {
        // Update document title
        document.title = `${item.internship_title} at ${item.company_name} — RLabz`;

        // 1. Company Logo & Name
        const logoWrap = document.getElementById('detailCompanyLogoWrap');
        if (item.company_logo) {
            logoWrap.innerHTML = `<img src="${escapeHtml(item.company_logo)}" alt="${escapeHtml(item.company_name)} logo" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\\'fa-solid fa-building\\'></i>';">`;
        } else {
            logoWrap.innerHTML = `<i class="fa-solid fa-building"></i>`;
        }

        document.getElementById('detailCompanyName').textContent = item.company_name;

        // Company Website
        const compWeb = document.getElementById('detailCompanyWebsite');
        if (item.company_website) {
            compWeb.href = item.company_website;
            compWeb.style.display = 'inline-flex';
        } else {
            compWeb.style.display = 'none';
        }

        // Title
        document.getElementById('detailTitle').textContent = item.internship_title;

        // Pills Row
        const pillsRow = document.getElementById('detailPillsRow');
        pillsRow.innerHTML = '';

        const dlInfo = getDeadlineInfo(item.raw_deadline || item.application_deadline, item.days_left);
        pillsRow.innerHTML += `<span class="id-pill id-pill-deadline ${dlInfo.badgeClass}"><i class="${dlInfo.icon}"></i> ${escapeHtml(dlInfo.label)}</span>`;

        if (item.category) {
            pillsRow.innerHTML += `<span class="id-pill"><i class="fa-solid fa-layer-group"></i> ${escapeHtml(item.category)}</span>`;
        }
        if (item.work_mode) {
            pillsRow.innerHTML += `<span class="id-pill"><i class="fa-solid fa-laptop-code"></i> ${escapeHtml(item.work_mode)}</span>`;
        }
        if (item.location) {
            pillsRow.innerHTML += `<span class="id-pill"><i class="fa-solid fa-location-dot"></i> ${escapeHtml(item.location)}</span>`;
        }
        if (item.stipend_type) {
            const stVal = (item.stipend_type === 'Paid' && item.stipend_amount) ? `${item.stipend_type} (${item.stipend_amount})` : item.stipend_type;
            pillsRow.innerHTML += `<span class="id-pill"><i class="fa-solid fa-wallet"></i> ${escapeHtml(stVal)}</span>`;
        }
        if (item.openings && parseInt(item.openings, 10) > 0) {
            pillsRow.innerHTML += `<span class="id-pill"><i class="fa-solid fa-users"></i> ${escapeHtml(item.openings)} Openings</span>`;
        }

        // 2. Main Column Content Sections
        // Description
        const descContent = item.description || item.short_description || '';
        document.getElementById('detailDescription').textContent = descContent;

        // Responsibilities
        const cardResp = document.getElementById('cardResponsibilities');
        if (item.responsibilities && item.responsibilities.trim().length > 0) {
            document.getElementById('detailResponsibilities').textContent = item.responsibilities;
            cardResp.style.display = 'block';
        } else {
            cardResp.style.display = 'none';
        }

        // Requirements
        const cardReq = document.getElementById('cardRequirements');
        if (item.requirements && item.requirements.trim().length > 0) {
            document.getElementById('detailRequirements').textContent = item.requirements;
            cardReq.style.display = 'block';
        } else {
            cardReq.style.display = 'none';
        }

        // Eligibility
        const cardElig = document.getElementById('cardEligibility');
        if (item.eligibility && item.eligibility.trim().length > 0) {
            document.getElementById('detailEligibility').textContent = item.eligibility;
            cardElig.style.display = 'block';
        } else {
            cardElig.style.display = 'none';
        }

        // Poster
        const cardPoster = document.getElementById('cardPoster');
        const posterImg = document.getElementById('posterImg');
        const modalPosterImg = document.getElementById('modalPosterImg');
        if (item.poster_path) {
            posterImg.src = item.poster_path;
            modalPosterImg.src = item.poster_path;
            cardPoster.style.display = 'block';

            // Lightbox Modal Setup
            const posterWrap = document.getElementById('posterPreviewWrap');
            const posterModal = document.getElementById('posterModal');
            const modalCloseBtn = document.getElementById('modalCloseBtn');

            const closeModal = () => {
                posterModal.classList.remove('active');
                setTimeout(() => { posterModal.style.display = 'none'; }, 200);
            };

            posterWrap.addEventListener('click', () => {
                posterModal.style.display = 'flex';
                requestAnimationFrame(() => {
                    posterModal.classList.add('active');
                });
            });

            modalCloseBtn.addEventListener('click', closeModal);

            posterModal.addEventListener('click', (e) => {
                if (e.target === posterModal || e.target.closest('#modalCloseBtn')) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && posterModal.classList.contains('active')) {
                    closeModal();
                }
            });
        } else {
            cardPoster.style.display = 'none';
        }

        // 3. Sidebar - How to Apply Card
        // Email
        const itemEmail = document.getElementById('itemEmail');
        const btnEmailLink = document.getElementById('btnEmailLink');
        const textEmailVal = document.getElementById('textEmailVal');
        const btnCopyEmail = document.getElementById('btnCopyEmail');

        if (item.application_email) {
            const email = item.application_email.trim();
            btnEmailLink.href = `mailto:${encodeURIComponent(email)}?subject=Application for ${encodeURIComponent(item.internship_title)} via RLabZ`;
            textEmailVal.textContent = email;
            itemEmail.style.display = 'block';

            btnCopyEmail.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(email);
                    btnCopyEmail.innerHTML = '<i class="fa-solid fa-check" style="color: #10b981;"></i>';
                    setTimeout(() => {
                        btnCopyEmail.innerHTML = '<i class="fa-regular fa-copy"></i>';
                    }, 2000);
                } catch (err) {
                    // Fallback
                    const ta = document.createElement('textarea');
                    ta.value = email;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    btnCopyEmail.innerHTML = '<i class="fa-solid fa-check" style="color: #10b981;"></i>';
                    setTimeout(() => {
                        btnCopyEmail.innerHTML = '<i class="fa-regular fa-copy"></i>';
                    }, 2000);
                }
            });
        } else {
            itemEmail.style.display = 'none';
        }

        // External Application URL
        const itemUrl = document.getElementById('itemUrl');
        const btnExternalUrl = document.getElementById('btnExternalUrl');
        if (item.application_url) {
            btnExternalUrl.href = item.application_url;
            itemUrl.style.display = 'block';
        } else {
            itemUrl.style.display = 'none';
        }

        // Contact Person
        const itemContactPerson = document.getElementById('itemContactPerson');
        const textContactPerson = document.getElementById('textContactPerson');
        if (item.contact_person) {
            textContactPerson.textContent = item.contact_person;
            itemContactPerson.style.display = 'block';
        } else {
            itemContactPerson.style.display = 'none';
        }

        // Contact Phone
        const itemContactPhone = document.getElementById('itemContactPhone');
        const linkContactPhone = document.getElementById('linkContactPhone');
        if (item.contact_phone) {
            linkContactPhone.href = `tel:${item.contact_phone.replace(/[^0-9+]/g, '')}`;
            linkContactPhone.textContent = item.contact_phone;
            itemContactPhone.style.display = 'block';
        } else {
            itemContactPhone.style.display = 'none';
        }

        // Application Instructions
        const itemInstructions = document.getElementById('itemInstructions');
        const textInstructions = document.getElementById('textInstructions');
        if (item.application_instructions) {
            textInstructions.textContent = item.application_instructions;
            itemInstructions.style.display = 'block';
        } else {
            itemInstructions.style.display = 'none';
        }

        // 4. Specs Table (Only display non-empty fields)
        const specsTable = document.getElementById('specsTable');
        specsTable.innerHTML = '';

        const addSpecRow = (icon, label, value, isHtml = false) => {
            if (!value) return;
            const row = document.createElement('div');
            row.className = 'id-spec-row';
            row.innerHTML = `
                <span class="id-spec-label"><i class="${icon}"></i> ${escapeHtml(label)}</span>
                <span class="id-spec-value">${isHtml ? value : escapeHtml(value)}</span>
            `;
            specsTable.appendChild(row);
        };

        addSpecRow('fa-solid fa-layer-group', 'Category', item.category);
        addSpecRow('fa-solid fa-laptop-code', 'Work Mode', item.work_mode);
        addSpecRow('fa-solid fa-location-dot', 'Location', item.location);
        addSpecRow('fa-regular fa-clock', 'Duration', item.duration);
        addSpecRow('fa-solid fa-wallet', 'Stipend', (item.stipend_type === 'Paid' && item.stipend_amount) ? `${item.stipend_type} (${item.stipend_amount})` : item.stipend_type);
        if (item.openings && parseInt(item.openings, 10) > 0) {
            addSpecRow('fa-solid fa-users', 'Openings', item.openings);
        }
        if (item.start_date) {
            addSpecRow('fa-regular fa-calendar-check', 'Start Date', formatDate(item.start_date));
        }

        const dlValueHtml = `${escapeHtml(formatDate(item.application_deadline))} <span class="deadline-badge ${dlInfo.badgeClass}" style="margin-left: 0.5rem; vertical-align: middle;"><i class="${dlInfo.icon}"></i> ${escapeHtml(dlInfo.label)}</span>`;
        addSpecRow('fa-regular fa-calendar-xmark', 'Application Deadline', dlValueHtml, true);

        if (item.published_at || item.created_at) {
            addSpecRow('fa-regular fa-calendar', 'Posted On', formatDate(item.published_at || item.created_at));
        }

        // Show container
        container.style.display = 'block';
    }

    loadDetail();
});
