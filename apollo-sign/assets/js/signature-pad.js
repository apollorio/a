/**
 * Apollo Sign — Signature Pad
 * Hand-drawn signature capture: draw, upload image, undo, clear, color picker.
 * Exports signature as PNG dataURL for visual stamp on signed PDF.
 *
 * Adapted from: _library/signing doc/advanced-pdf-esignature-main/assets/src/js/modules/signaturePad.js
 * No external dependencies — uses native Canvas API.
 *
 * @package Apollo\Sign
 * @version 1.2.0
 */
(function () {
    'use strict';

    /* ───── Guard ───── */
    if (!document.getElementById('apollo-sigpad-canvas')) return;

    /* ───── DOM refs ───── */
    var wrapper    = document.getElementById('apollo-sigpad-wrap');
    var canvas     = document.getElementById('apollo-sigpad-canvas');
    var ctx        = canvas.getContext('2d');
    var btnClear   = document.getElementById('apollo-sigpad-clear');
    var btnUndo    = document.getElementById('apollo-sigpad-undo');
    var btnUpload  = document.getElementById('apollo-sigpad-upload');
    var fileInput  = document.getElementById('apollo-sigpad-file');
    var colorInput = document.getElementById('apollo-sigpad-color');
    var preview    = document.getElementById('apollo-sigpad-preview');
    var hiddenData = document.getElementById('sign-signature-image');
    var padToggle  = document.getElementById('apollo-sigpad-toggle');
    var padSection = document.getElementById('apollo-sigpad-section');

    if (!canvas || !ctx) return;

    /* ───── State ───── */
    var strokes  = [];       // Array of stroke arrays [{x,y}]
    var current  = [];       // Current stroke being drawn
    var drawing  = false;
    var penColor = '#e8e8ec';
    var penWidth = 2.5;
    var bgColor  = 'transparent';
    var hasImage = false;    // If an uploaded image is on canvas
    var imageDataURL = null; // Uploaded image data

    /* ───── Canvas setup ───── */
    function resizeCanvas() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var w = wrapper.clientWidth;
        var h = 160; // Fixed height for pad

        canvas.width  = w * ratio;
        canvas.height = h * ratio;
        canvas.style.width  = w + 'px';
        canvas.style.height = h + 'px';
        ctx.scale(ratio, ratio);
        redraw();
    }

    function clearCanvas() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        ctx.clearRect(0, 0, canvas.width / ratio, canvas.height / ratio);

        if (bgColor !== 'transparent') {
            ctx.fillStyle = bgColor;
            ctx.fillRect(0, 0, canvas.width / ratio, canvas.height / ratio);
        }
    }

    /* ───── Redraw all strokes ───── */
    function redraw() {
        clearCanvas();

        /* Draw uploaded image first (background layer) */
        if (hasImage && imageDataURL) {
            var img = new Image();
            img.onload = function () {
                var ratio = Math.max(window.devicePixelRatio || 1, 1);
                var cw = canvas.width / ratio;
                var ch = canvas.height / ratio;
                var scale = Math.min(cw / img.width, ch / img.height) * 0.9;
                var iw = img.width * scale;
                var ih = img.height * scale;
                ctx.drawImage(img, (cw - iw) / 2, (ch - ih) / 2, iw, ih);
                drawAllStrokes();
            };
            img.src = imageDataURL;
        } else {
            drawAllStrokes();
        }
    }

    function drawAllStrokes() {
        for (var i = 0; i < strokes.length; i++) {
            drawStroke(strokes[i]);
        }
    }

    function drawStroke(points) {
        if (!points || points.length < 2) return;
        ctx.beginPath();
        ctx.strokeStyle = points[0].color || penColor;
        ctx.lineWidth   = points[0].width || penWidth;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
        ctx.moveTo(points[0].x, points[0].y);

        for (var i = 1; i < points.length; i++) {
            /* Smooth curve using quadratic bezier */
            var mid = {
                x: (points[i - 1].x + points[i].x) / 2,
                y: (points[i - 1].y + points[i].y) / 2
            };
            ctx.quadraticCurveTo(points[i - 1].x, points[i - 1].y, mid.x, mid.y);
        }
        ctx.lineTo(points[points.length - 1].x, points[points.length - 1].y);
        ctx.stroke();
    }

    /* ───── Pointer coord helpers ───── */
    function getPointerPos(e) {
        var rect = canvas.getBoundingClientRect();
        var touch = e.touches ? e.touches[0] : e;
        return {
            x: touch.clientX - rect.left,
            y: touch.clientY - rect.top,
            color: penColor,
            width: penWidth
        };
    }

    /* ───── Drawing events ───── */
    function onStart(e) {
        e.preventDefault();
        drawing = true;
        current = [getPointerPos(e)];
    }

    function onMove(e) {
        if (!drawing) return;
        e.preventDefault();
        var pos = getPointerPos(e);
        current.push(pos);

        /* Draw live stroke */
        if (current.length >= 2) {
            var p1 = current[current.length - 2];
            var p2 = current[current.length - 1];
            ctx.beginPath();
            ctx.strokeStyle = p2.color || penColor;
            ctx.lineWidth   = p2.width || penWidth;
            ctx.lineCap     = 'round';
            ctx.lineJoin    = 'round';
            ctx.moveTo(p1.x, p1.y);
            ctx.lineTo(p2.x, p2.y);
            ctx.stroke();
        }
    }

    function onEnd(e) {
        if (!drawing) return;
        drawing = false;
        if (current.length > 1) {
            strokes.push(current.slice());
        }
        current = [];
        updateOutput();
    }

    /* Mouse events */
    canvas.addEventListener('mousedown', onStart);
    canvas.addEventListener('mousemove', onMove);
    canvas.addEventListener('mouseup', onEnd);
    canvas.addEventListener('mouseleave', onEnd);

    /* Touch events */
    canvas.addEventListener('touchstart', onStart, { passive: false });
    canvas.addEventListener('touchmove', onMove, { passive: false });
    canvas.addEventListener('touchend', onEnd);
    canvas.addEventListener('touchcancel', onEnd);

    /* ───── Actions ───── */

    /* Clear */
    if (btnClear) {
        btnClear.addEventListener('click', function () {
            strokes = [];
            current = [];
            hasImage = false;
            imageDataURL = null;
            redraw();
            updateOutput();
        });
    }

    /* Undo (adapted from signaturePad.js: data.pop()) */
    if (btnUndo) {
        btnUndo.addEventListener('click', function () {
            if (strokes.length > 0) {
                strokes.pop();
                redraw();
                updateOutput();
            }
        });
    }

    /* Upload image (adapted from signaturePad.js: loadSignatureImage) */
    if (btnUpload && fileInput) {
        btnUpload.addEventListener('click', function () {
            fileInput.click();
        });

        fileInput.addEventListener('change', function (e) {
            var file = e.target.files[0];
            if (!file) return;

            /* Validate file type */
            if (!file.type.match(/^image\/(png|jpeg|jpg|webp|svg\+xml)$/)) {
                showPadToast('Formato inválido. Use PNG, JPG ou SVG.', 'warning');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                showPadToast('Imagem muito grande. Máximo 5MB.', 'warning');
                return;
            }

            var reader = new FileReader();
            reader.onload = function (evt) {
                imageDataURL = evt.target.result;
                hasImage = true;
                strokes = []; // Clear strokes when uploading image
                redraw();
                updateOutput();
                showPadToast('Imagem carregada', 'success');
            };
            reader.readAsDataURL(file);
            /* Reset input so same file can be re-uploaded */
            fileInput.value = '';
        });
    }

    /* Color picker */
    if (colorInput) {
        colorInput.addEventListener('input', function (e) {
            penColor = e.target.value;
        });
    }

    /* ───── Toggle pad visibility ───── */
    if (padToggle && padSection) {
        padToggle.addEventListener('click', function () {
            var isHidden = padSection.style.display === 'none';
            padSection.style.display = isHidden ? 'block' : 'none';
            padToggle.classList.toggle('active', isHidden);

            if (isHidden) {
                resizeCanvas();
            }
        });
    }

    /* ───── Output: export as PNG dataURL ───── */
    function updateOutput() {
        var isEmpty = strokes.length === 0 && !hasImage;

        if (isEmpty) {
            if (hiddenData) hiddenData.value = '';
            if (preview) {
                preview.style.display = 'none';
                preview.src = '';
            }
            return;
        }

        /* Generate clean PNG (no bg artifacts) */
        var exportCanvas = document.createElement('canvas');
        var exportCtx = exportCanvas.getContext('2d');
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        exportCanvas.width = canvas.width;
        exportCanvas.height = canvas.height;
        exportCtx.scale(ratio, ratio);

        /* Transparent background for the export */
        var cw = canvas.width / ratio;
        var ch = canvas.height / ratio;

        /* Draw uploaded image if present */
        if (hasImage && imageDataURL) {
            var img = new Image();
            img.onload = function () {
                var scale = Math.min(cw / img.width, ch / img.height) * 0.9;
                var iw = img.width * scale;
                var ih = img.height * scale;
                exportCtx.drawImage(img, (cw - iw) / 2, (ch - ih) / 2, iw, ih);

                /* Draw strokes on top */
                for (var i = 0; i < strokes.length; i++) {
                    drawStrokeOnCtx(exportCtx, strokes[i]);
                }

                finishExport(exportCanvas);
            };
            img.src = imageDataURL;
        } else {
            /* Strokes only */
            for (var i = 0; i < strokes.length; i++) {
                drawStrokeOnCtx(exportCtx, strokes[i]);
            }
            finishExport(exportCanvas);
        }
    }

    function drawStrokeOnCtx(targetCtx, points) {
        if (!points || points.length < 2) return;
        targetCtx.beginPath();
        targetCtx.strokeStyle = points[0].color || penColor;
        targetCtx.lineWidth   = points[0].width || penWidth;
        targetCtx.lineCap     = 'round';
        targetCtx.lineJoin    = 'round';
        targetCtx.moveTo(points[0].x, points[0].y);

        for (var j = 1; j < points.length; j++) {
            var mid = {
                x: (points[j - 1].x + points[j].x) / 2,
                y: (points[j - 1].y + points[j].y) / 2
            };
            targetCtx.quadraticCurveTo(points[j - 1].x, points[j - 1].y, mid.x, mid.y);
        }
        targetCtx.lineTo(points[points.length - 1].x, points[points.length - 1].y);
        targetCtx.stroke();
    }

    function finishExport(exportCanvas) {
        var dataURL = exportCanvas.toDataURL('image/png');
        if (hiddenData) hiddenData.value = dataURL;
        if (preview) {
            preview.src = dataURL;
            preview.style.display = 'block';
        }
    }

    /* ───── Toast (reuse placement toast or create standalone) ───── */
    function showPadToast(msg, type) {
        var toast = document.getElementById('apollo-placement-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'sign-toast';
            toast.style.display = 'none';
            document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.className = 'sign-toast' + (type ? ' ' + type : '');
        toast.style.display = 'block';
        clearTimeout(toast._padTimer);
        toast._padTimer = setTimeout(function () {
            toast.style.display = 'none';
        }, 2500);
    }

    /* ───── Window resize handler ───── */
    var padResizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(padResizeTimer);
        padResizeTimer = setTimeout(resizeCanvas, 200);
    });

    /* ───── Init ───── */
    resizeCanvas();

})();
