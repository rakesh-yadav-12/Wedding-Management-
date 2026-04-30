

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Wedding Management</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <?php @include 'header.php'; ?>

    <!-- HOME SECTION -->
    <section class="home">
        <div class="swiper home-slider">
            <div class="swiper-wrapper">
                <!-- Slide 1 -->
                <div class="swiper-slide slide" style="background:url(images/home-slide-1.png) no-repeat center/cover;">
                    <div class="content">
                        <h3>Plan Your Wedding!</h3>
                        <p>From engagement to the big day, we help you plan every detail seamlessly and stress-free.</p>
                        <a href="about.php" class="btn">Discover More</a>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="swiper-slide slide" style="background:url(images/home-slide-2.png) no-repeat center/cover;">
                    <div class="content">
                        <h3>Perfect Venues</h3>
                        <p>Discover stunning wedding venues that match your style, budget, and guest list perfectly.</p>
                        <a href="about.php" class="btn">Discover More</a>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="swiper-slide slide" style="background:url(images/home-slide-3.png) no-repeat center/cover;">
                    <div class="content">
                        <h3>Beautiful Decorations</h3>
                        <p>Transform your special day with beautiful décor, custom themes, and magical details.</p>
                        <a href="about.php" class="btn">Discover More</a>
                    </div>
                </div>
            </div>
            <!-- Pagination -->
            <div class="swiper-pagination"></div>
        </div>
    </section>

    <!-- SERVICES SECTION -->
    <section class="services">
        <h1 class="heading">our <span>services</span></h1>
        <div class="swiper service-slider">
            <div class="swiper-wrapper">
                <!-- Service 1 -->
                <div class="swiper-slide slide">
                    <img src="images/service-1.png" alt="Photography">
                    <div class="content">
                        <h3>photography</h3>
                        <p>Capture every special moment with professional wedding photography and cinematic storytelling.</p>
                        <a href="photography.php" class="btn">View Gallery</a>
                    </div>
                </div>

                <!-- Service 2 -->
                <div class="swiper-slide slide">
                    <img src="images/service-2.png" alt="Wedding Registry">
                    <div class="content">
                        <h3>wedding_registry</h3>
                        <p>Create and manage your wedding registry easily, helping guests choose the perfect gifts.</p>
                        <a href="wedding_registry.php" class="btn">Create Registry</a>
                    </div>
                </div>

                <!-- Service 3 -->
                <div class="swiper-slide slide">
                    <img src="images/service-3.png" alt="Guest List">
                    <div class="content">
                        <h3>guest list</h3>
                        <p>Organize your guest list, track invitations, and manage RSVPs all in one place.</p>
                        <a href="about.php" class="btn">Manage Guests</a>
                    </div>
                </div>

                <!-- Service 4 -->
                <div class="swiper-slide slide">
                    <img src="images/service-4.png" alt="Wedding Cake">
                    <div class="content">
                        <h3>wedding cake</h3>
                        <p>Custom-designed wedding cakes crafted to match your theme and delight every guest.</p>
                        <a href="about.php" class="btn">View Designs</a>
                    </div>
                </div>

                <!-- Service 5 -->
                <div class="swiper-slide slide">
                    <img src="images/service-5.png" alt="Wedding Ceremony">
                    <div class="content">
                        <h3>wedding ceremony</h3>
                        <p>Plan a beautiful and meaningful ceremony with perfect coordination and elegant details.</p>
                        <a href="about.php" class="btn">Plan Ceremony</a>
                    </div>
                </div>

                <!-- Service 6 -->
                <div class="swiper-slide slide">
                    <img src="images/service-6.png" alt="Fine Dining">
                    <div class="content">
                        <h3>fine dining</h3>
                        <p>Enjoy a luxury dining experience with customized menus prepared by expert chefs.</p>
                        <a href="about.php" class="btn">View Menu</a>
                    </div>
                </div>
            </div>
            <!-- Pagination for Services -->
            <div class="swiper-pagination"></div>
        </div>
    </section>

    <?php @include 'footer.php'; ?>

</div>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- Custom JS -->
<script src="script.js"></script>
</body>
</html>