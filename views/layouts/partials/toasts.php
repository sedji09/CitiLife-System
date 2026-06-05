    <!-- FLOATING TOAST NOTIFICATIONS (System Alerts) -->
    <div id="toast-container"
      style="position: fixed; top: 80px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; max-width: 360px; width: calc(100% - 48px); pointer-events: none;">
      <div v-for="toast in toasts" :key="toast.id" @click="handleToastClick(toast)"
        style="pointer-events: auto; display: flex; align-items: flex-start; gap: 12px; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(229, 231, 235, 0.5); border-radius: 12px; padding: 14px 16px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.05); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;"
        class="toast-item group hover:shadow-lg hover:border-gray-300">
        <!-- Icon depending on type -->
        <div
          style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;"
          :style="{ background: getToastStyle(toast).bg, color: getToastStyle(toast).color }">
          <i :data-lucide="getToastIcon(toast)" style="width: 20px; height: 20px;"></i>
        </div>
        <!-- Text details -->
        <div style="flex: 1; padding-top: 2px;">
          <div style="font-size: 13px; font-weight: 600; color: #1f2937;" class="toast-title">{{ toast.title ||
            'Notification' }}</div>
          <div style="font-size: 11px; color: #6b7280; margin-top: 2px; line-height: 1.4;" class="toast-msg">{{
            toast.message }}</div>
        </div>
        <!-- Close button -->
        <button @click.stop="dismissToast(toast.id)"
          style="background: none; border: none; padding: 4px; cursor: pointer; color: #9ca3af; display: flex; align-items: center; justify-content: center; border-radius: 6px;"
          class="hover:text-gray-600 dark:hover:text-gray-300">
          <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>
