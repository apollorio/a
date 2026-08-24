<?php
/**
 * Apollo Radio — Compact widget template (bottom-bar).
 *
 * Loaded via [apollo_radio mode="widget"].
 * Fixed bottom bar with play/pause, track info, and waveform.
 *
 * @package Apollo\Radio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="apollo-radio-widget" id="apolloRadioWidget">
    <div class="arw" id="ar">
        <div class="ar-screen">

            <button class="arw-play" id="xBtn" type="button" aria-label="<?php esc_attr_e( 'Tocar rádio', 'apollo-radio' ); ?>">
                <svg viewBox="0 0 24 24" id="xIco" width="20" height="20"><polygon points="6,3 20,12 6,21"></polygon></svg>
            </button>

            <div class="arw-info">
                <div class="arw-title" id="xTitle">apollo::radio</div>
                <div class="arw-artist" id="xArtist">Pronto para tocar</div>
            </div>

            <div class="arw-wave" id="xWave"></div>

            <div class="arw-meta">
                <span class="sys-badge" id="sysState">STANDBY</span>
                <span class="ar-dot" id="liveDot"></span>
            </div>

            <!-- Hidden elements for engine compatibility -->
            <span id="xProg" style="display:none"></span>
            <span id="xDur" style="display:none"></span>
            <span id="xElap" style="display:none"></span>
            <div id="xBar" style="display:none"></div>
            <span id="xBlk" style="display:none"></span>
            <span id="xNxt" style="display:none"></span>
            <div id="xInfo" style="display:none"></div>
            <div id="xErr" style="display:none"></div>
            <span id="xLive" style="display:none"></span>
            <span id="liveText" style="display:none"></span>
        </div>
    </div>
</div>

<script>
;(function(){
'use strict';
var widget = document.getElementById('apolloRadioWidget');
if(!widget) return;

widget.addEventListener('click', function(e){
  if(e.target.closest('.arw-play') || e.target.closest('.arw-info')){
    if(window.apolloRadio){
      window.apolloRadio.toggle();
    }
  }
});
}());
</script>
