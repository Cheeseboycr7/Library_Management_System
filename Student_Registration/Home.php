<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore our college library resources, services, and facilities for academic success">
    <title>ECOT College Library - Your Gateway to Knowledge</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">


    
    <!-- Preload important resources -->
    <link rel="preload" href="../Student_Registration/css/style.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style">
    <link rel="preload" href="https://unpkg.com/swiper/swiper-bundle.min.css" as="style">
    
    <!-- Favicon -->
    <link rel="icon" href="../ECOT.jpg" type="image/jpeg">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../Student_Registration/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="https://yourcollege.edu/library">


    <style>

        /* ===== Base Styles ===== */
:root {
  --primary-color: #00264d;
  --secondary-color: #2c3e50;
  --accent-color: #e74c3c;
  --light-color: #ecf0f1;
  --dark-color: #2c3e50;
  --text-color: #333;
  --text-light: #7f8c8d;
  --white: #fff;
  --success-color: #2ecc71;
  --warning-color: #f39c12;
  --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  --transition: all 0.3s ease;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  font-family: 'Poppins', sans-serif;
  line-height: 1.6;
  color: var(--text-color);
  background-color: var(--light-color);
  overflow-x: hidden;
}

/* ===== Typography ===== */
h1, h2, h3, h4, h5, h6 {
  font-weight: 600;
  line-height: 1.2;
  margin-bottom: 1rem;
  color: var(--dark-color);
}

h1 { font-size: 2.5rem; }
h2 { font-size: 2rem; }
h3 { font-size: 1.75rem; }
h4 { font-size: 1.5rem; }

p {
  margin-bottom: 1rem;
}

a {
  text-decoration: none;
  color: var(--primary-color);
  transition: var(--transition);
}

a:hover {
  color: var(--secondary-color);
}

/* ===== Utility Classes ===== */
.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

.btn {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background-color: var(--primary-color);
  color: var(--white);
  border: none;
  border-radius: 4px;
  font-family: 'Poppins', sans-serif;
  font-weight: 500;
  cursor: pointer;
  transition: var(--transition);
  text-align: center;
}

.btn:hover {
  background-color: var(--secondary-color);
  transform: translateY(-2px);
}

.btn-secondary {
  background-color: var(--secondary-color);
}

.btn-secondary:hover {
  background-color: var(--dark-color);
}

.section-title {
  position: relative;
  padding-bottom: 1rem;
  margin-bottom: 2rem;
  text-align: center;
}

.section-title::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 3px;
  background-color: var(--primary-color);
}

.section-subtitle {
  text-align: center;
  color: var(--text-light);
  margin-bottom: 3rem;
  font-weight: 300;
}

/* ===== Header Styles ===== */
.header {
  background-color: var(--white);
  box-shadow: var(--shadow);
  position: fixed;
  width: 100%;
  top: 0;
  z-index: 1000;
}

.header-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
}

.logo-container {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-container img {
  border-radius: 4px;
}

.logo-text h1 {
  margin-bottom: 0.25rem;
  font-size: 1.5rem;
}

.tagline {
  font-size: 0.875rem;
  color: var(--text-light);
  font-weight: 300;
}

.main-nav ul {
  display: flex;
  list-style: none;
  gap: 2rem;
}

.main-nav a {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  color: var(--dark-color);
}

.main-nav a:hover {
  color: var(--primary-color);
}

.nav-login-btn {
  background-color: var(--primary-color);
  color: var(--white) !important;
  padding: 0.5rem 1rem;
  border-radius: 4px;
}

.nav-login-btn:hover {
  background-color: var(--secondary-color);
  color: var(--white) !important;
}

.mobile-menu-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--dark-color);
  cursor: pointer;
}

/* ===== Hero Slider ===== */
.hero-slider {
  margin-top: 80px;
  position: relative;
}

.swiper {
  width: 100%;
  height: 500px;
}

.swiper-slide {
  position: relative;
}

.swiper-slide img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.slide-caption {
  position: absolute;
  bottom: 20%;
  left: 10%;
  color: var(--white);
  max-width: 600px;
  background-color: rgba(0, 0, 0, 0.6);
  padding: 2rem;
  border-radius: 4px;
}

.slide-caption h2 {
  font-size: 2.5rem;
  color: var(--white);
  margin-bottom: 1rem;
}

.swiper-button-prev,
.swiper-button-next {
  color: var(--white) !important;
  background-color: rgba(0, 0, 0, 0.5);
  width: 50px !important;
  height: 50px !important;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.swiper-button-prev::after,
.swiper-button-next::after {
  font-size: 1.5rem !important;
}

.swiper-pagination-bullet {
  background-color: var(--white) !important;
  opacity: 0.7 !important;
}

.swiper-pagination-bullet-active {
  background-color: var(--primary-color) !important;
  opacity: 1 !important;
}

/* ===== Quick Links ===== */
.quick-links {
  padding: 4rem 0;
  background-color: var(--white);
}

.links-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
}

.quick-link-card {
  background-color: var(--light-color);
  padding: 2rem;
  border-radius: 8px;
  text-align: center;
  transition: var(--transition);
  box-shadow: var(--shadow);
}

.quick-link-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.quick-link-card i {
  font-size: 2.5rem;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

.quick-link-card h3 {
  margin-bottom: 0.5rem;
}

/* ===== About Section ===== */
.about-section {
  padding: 4rem 0;
  background-color: var(--light-color);
}

.about-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  align-items: center;
}

.about-image img {
  width: 100%;
  border-radius: 8px;
  box-shadow: var(--shadow);
}

.feature-list {
  list-style: none;
  margin: 2rem 0;
}

.feature-list li {
  margin-bottom: 1rem;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.feature-list i {
  color: var(--success-color);
  margin-top: 0.25rem;
}

/* ===== Services Section ===== */
.services-section {
  padding: 4rem 0;
  background-color: var(--white);
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.service-card {
  background-color: var(--light-color);
  padding: 2rem;
  border-radius: 8px;
  text-align: center;
  transition: var(--transition);
}

.service-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow);
}

.service-icon {
  width: 80px;
  height: 80px;
  background-color: var(--primary-color);
  color: var(--white);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  font-size: 1.75rem;
}

/* ===== Resources Section ===== */
.resources-section {
  padding: 4rem 0;
  background-color: var(--light-color);
}

.resources-tabs {
  margin-top: 2rem;
}

.tab-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 2rem;
  justify-content: center;
}

.tab-button {
  padding: 0.75rem 1.5rem;
  background-color: var(--white);
  border: 1px solid var(--primary-color);
  color: var(--primary-color);
  border-radius: 4px;
  cursor: pointer;
  transition: var(--transition);
  font-family: 'Poppins', sans-serif;
}

.tab-button.active,
.tab-button:hover {
  background-color: var(--primary-color);
  color: var(--white);
}

.tab-pane {
  background-color: var(--white);
  padding: 2rem;
  border-radius: 8px;
  box-shadow: var(--shadow);
}

/* ===== Hours Section ===== */
.hours-section {
  padding: 4rem 0;
  background-color: var(--white);
}

.hours-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
}

.hours-current {
  background-color: var(--light-color);
  padding: 2rem;
  border-radius: 8px;
  text-align: center;
}

.current-status {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1rem 0;
  padding: 0.5rem;
  border-radius: 4px;
}

.current-status.open {
  background-color: rgba(46, 204, 113, 0.2);
  color: var(--success-color);
}

.current-status.closed {
  background-color: rgba(231, 76, 60, 0.2);
  color: var(--accent-color);
}

.hours-list {
  list-style: none;
}

.hours-list li {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #eee;
}

.hours-list li:last-child {
  border-bottom: none;
}

.day {
  font-weight: 500;
}

.time {
  color: var(--primary-color);
  font-weight: 500;
}

.hours-note {
  margin-top: 2rem;
  padding: 1rem;
  background-color: rgba(52, 152, 219, 0.1);
  border-left: 4px solid var(--primary-color);
  border-radius: 0 4px 4px 0;
}

/* ===== Contact Section ===== */
.contact-section {
  padding: 4rem 0;
  background-color: var(--light-color);
}

.contact-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
}

.contact-info {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.contact-method {
  background-color: var(--white);
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: var(--shadow);
}

.contact-method i {
  font-size: 1.5rem;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

.contact-method h3 {
  margin-bottom: 0.5rem;
}

.contact-note {
  font-size: 0.875rem;
  color: var(--text-light);
  margin-top: 0.5rem;
}

.contact-form-container {
  background-color: var(--white);
  padding: 2rem;
  border-radius: 8px;
  box-shadow: var(--shadow);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: 'Poppins', sans-serif;
}

.form-group textarea {
  min-height: 150px;
}

.btn-submit {
  width: 100%;
  padding: 1rem;
  font-size: 1rem;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.checkbox-group input {
  width: auto;
}

/* ===== Newsletter Section ===== */
.newsletter-section {
  padding: 4rem 0;
  background-color: var(--primary-color);
  color: var(--white);
  text-align: center;
}

.newsletter-form {
  max-width: 600px;
  margin: 0 auto;
}

.newsletter-form .form-group {
  position: relative;
}

.newsletter-form input {
  padding: 1rem;
  border: none;
  border-radius: 4px;
}

.btn-newsletter {
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  border-radius: 0 4px 4px 0;
  background-color: var(--secondary-color);
}

.newsletter-form .checkbox-group {
  justify-content: center;
  margin-top: 1rem;
}

/* ===== Footer ===== */
.footer {
  background-color: var(--secondary-color);
  color: var(--white);
  padding: 4rem 0 0;
}

.footer-container {
  display: flex;
  flex-direction: column;
}

.footer-main {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 3rem;
  margin-bottom: 3rem;
}

.footer-about h3 {
  color: var(--white);
  margin-bottom: 1.5rem;
}

.social-links {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.social-links a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  color: var(--white);
  transition: var(--transition);
}

.social-links a:hover {
  background-color: var(--primary-color);
  transform: translateY(-3px);
}

.footer-links h3 {
  color: var(--white);
  margin-bottom: 1.5rem;
  font-size: 1.25rem;
}

.footer-links ul {
  list-style: none;
}

.footer-links li {
  margin-bottom: 0.75rem;
}

.footer-links a {
  color: var(--light-color);
}

.footer-links a:hover {
  color: var(--white);
  padding-left: 5px;
}

.footer-contact address {
  font-style: normal;
}

.footer-contact i {
  margin-right: 0.5rem;
  color: var(--primary-color);
}

.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding: 1.5rem 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.copyright {
  font-size: 0.875rem;
  color: var(--light-color);
}

.footer-legal ul {
  display: flex;
  list-style: none;
  gap: 1.5rem;
}

.footer-legal a {
  color: var(--light-color);
  font-size: 0.875rem;
}

.footer-legal a:hover {
  color: var(--white);
}

/* ===== Back to Top Button ===== */
.back-to-top {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 50px;
  height: 50px;
  background-color: var(--primary-color);
  color: var(--white);
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  opacity: 0;
  visibility: hidden;
  transition: var(--transition);
  z-index: 999;
}

.back-to-top.show {
  opacity: 1;
  visibility: visible;
}

.back-to-top:hover {
  background-color: var(--secondary-color);
  transform: translateY(-3px);
}

/* ===== Accessibility ===== */
.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.skip-link {
  position: absolute;
  top: -40px;
  left: 0;
  background-color: var(--secondary-color);
  color: var(--white);
  padding: 0.75rem 1.5rem;
  z-index: 9999;
  transition: top 0.3s;
}

.skip-link:focus {
  top: 0;
}

/* ===== Mobile Responsiveness ===== */
@media (max-width: 992px) {
  .about-content,
  .contact-content {
    grid-template-columns: 1fr;
  }
  
  .contact-info {
    grid-template-columns: 1fr;
  }
  
  .hours-container {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .mobile-menu-toggle {
    display: block;
  }
  
  .main-nav {
    position: fixed;
    top: 80px;
    left: 0;
    width: 100%;
    background-color: var(--white);
    box-shadow: var(--shadow);
    padding: 2rem;
    transform: translateY(-150%);
    transition: transform 0.3s ease;
  }
  
  .main-nav.active {
    transform: translateY(0);
  }
  
  .main-nav ul {
    flex-direction: column;
    gap: 1rem;
  }
  
  .swiper {
    height: 400px;
  }
  
  .slide-caption {
    left: 5%;
    bottom: 10%;
    padding: 1.5rem;
  }
  
  .slide-caption h2 {
    font-size: 1.75rem;
  }
}

@media (max-width: 576px) {
  .logo-text h1 {
    font-size: 1.25rem;
  }
  
  .tagline {
    display: none;
  }
  
  .swiper {
    height: 300px;
  }
  
  .section-title {
    font-size: 1.75rem;
  }
  
  .footer-bottom {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .footer-legal ul {
    justify-content: center;
  }
}
    </style>
</head>
<body>
    <!-- Skip to Content Link for Accessibility -->
    <a class="skip-link" href="#main-content">Skip to main content</a>
    
    <!-- Header Section -->
    <header class="header">
        <div class="header-container">
            <div class="logo-container">
                <img src="../Student_Registration/include/ECOT.jpg" alt="ECOT College Logo" width="80" height="80" loading="lazy">
                <div class="logo-text">
                    <h1>ECOT College Library</h1>
                    <p class="tagline">Empowering Minds Through Knowledge</p>
                </div>
            </div>
            
            <button class="mobile-menu-toggle" aria-label="Toggle navigation menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav class="main-nav" aria-label="Main navigation">
                <ul>
                    <li><a href="#about"><i class="fas fa-info-circle" aria-hidden="true"></i> About</a></li>
                    <li><a href="#services"><i class="fas fa-book-open" aria-hidden="true"></i> Services</a></li>
                    <li><a href="#resources"><i class="fas fa-database" aria-hidden="true"></i> Resources</a></li>
                    <li><a href="#contact"><i class="fas fa-envelope" aria-hidden="true"></i> Contact</a></li>
                    <li><a href="../Student_Registration/student_login.php" class="nav-login-btn"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Student Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content">
        <!-- Hero Slider Section -->
        <section class="hero-slider" aria-label="Library images gallery">
            <div class="swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <img src="../css/images/1733142994175.jpg" 
                             alt="Students studying in the library" 
                             width="1600" 
                             height="600"
                             loading="lazy">
                        <div class="slide-caption">
                            <h2>Your Space for Learning</h2>
                            <p>Discover our quiet study areas perfect for focused work</p>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <img src="../css/images/1733142994196.jpg" 
                             alt="Library bookshelves with extensive collection" 
                             width="1600" 
                             height="600"
                             loading="lazy">
                        <div class="slide-caption">
                            <h2>Extensive Collection</h2>
                            <p>Over 50,000 books and resources at your fingertips</p>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <img src="../css/images/1733142994216.jpg"
                             alt="Modern library computer lab" 
                             width="1600" 
                             height="600"
                             loading="lazy">
                        <div class="slide-caption">
                            <h2>Digital Resources</h2>
                            <p>Access our e-books, journals, and databases anytime</p>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation and Pagination -->
                <div class="swiper-pagination" aria-hidden="true"></div>
                <button class="swiper-button-prev" aria-label="Previous slide">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="swiper-button-next" aria-label="Next slide">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </section>

        <!-- Quick Links Section -->
        <section class="quick-links" aria-label="Quick links">
            <div class="container">
                <h2 class="section-title">Quick Access</h2>
                <div class="links-grid">
                    <a href="../Student_Registration/student_login.php" class="quick-link-card">
                        <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                        <h3>Student Login</h3>
                        <p>Access your library account and resources</p>
                    </a>
                    <a href="APPLY.php" class="quick-link-card">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>
                        <h3>New Student Registration</h3>
                        <p>Apply for library membership</p>
                    </a>
                    <a href="#resources" class="quick-link-card">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <h3>Search Catalog</h3>
                        <p>Find books and resources in our collection</p>
                    </a>
                    <a href="#services" class="quick-link-card">
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        <h3>Opening Hours</h3>
                        <p>Check when we're open</p>
                    </a>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="about-section" aria-labelledby="about-heading">
            <div class="container">
                <div class="section-header">
                    <h2 id="about-heading" class="section-title">About Our Library</h2>
                    <p class="section-subtitle">A center of academic excellence</p>
                </div>
                
                <div class="about-content">
                    <div class="about-text">
                        <p>The ECOT College Library serves as the intellectual hub of our campus, providing students and faculty with access to a wealth of knowledge resources. Our mission is to support the academic and research needs of our college community through:</p>
                        
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle" aria-hidden="true"></i> A collection of over 50,000 print and digital resources</li>
                            <li><i class="fas fa-check-circle" aria-hidden="true"></i> State-of-the-art study spaces and research facilities</li>
                            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Expert librarians available for research assistance</li>
                            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Technology lending program (laptops, tablets, etc.)</li>
                            <li><i class="fas fa-check-circle" aria-hidden="true"></i> Special collections supporting our curriculum</li>
                        </ul>
                    </div>
                    
                    <div class="about-image">
                        <img src="Students.jpg" 
                             alt="Students collaborating in library study room" 
                             width="500px" 
                             height="400px"
                             loading="lazy">
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="services-section" aria-labelledby="services-heading">
            <div class="container">
                <div class="section-header">
                    <h2 id="services-heading" class="section-title">Our Services</h2>
                    <p class="section-subtitle">Supporting your academic journey</p>
                </div>
                
                <div class="services-grid">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-book" aria-hidden="true"></i>
                        </div>
                        <h3>Book Borrowing</h3>
                        <p>Check out books for 2 weeks with possible renewals</p>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-laptop" aria-hidden="true"></i>
                        </div>
                        <h3>Tech Lending</h3>
                        <p>Borrow laptops, tablets, and other devices</p>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-search" aria-hidden="true"></i>
                        </div>
                        <h3>Research Help</h3>
                        <p>Get assistance with your research projects</p>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                        </div>
                        <h3>Information Literacy</h3>
                        <p>Workshops on research skills and citations</p>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-print" aria-hidden="true"></i>
                        </div>
                        <h3>Printing & Scanning</h3>
                        <p>Affordable printing and scanning services</p>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-users" aria-hidden="true"></i>
                        </div>
                        <h3>Group Study Rooms</h3>
                        <p>Reservable spaces for collaborative work</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Resources Section -->
        <section id="resources" class="resources-section" aria-labelledby="resources-heading">
            <div class="container">
                <div class="section-header">
                    <h2 id="resources-heading" class="section-title">Library Resources</h2>
                    <p class="section-subtitle">Explore our collections</p>
                </div>
                
                <div class="resources-tabs">
                    <div class="tab-buttons" role="tablist" aria-label="Resource categories">
                        <button class="tab-button active" role="tab" aria-selected="true" aria-controls="books-tab">Books</button>
                        <button class="tab-button" role="tab" aria-selected="false" aria-controls="ejournals-tab">E-Journals</button>
                        <button class="tab-button" role="tab" aria-selected="false" aria-controls="databases-tab">Databases</button>
                        <button class="tab-button" role="tab" aria-selected="false" aria-controls="theses-tab">Theses</button>
                    </div>
                    
                    <div class="tab-content">
                        <div id="books-tab" class="tab-pane active" role="tabpanel" aria-labelledby="books-tab">
                            <h3>Book Collections</h3>
                            <p>Our library houses an extensive collection of textbooks, reference materials, and general reading books across all disciplines offered at ECOT College. Search our online catalog to find materials for your courses and research.</p>
                            <a href="#" class="btn">Search Catalog</a>
                        </div>
                        
                        <div id="ejournals-tab" class="tab-pane" role="tabpanel" aria-labelledby="ejournals-tab" hidden>
                            <h3>Electronic Journals</h3>
                            <p>Access thousands of academic journals through our subscriptions to major databases including JSTOR, ScienceDirect, IEEE Xplore, and more. Available on-campus or remotely with your student login.</p>
                            <a href="#" class="btn">Browse E-Journals</a>
                        </div>
                        
                        <div id="databases-tab" class="tab-pane" role="tabpanel" aria-labelledby="databases-tab" hidden>
                            <h3>Research Databases</h3>
                            <p>Specialized databases for different disciplines provide access to scholarly articles, conference proceedings, industry reports, and other valuable research materials.</p>
                            <a href="#" class="btn">View Databases</a>
                        </div>
                        
                        <div id="theses-tab" class="tab-pane" role="tabpanel" aria-labelledby="theses-tab" hidden>
                            <h3>Student Theses</h3>
                            <p>Explore the research work of previous ECOT students through our digital repository of honors theses and capstone projects.</p>
                            <a href="#" class="btn">Browse Theses</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Opening Hours Section -->
        <section class="hours-section" aria-labelledby="hours-heading">
            <div class="container">
                <h2 id="hours-heading" class="section-title">Opening Hours</h2>
                
                <div class="hours-container">
                    <div class="hours-current">
                        <h3>Today's Hours</h3>
                        <p class="current-status open">Open now: 8:00 AM - 9:00 PM</p>
                        <p>Next day change in <span class="countdown">3 hours 15 minutes</span></p>
                    </div>
                    
                    <div class="hours-full">
                        <h3>Regular Hours</h3>
                        <ul class="hours-list">
                            <li><span class="day">Monday - Thursday</span> <span class="time">8:00 AM - 5:00 PM</span></li>
                            <li><span class="day">Friday</span> <span class="time">8:00 AM - 4:00 PM</span></li>
                            <li><span class="day">Saturday</span> <span class="time">10:00 AM - 4:00 PM</span></li>
                            <li><span class="day">Sunday</span> <span class="time">Closed</span></li>
                        </ul>
                        
                        <div class="hours-note">
                            <p><i class="fas fa-info-circle" aria-hidden="true"></i> <strong>Note:</strong> Hours may vary during holidays and semester breaks. Check our announcements for updates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="contact-section" aria-labelledby="contact-heading">
            <div class="container">
                <div class="section-header">
                    <h2 id="contact-heading" class="section-title">Contact Us</h2>
                    <p class="section-subtitle">We're here to help</p>
                </div>
                
                <div class="contact-content">
                    <div class="contact-info">
                        <div class="contact-method">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            <h3>Location</h3>
                            <address>
                                ECOT College Library<br>
                                123 College Road<br>
                                City Name, State 12345<br>
                                Building 4, 2nd Floor
                            </address>
                        </div>
                        
                        <div class="contact-method">
                            <i class="fas fa-phone-alt" aria-hidden="true"></i>
                            <h3>Phone</h3>
                            <p><a href="tel:+1234567890">(123) 456-7890</a></p>
                            <p class="contact-note">Circulation Desk ext. 123</p>
                            <p class="contact-note">Reference Desk ext. 124</p>
                        </div>
                        
                        <div class="contact-method">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <h3>Email</h3>
                            <p><a href="mailto:library@ecot.edu">library@ecot.edu</a></p>
                            <p class="contact-note">Response within 24 hours</p>
                        </div>
                        
                        <div class="contact-method">
                            <i class="fas fa-clock" aria-hidden="true"></i>
                            <h3>Staff Hours</h3>
                            <p>Monday-Friday: 9:00 AM - 5:00 PM</p>
                            <p>Librarians available for consultations by appointment</p>
                        </div>
                    </div>
                    
                    <div class="contact-form-container">
                        <h3>Send Us a Message</h3>
                        <form class="contact-form" action="#" method="POST">
                            <div class="form-group">
                                <label for="name">Your Name</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <select id="subject" name="subject">
                                    <option value="general">General Inquiry</option>
                                    <option value="research">Research Help</option>
                                    <option value="circulation">Circulation Questions</option>
                                    <option value="suggestions">Suggestions</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Your Message</label>
                                <textarea id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-submit">
                                <i class="fas fa-paper-plane" aria-hidden="true"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="newsletter-section" aria-labelledby="newsletter-heading">
            <div class="container">
                <h2 id="newsletter-heading" class="section-title">Stay Updated</h2>
                <p class="section-subtitle">Subscribe to our library newsletter</p>
                
                <form class="newsletter-form" action="#" method="POST">
                    <div class="form-group">
                        <label for="newsletter-email" class="visually-hidden">Email Address</label>
                        <input type="email" id="newsletter-email" name="email" placeholder="Your email address" required>
                        <button type="submit" class="btn btn-newsletter">Subscribe</button>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="agree-terms" name="agree-terms" required>
                        <label for="agree-terms">I agree to receive library updates and announcements</label>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-main">
                <div class="footer-about">
                    <h3>ECOT College Library</h3>
                    <p>Providing resources and services to support the academic mission of ECOT College since 1995.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#">Library Catalog</a></li>
                        <li><a href="#">Research Guides</a></li>
                        <li><a href="#">Course Reserves</a></li>
                        <li><a href="#">Interlibrary Loan</a></li>
                        <li><a href="#">Faculty Services</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h3>Resources</h3>
                    <ul>
                        <li><a href="#">E-Journals</a></li>
                        <li><a href="#">Databases A-Z</a></li>
                        <li><a href="#">Thesis Archive</a></li>
                        <li><a href="#">Institutional Repository</a></li>
                        <li><a href="#">Open Access Resources</a></li>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <h3>Contact</h3>
                    <address>
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i> 123 College Road, City<br>
                        <i class="fas fa-phone-alt" aria-hidden="true"></i> (123) 456-7890<br>
                        <i class="fas fa-envelope" aria-hidden="true"></i> library@ecot.edu
                    </address>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    <p>&copy; <span id="current-year">2025</span> ECOT College Library. All rights reserved.</p>
                </div>
                
                <div class="footer-legal">
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Use</a></li>
                        <li><a href="#">Accessibility</a></li>
                        <li><a href="#">Sitemap</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button class="back-to-top" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
    <script>
        // Initialize Swiper
        document.addEventListener('DOMContentLoaded', function() {
            const swiper = new Swiper('.swiper', {
                loop: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                a11y: {
                    prevSlideMessage: 'Previous slide',
                    nextSlideMessage: 'Next slide',
                    paginationBulletMessage: 'Go to slide {{index}}',
                }
            });
            
            // Mobile menu toggle
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            const mainNav = document.querySelector('.main-nav');
            
            menuToggle.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !isExpanded);
                mainNav.classList.toggle('active');
                this.classList.toggle('active');
            });
            
            // Tab functionality
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabButtons.forEach((button, index) => {
                button.addEventListener('click', () => {
                    // Remove active class from all buttons and panes
                    tabButtons.forEach(btn => {
                        btn.setAttribute('aria-selected', 'false');
                        btn.classList.remove('active');
                    });
                    tabPanes.forEach(pane => pane.hidden = true);
                    
                    // Add active class to clicked button and corresponding pane
                    button.setAttribute('aria-selected', 'true');
                    button.classList.add('active');
                    const paneId = button.getAttribute('aria-controls');
                    document.getElementById(paneId).hidden = false;
                });
            });
            
            // Back to top button
            const backToTopButton = document.querySelector('.back-to-top');
            
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('show');
                } else {
                    backToTopButton.classList.remove('show');
                }
            });
            
            backToTopButton.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Update current year in footer
            document.getElementById('current-year').textContent = new Date().getFullYear();
            
            // Simulate current library status (would be dynamic in real implementation)
            function updateLibraryStatus() {
                const now = new Date();
                const hours = now.getHours();
                const day = now.getDay(); // 0 = Sunday, 1 = Monday, etc.
                
                let isOpen = false;
                let statusText = '';
                
                // Simplified logic - would match actual hours in real implementation
                if (day >= 1 && day <= 4) { // Mon-Thu
                    isOpen = hours >= 8 && hours < 17;
                    statusText = isOpen ? 'Open now: 8:00 AM - 5:00 PM' : 'Closed: Opens at 8:00 AM';
                } else if (day === 5) { // Fri
                    isOpen = hours >= 8 && hours < 18;
                    statusText = isOpen ? 'Open now: 8:00 AM - 4:00 PM' : 'Closed: Opens at 8:00 AM';
                } else if (day === 6) { // Sat
                    isOpen = hours >= 10 && hours < 16;
                    statusText = isOpen ? 'Open now: 10:00 AM - 4:00 PM' : 'Closed: Opens at 10:00 AM';
                } else { // Sun
                    isOpen = false;
                    statusText = 'Closed: Opens Monday at 8:00 AM';
                }
                
                const statusElement = document.querySelector('.current-status');
                if (statusElement) {
                    statusElement.textContent = statusText;
                    statusElement.classList.toggle('open', isOpen);
                    statusElement.classList.toggle('closed', !isOpen);
                }
            }
            
            updateLibraryStatus();
            setInterval(updateLibraryStatus, 60000); // Update every minute
        });
    </script>
</body>
</html>