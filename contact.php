<?php
require_once 'includes/config.php';
$pageTitle = 'Contact Us | ' . SITE_NAME;
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="py-5 text-center" style="background-color: var(--deep-chocolate); color: white;">
    <div class="container py-4">
        <h1 class="display-4 text-white">Get In Touch</h1>
        <p class="lead text-gold mb-0">We'd love to hear from you</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Contact Info -->
            <div class="col-lg-5">
                <h3 class="mb-4">Contact Information</h3>
                <p class="text-muted mb-4">Have a question about our products, an order, or just want to say hello? Reach out to us!</p>
                
                <div class="d-flex mb-4">
                    <div class="me-3">
                        <i class="fas fa-map-marker-alt fa-2x text-gold"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Our Location</h6>
                        <p class="text-muted mb-0">25 Tawney Eagle Turn<br>Stonehenge, Nelspruit, 1200</p>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="me-3">
                        <i class="fas fa-phone-alt fa-2x text-gold"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Phone / WhatsApp</h6>
                        <p class="text-muted mb-0">074 498 6813</p>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="me-3">
                        <i class="fas fa-envelope fa-2x text-gold"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Email Us</h6>
                        <p class="text-muted mb-0">Sakainankuna@gmail.com</p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="mb-4">Send us a Message</h4>
                        <form action="" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Your Name</label>
                                    <input type="text" class="form-control" placeholder="John Doe" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Your Email</label>
                                    <input type="email" class="form-control" placeholder="john@example.com" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Subject</label>
                                    <input type="text" class="form-control" placeholder="How can we help?" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message</label>
                                    <textarea class="form-control" rows="5" placeholder="Write your message here..." required></textarea>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-gold w-100 py-3">Send Message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>