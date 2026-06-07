/**
 * Google Drive-Style Cinematic Gallery (v4.0)
 */
window.DrivePreviewer = {
    modal: null,
    thumbStrip: null,
    contentWrapper: null,
    filenameEl: null,
    pageInfoEl: null,
    prevBtn: null,
    nextBtn: null,
    zoomValEl: null,
    panOverlay: null,

    galleryContents: [],
    currentIndex: 0,

    scale: 1,
    baseFitScale: 1, // Store the initial fit-to-screen scale
    isDragging: false,
    startX: 0, startY: 0,
    translateX: 0, translateY: 0,

    init() {
        if (this.modal) return;

        this.modal = document.getElementById('drive-preview-modal');
        if (!this.modal) return;

        this.thumbStrip = document.getElementById('drive-thumb-strip');
        this.contentWrapper = document.getElementById('drive-content-wrapper');
        this.filenameEl = document.getElementById('drive-filename');
        this.pageInfoEl = document.getElementById('drive-page-info');
        this.prevBtn = document.getElementById('drive-prev-side');
        this.nextBtn = document.getElementById('drive-next-side');
        this.zoomValEl = document.getElementById('drive-zoom-val');
        this.panOverlay = document.getElementById('drive-panning-overlay');

        // Bind Events
        const closeBtn = document.getElementById('drive-close-btn');
        if (closeBtn) closeBtn.onclick = () => this.close();

        if (this.prevBtn) this.prevBtn.onclick = (e) => { e.stopPropagation(); this.prev(); };
        if (this.nextBtn) this.nextBtn.onclick = (e) => { e.stopPropagation(); this.next(); };

        const zoomInBtn = document.getElementById('drive-zoom-in');
        const zoomOutBtn = document.getElementById('drive-zoom-out');
        if (zoomInBtn) zoomInBtn.onclick = () => this.zoom(0.2);
        if (zoomOutBtn) zoomOutBtn.onclick = () => this.zoom(-0.2);

        this.contentWrapper.ondblclick = () => this.toggleZoom();

        window.addEventListener('keydown', (e) => {
            if (!this.modal || this.modal.classList.contains('hidden')) return;
            if (e.key === 'Escape') this.close();
            if (e.key === 'ArrowRight') this.next();
            if (e.key === 'ArrowLeft') this.prev();
        });

        this.initPanning(this.panOverlay);
        console.log('DrivePreviewer: Cinematic v5.0 (Bottom Thumbs) Initialized');
    },

    open(items, startIndex = 0) {
        if (!this.modal) this.init();
        if (!this.modal) return;

        this.modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        this.galleryContents = Array.isArray(items) ? [...items] : [items];
        this.currentIndex = Math.min(Math.max(0, startIndex), this.galleryContents.length - 1);

        if (!this._resizeHandler) {
            this._resizeHandler = () => this.fitToScreen();
            window.addEventListener('resize', this._resizeHandler);
        }

        this.loadCurrentItem();
    },

    close() {
        if (!this.modal) return;
        this.modal.classList.add('hidden');
        document.body.style.overflow = '';
        if (this.contentWrapper) this.contentWrapper.innerHTML = '';
        this.galleryContents = [];
    },

    renderThumbStrip() {
        if (!this.thumbStrip) return;
        this.thumbStrip.innerHTML = '';

        this.galleryContents.forEach((item, index) => {
            const div = document.createElement('div');
            const isActive = index === this.currentIndex;
            div.className = `drive-thumb-item ${isActive ? 'active' : ''}`;

            const isPage = item.reportPageIndex !== undefined;
            const iconName = item.type === 'report' || isPage ? 'file-text' : 'image';

            if (item.type === 'image' && item.url) {
                div.innerHTML = `<img src="${item.url}" alt="thumb" class="w-full h-full object-cover">`;
            } else {
                div.innerHTML = `<i data-lucide="${iconName}" class="w-6 h-6"></i>`;
            }

            div.onclick = () => this.jumpTo(index);
            this.thumbStrip.appendChild(div);

            if (isActive) {
                div.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        });

        if (window.lucide) lucide.createIcons();
    },

    jumpTo(index) {
        if (index === this.currentIndex) return;

        const prevItem = this.galleryContents[this.currentIndex];
        const nextItem = this.galleryContents[index];
        this.currentIndex = index;

        const isSameDoc = prevItem && nextItem && prevItem.name === nextItem.name;

        if (prevItem && nextItem && prevItem.url === nextItem.url && nextItem.type === 'report') {
            this.applyDiscretePaging();
            this.renderThumbStrip();
            this.updatePageCounter();
            this.updateNavButtons();
            if (!isSameDoc) this.resetContentPos();
        } else {
            this.loadCurrentItem(isSameDoc);
        }
    },

    loadCurrentItem(preserveState = false) {
        const item = this.galleryContents[this.currentIndex];
        if (!item) return;

        if (!preserveState) this.resetContentPos();

        const fallbackTitle = item.type === 'report' ? 'Findings Report' : 'X-ray Image';
        const displayTitle = item.name || fallbackTitle;
        if (this.filenameEl) this.filenameEl.textContent = displayTitle;

        this.contentWrapper.innerHTML = '';
        if (!preserveState) this.contentWrapper.style.opacity = '0';

        if (item.type === 'report') {
            this.scale = 0.5;
            const iframe = document.createElement('iframe');
            iframe.style.width = '814px';
            iframe.style.height = '1142px';
            iframe.style.transformOrigin = 'center top';
            iframe.style.transform = `translate(-50%, 0) scale(${this.scale})`;
            iframe.className = 'border-none report-frame-container absolute top-[80px] left-[50%]';

            iframe.onload = () => {
                this.contentWrapper.style.opacity = '1';
                if (!preserveState) this.fitToScreen();
                else this.updateTransform();
                this.injectReportStyles(iframe);
                this.pollForReportPages(iframe);
            };
            iframe.src = item.url;
            this.contentWrapper.appendChild(iframe);
        } else if (item.type === 'image' || item.type === 'report_image') {
            const img = document.createElement('img');
            if (item.type === 'report_image') {
                this.scale = 0.5;
                img.style.width = '814px';
                img.style.height = '1142px';
                img.style.transformOrigin = 'center top';
                img.style.transform = `translate(-50%, 0) scale(${this.scale})`;
                img.className = 'border-none report-frame-container shadow-2xl absolute top-[80px] left-[50%] object-contain bg-white';
            } else {
                // Fix for regular X-ray images (Diagnostic Image)
                img.className = 'max-w-full max-h-full object-contain report-frame-container shadow-2xl absolute top-[50%] left-[50%]';
            }

            img.onload = () => {
                this.contentWrapper.style.opacity = '1';
                this.fitToScreen(); // Compute fit for ALL items now
                this.updateTransform();
            };
            img.src = item.url;
            this.contentWrapper.appendChild(img);
        }

        this.renderThumbStrip();
        this.updateNavButtons();
        this.updatePageCounter();
    },

    injectReportStyles(iframe) {
        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            if (!doc.getElementById('drive-pagination-styles')) {
                const style = doc.createElement('style');
                style.id = 'drive-pagination-styles';
                style.textContent = `
                    .report-page-hidden { display: none !important; }
                    body { overflow: hidden !important; background: transparent !important; }
                    .page { margin: 0 auto !important; box-shadow: 0 10px 40px rgba(0,0,0,0.5) !important; border: 1px solid #eee !important; }
                `;
                doc.head.appendChild(style);
            }
        } catch (e) { }
    },

    fitToScreen() {
        const item = this.galleryContents[this.currentIndex];
        if (!item || !this.contentWrapper) return;

        // Account for top header (64)
        const vh = window.innerHeight - 80;
        // Account for right-side strip (approx 100px)
        const vw = window.innerWidth - 120;

        if (item.type === 'report' || item.type === 'report_image') {
            // Document height is 1142px. We want it to fit in vh.
            this.scale = Math.min(0.5, vh / 1142);
        } else {
            const el = this.contentWrapper.querySelector('img');
            if (el) {
                const scaleX = vw / el.naturalWidth;
                const scaleY = vh / el.naturalHeight;
                this.scale = Math.min(Math.min(scaleX, scaleY), 1);
            } else {
                this.scale = 1;
            }
        }
        this.translateX = 0;
        this.translateY = 0;
        this.baseFitScale = this.scale; // Capture the initial fit scale
        this.updateTransform();
    },

    resetContentPos() {
        this.scale = 1;
        this.translateX = 0;
        this.translateY = 0;
        this.updateTransform();
        this.togglePanningOverlay();
    },

    pollForReportPages(iframe) {
        let attempts = 0;
        const check = () => {
            try {
                const doc = iframe.contentDocument || iframe.contentWindow.document;
                const pages = doc.querySelectorAll('.report-page');
                if (pages.length > 0) {
                    this.expandReport(pages);
                } else if (attempts < 30) {
                    attempts++;
                    setTimeout(check, 100);
                }
            } catch (e) { }
        };
        check();
    },

    expandReport(pages) {
        const item = this.galleryContents[this.currentIndex];
        if (!item || item.reportPageIndex !== undefined || item.type !== 'report') return;

        const newEntries = Array.from(pages).map((p, i) => ({
            ...item,
            reportPageIndex: i
        }));

        this.galleryContents.splice(this.currentIndex, 1, ...newEntries);
        this.renderThumbStrip();
        this.applyDiscretePaging();
        this.updatePageCounter();
        this.updateNavButtons();
    },

    applyDiscretePaging() {
        const item = this.galleryContents[this.currentIndex];
        if (!item || item.reportPageIndex === undefined) return;
        const iframe = this.contentWrapper.querySelector('iframe');
        if (!iframe) return;

        try {
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            const pages = doc.querySelectorAll('.report-page');
            pages.forEach((p, i) => {
                p.classList.toggle('report-page-hidden', i !== item.reportPageIndex);
            });
            doc.documentElement.scrollTop = 0;
        } catch (e) { }
    },

    updatePageCounter() {
        if (this.pageInfoEl) {
            this.pageInfoEl.textContent = `${this.currentIndex + 1} / ${this.galleryContents.length}`;
        }
    },

    updateNavButtons() {
        const hasMultiple = this.galleryContents.length > 1;
        if (this.prevBtn) {
            this.prevBtn.disabled = this.currentIndex === 0;
            this.prevBtn.style.display = hasMultiple ? '' : 'none';
        }
        if (this.nextBtn) {
            this.nextBtn.disabled = this.currentIndex === this.galleryContents.length - 1;
            this.nextBtn.style.display = hasMultiple ? '' : 'none';
        }
    },

    next() {
        if (this.currentIndex < this.galleryContents.length - 1) this.jumpTo(this.currentIndex + 1);
    },

    prev() {
        if (this.currentIndex > 0) this.jumpTo(this.currentIndex - 1);
    },

    toggleZoom() {
        if (this.scale > 1) {
            this.scale = 0.5;
            this.translateX = 0;
            this.translateY = 0;
        } else {
            this.scale = 2.0;
        }
        this.updateTransform();
        this.togglePanningOverlay();
    },

    zoom(delta) {
        this.scale = Math.min(Math.max(0.2, this.scale + delta), 4);
        this.updateTransform();
        this.togglePanningOverlay();
    },

    togglePanningOverlay() {
        // Enable panning whenever scale is greater than the initial fit scale
        const shouldPan = this.scale > (this.baseFitScale + 0.01);
        if (this.panOverlay) {
            this.panOverlay.classList.toggle('active', shouldPan);
        }
    },

    updateTransform() {
        const item = this.galleryContents ? this.galleryContents[this.currentIndex] : null;
        const el = this.contentWrapper.querySelector('img, iframe');
        if (el) {
            const isDoc = el.tagName.toLowerCase() === 'iframe' || (item && item.type === 'report_image');
            if (isDoc) {
                el.style.transformOrigin = 'center top';
                el.style.transform = `translate(calc(-50% + ${this.translateX}px), ${this.translateY}px) scale(${this.scale})`;
            } else {
                el.style.transformOrigin = 'center center';
                el.style.transform = `translate(calc(-50% + ${this.translateX}px), calc(-50% + ${this.translateY}px)) scale(${this.scale})`;
            }

            // Disable pointer events whenever panning overlay is active to prevent click stealing
            const isPanningActive = this.panOverlay && this.panOverlay.classList.contains('active');
            el.style.pointerEvents = isPanningActive ? 'none' : 'auto';

            if (this.zoomValEl) this.zoomValEl.textContent = Math.round(this.scale * 100) + '%';
        }
    },

    initPanning(overlay) {
        if (!overlay) return;
        overlay.onmousedown = (e) => {
            const el = this.contentWrapper.querySelector('img, iframe');
            if (!el) return;
            e.preventDefault();
            this.isDragging = true;
            this.startX = e.clientX - this.translateX;
            this.startY = e.clientY - this.translateY;
            overlay.classList.add('dragging');
            if (this.contentWrapper) this.contentWrapper.classList.add('is-dragging');
        };
        window.onmousemove = (e) => {
            if (!this.isDragging) return;
            this.translateX = e.clientX - this.startX;
            this.translateY = e.clientY - this.startY;
            this.updateTransform();
        };
        window.onmouseup = () => {
            this.isDragging = false;
            if (overlay) overlay.classList.remove('dragging');
            if (this.contentWrapper) this.contentWrapper.classList.remove('is-dragging');
        };
        this.modal.onwheel = (e) => {
            if (e.ctrlKey) {
                e.preventDefault();
                this.zoom(e.deltaY > 0 ? -0.1 : 0.1);
            }
        };
    }
};

// Auto-init
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.DrivePreviewer.init());
} else {
    window.DrivePreviewer.init();
}
