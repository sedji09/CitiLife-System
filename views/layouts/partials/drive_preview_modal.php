      <!-- Google Drive-Style Preview Modal (Cinematic v4.0) -->
      <div id="drive-preview-modal"
        class="hidden fixed inset-0 z-[1000] bg-[#0a0a0a] flex flex-col font-sans select-none overflow-hidden backdrop-blur-sm transition-all duration-300">

        <!-- Premium Red Top Bar -->
        <div
          class="drive-top-bar absolute top-0 left-0 right-0 flex items-center justify-between px-5 h-16 text-white z-[100] bg-red-600 shadow-[0_2px_20px_rgba(0,0,0,0.4)]">
          <div class="flex items-center gap-4">
            <button id="drive-close-btn"
              class="p-2.5 hover:bg-white/10 rounded-full transition-all active:scale-90 has-tooltip bottom-tooltip"
              data-tooltip="Exit (Esc)">
              <i data-lucide="arrow-left" class="w-6 h-6"></i>
            </button>
            <div class="flex flex-col">
              <div class="flex items-center gap-3">
                <span id="drive-filename" class="font-black text-xs uppercase tracking-tight opacity-95">--</span>
              </div>
            </div>
          </div>

          <!-- Action Cluster -->
          <div class="flex items-center gap-2">

            <!-- Zoom Controls -->
            <div class="flex items-center gap-4 bg-black/10 rounded-lg px-3 py-1.5 border border-white/5 shadow-inner">
              <button id="drive-zoom-out" class="p-1 hover:text-red-100 transition-colors has-tooltip bottom-tooltip"
                data-tooltip="Zoom Out">
                <i data-lucide="minus-circle" class="w-5 h-5"></i>
              </button>
              <span id="drive-zoom-val"
                class="text-[11px] font-black min-w-[3.5rem] text-center tracking-widest text-white/90">100%</span>
              <button id="drive-zoom-in" class="p-1 hover:text-red-100 transition-colors has-tooltip bottom-tooltip"
                data-tooltip="Zoom In">
                <i data-lucide="plus-circle" class="w-5 h-5"></i>
              </button>
              <div class="w-px h-4 bg-white/10 mx-2"></div>
              <span id="drive-page-info"
                class="text-[10px] font-black tracking-widest text-white/90 min-w-[3rem] text-center">1 / 1</span>
            </div>
          </div>
        </div>

        <!-- Main Interaction Area -->
        <div class="flex-1 relative flex items-center justify-center overflow-hidden h-full">

          <!-- Large Floating Side Arrows -->
          <button id="drive-prev-side"
            class="absolute left-6 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-black/40 hover:bg-black/60 text-white/80 hover:text-white flex items-center justify-center backdrop-blur-md border border-white/10 transition-all active:scale-90 z-[110] group"
            title="Previous (Left Arrow)"
            style="position: absolute !important; left: 1.5rem !important; top: 50% !important; transform: translateY(-50%) !important; margin: 0 !important;">
            <i data-lucide="chevron-left"
              class="w-8 h-8 group-hover:-translate-x-0.5 transition-transform text-white shadow-sm"></i>
          </button>

          <button id="drive-next-side"
            class="absolute right-6 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-black/40 hover:bg-black/60 text-white/80 hover:text-white flex items-center justify-center backdrop-blur-md border border-white/10 transition-all active:scale-90 z-[110] group"
            title="Next (Right Arrow)"
            style="position: absolute !important; right: 1.5rem !important; top: 50% !important; transform: translateY(-50%) !important; margin: 0 !important;">
            <i data-lucide="chevron-right"
              class="w-8 h-8 group-hover:translate-x-0.5 transition-transform text-white shadow-sm"></i>
          </button>

          <!-- The Content -->
          <div id="drive-content-wrapper"
            class="w-full h-full flex items-center justify-center transition-all duration-300">
            <!-- Content injected via JS -->
          </div> <!-- Bottom Thumbnail Strip (FLOATING OVERLAY) -->
          <div id="drive-thumb-strip"
            class="absolute bottom-6 left-1/2 -translate-x-1/2 h-16 bg-black/40 backdrop-blur-md rounded-2xl flex flex-row items-center px-4 gap-3 z-[110] transition-all duration-300 border border-white/10 scrollbar-hide overflow-x-auto max-w-[90%] shadow-2xl">
            <!-- Thumbnails injected via JS -->
          </div>

          <!-- Panning Overlay -->
          <div id="drive-panning-overlay"></div>

        </div>
      </div>
