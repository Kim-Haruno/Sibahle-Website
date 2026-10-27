<?php
require_once 'includes/config.php';
$pageTitle = 'About Us | ' . SITE_NAME;
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="py-5 text-center" style="background-color: var(--deep-chocolate); color: white;">
    <div class="container py-4">
        <h1 class="display-4 text-white">Our Story</h1>
        <p class="lead text-gold mb-0">Sibahle Ngobuntu Bethu — Beautiful Through Our Humanity</p>
    </div>
</section>

<!-- Brand Story Section -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <!-- Save an image of the models to assets/images/about.jpg -->
                <img src="assets/images/about.jpg" alt="Sibahle Brand" class="img-fluid rounded-4 shadow-lg" style="object-fit: cover;">
            </div>
            <div class="col-lg-6 ps-lg-5">
                <h2 class="mb-4">Proudly South African</h2>
                <p class="lead text-muted">Born in Stonehenge, Nelspruit, Sibahle is more than a brand. It's a celebration of African identity, self-care, and natural living.</p>
                <p>We operate at the intersection of fashion, wellness, and cultural heritage. Our exclusive product range includes African-inspired apparel, handcrafted herbal teas, bath salts, foot soaks, and magnesium oil.</p>
                <p>Every product tells a story of heritage, craftsmanship, and love. We believe in promoting self-care, confidence, and natural living, all rooted in African identity.</p>
                
                <div class="mt-4 p-4 rounded-3" style="background-color: var(--cream-bg); border-left: 4px solid var(--primary-gold);">
                    <h5 class="mb-2">Our Philosophy</h5>
                    <p class="mb-0 fst-italic text-muted">"Beautifully African. Made for you. Worn with Pride."</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Values -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">What We Stand For</h2>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <i class="fas fa-heart fa-2x text-gold mb-3"></i>
                    <h5>Made With Love</h5>
                    <p class="text-muted small mb-0">Handcrafted products that promote self-care and confidence.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <i class="fas fa-globe-africa fa-2x text-gold mb-3"></i>
                    <h5>Inspired By Culture</h5>
                    <p class="text-muted small mb-0">Rooted in African heritage, celebrating our unique identity.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <i class="fas fa-leaf fa-2x text-gold mb-3"></i>
                    <h5>Natural Living</h5>
                    <p class="text-muted small mb-0">Committed to quality, natural ingredients, and wellness.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>