# RLabz Website Design Documentation

## 1. Overview
The RLabz website is designed as a premium, modern, and highly interactive digital portfolio and project repository. It showcases technological innovations and projects developed by the team.

## 2. Tech Stack
- **Frontend Core**: HTML5, Vanilla JavaScript, CSS3
- **Backend Core**: PHP (Vanilla)
- **Database**: MySQL / MariaDB
- **Libraries**:
  - Font Awesome 6.4.0 (Icons)
  - Swiper.js (Sliders and Carousels)

## 3. UI/UX Philosophy & Aesthetics
The design emphasizes a "Next Gen Innovation" and "Premium" feel.
- **Color Palette**: A professional "Blue Theme" dominant across the site, offset by clean whites and deep slate/dark tones for contrast. Highlights use a "Digital Blue" (`--digital-blue`) or "Digital Flow" accent color.
- **Visual Elements**:
  - **Glassmorphism & Blurs**: Used in the "Premium Floating Navbar" and overlay backgrounds.
  - **Blob Backgrounds**: Organic, ambient shapes in the background (e.g., Hero section) to create depth without distraction.
  - **Bento Grids**: Used in the "Solutions/What We Do" section for a modern, structured presentation of features.
  - **Ambient Canvas**: A dynamic background canvas effect used in features and contact sections.
- **Animations**:
  - **Custom Preloader**: A branded spinner with concentric rings and faint tracks surrounding the RLabz logo.
  - **Micro-interactions**: Hover effects on project cards (transform, shadow elevation, border color change), sliding pills in the navbar, and typing text effects in the hero section.

## 4. Typography
The project uses a curated mix of modern Google Fonts to ensure readability and a sleek aesthetic:
- **Outfit**: Used for bold headings, tags, and stylized UI elements.
- **Plus Jakarta Sans**: Primary UI and reading text.
- **Space Grotesk**: Used for technical or futuristic accents.
- **Inter**: Clean sans-serif fallback for standard body text and data presentation.

## 5. CSS Architecture
The styling is modularized to keep files manageable:
- `assets/css/styles.css`: The massive core stylesheet containing global variables, layout resets, navigation, hero sections, bento grids, forms, and utilities.
- `assets/css/projects-page.css`: Specific styling for the project gallery, filters, and project cards.
- `assets/css/project-details.css`: Specific layout for individual project detail pages (screenshots, team, faculty, overview).

## 6. Responsive Design
The site is built with a Mobile-First approach.
- The Floating Navbar collapses into a hamburger menu on smaller screens.
- Bento grids convert into mobile-friendly swipeable sliders (utilizing Swiper.js) to preserve screen real estate.
- Project cards stack vertically, adjusting typography scale and padding for touch interfaces.
