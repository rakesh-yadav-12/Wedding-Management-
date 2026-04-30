<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>home</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css"/>

    <!-- LightGallery CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery-js/1.4.0/css/lightgallery.min.css">
</head>

<body>
<div class="container">

<?php @include 'header.php';?>

<section class="portfolio">

<h1 class="heading">our portfolio</h1>


    <div class="portfolio-container">

        <a href="images/port-img-1.png" class="box">
            <div class="image">
                <img src="images/port-img-1.png" alt="">
            </div>
            <h3>wedding ceremony</h3>
        </a>

        <a href="images/port-img-2.png" class="box">
            <div class="image">
                <img src="images/port-img-2.png" alt="">
            </div>
            <h3>wedding ceremony</h3>
        </a>

        <a href="images/port-img-3.png" class="box">
            <div class="image">
                <img src="images/port-img-3.png" alt="">
            </div>
            <h3>wedding ceremony</h3>
        </a>

        <a href="images/port-img-4.png" class="box">
            <div class="image">
                <img src="images/port-img-4.png" alt="">
            </div>
            <h3>wedding ceremony</h3>
        </a>

        <a href="images/port-img-5.png" class="box">
            <div class="image">
                <img src="images/port-img-5.png" alt="">
            </div>
            <h3>wedding ceremony</h3>
        </a>

        <a href="images/port-img-6.png" class="box">
            <div class="image">
                <img src="images/port-img-6.png" alt="">
            </div>
            <h3>wedding ceremony</h3>
        </a>

    </div>
</section>

<?php @include 'footer.php';?>

</div>

<!-- REQUIRED: jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- LightGallery JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery-js/1.4.0/js/lightgallery.min.js"></script>

<!-- Swiper -->
<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>

<script src="script.js"></script>

<!-- LightGallery Init -->
<script>
    
        lightGallery(document.querySelector('.portfolio .portfolio-container'));
    </script>

</body>
</html>
