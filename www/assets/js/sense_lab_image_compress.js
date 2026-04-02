/**
 * Sense Lab: 2MB を超える画像を選択時にブラウザ側で JPEG へ縮小・圧縮する。
 */
(function () {
    var MAX_BYTES = 2 * 1024 * 1024;
    var MAX_EDGE = 2048;
    var MIN_EDGE = 400;

    function compressImageToBlob(img, maxBytes, maxEdge) {
        var iw = img.naturalWidth || img.width;
        var ih = img.naturalHeight || img.height;
        if (!iw || !ih) {
            return Promise.resolve(null);
        }
        var scale = Math.min(1, maxEdge / iw, maxEdge / ih);
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        if (!ctx) {
            return Promise.resolve(null);
        }

        function tryAtCurrentScale() {
            var cw = Math.max(1, Math.round(iw * scale));
            var ch = Math.max(1, Math.round(ih * scale));
            canvas.width = cw;
            canvas.height = ch;
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, cw, ch);
            ctx.drawImage(img, 0, 0, cw, ch);
            return new Promise(function (resolve) {
                var q = 0.9;
                function attempt() {
                    if (q < 0.38) {
                        resolve(null);
                        return;
                    }
                    canvas.toBlob(function (blob) {
                        if (blob && blob.size <= maxBytes) {
                            resolve(blob);
                        } else {
                            q -= 0.06;
                            attempt();
                        }
                    }, 'image/jpeg', q);
                }
                attempt();
            });
        }

        function loop(round) {
            if (round > 14) {
                return Promise.resolve(null);
            }
            return tryAtCurrentScale().then(function (blob) {
                if (blob) {
                    return blob;
                }
                var cw = Math.max(1, Math.round(iw * scale));
                var ch = Math.max(1, Math.round(ih * scale));
                if (cw <= MIN_EDGE && ch <= MIN_EDGE) {
                    return null;
                }
                scale *= 0.82;
                return loop(round + 1);
            });
        }

        return loop(0);
    }

    function bind(input) {
        if (!input || input.getAttribute('data-sense-lab-compress') === '1') {
            return;
        }
        input.setAttribute('data-sense-lab-compress', '1');

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file || file.type.indexOf('image/') !== 0) {
                return;
            }
            if (file.size <= MAX_BYTES) {
                return;
            }

            var prevName = file.name;
            var base = prevName.replace(/\.[^.]+$/, '') || 'image';

            var img = new Image();
            var url = URL.createObjectURL(file);
            img.onload = function () {
                URL.revokeObjectURL(url);
                compressImageToBlob(img, MAX_BYTES, MAX_EDGE).then(function (blob) {
                    if (!blob) {
                        window.alert('画像を2MB以内に自動圧縮できませんでした。別の画像を試すか、写真アプリで縮小してからお試しください。');
                        input.value = '';
                        return;
                    }
                    var out = new File([blob], base + '.jpg', {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });
                    var dt = new DataTransfer();
                    dt.items.add(out);
                    input.files = dt.files;
                });
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                window.alert('画像を読み込めませんでした。対応していない形式の可能性があります。');
                input.value = '';
            };
            img.src = url;
        });
    }

    bind(document.getElementById('image'));
})();
