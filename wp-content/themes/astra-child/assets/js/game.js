(function() {
    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');
    const scoreSpan = document.getElementById('score');
    const speedSpan = document.getElementById('speed');
    const finalScoreSpan = document.getElementById('final-score');
    const topSpeedSpan = document.getElementById('top-speed');
    const gameOverOverlay = document.getElementById('game-over-overlay');
    const restartBtn = document.getElementById('restart-btn');

    const LANE_COUNT = 3;
    const LANE_HEIGHT = canvas.height / LANE_COUNT;
    const CAR_WIDTH = 70;
    const CAR_HEIGHT = 50;
    const PLAYER_SPEED = 5;
    const OBSTACLE_SPEED_INIT = 2;
    const OBSTACLE_SPEED_INCREMENT = 0.03;
    const GRACE_PERIOD = 5;
    const SCROLL_SPEED_MULTIPLIER = 0.8;
    const SPEED_TO_KPH = 30;
    const GRAZE_MARGIN = 20;
    const GRAZE_BONUS = 5;

    let player = {
        x: 60,
        y: canvas.height/2 - CAR_HEIGHT/2,
        width: CAR_WIDTH,
        height: CAR_HEIGHT,
    };

    let obstacles = [];
    let score = 0;
    let gameOver = false;
    let frame = 0;
    let obstacleSpeed = OBSTACLE_SPEED_INIT;
    let startTime = 0;
    let roadOffset = 0;
    let topSpeed = 0;
    let floatingTexts = [];
    let grazeFlash = 0;
    let laneOccupied = {};

    let keys = { left: false, right: false, up: false, down: false };

    function getLaneY(laneIndex) {
        const laneTop = laneIndex * LANE_HEIGHT;
        const margin = 10;
        const minY = laneTop + margin;
        const maxY = laneTop + LANE_HEIGHT - CAR_HEIGHT - margin;
        if (maxY < minY) return laneTop + (LANE_HEIGHT - CAR_HEIGHT) / 2;
        return minY + Math.random() * (maxY - minY);
    }

    function rectCollide(r1, r2) {
        return !(r2.x > r1.x + r1.width ||
                 r2.x + r2.width < r1.x ||
                 r2.y > r1.y + r1.height ||
                 r2.y + r2.height < r1.y);
    }

    function rectsOverlap(r1, r2) {
        return !(r2.x > r1.x + r1.width ||
                 r2.x + r2.width < r1.x ||
                 r2.y > r1.y + r1.height ||
                 r2.y + r2.height < r1.y);
    }

    function getLaneKey(lane, direction) {
        return lane + '_' + direction;
    }

    function spawnObstacle() {
        const direction = Math.random() < 0.5 ? 'left' : 'right';
        let lane = Math.floor(Math.random() * LANE_COUNT);

        const elapsed = (Date.now() - startTime) / 1000;
        if (elapsed < GRACE_PERIOD) {
            if (lane === 1) {
                lane = Math.random() < 0.5 ? 0 : 2;
            }
        }

        const key = getLaneKey(lane, direction);
        if (laneOccupied[key]) return;

        const y = getLaneY(lane);
        const spawnX = direction === 'left' ? canvas.width : -CAR_WIDTH;
        const speedX = direction === 'left' ? -obstacleSpeed : obstacleSpeed;

        const newObs = {
            x: spawnX,
            y: y,
            width: CAR_WIDTH,
            height: CAR_HEIGHT,
            speedX: speedX,
            color: `hsl(${Math.random() * 360}, 70%, 50%)`,
            lane: lane,
            direction: direction,
            grazed: false,
        };

        if (rectCollide(player, newObs)) return;

        obstacles.push(newObs);
        laneOccupied[key] = true;
    }

    function addFloatingText(x, y, text, color) {
        floatingTexts.push({
            x: x,
            y: y,
            text: text,
            color: color || '#ffdd44',
            life: 60,
            maxLife: 60,
        });
    }

    function resetGame() {
        player.x = 60;
        player.y = canvas.height/2 - CAR_HEIGHT/2;
        obstacles = [];
        laneOccupied = {};
        floatingTexts = [];
        score = 0;
        obstacleSpeed = OBSTACLE_SPEED_INIT;
        frame = 0;
        gameOver = false;
        startTime = Date.now();
        roadOffset = 0;
        topSpeed = 0;
        grazeFlash = 0;
        gameOverOverlay.style.display = 'none';
        scoreSpan.textContent = '0';
        speedSpan.textContent = '0';
    }

    function drawCar(x, y, w, h, color, direction) {
        // direction: 'left' or 'right'
        const isLeft = direction === 'left';

        // Body
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.roundRect(x, y, w, h, 6);
        ctx.fill();

        // Windshield (front)
        ctx.fillStyle = '#aaccff';
        if (isLeft) {
            ctx.fillRect(x + 4, y + 6, 16, h - 12);
        } else {
            ctx.fillRect(x + w - 20, y + 6, 16, h - 12);
        }

        // Rear window
        ctx.fillStyle = '#88aadd';
        if (isLeft) {
            ctx.fillRect(x + w - 18, y + 8, 12, h - 16);
        } else {
            ctx.fillRect(x + 6, y + 8, 12, h - 16);
        }

        // Headlights (front)
        ctx.fillStyle = '#ffee66';
        if (isLeft) {
            ctx.fillRect(x + 2, y + 4, 4, 6);
            ctx.fillRect(x + 2, y + h - 10, 4, 6);
        } else {
            ctx.fillRect(x + w - 6, y + 4, 4, 6);
            ctx.fillRect(x + w - 6, y + h - 10, 4, 6);
        }

        // Tail lights (rear)
        ctx.fillStyle = '#ff4444';
        if (isLeft) {
            ctx.fillRect(x + w - 6, y + 4, 4, 6);
            ctx.fillRect(x + w - 6, y + h - 10, 4, 6);
        } else {
            ctx.fillRect(x + 2, y + 4, 4, 6);
            ctx.fillRect(x + 2, y + h - 10, 4, 6);
        }

        // Wheels
        ctx.fillStyle = '#333';
        ctx.fillRect(x + 4, y - 4, 8, 4);
        ctx.fillRect(x + 4, y + h, 8, 4);
        ctx.fillRect(x + w - 12, y - 4, 8, 4);
        ctx.fillRect(x + w - 12, y + h, 8, 4);
        // Wheel hubs
        ctx.fillStyle = '#555';
        ctx.fillRect(x + 6, y - 2, 4, 2);
        ctx.fillRect(x + 6, y + h, 4, 2);
        ctx.fillRect(x + w - 10, y - 2, 4, 2);
        ctx.fillRect(x + w - 10, y + h, 4, 2);
    }

    // Polyfill roundRect if needed (for older browsers)
    if (!CanvasRenderingContext2D.prototype.roundRect) {
        CanvasRenderingContext2D.prototype.roundRect = function(x, y, w, h, r) {
            if (r > w/2) r = w/2;
            if (r > h/2) r = h/2;
            this.moveTo(x + r, y);
            this.lineTo(x + w - r, y);
            this.quadraticCurveTo(x + w, y, x + w, y + r);
            this.lineTo(x + w, y + h - r);
            this.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            this.lineTo(x + r, y + h);
            this.quadraticCurveTo(x, y + h, x, y + h - r);
            this.lineTo(x, y + r);
            this.quadraticCurveTo(x, y, x + r, y);
            this.closePath();
            return this;
        };
    }

    function update() {
        if (gameOver) return;

        if (keys.left) player.x -= PLAYER_SPEED;
        if (keys.right) player.x += PLAYER_SPEED;
        if (keys.up) player.y -= PLAYER_SPEED;
        if (keys.down) player.y += PLAYER_SPEED;
        player.x = Math.max(0, Math.min(canvas.width - player.width, player.x));
        player.y = Math.max(0, Math.min(canvas.height - player.height, player.y));

        frame++;
        if (frame % 30 === 0) {
            spawnObstacle();
        }

        const grazeZone = {
            x: player.x - GRAZE_MARGIN,
            y: player.y - GRAZE_MARGIN,
            width: player.width + GRAZE_MARGIN * 2,
            height: player.height + GRAZE_MARGIN * 2,
        };

        for (let i = obstacles.length - 1; i >= 0; i--) {
            const obs = obstacles[i];
            obs.x += obs.speedX;

            if (rectCollide(player, obs)) {
                gameOver = true;
                finalScoreSpan.textContent = Math.floor(score);
                topSpeedSpan.textContent = Math.floor(topSpeed);
                gameOverOverlay.style.display = 'flex';
                return;
            }

            if (!obs.grazed && rectsOverlap(grazeZone, obs)) {
                obs.grazed = true;
                score += GRAZE_BONUS;
                scoreSpan.textContent = Math.floor(score);
                const textX = (player.x + player.width/2) - 10;
                const textY = player.y - 20;
                addFloatingText(textX, textY, '+' + GRAZE_BONUS, '#ffdd44');
                grazeFlash = 15;
            }

            if (obs.x > canvas.width + 50 || obs.x < -50) {
                const key = getLaneKey(obs.lane, obs.direction);
                laneOccupied[key] = false;
                obstacles.splice(i, 1);
                score += 10;
                scoreSpan.textContent = Math.floor(score);
            }
        }

        for (let i = floatingTexts.length - 1; i >= 0; i--) {
            const ft = floatingTexts[i];
            ft.life--;
            ft.y -= 0.5;
            if (ft.life <= 0) {
                floatingTexts.splice(i, 1);
            }
        }

        if (grazeFlash > 0) grazeFlash--;

        obstacleSpeed += OBSTACLE_SPEED_INCREMENT;
        const currentSpeedKph = obstacleSpeed * SPEED_TO_KPH;
        if (currentSpeedKph > topSpeed) {
            topSpeed = currentSpeedKph;
        }
        speedSpan.textContent = Math.floor(currentSpeedKph);

        score += 0.1;
        scoreSpan.textContent = Math.floor(score);

        roadOffset -= obstacleSpeed * SCROLL_SPEED_MULTIPLIER;
        if (roadOffset < -40) {
            roadOffset += 40;
        }
    }

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        ctx.fillStyle = '#1a1a2e';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.fillStyle = '#2a2a3e';
        for (let y = 0; y < canvas.height; y += 20) {
            const offsetY = (y + roadOffset * 0.2) % 40;
            if (offsetY < 20) {
                ctx.fillRect(0, y, canvas.width, 1);
            }
        }

        ctx.fillStyle = '#222';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const dashLength = 30;
        const gapLength = 20;
        const totalPattern = dashLength + gapLength;
        const offsetX = roadOffset % totalPattern;

        for (let i = 1; i < LANE_COUNT; i++) {
            const y = i * LANE_HEIGHT;
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.beginPath();
            for (let x = -totalPattern + offsetX; x < canvas.width + totalPattern; x += totalPattern) {
                ctx.moveTo(x, y);
                ctx.lineTo(x + dashLength, y);
            }
            ctx.stroke();
        }

        const grazeZone = {
            x: player.x - GRAZE_MARGIN,
            y: player.y - GRAZE_MARGIN,
            width: player.width + GRAZE_MARGIN * 2,
            height: player.height + GRAZE_MARGIN * 2,
        };

        if (grazeFlash > 0) {
            ctx.strokeStyle = '#ffff00';
            ctx.lineWidth = 3;
            ctx.setLineDash([6, 4]);
        } else {
            ctx.strokeStyle = 'rgba(255, 255, 200, 0.3)';
            ctx.lineWidth = 1;
            ctx.setLineDash([4, 6]);
        }
        ctx.strokeRect(grazeZone.x, grazeZone.y, grazeZone.width, grazeZone.height);
        ctx.setLineDash([]);

        drawCar(player.x, player.y, player.width, player.height, '#ff4444', 'right');

        obstacles.forEach(obs => {
            drawCar(obs.x, obs.y, obs.width, obs.height, obs.color, obs.direction);
        });

        floatingTexts.forEach(ft => {
            const alpha = ft.life / ft.maxLife;
            ctx.globalAlpha = alpha;
            ctx.fillStyle = ft.color;
            ctx.font = 'bold 20px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(ft.text, ft.x, ft.y);
            ctx.globalAlpha = 1.0;
        });
    }

    function gameLoop() {
        update();
        draw();
        requestAnimationFrame(gameLoop);
    }

    function handleKeyDown(e) {
        const key = e.key;
        if (key === 'ArrowLeft' || key === 'a') keys.left = true;
        if (key === 'ArrowRight' || key === 'd') keys.right = true;
        if (key === 'ArrowUp' || key === 'w') keys.up = true;
        if (key === 'ArrowDown' || key === 's') keys.down = true;
        if (['ArrowUp','ArrowDown','ArrowLeft','ArrowRight',' ', 'Space'].includes(key)) {
            e.preventDefault();
        }
    }
    function handleKeyUp(e) {
        const key = e.key;
        if (key === 'ArrowLeft' || key === 'a') keys.left = false;
        if (key === 'ArrowRight' || key === 'd') keys.right = false;
        if (key === 'ArrowUp' || key === 'w') keys.up = false;
        if (key === 'ArrowDown' || key === 's') keys.down = false;
        if (['ArrowUp','ArrowDown','ArrowLeft','ArrowRight',' ', 'Space'].includes(key)) {
            e.preventDefault();
        }
    }

    let touchX = null, touchY = null;
    function handleTouchStart(e) {
        e.preventDefault();
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        keys.left = false; keys.right = false; keys.up = false; keys.down = false;
        if (x < centerX - 20) keys.left = true;
        else if (x > centerX + 20) keys.right = true;
        if (y < centerY - 20) keys.up = true;
        else if (y > centerY + 20) keys.down = true;
        touchX = x; touchY = y;
    }
    function handleTouchMove(e) {
        e.preventDefault();
        if (touchX === null) return;
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        keys.left = false; keys.right = false; keys.up = false; keys.down = false;
        if (x < centerX - 20) keys.left = true;
        else if (x > centerX + 20) keys.right = true;
        if (y < centerY - 20) keys.up = true;
        else if (y > centerY + 20) keys.down = true;
    }
    function handleTouchEnd(e) {
        e.preventDefault();
        keys.left = false; keys.right = false; keys.up = false; keys.down = false;
        touchX = null; touchY = null;
    }

    function init() {
        window.addEventListener('keydown', handleKeyDown);
        window.addEventListener('keyup', handleKeyUp);
        canvas.addEventListener('touchstart', handleTouchStart, {passive: false});
        canvas.addEventListener('touchmove', handleTouchMove, {passive: false});
        canvas.addEventListener('touchend', handleTouchEnd, {passive: false});
        canvas.addEventListener('touchcancel', handleTouchEnd, {passive: false});

        restartBtn.addEventListener('click', resetGame);

        resetGame();
        gameLoop();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();