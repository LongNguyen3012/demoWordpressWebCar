<?php
/**
 * Template Name: Driving Game
 */
get_header();
?>
<div class="game-page">
    <div class="container" style="text-align:center;">
        <h1><?php _te('game_title', 'Midnight Drive'); ?></h1>
        <div id="game-wrapper" style="display:inline-block; position:relative;">
            <canvas id="gameCanvas" width="600" height="400"></canvas>
            <div id="game-ui" style="position:relative;">
                <!-- Score display - top left -->
                <div id="score-display" style="position:absolute; top:10px; left:10px; color:#fff; font-size:1.2rem; font-weight:bold; text-shadow:1px 1px 2px #000; z-index:10;">
                    <?php _te('game_score', 'Score'); ?>: <span id="score">0</span>
                </div>
                <!-- Speed display - top right -->
                <div id="speed-display" style="position:absolute; top:10px; right:10px; color:#fff; font-size:1.2rem; font-weight:bold; text-shadow:1px 1px 2px #000; z-index:10;">
                    <?php _te('game_speed', 'Speed'); ?>: <span id="speed">0</span> km/h
                </div>
                <!-- Game Over Overlay -->
                <div id="game-over-overlay" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); flex-direction:column; justify-content:center; align-items:center; color:#fff; z-index:20;">
                    <h2><?php _te('game_over', 'Game Over'); ?></h2>
                    <p><?php _te('game_final_score', 'Final Score'); ?>: <span id="final-score">0</span></p>
                    <p><?php _te('game_top_speed', 'Top Speed'); ?>: <span id="top-speed">0</span> km/h</p>
                    <button id="restart-btn" style="padding:10px 30px; background:#2C2C2C; color:#fff; border:none; border-radius:4px; font-size:1.1rem; cursor:pointer; margin-top:10px;"><?php _te('game_restart', 'Restart'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>