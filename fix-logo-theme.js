const fs = require('fs');

// 1. Update script.js to add .nav-over-dark logic
let js = fs.readFileSync('assets/js/script.js', 'utf8');

const scrollLogicStart = js.indexOf(`if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }`);

if (scrollLogicStart !== -1) {
    const replacement = `if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }

            // Dark sections detection for logo color swap
            const darkSections = document.querySelectorAll('.hero-blue-section, #client_form_section, .footer-dark');
            let isOverDark = false;
            const navCenterY = nav.getBoundingClientRect().height / 2 || 40;
            darkSections.forEach(sec => {
                const rect = sec.getBoundingClientRect();
                if (rect.top <= navCenterY && rect.bottom >= navCenterY) {
                    isOverDark = true;
                }
            });
            if (isOverDark || window.scrollY < 50) { 
                nav.classList.add('nav-over-dark');
            } else {
                nav.classList.remove('nav-over-dark');
            }`;
    js = js.substring(0, scrollLogicStart) + replacement + js.substring(scrollLogicStart + 168); // length of original block
    fs.writeFileSync('assets/js/script.js', js);
    console.log('script.js updated successfully.');
} else {
    console.log('Could not find scroll logic in script.js');
}

// 2. Append CSS for nav-over-dark
const cssAppend = `\n/* Force light logo when scrolled over dark sections */\n.premium-nav.scrolled.nav-over-dark .logo-dark { display: none !important; }\n.premium-nav.scrolled.nav-over-dark .logo-light { display: block !important; }\n`;
fs.appendFileSync('assets/css/styles.css', cssAppend);
console.log('styles.css appended successfully.');
