<?php
$hero = $sections['hero'];
$about = $sections['about'];
$contact = $sections['contact'];
$heroImage = public_file($hero['image_path'] ?? null);
$aboutImage = public_file($about['image_path'] ?? null);
?>

<nav class="public-nav public-nav--modern">
    <a class="public-brand" href="<?= e(url('/')) ?>" aria-label="<?= e(__('app.name')) ?>">
        <span class="brand-logo-wrap"><img src="<?= e($heroImage) ?>" alt="<?= e(__('app.name')) ?>"></span>
        <span class="brand-copy"><strong><?= e(__('app.name')) ?></strong><small>Contracting &amp; Enterprise Solutions</small></span>
    </a>

    <div class="public-links public-links--desktop">
        <a href="#about">About</a>
        <a href="#services"><?= e(__('home.services')) ?></a>
        <a href="#projects"><?= e(__('home.recent_projects')) ?></a>
        <a href="#updates"><?= e(__('home.recent_updates')) ?></a>
        <a href="#contact"><?= e(__('home.contact')) ?></a>
        <a href="<?= e(url('/language/bn')) ?>" class="language-link"><?= e(__('app.bangla')) ?></a>
        <a href="<?= e(url('/language/en')) ?>" class="language-link">EN</a>
        <a class="btn btn-primary btn-sm nav-login" href="<?= e(url('/login')) ?>"><i class="fa-solid fa-arrow-right-to-bracket"></i> <?= e(__('auth.login')) ?></a>
    </div>

    <details class="mobile-menu">
        <summary aria-label="Open navigation"><i class="fa-solid fa-bars"></i></summary>
        <div class="mobile-menu-panel">
            <a href="#about">About</a>
            <a href="#services"><?= e(__('home.services')) ?></a>
            <a href="#projects"><?= e(__('home.recent_projects')) ?></a>
            <a href="#updates"><?= e(__('home.recent_updates')) ?></a>
            <a href="#contact"><?= e(__('home.contact')) ?></a>
            <div class="mobile-menu-languages">
                <a href="<?= e(url('/language/bn')) ?>"><?= e(__('app.bangla')) ?></a>
                <a href="<?= e(url('/language/en')) ?>">English</a>
            </div>
            <a class="btn btn-primary" href="<?= e(url('/login')) ?>"><i class="fa-solid fa-arrow-right-to-bracket"></i> <?= e(__('auth.login')) ?></a>
        </div>
    </details>
</nav>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const menu = document.querySelector('.mobile-menu');
    if (!menu) return;

    // Close the mobile menu after selecting a navigation item.
    menu.querySelectorAll('.mobile-menu-panel a').forEach(function (link) {
        link.addEventListener('click', function () {
            menu.removeAttribute('open');
        });
    });

    // Close it when tapping outside the navigation.
    document.addEventListener('click', function (event) {
        if (menu.hasAttribute('open') && !menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });
});
</script>


<header class="home-hero home-hero--modern">
    <div class="hero-glow hero-glow--one"></div>
    <div class="hero-glow hero-glow--two"></div>
    <div class="hero-grid-pattern"></div>
    <div class="home-hero-inner">
        <div class="home-hero-copy">
            <span class="eyebrow"><i class="fa-solid fa-building-shield"></i> Trusted contracting &amp; project operations</span>
            <h1><?= e(localized($hero, 'title')) ?></h1>
            <p class="hero-lead"><?= e(localized($hero, 'body')) ?></p>
            <div class="hero-actions">
                <a class="btn btn-light btn-lg" href="#contact"><i class="fa-solid fa-arrow-right"></i> Start a conversation</a>
                <a class="btn btn-outline-light btn-lg" href="#services">Explore our services</a>
            </div>
            <div class="hero-trust-row">
                <span><i class="fa-solid fa-circle-check"></i> Professional execution</span>
                <span><i class="fa-solid fa-circle-check"></i> Workforce coordination</span>
                <span><i class="fa-solid fa-circle-check"></i> Project-focused delivery</span>
            </div>
        </div>

        <div class="hero-visual" aria-hidden="true">
            <div class="hero-orbit hero-orbit--one"></div>
            <div class="hero-orbit hero-orbit--two"></div>
            <div class="hero-card hero-card--main">
                <div class="hero-card-top">
                    <span class="hero-status"><span></span> Operations</span>
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="hero-card-logo"><img src="<?= e($heroImage) ?>" alt=""></div>
                <strong><?= e(__('app.name')) ?></strong>
                <span>Building dependable project outcomes.</span>
                <div class="hero-card-line"><i></i><i></i><i></i><i></i><i></i></div>
                <div class="hero-card-bottom"><span>Projects</span><b><?= count($recentProjects) + count($runningProjects) ?></b><span>Services</span><b><?= count($services) ?></b></div>
            </div>
            <div class="floating-card floating-card--workers"><i class="fa-solid fa-people-group"></i><span><small>Workforce</small><b>Coordinated teams</b></span></div>
            <div class="floating-card floating-card--quality"><i class="fa-solid fa-medal"></i><span><small>Commitment</small><b>Quality delivery</b></span></div>
        </div>
    </div>
</header>

<main>
    <section class="home-section intro-strip">
        <div class="intro-item"><span class="intro-icon"><i class="fa-solid fa-helmet-safety"></i></span><div><strong>Project ready</strong><small>Organized resources for every job</small></div></div>
        <div class="intro-item"><span class="intro-icon"><i class="fa-solid fa-users-gear"></i></span><div><strong>Skilled workforce</strong><small>People, tools and coordination</small></div></div>
        <div class="intro-item"><span class="intro-icon"><i class="fa-solid fa-file-shield"></i></span><div><strong>Managed operations</strong><small>Clear records and accountable work</small></div></div>
    </section>

    <section id="about" class="home-section home-section--modern about-section">
        <div class="section-heading">
            <span class="section-kicker">WHO WE ARE</span>
            <h2><?= e(localized($about, 'title')) ?></h2>
            <span class="heading-line"></span>
        </div>
        <div class="about-grid">
            <div class="about-copy">
                <p class="section-lead"><?= e(localized($about, 'body')) ?></p>
                <p>We bring people, projects, equipment and operational information together so teams can focus on safe, efficient and dependable execution.</p>
                <div class="about-points">
                    <div><i class="fa-solid fa-check"></i><span><strong>Structured operations</strong><small>Better visibility from planning to completion.</small></span></div>
                    <div><i class="fa-solid fa-check"></i><span><strong>Practical expertise</strong><small>Focused on real-world contracting requirements.</small></span></div>
                </div>
                <a class="text-link" href="#contact">Talk to our team <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="about-visual">
                <div class="about-image-frame">
                    <img src="<?= e($aboutImage) ?>" alt="<?= e(__('home.about')) ?>">
                    <div class="about-badge"><strong><?= e(__('app.name')) ?></strong><span>Reliable by design.</span></div>
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="home-section home-section--modern services-section">
        <div class="section-heading centered">
            <span class="section-kicker">WHAT WE DO</span>
            <h2><?= e(__('home.services')) ?></h2>
            <p>Professional services built around reliable project execution.</p>
        </div>
        <div class="home-grid modern-grid services-grid">
            <?php foreach ($services as $index => $row): ?>
                <article class="home-card service-card">
                    <span class="service-number">0<?= $index + 1 ?></span>
                    <div class="service-icon"><i class="fa-solid <?= e($row['icon'] ?: 'fa-briefcase') ?>"></i></div>
                    <h3><?= e(localized($row, 'title')) ?></h3>
                    <p><?= e(localized($row, 'body')) ?></p>
                    <a href="#contact" aria-label="Learn more about <?= e(localized($row, 'title')) ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="projects" class="home-section home-section--modern projects-section">
        <div class="section-heading-row">
            <div class="section-heading">
                <span class="section-kicker">OUR WORK</span>
                <h2><?= e(__('home.recent_projects')) ?></h2>
            </div>
            <span class="section-count"><?= count($recentProjects) ?> recent / <?= count($runningProjects) ?> running</span>
        </div>
        <div class="project-grid">
            <?php foreach ($recentProjects as $project): ?>
                <article class="project-card">
                    <div class="project-card-top"><span class="project-tag">PROJECT</span><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                    <h3><?= e(localized($project, 'name')) ?></h3>
                    <p><i class="fa-solid fa-location-dot"></i> <?= e($project['location'] ?? 'Bangladesh') ?></p>
                    <span class="project-status"><span></span><?= e(option_label($project['status'])) ?></span>
                </article>
            <?php endforeach; ?>
            <?php if (!$recentProjects): ?>
                <article class="project-card project-empty"><i class="fa-solid fa-diagram-project"></i><h3>Building what comes next</h3><p>Project highlights will appear here as they are added from the management system.</p></article>
            <?php endif; ?>
        </div>

        <div class="running-heading"><span><?= e(__('home.running_projects')) ?></span><i></i></div>
        <div class="running-list">
            <?php foreach ($runningProjects as $project): ?>
                <div class="running-item"><span class="running-dot"></span><strong><?= e(localized($project, 'name')) ?></strong><span class="running-location"><i class="fa-solid fa-location-dot"></i> <?= e($project['location'] ?? 'Bangladesh') ?></span><span class="project-status"><span></span><?= e(option_label($project['status'])) ?></span></div>
            <?php endforeach; ?>
            <?php if (!$runningProjects): ?><div class="running-item"><span class="running-dot"></span><strong>No running projects published yet.</strong></div><?php endif; ?>
        </div>
    </section>

    <section id="updates" class="home-section home-section--modern updates-section">
        <div class="section-heading-row">
            <div class="section-heading"><span class="section-kicker">LATEST</span><h2><?= e(__('home.recent_updates')) ?></h2></div>
            <a class="text-link" href="#contact">Stay connected <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="home-grid modern-grid updates-grid">
            <?php foreach ($updates as $row): ?>
                <article class="home-card update-card">
                    <?php if (!empty($row['image_path'])): ?><div class="update-image"><img src="<?= e(public_file($row['image_path'])) ?>" alt=""></div><?php endif; ?>
                    <div class="update-content"><span class="update-label">COMPANY UPDATE</span><h3><?= e(localized($row, 'title')) ?></h3><p><?= e(localized($row, 'body')) ?></p></div>
                </article>
            <?php endforeach; ?>
            <?php if (!$updates): ?><article class="home-card update-card update-empty"><i class="fa-regular fa-newspaper"></i><h3><?= e(__('home.recent_updates')) ?></h3><p><?= e(__('messages.empty')) ?></p></article><?php endif; ?>
        </div>
    </section>

    <section class="home-section home-section--modern gallery-section">
        <div class="section-heading centered"><span class="section-kicker">IN FOCUS</span><h2><?= e(__('home.gallery')) ?></h2></div>
        <div class="gallery-grid modern-gallery">
            <?php foreach ($media as $item): ?>
                <figure class="gallery-item">
                    <?php if (($item['media_type'] ?? 'photo') === 'video'): ?><video controls src="<?= e(public_file($item['media_path'])) ?>"></video><?php else: ?><img src="<?= e(public_file($item['media_path'])) ?>" alt="<?= e(localized($item, 'title')) ?>"><?php endif; ?>
                    <figcaption><?= e(localized($item, 'title')) ?></figcaption>
                </figure>
            <?php endforeach; ?>
            <?php if (!$media): ?><figure class="gallery-item gallery-placeholder"><img src="<?= e(asset('images/nousin-logo.svg')) ?>" alt="<?= e(__('app.name')) ?>"><figcaption><?= e(__('home.photo_gallery')) ?></figcaption></figure><?php endif; ?>
        </div>
    </section>

    <section id="contact" class="home-section contact-band contact-band--modern">
        <div class="contact-shape contact-shape--one"></div><div class="contact-shape contact-shape--two"></div>
        <div class="contact-inner">
            <span class="section-kicker">LET'S WORK TOGETHER</span>
            <h2><?= e(localized($contact, 'title')) ?></h2>
            <p><?= e(localized($contact, 'body')) ?></p>
            
        </div>
    </section>
</main>

<footer class="public-footer">
    <div><strong><?= e(__('app.name')) ?></strong><span>Contracting &amp; Enterprise Solutions</span></div>
    <div>© <?= date('Y') ?> <?= e(__('app.name')) ?>. All rights reserved || Developd by <a href="https://hyperastic.com/">Hyperastic Soluation</a></div>
</footer>
