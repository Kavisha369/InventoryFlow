/**
 * assets/js/charts.js
 * Native HTML5 Canvas Chart Renderer
 * ────────────────────────────────────
 * Renders a dual-dataset line/area chart from the /api/stock_chart.php endpoint.
 * Zero external dependencies — pure Canvas 2D API.
 */

'use strict';

const StockChart = (() => {

    const CANVAS    = document.getElementById('stockChart');
    if (!CANVAS) return { init: () => {} };

    const CTX       = CANVAS.getContext('2d');
    let   chartData = null;
    let   activeSet = 0;   // 0 = stock value, 1 = units
    let   animReq;

    // ── Config ────────────────────────────────────────────────────────────────
    const CONFIG = {
        padding:     { top: 30, right: 24, bottom: 50, left: 72 },
        gridLines:   6,
        pointRadius: 4,
        lineWidth:   2.5,
        areaAlpha:   0.12,
        font:        "'Inter', sans-serif",
        animDuration:700,
    };

    // Dark mode aware colours
    function getColors() {
        const dark  = document.documentElement.getAttribute('data-theme') === 'dark';
        return {
            grid:   dark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)',
            text:   dark ? '#8b949e'                : '#94a3b8',
            bg:     dark ? '#161b22'                : '#ffffff',
        };
    }

    // ── Data Fetching ─────────────────────────────────────────────────────────
    async function fetchData(days = 30) {
        const loading = document.querySelector('.chart-loading');
        if (loading) loading.style.display = 'flex';

        try {
            const res  = await fetch(`/Collaborative_project/api/stock_chart.php?days=${days}`);
            if (!res.ok) throw new Error('API error');
            const json = await res.json();
            if (!json.success) throw new Error(json.error);

            chartData = json;
            renderChart(json, activeSet);

        } catch (e) {
            if (loading) {
                loading.innerHTML = '<span style="color:var(--brand-danger)">⚠️ Failed to load chart data</span>';
            }
        } finally {
            if (loading && chartData) loading.style.display = 'none';
        }
    }

    // ── Canvas Sizing ─────────────────────────────────────────────────────────
    function resizeCanvas() {
        const dpr    = window.devicePixelRatio || 1;
        const width  = CANVAS.parentElement.clientWidth;
        const height = 240;

        CANVAS.width  = width  * dpr;
        CANVAS.height = height * dpr;
        CANVAS.style.width  = width  + 'px';
        CANVAS.style.height = height + 'px';
        CTX.scale(dpr, dpr);

        return { width, height };
    }

    // ── Chart Render ──────────────────────────────────────────────────────────
    function renderChart(json, datasetIdx = 0, progress = 1) {
        const { width, height } = resizeCanvas();
        const { padding: P, gridLines, pointRadius, lineWidth, areaAlpha } = CONFIG;
        const colors = getColors();

        const dataset = json.datasets[datasetIdx];
        const labels  = json.labels;
        const data    = dataset.data;
        const color   = dataset.color;

        if (!data || data.length === 0) return;

        const drawW = width  - P.left - P.right;
        const drawH = height - P.top  - P.bottom;

        const maxVal = Math.max(...data) * 1.10 || 1;
        const minVal = 0;

        // ── Clear ─────────────────────────────────────────────────────────────
        CTX.clearRect(0, 0, width, height);

        // ── Grid Lines ────────────────────────────────────────────────────────
        CTX.save();
        CTX.strokeStyle = colors.grid;
        CTX.lineWidth   = 1;
        CTX.setLineDash([4, 4]);

        for (let i = 0; i <= gridLines; i++) {
            const y = P.top + drawH - (i / gridLines) * drawH;
            CTX.beginPath();
            CTX.moveTo(P.left, y);
            CTX.lineTo(P.left + drawW, y);
            CTX.stroke();
        }
        CTX.setLineDash([]);
        CTX.restore();

        // ── Y-Axis Labels ─────────────────────────────────────────────────────
        CTX.save();
        CTX.fillStyle    = colors.text;
        CTX.font         = `11px ${CONFIG.font}`;
        CTX.textAlign    = 'right';
        CTX.textBaseline = 'middle';

        for (let i = 0; i <= gridLines; i++) {
            const val   = minVal + (i / gridLines) * (maxVal - minVal);
            const y     = P.top + drawH - (i / gridLines) * drawH;
            const label = datasetIdx === 0
                ? '$' + formatKNumber(val)
                : formatKNumber(val);
            CTX.fillText(label, P.left - 8, y);
        }
        CTX.restore();

        // ── X-Axis Labels ─────────────────────────────────────────────────────
        CTX.save();
        CTX.fillStyle    = colors.text;
        CTX.font         = `10px ${CONFIG.font}`;
        CTX.textAlign    = 'center';
        CTX.textBaseline = 'top';

        const step = Math.max(1, Math.floor(labels.length / 8));
        labels.forEach((lbl, i) => {
            if (i % step !== 0 && i !== labels.length - 1) return;
            const x = P.left + (i / Math.max(1, data.length - 1)) * drawW;
            CTX.fillText(lbl, x, P.top + drawH + 10);
        });
        CTX.restore();

        // ── Animated line drawing ─────────────────────────────────────────────
        const visibleCount = Math.ceil(data.length * progress);

        // Map data to canvas coords
        const points = data.map((val, i) => ({
            x: P.left + (i / Math.max(1, data.length - 1)) * drawW,
            y: P.top  + drawH - ((val - minVal) / (maxVal - minVal)) * drawH,
        })).slice(0, visibleCount);

        if (points.length < 2) return;

        // ── Area Fill ─────────────────────────────────────────────────────────
        const gradient = CTX.createLinearGradient(0, P.top, 0, P.top + drawH);
        gradient.addColorStop(0,   hexToRgba(color, areaAlpha * 2.5));
        gradient.addColorStop(0.6, hexToRgba(color, areaAlpha));
        gradient.addColorStop(1,   hexToRgba(color, 0));

        CTX.save();
        CTX.fillStyle = gradient;
        CTX.beginPath();
        CTX.moveTo(points[0].x, P.top + drawH);
        points.forEach(pt => CTX.lineTo(pt.x, pt.y));
        CTX.lineTo(points[points.length - 1].x, P.top + drawH);
        CTX.closePath();
        CTX.fill();
        CTX.restore();

        // ── Line (with bezier smoothing) ──────────────────────────────────────
        CTX.save();
        CTX.strokeStyle = color;
        CTX.lineWidth   = lineWidth;
        CTX.lineJoin    = 'round';
        CTX.lineCap     = 'round';
        CTX.shadowColor = hexToRgba(color, 0.4);
        CTX.shadowBlur  = 8;

        CTX.beginPath();
        CTX.moveTo(points[0].x, points[0].y);

        for (let i = 1; i < points.length; i++) {
            const prev = points[i - 1];
            const curr = points[i];
            const cpX  = (prev.x + curr.x) / 2;
            CTX.bezierCurveTo(cpX, prev.y, cpX, curr.y, curr.x, curr.y);
        }

        CTX.stroke();
        CTX.restore();

        // ── Data Points ──────────────────────────────────────────────────────
        CTX.save();
        CTX.fillStyle   = color;
        CTX.strokeStyle = getColors().bg;
        CTX.lineWidth   = 2;

        points.forEach((pt, i) => {
            // Only render visible points (step-skip for clarity)
            const shouldShow = points.length <= 15 ||
                               i === 0 ||
                               i === points.length - 1 ||
                               i % Math.floor(points.length / 8) === 0;
            if (!shouldShow) return;

            CTX.beginPath();
            CTX.arc(pt.x, pt.y, pointRadius, 0, Math.PI * 2);
            CTX.fill();
            CTX.stroke();
        });
        CTX.restore();

        // ── Tooltip on last visible point ─────────────────────────────────────
        if (progress >= 1 && points.length > 0) {
            const last = points[points.length - 1];
            const val  = data[points.length - 1];
            const lbl  = datasetIdx === 0 ? `$${val.toFixed(2)}` : `${val} units`;

            CTX.save();
            const boxW = 90, boxH = 28, pad = 8;
            let bx = last.x - boxW / 2;
            if (bx < P.left) bx = P.left;
            if (bx + boxW > width - P.right) bx = width - P.right - boxW;
            const by = last.y - boxH - 10;

            // Bubble
            CTX.fillStyle   = color;
            CTX.shadowColor = hexToRgba(color, 0.4);
            CTX.shadowBlur  = 10;
            roundRect(CTX, bx, by, boxW, boxH, 6);
            CTX.fill();
            CTX.shadowBlur = 0;

            // Text
            CTX.fillStyle    = '#fff';
            CTX.font         = `bold 11px ${CONFIG.font}`;
            CTX.textAlign    = 'center';
            CTX.textBaseline = 'middle';
            CTX.fillText(lbl, bx + boxW / 2, by + boxH / 2);
            CTX.restore();
        }
    }

    // ── Animation Loop ────────────────────────────────────────────────────────
    function animateChart(json, datasetIdx) {
        if (animReq) cancelAnimationFrame(animReq);
        const start = performance.now();
        const dur   = CONFIG.animDuration;

        function tick(now) {
            const elapsed  = now - start;
            const progress = Math.min(1, elapsed / dur);
            // Ease out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            renderChart(json, datasetIdx, eased);

            if (progress < 1) {
                animReq = requestAnimationFrame(tick);
            }
        }

        animReq = requestAnimationFrame(tick);
    }

    // ── Dataset Switch ────────────────────────────────────────────────────────
    function switchDataset(idx) {
        activeSet = idx;
        if (chartData) animateChart(chartData, idx);

        document.querySelectorAll('.chart-btn').forEach((btn, i) => {
            btn.classList.toggle('active', i === idx);
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function hexToRgba(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r},${g},${b},${alpha})`;
    }

    function formatKNumber(n) {
        if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
        if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K';
        return Math.round(n).toString();
    }

    function roundRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    function init() {
        // Period selector buttons
        document.querySelectorAll('.chart-btn[data-days]').forEach((btn, i) => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.chart-btn[data-days]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                fetchData(parseInt(btn.dataset.days, 10));
            });
        });

        // Dataset toggle (Value vs Units)
        document.querySelectorAll('.chart-btn[data-dataset]').forEach(btn => {
            btn.addEventListener('click', () => switchDataset(parseInt(btn.dataset.dataset, 10)));
        });

        // Responsive redraw on resize
        const ro = new ResizeObserver(() => {
            if (chartData) renderChart(chartData, activeSet);
        });
        ro.observe(CANVAS.parentElement);

        // Re-render on theme change (MutationObserver on html[data-theme])
        new MutationObserver(() => {
            if (chartData) renderChart(chartData, activeSet);
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

        // Initial load
        fetchData(30);
    }

    return { init, fetchData, switchDataset };
})();

document.addEventListener('DOMContentLoaded', () => StockChart.init());
window.StockChart = StockChart;
