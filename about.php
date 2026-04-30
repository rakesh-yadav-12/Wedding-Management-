<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css"/>
    <title>about</title>
</head>
<body>
    <div class="container">
<?php @include 'header.php';?>

<section class="about">
<img src="images/about-img.png" alt="">
<h3>about us</h3>
<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Impedit id numquam neque ab qui odit, praesentium, voluptatibus fugiat esse eveniet ipsum doloribus nemo in dolorum doloremque nobis quam quas quasi.</p>
<a href="contact.php" class="btn">contact us</a>
</section>

<section class="team">

<h1 class="heading">our team</h1>

<div class="box-container">
<div class="box">
<img src="images/team-1.png" alt="">
<h3>Rakesh Yadav</h3>
<p>wedding plannner</p>
<div class="share">
    <a href="#" class="fab fa-facebook-f"></a>
    <a href="#" class="fab fa-twitter"></a>
    <a href="#" class="fab fa-linkedin"></a>
    <a href="#" class="fab fa-instagram"></a>
</div>
</div>

<div class="box">
<img src="images/team-2.png" alt="">
<h3>Ankur Upadhyay</h3>
<p>wedding plannner</p>
<div class="share">
    <a href="#" class="fab fa-facebook-f"></a>
    <a href="#" class="fab fa-twitter"></a>
    <a href="#" class="fab fa-linkedin"></a>
    <a href="#" class="fab fa-instagram"></a>
</div>
</div>

<div class="box">
<img src="images/team-3.png" alt="">
<h3>ShreeKrishna Yadav</h3>
<p>wedding plannner</p>
<div class="share">
    <a href="#" class="fab fa-facebook-f"></a>
    <a href="#" class="fab fa-twitter"></a>
    <a href="#" class="fab fa-linkedin"></a>
    <a href="#" class="fab fa-instagram"></a>
</div>
</div>



</div>
</section>

<?php @include 'footer.php';?>

    </div>
    








<!-- swiper js link -->
<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>