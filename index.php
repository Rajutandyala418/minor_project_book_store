<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Interactive Book Store</title>

  <!-- TailwindCSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Three.js for stars -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

  <style>
    :root {
      --slide-speed: 45s; /* slower = smoother loop */
      --img-h: 70vh;      /* height of hero images */
      --bubble-size: 110px;
    }

    body {
      margin: 0;
      font-family: "Poppins", sans-serif;
      overflow-x: hidden;
      background: #0f172a;
      color: #fff;
    }

    /* HERO: continuous horizontal scrolling gallery */
    .hero {
      position: relative;
      height: 100vh;
      overflow: hidden;
      display: grid;
      place-items: center;
    }

    .hero::after {
      /* subtle gradient overlay for text readability */
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.15) 40%, rgba(0,0,0,0.6) 100%);
      pointer-events: none;
      z-index: 1;
    }

    .scrolling-gallery {
      position: absolute;
      inset: 0;
      overflow: hidden;
      z-index: 0;
    }

    .track {
      display: flex;
      gap: 24px;
      align-items: center;
      height: 100%;
      padding: 0 24px;
      animation: scroll-left var(--slide-speed) linear infinite;
      will-change: transform;
    }

    /* Duplicate the set of images for seamless loop */
    .track:hover { animation-play-state: paused; } /* pause on hover if you like */

    .slide {
      flex: 0 0 auto;
      height: var(--img-h);
      width: calc(var(--img-h) * 1.6); /* consistent aspect for uniform tiles */
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 15px 35px rgba(0,0,0,0.45);
      transform: translateZ(0);
    }

    .slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform .6s ease;
    }

    .slide:hover img {
      transform: scale(1.06);
    }

    @keyframes scroll-left {
      0%   { transform: translateX(0); }
      100% { transform: translateX(-50%); } /* because we duplicate the set once */
    }

    /* Headline on top of hero */
    .hero-content {
      position: relative;
      z-index: 2;
      text-align: center;
      padding: 0 1rem;
    }

    /* FEATURED images + stars background */
    #stars-bg {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
    }

    /* Bubbles */
    .bubbles {
      position: fixed;
      bottom: 20px;
      right: 20px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      z-index: 9999;
    }

    .bubble {
      width: var(--bubble-size);
      height: var(--bubble-size);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      font-weight: 600;
      color: #fff;
      cursor: pointer;
      box-shadow: 0 10px 30px rgba(0,0,0,0.35), 0 0 22px rgba(255,255,255,0.25) inset;
      transition: transform .25s ease, box-shadow .25s ease, filter .25s ease;
      backdrop-filter: blur(2px);
      -webkit-backdrop-filter: blur(2px);
    }
    .bubble:hover {
      transform: translateY(-4px) scale(1.05);
      box-shadow: 0 16px 40px rgba(0,0,0,0.45), 0 0 28px rgba(255,255,255,0.28) inset;
      filter: brightness(1.05);
    }
    .bubble.login    { background: linear-gradient(135deg, #f97316, #ef4444); }
    .bubble.register { background: linear-gradient(135deg, #10b981, #22c55e); }
    .bubble.support  { background: linear-gradient(135deg, #06b6d4, #3b82f6); }

    /* Gentle idle float so they feel alive but remain visible */
    .bubble.login    { animation: float1 6s ease-in-out infinite; }
    .bubble.register { animation: float2 7s ease-in-out infinite; }
    .bubble.support  { animation: float3 5.5s ease-in-out infinite; }

    @keyframes float1 { 0%,100%{ transform: translateY(0) } 50%{ transform: translateY(-10px) } }
    @keyframes float2 { 0%,100%{ transform: translateY(0) } 50%{ transform: translateY(-12px) } }
    @keyframes float3 { 0%,100%{ transform: translateY(0) } 50%{ transform: translateY(-9px) } }

    /* Chat popup */
    #chat-popup {
      display: none;
      position: fixed;
      bottom: calc(20px + var(--bubble-size) + 16px);
      right: calc(20px + var(--bubble-size) + 16px);
      width: 300px;
      background: #ffffff;
      color: #0f172a;
      border-radius: 14px;
      box-shadow: 0 20px 50px rgba(0,0,0,0.35);
      padding: 18px 16px;
      z-index: 10000;
    }
    #chat-popup header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    #chat-popup h3 {
      margin: 0;
      font-size: 1.05rem;
      color: #0ea5e9;
      font-weight: 700;
    }
    #close-chat {
      cursor: pointer;
      font-size: 1.3rem;
      line-height: 1;
      color: #111827;
    }
    #chat-popup a { color: #0f172a; text-decoration: underline; }
  </style>
</head>
<body>

  <!-- HERO: Scrolling Bookstore Images -->
  <section class="hero">
    <div class="scrolling-gallery">
    <div class="track">
  <!-- Set A (your 6 images) -->
  <div class="slide"><img src="images/image1.jpeg" alt="Book Image 1"></div>
  <div class="slide"><img src="images/image2.jpeg" alt="Book Image 2"></div>
  <div class="slide"><img src="images/image3.jpeg" alt="Book Image 3"></div>
  <div class="slide"><img src="images/image4.jpeg" alt="Book Image 4"></div>
  <div class="slide"><img src="images/image5.jpeg" alt="Book Image 5"></div>
  <div class="slide"><img src="images/image6.jpeg" alt="Book Image 6"></div>

  <!-- Set B duplicate for seamless scrolling -->
  <div class="slide"><img src="images/image1.jpeg" alt="Book Image 1"></div>
  <div class="slide"><img src="images/image2.jpeg" alt="Book Image 2"></div>
  <div class="slide"><img src="images/image3.jpeg" alt="Book Image 3"></div>
  <div class="slide"><img src="images/image4.jpeg" alt="Book Image 4"></div>
  <div class="slide"><img src="images/image5.jpeg" alt="Book Image 5"></div>
  <div class="slide"><img src="images/image6.jpeg" alt="Book Image 6"></div>
</div>

    </div>

    <div class="hero-content">
      <h1 class="text-5xl md:text-7xl font-bold mb-4">📚 Welcome to Book Haven</h1>
      <p class="text-xl md:text-2xl opacity-90">Discover books, stories, and adventures</p>
    </div>
  </section>

  <!-- FEATURED IMAGES with Stars Background -->
  <section class="relative py-16 bg-slate-900 text-center overflow-hidden">
    <canvas id="stars-bg"></canvas>
    <div class="relative z-10">
      <h2 class="text-4xl font-bold mb-10">📸 Featured Images</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 px-6 md:px-10">
        <img src="images/image7.jpeg" class="rounded-lg shadow-lg w-full h-64 object-cover" alt="Book Store 1"/>
        <img src="images/image8.jpeg" class="rounded-lg shadow-lg w-full h-64 object-cover" alt="Book Store 2"/>
        <img src="images/image9.jpg" class="rounded-lg shadow-lg w-full h-64 object-cover" alt="Book Store 3"/>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="py-6 bg-black text-center text-gray-400">
    <p>© 2025 Book Haven | Made with ❤️ by Raju</p>
  </footer>

  <!-- FLOATING BUBBLES (always visible at bottom-right) -->
  <div class="bubbles">
    <div class="bubble support" title="Chat Support">💬 Chat</div>
    <div class="bubble login"   title="Login">🔑 Login</div>
    <div class="bubble register" title="Register">📝 Register</div>
  </div>

  <!-- CHAT POPUP -->
  <div id="chat-popup">
    <header>
      <h3>Support</h3>
      <span id="close-chat" aria-label="Close chat">&times;</span>
    </header>
    <div class="space-y-2 text-sm">
      <p><strong>Phone:</strong> <a href="tel:7569398385">7569398385</a></p>
      <p><strong>Email:</strong> <a href="mailto:y22cm171@rvrjc.ac.in">y22cm171@rvrjc.ac.in</a></p>
      <p><strong>WhatsApp:</strong> <a href="https://wa.me/917569398385" target="_blank" rel="noopener">Chat Now</a></p>
      <hr/>
         </div>
  </div>

  <script>
    /* ===== Stars background (featured section) ===== */
    const starsCanvas = document.getElementById("stars-bg");
    const renderer = new THREE.WebGLRenderer({ canvas: starsCanvas, alpha: true });
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, starsCanvas.clientWidth / starsCanvas.clientHeight, 0.1, 1000);
    camera.position.z = 5;

    function sizeStarsCanvas() {
      const section = starsCanvas.parentElement;
      const rect = section.getBoundingClientRect();
      renderer.setSize(rect.width, rect.height, false);
      camera.aspect = rect.width / rect.height;
      camera.updateProjectionMatrix();
    }
    sizeStarsCanvas();

    const starCount = 1500;
    const starPositions = new Float32Array(starCount * 3);
    for (let i = 0; i < starCount * 3; i++) starPositions[i] = (Math.random() - 0.5) * 12;
    const starGeometry = new THREE.BufferGeometry();
    starGeometry.setAttribute("position", new THREE.BufferAttribute(starPositions, 3));
    const starMaterial = new THREE.PointsMaterial({ color: 0xffffff, size: 0.02 });
    const stars = new THREE.Points(starGeometry, starMaterial);
    scene.add(stars);

    function animateStars() {
      requestAnimationFrame(animateStars);
      stars.rotation.y += 0.0009;
      stars.rotation.x += 0.0005;
      renderer.render(scene, camera);
    }
    animateStars();

    window.addEventListener("resize", sizeStarsCanvas);

    /* ===== Bubble actions (always visible bottom-right) ===== */
    document.querySelector(".bubble.login").addEventListener("click", () => {
      window.location.href = "login.php";
    });
    document.querySelector(".bubble.register").addEventListener("click", () => {
      window.location.href = "register.php";
    });
    const chatPopup = document.getElementById("chat-popup");
    document.querySelector(".bubble.support").addEventListener("click", () => {
      chatPopup.style.display = "block";
    });
    document.getElementById("close-chat").addEventListener("click", () => {
      chatPopup.style.display = "none";
    });
  </script>
</body>
</html>
