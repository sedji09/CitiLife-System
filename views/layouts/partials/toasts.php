    <!-- FLOATING TOAST NOTIFICATIONS (System Alerts) -->
    <div id="toast-container"
      style="position: fixed; top: 80px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; max-width: 380px; width: calc(100% - 48px); pointer-events: none;">
      <div v-for="toast in toasts" :key="toast.id" @click="handleToastClick(toast)"
        style="pointer-events: auto; display: flex; align-items: flex-start; gap: 12px; border-radius: 14px; padding: 14px 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;"
        :class="['toast-item group hover:shadow-xl hover:scale-[1.01]', 'toast-card-' + getNotificationCategory(toast)]">
        <!-- Icon matching notification section circle -->
        <div
          class="h-10 w-10 rounded-full flex items-center justify-center shrink-0 transition-colors shadow-2xs"
          :class="getNotificationCircleClass(toast)">
          <i data-lucide="bell" class="w-5 h-5"></i>
        </div>
        <!-- Text details -->
        <div style="flex: 1; padding-top: 2px; min-width: 0;">
          <div style="font-size: 13px; font-weight: 700; line-height: 1.3;" class="toast-title truncate">{{ toast.title ||
            'Notification' }}</div>
          <div style="font-size: 11px; margin-top: 3px; line-height: 1.45; font-weight: 500;" class="toast-msg">{{
            toast.message }}</div>
        </div>
        <!-- Close button -->
        <button @click.stop="dismissToast(toast.id)"
          style="background: none; border: none; padding: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 6px; flex-shrink: 0; margin-top: -2px; margin-right: -4px;"
          class="toast-close opacity-60 hover:opacity-100 transition-opacity">
          <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Undo Delete Toast -->
    <div v-if="showUndoToast" id="undo-toast-box" class="fixed z-[9999] px-4 py-3 rounded-xl shadow-2xl flex items-center justify-between gap-8" style="top: 24px; left: 50%; transform: translateX(-50%); background: #222222; color: #f4f4f5; min-width: 280px; transition: all 0.3s ease;">
      <div class="flex items-center gap-3">
        <!-- Circular Timer -->
        <div style="position: relative; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;">
          <svg width="22" height="22" viewBox="0 0 24 24" style="position: absolute; top: 0; left: 0; transform: rotate(-90deg);">
            <circle cx="12" cy="12" r="10" fill="none" stroke="#3f3f46" stroke-width="2.5"></circle>
            <circle cx="12" cy="12" r="10" fill="none" stroke="#ef4444" stroke-width="2.5"
                    stroke-dasharray="62.83"
                    stroke-linecap="round"
                    :style="{ strokeDashoffset: undoRingOffset + 'px', transition: 'stroke-dashoffset 5s linear' }"></circle>
          </svg>
          <!-- Timer text inside -->
          <span style="font-size: 12px; font-weight: 700; color: #ef4444; z-index: 1; line-height: 1;">{{ undoTimerCount }}</span>
        </div>
        <span style="font-size: 14px; font-weight: 600; color: #f4f4f5;">Deleted</span>
      </div>

      <button @click="undoDelete" class="hover:opacity-90 transition" style="background: #ef4444; color: #ffffff; font-weight: 700; font-size: 13px; padding: 6px 16px; border-radius: 99px; border: none; cursor: pointer;">
        Undo
      </button>
    </div>
