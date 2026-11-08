{{--
    Downloads of the chart and of the table as pictures, drawn in the browser: the CourtWatch mark and name in the
    top-left corner, the title, the choices and filters, then the chart (or table), then the notes and the source.
    The words come from the page's [data-chart-caption]; the server has already logged the download (Charts::logImage).
--}}
<script>
    window.cwChartExport ??= (() => {
        const NAVY = '#0c2a42', GOLD = '#f8c33a', MUTED = '#52607a', LINE = '#e2e5ea';
        const FONT = getComputedStyle(document.body).fontFamily || 'sans-serif';
        const MARK = new Path2D('M10 4h26v26l-6 6H4V10zM9.5 9.5v21h21v-21z');
        const SCALE = 2;

        const root = () => document.querySelector('[data-research-charts]');
        const caption = () => JSON.parse(root()?.querySelector('[data-chart-caption]')?.textContent || '{}');

        // Splits text into lines no wider than max.
        function wrap(ctx, text, max) {
            const lines = [];
            let line = '';
            for (const word of String(text).split(/\s+/)) {
                const next = line ? line + ' ' + word : word;
                if (ctx.measureText(next).width > max && line) { lines.push(line); line = word; } else { line = next; }
            }
            if (line) lines.push(line);
            return lines;
        }

        // Draws the picture: header (mark, name, title, filters), the body at bodyHeight, the notes below.
        function compose(width, bodyHeight, drawBody) {
            const pad = 40, c = caption();
            const probe = document.createElement('canvas').getContext('2d');
            probe.font = `400 15px ${FONT}`;
            const notes = [...(c.notes || []), c.source || ''].flatMap((note) => wrap(probe, note, width - 2 * pad));
            probe.font = `400 16px ${FONT}`;
            const filters = wrap(probe, c.filters || '', width - 2 * pad);
            const headerHeight = 40 + 34 + 24 + 34 + 26 + filters.length * 22 + 24;
            const height = headerHeight + bodyHeight + 24 + notes.length * 21 + pad;

            const canvas = document.createElement('canvas');
            canvas.width = width * SCALE;
            canvas.height = height * SCALE;
            const ctx = canvas.getContext('2d');
            ctx.scale(SCALE, SCALE);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);

            // The corner: the mark and the name.
            ctx.save();
            ctx.translate(pad, 36);
            ctx.scale(0.8, 0.8);
            ctx.fillStyle = GOLD;
            ctx.fill(MARK, 'evenodd');
            ctx.restore();
            ctx.fillStyle = NAVY;
            ctx.font = `800 20px ${FONT}`;
            ctx.textBaseline = 'middle';
            ctx.fillText('CourtWatch', pad + 42, 52);
            ctx.fillStyle = GOLD;
            ctx.fillRect(pad, 84, width - 2 * pad, 3);

            ctx.textBaseline = 'alphabetic';
            let y = 40 + 34 + 24 + 30;
            ctx.fillStyle = NAVY;
            ctx.font = `800 26px ${FONT}`;
            ctx.fillText(c.title || '', pad, y);
            y += 26;
            ctx.font = `600 17px ${FONT}`;
            ctx.fillText(c.subtitle || '', pad, y);
            ctx.font = `400 16px ${FONT}`;
            ctx.fillStyle = MUTED;
            for (const line of filters) { y += 22; ctx.fillText(line, pad, y); }

            drawBody(ctx, pad, headerHeight, width - 2 * pad);

            y = headerHeight + bodyHeight + 24;
            ctx.font = `400 15px ${FONT}`;
            ctx.fillStyle = MUTED;
            for (const line of notes) { y += 21; ctx.fillText(line, pad, y); }

            return canvas;
        }

        function save(canvas, what) {
            const link = document.createElement('a');
            link.download = `courtwatch-${what}-${new Date().toISOString().slice(0, 10)}.png`;
            link.href = canvas.toDataURL('image/png');
            document.body.appendChild(link);
            link.click();
            link.remove();
        }

        return {
            chart() {
                const source = root()?.querySelector('canvas');
                if (!source) return;
                const width = Math.max(900, source.clientWidth + 80);
                const w = width - 80, h = Math.round(source.clientHeight * (w / source.clientWidth));
                save(compose(width, h, (ctx, x, y) => ctx.drawImage(source, x, y, w, h)), 'chart');
            },

            table() {
                const table = root()?.querySelector('[data-chart-table]');
                if (!table) return;
                const rows = [...table.rows].map((row) => [...row.cells].map((cell) => cell.textContent.trim().replace(/\s+/g, ' ')));
                const probe = document.createElement('canvas').getContext('2d');
                probe.font = `700 15px ${FONT}`;
                const widths = rows[0].map((_, i) => Math.min(i === 0 ? 420 : 220, Math.max(...rows.map((row) => probe.measureText(row[i] ?? '').width)) + 28));
                const tableWidth = widths.reduce((a, b) => a + b, 0);
                const rowHeight = 34;
                const width = Math.max(900, tableWidth + 80);

                save(compose(width, rows.length * rowHeight, (ctx, x, y) => {
                    rows.forEach((row, r) => {
                        const top = y + r * rowHeight;
                        if (r === 0) { ctx.fillStyle = NAVY; ctx.fillRect(x, top, tableWidth, rowHeight); }
                        else if (r % 2 === 0) { ctx.fillStyle = '#f5f6f8'; ctx.fillRect(x, top, tableWidth, rowHeight); }
                        let left = x;
                        row.forEach((text, i) => {
                            ctx.font = `${r === 0 || i > 0 ? 700 : 400} 15px ${FONT}`;
                            ctx.fillStyle = r === 0 ? '#ffffff' : (text.endsWith('*') ? '#94a0ae' : NAVY);
                            ctx.textBaseline = 'middle';
                            ctx.textAlign = i === 0 ? 'left' : 'right';
                            let shown = text;
                            while (ctx.measureText(shown).width > widths[i] - 24 && shown.length > 1) shown = shown.slice(0, -2) + '…';
                            ctx.fillText(shown, i === 0 ? left + 12 : left + widths[i] - 12, top + rowHeight / 2);
                            left += widths[i];
                        });
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'alphabetic';
                        ctx.fillStyle = LINE;
                        ctx.fillRect(x, top + rowHeight - 1, tableWidth, 1);
                    });
                }), 'table');
            },
        };
    })();
</script>
