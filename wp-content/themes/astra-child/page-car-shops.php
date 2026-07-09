<?php
/*
Template Name: Car Shops Near Me
*/

get_header(); ?>

<div class="car-shops-page">
    <div class="container">
        <h1>Car Shops Near You</h1>
        <div id="location-status">
            <p>Detecting your location...</p>
        </div>

        <div id="shops-list">
            <h2>Nearby Shops</h2>
            <div id="shops-items"></div>
        </div>
    </div>
</div>

<?php get_footer(); ?>