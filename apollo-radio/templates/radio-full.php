<?php
/**
 * Apollo Radio — Fullscreen player template.
 *
 * Loaded via [apollo_radio] or [apollo_radio mode="full"].
 * Mobile-first, glassmorphism design, intro overlay + preloader + player.
 *
 * @package Apollo\Radio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<!-- APOLLO RADIO: INTRO -->
<div id="introOverlay" class="apollo-radio-intro">
    <button class="intro-btn" id="startBtn" type="button">Enter</button>
</div>

<!-- APOLLO RADIO: PRELOADER -->
<div id="apolloPreloader" class="apollo-radio-preloader">
    <span class="apollo-counter" id="apolloCounter">[000]</span>
    <span class="apollo-counter-label">apollo::radio — inicializando</span>
</div>

<!-- APOLLO RADIO: PLAYER -->
<div id="arWrap" class="apollo-radio-wrap">
    <div class="ar" id="ar">
        <div class="ar-screen">

            <div class="ar-head">
                <span class="ar-brand">apollo::radio</span>
                <div class="ar-status-wrap">
                    <span class="sys-badge" id="sysState">STANDBY</span>
                    <div class="ar-live" id="xLive">
                        <span class="ar-dot" id="liveDot"></span>
                        <span id="liveText">PRONTO</span>
                    </div>
                </div>
            </div>

            <div class="ar-wave" id="xWave"></div>

            <div class="ar-info" id="xInfo">
                <div class="ar-title" id="xTitle">Carregando...</div>
                <div class="ar-artist" id="xArtist">Sincronizando</div>
            </div>

            <div class="ar-prog-wrap">
                <span class="ar-elap" id="xElap">0:00</span>
                <div class="ar-bar-bg"><div class="ar-bar-fg" id="xBar"></div></div>
                <span class="ar-dur" id="xDur">0:00</span>
            </div>

        </div>

        <div class="ar-ctrl">
            <span class="ar-badge prog" id="xProg">&mdash;</span>
            <button class="ar-play" id="xBtn" type="button" aria-label="<?php esc_attr_e( 'Tocar rádio', 'apollo-radio' ); ?>">
                <svg viewBox="0 0 24 24" id="xIco"><polygon points="6,3 20,12 6,21"></polygon></svg>
            </button>
            <span class="ar-badge" id="xBlk">HQ</span>
        </div>

        <div class="ar-nxt">
            <div class="ar-nxt-l">A Seguir</div>
            <div class="ar-nxt-t" id="xNxt">&mdash;</div>
        </div>

        <div class="ar-err" id="xErr"></div>
    </div>
</div>

<script>
;(function(){
'use strict';
var overlay   = document.getElementById('introOverlay');
var preloader = document.getElementById('apolloPreloader');
var counter   = document.getElementById('apolloCounter');
var wrap      = document.getElementById('arWrap');
var started   = false;

document.getElementById('startBtn').addEventListener('click', function(){
  if(started) return;
  started = true;

  if(window.apolloRadio) window.apolloRadio.play();

  overlay.style.transition='opacity .5s';
  overlay.style.opacity='0';
  setTimeout(function(){ overlay.style.display='none'; }, 500);

  preloader.style.display='flex';

  var n=0;
  var tid=setInterval(function(){
    n++;
    counter.textContent='['+String(n).padStart(3,'0')+']';
    if(n>=100){
      clearInterval(tid);

      if(typeof gsap!=='undefined'){
        gsap.to(preloader,{y:-60,opacity:0,duration:.8,ease:'power2.inOut',
          onComplete:function(){ preloader.style.display='none'; }});
        gsap.fromTo(wrap,{opacity:0,y:30},{opacity:1,y:0,duration:1.1,delay:.2,ease:'power3.out'});
      } else {
        preloader.style.display='none';
        wrap.style.opacity='1';
      }

      if(window.apolloRadio) window.apolloRadio.fadeIn(3000);
    }
  }, 50);
});
}());
</script>
